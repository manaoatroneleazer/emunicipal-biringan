<?php

namespace Emunicipal\Controllers\Api;

use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Validators\Validator;
use Emunicipal\Models\User;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Services\EmailService;
use Emunicipal\Core\Logger;

/**
 * AuthApiController — API authentication endpoints.
 *
 * POST /api/auth/login    — authenticate, create session
 * POST /api/auth/logout   — destroy session
 * POST /api/auth/register — register a new Resident account
 *
 * Per Req 15.1 (API plan), Req 2 (authentication), Req 1 (registration).
 * Per Req 14.10 — 401 for unauthenticated, 403 for forbidden.
 * Per Req 2.4   — generic error message on failed login (no field disclosure).
 * Per Req 14.4  — password_hash never exposed in responses.
 */
class AuthApiController extends BaseApiController
{
    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/login
    // ─────────────────────────────────────────────────────────────────────────

    public function login(): void
    {
        // Must not be already authenticated
        if (AuthMiddleware::isAuthenticated()) {
            $this->json($this->safeUser(
                (new User())->findActiveById(AuthMiddleware::userId()) ?: []
            ), 'Already authenticated.');
        }

        $email    = $this->input('email', '');
        $password = $this->input('password', '');

        if ($email === '' || $password === '') {
            $this->error('Email and password are required.', 422,
                ['email' => 'Email and password are required.']);
        }

        $genericError = 'Invalid email address or password.';

        try {
            $userModel = new User();
            $user      = $userModel->findByEmail($email);

            if (!$user) {
                AuditLogService::log(null, null, AuditLog::ACTION_LOGIN_FAILED, 'auth',
                    'users', null, null, null, "API failed login: email not found ({$email})");
                $this->error($genericError, 401);
            }

            // Account lockout check (Req 14.12)
            if ($userModel->isLocked($user)) {
                $this->error('This account is temporarily locked. Please try again later.', 403);
            }

            // Inactive account (Req 2.5)
            if (!(bool)$user['is_active']) {
                AuditLogService::log($user['id'], $user['role'], AuditLog::ACTION_LOGIN_FAILED, 'auth',
                    'users', $user['id'], null, null, 'API login attempt on inactive account');
                $this->error('This account has been deactivated.', 403);
            }

            // Password check (Req 2.2)
            if (!password_verify($password, $user['password_hash'])) {
                $attempts  = (int)$user['failed_login_attempts'] + 1;
                $lockUntil = null;
                if ($attempts >= 5) {
                    $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    AuditLogService::log($user['id'], $user['role'], AuditLog::ACTION_ACCOUNT_LOCKED, 'auth',
                        'users', $user['id'], null, ['locked_until' => $lockUntil],
                        'API: Account locked after 5 failed login attempts');
                }
                $update = ['failed_login_attempts' => $attempts];
                if ($lockUntil !== null) $update['locked_until'] = $lockUntil;
                $userModel->update($user['id'], $update);

                AuditLogService::log($user['id'], $user['role'], AuditLog::ACTION_LOGIN_FAILED, 'auth',
                    'users', $user['id'], null, null,
                    "API failed login attempt {$attempts} for user ID {$user['id']}");

                $this->error($genericError, 401);
            }

            // SUCCESS
            AuthMiddleware::loginUser($user);
            $userModel->update($user['id'], [
                'failed_login_attempts' => 0,
                'locked_until'          => null,
                'last_login_at'         => date('Y-m-d H:i:s'),
            ]);

            AuditLogService::log($user['id'], $user['role'], AuditLog::ACTION_USER_LOGIN, 'auth',
                'users', $user['id'], null, null,
                "User {$user['email']} logged in via API");

            $this->json($this->safeUser($user), 'Login successful.');

        } catch (\Throwable $e) {
            Logger::error('AuthApiController::login: ' . $e->getMessage());
            $this->error('A server error occurred.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/logout
    // ─────────────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        $userId   = $this->requireAuth();
        $userRole = AuthMiddleware::getRole();

        AuditLogService::log($userId, $userRole, AuditLog::ACTION_USER_LOGOUT, 'auth',
            'users', $userId, null, null, "User ID {$userId} logged out via API");

        AuthMiddleware::logoutUser();
        $this->json([], 'Logged out successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/register
    // ─────────────────────────────────────────────────────────────────────────

    public function register(): void
    {
        if (AuthMiddleware::isAuthenticated()) {
            $this->error('Already authenticated.', 400);
        }

        $data = $this->body();

        $v = new Validator($data);
        $v->required(['first_name','last_name','email','password','password_confirmation',
                      'contact_number','house_number','street','barangay','city','province'])
          ->email('email')
          ->maxLength('first_name', 100)
          ->maxLength('last_name', 100)
          ->maxLength('email', 255)
          ->passwordStrength('password')
          ->matches('password_confirmation', 'password')
          ->philippinePhone('contact_number');

        if ($v->fails()) {
            $this->error('Validation failed.', 422, $v->errors());
        }

        try {
            $userModel = new User();

            if ($userModel->findByEmail($v->get('email'))) {
                $this->error('Validation failed.', 422,
                    ['email' => 'This email address is already registered.']);
            }

            $address = implode(', ', array_filter([
                $v->get('house_number'), $v->get('street'),
                'Brgy. ' . $v->get('barangay'), $v->get('city'), $v->get('province'),
            ]));

            // Insert user — role stored directly as 'resident' (role ENUM on users table)
            $userId = $userModel->insert([
                'role'           => 'resident',
                'first_name'     => $v->get('first_name'),
                'last_name'      => $v->get('last_name'),
                'email'          => $v->get('email'),
                'password_hash'  => password_hash($v->get('password'), PASSWORD_DEFAULT),
                'contact_number' => $v->get('contact_number'),
                'address'        => $address,
                'is_active'      => 1,
            ]);

            AuditLogService::log($userId, 'resident', AuditLog::ACTION_ACCOUNT_CREATED, 'users',
                'users', $userId, null, ['email' => $v->get('email')],
                "New resident account created via API: {$v->get('email')}");

            // Registration email — non-fatal (Req 1.7)
            try {
                $newUser = $userModel->findActiveById($userId);
                if ($newUser) EmailService::sendRegistrationConfirmation($newUser);
            } catch (\Throwable $emailEx) {
                Logger::error('API register email error: ' . $emailEx->getMessage());
            }

            $created = $userModel->findActiveById($userId);
            $this->json($this->safeUser($created ?: []), 'Account created successfully.', 201);

        } catch (\Throwable $e) {
            Logger::error('AuthApiController::register: ' . $e->getMessage());
            $this->error('A server error occurred.', 500);
        }
    }
}
