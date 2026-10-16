<?php

namespace Emunicipal\Controllers;

use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Validators\Validator;
use Emunicipal\Models\User;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Services\EmailService;
use Emunicipal\Core\Logger;

/**
 * AuthController - registration, login, and logout.
 *
 * Security requirements implemented:
 *  Req 1  - Registration: field validation, password hashing, duplicate email check
 *  Req 2  - Login: password_verify(), session creation, role redirect, generic error message
 *  Req 2.5 - Inactive account rejection
 *  Req 2.7 - CSRF on all forms
 *  Req 2.8 - Logout destroys session
 *  Req 2.10 - Session timeout enforced in AuthMiddleware
 *  Req 13.1 - Audit log entries for login, logout, failed login, account created
 */
class AuthController extends BaseController
{
    // -------------------------------------------------------------------------
    // LOGIN
    // -------------------------------------------------------------------------

    /** GET /login */
    public function loginPage(): void
    {
        AuthMiddleware::requireGuest();

        $this->renderWithLayout('auth/login', [
            'title'      => 'Sign In - eMunicipal',
            'csrfToken'  => SecurityHelper::getCsrfToken(),
            'errors'     => SessionHelper::getFlash('auth_errors', []),
            'oldEmail'   => SessionHelper::getFlash('old_email', ''),
        ], 'layouts/minimal');
    }

    /** POST /login */
    public function login(): void
    {
        // CSRF validation (Req 2.7)
        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('auth_errors', ['form' => 'Invalid security token. Please try again.']);
            UrlHelper::redirect('/login');
        }

        // G3: normalize to lowercase before any check (consistent with registration storage)
        $email    = strtolower(trim($_POST['email']    ?? ''));
        $password = trim($_POST['password'] ?? '');

        // Generic error message - never reveal which field was wrong (Req 2.4)
        $genericError = ['form' => 'The email address or password you entered is incorrect.'];

        if ($email === '' || $password === '') {
            SessionHelper::flash('auth_errors', $genericError);
            SessionHelper::flash('old_email', $email);
            UrlHelper::redirect('/login');
        }

        // G3: validate email format before hitting the database.
        // Uses the same rule as registration: filter_var + dot-in-domain check.
        // On failure, use the same generic error — never disclose format mismatch.
        $atPos        = strrpos($email, '@');
        $loginDomain  = $atPos !== false ? substr($email, $atPos + 1) : '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strpos($loginDomain, '.') === false) {
            SessionHelper::flash('auth_errors', $genericError);
            SessionHelper::flash('old_email', $email);
            UrlHelper::redirect('/login');
        }

        try {
            $userModel = new User();
            $user      = $userModel->findByEmail($email);

            if (!$user) {
                // Log failed attempt (no user_id)
                AuditLogService::log(null, null, AuditLog::ACTION_LOGIN_FAILED, 'auth',
                    'users', null, null, null, "Failed login: email not found ({$email})");

                SessionHelper::flash('auth_errors', $genericError);
                SessionHelper::flash('old_email', $email);
                UrlHelper::redirect('/login');
            }

            // Account lockout check (Req 14.12) (database.md Section5.3)
            if ($userModel->isLocked($user)) {
                SessionHelper::flash('auth_errors', [
                    'form' => 'This account is temporarily locked due to multiple failed login attempts. Please try again later.',
                ]);
                SessionHelper::flash('old_email', $email);
                UrlHelper::redirect('/login');
            }


            // 15-minute window reset (Req 14.12): if a previous lockout has EXPIRED,
            // reset the counter so stale attempts don't carry into the next window.
            if (!empty($user['locked_until'])
                && strtotime($user['locked_until']) <= time()
                && (int)$user['failed_login_attempts'] > 0
            ) {
                $userModel->update($user['id'], [
                    'failed_login_attempts' => 0,
                    'locked_until'          => null,
                ]);
                $user['failed_login_attempts'] = 0;
                $user['locked_until']          = null;
            }
            // Inactive account (Req 2.5)
            if (!(bool)$user['is_active']) {
                AuditLogService::log($user['id'], $user['role'], AuditLog::ACTION_LOGIN_FAILED, 'auth',
                    'users', $user['id'], null, null, 'Login attempt on inactive account');

                SessionHelper::flash('auth_errors', [
                    'form' => 'Your account has been deactivated. Please contact the municipal office for assistance.',
                ]);
                SessionHelper::flash('old_email', $email);
                UrlHelper::redirect('/login');
            }

            // Password verification (Req 2.2)
            if (!password_verify($password, $user['password_hash'])) {
                // Increment failed attempts and potentially lock account
                $attempts = (int)$user['failed_login_attempts'] + 1;
                $lockUntil = null;
                if ($attempts >= 5) {
                    $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    AuditLogService::log($user['id'], $user['role'], AuditLog::ACTION_ACCOUNT_LOCKED, 'auth',
                        'users', $user['id'], null, ['locked_until' => $lockUntil],
                        'Account locked after 5 failed login attempts');
                }

                $updateData = ['failed_login_attempts' => $attempts];
                if ($lockUntil !== null) {
                    $updateData['locked_until'] = $lockUntil;
                }
                $userModel->update($user['id'], $updateData);

                AuditLogService::log($user['id'], $user['role'], AuditLog::ACTION_LOGIN_FAILED, 'auth',
                    'users', $user['id'], null, null,
                    "Failed login attempt {$attempts} for user ID {$user['id']}");

                SessionHelper::flash('auth_errors', $genericError);
                SessionHelper::flash('old_email', $email);
                UrlHelper::redirect('/login');
            }

            // SUCCESS - create session (Req 2.3)
            AuthMiddleware::loginUser($user);

            // Reset failed attempts and update last_login_at
            $userModel->update($user['id'], [
                'failed_login_attempts' => 0,
                'locked_until'          => null,
                'last_login_at'         => date('Y-m-d H:i:s'),
            ]);

            // Audit log (Req 2.6, 13.1)
            AuditLogService::log($user['id'], $user['role'], AuditLog::ACTION_USER_LOGIN, 'auth',
                'users', $user['id'], null, null,
                "User {$user['email']} logged in successfully");

            // Redirect to role-appropriate dashboard (Req 2.3)
            $redirectTo = SessionHelper::getFlash('redirect_after_login', '');
            if (!$redirectTo) {
                $redirectTo = match($user['role']) {
                    'staff' => '/staff/dashboard',
                    'admin' => '/admin/dashboard',
                    default => '/resident/dashboard',
                };
            }
            UrlHelper::redirect($redirectTo);

        } catch (\Throwable $e) {
            Logger::error('Login error: ' . $e->getMessage());
            SessionHelper::flash('auth_errors', ['form' => 'A system error occurred. Please try again.']);
            SessionHelper::flash('old_email', $email);
            UrlHelper::redirect('/login');
        }
    }

    // -------------------------------------------------------------------------
    // REGISTRATION
    // -------------------------------------------------------------------------

    /** GET /register */
    public function registerPage(): void
    {
        AuthMiddleware::requireGuest();

        $this->renderWithLayout('auth/register', [
            'title'     => 'Create Account - eMunicipal',
            'csrfToken' => SecurityHelper::getCsrfToken(),
            'errors'    => SessionHelper::getFlash('reg_errors', []),
            'old'       => SessionHelper::getFlash('reg_old', []),
        ], 'layouts/minimal');
    }

    /** POST /register */
    public function register(): void
    {
        // CSRF (Req 2.7)
        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('reg_errors', ['form' => 'Invalid security token. Please try again.']);
            UrlHelper::redirect('/register');
        }

        $v = new Validator($_POST);
        $v->required(['first_name','last_name','email','password','password_confirmation','contact_number',
                 'house_number','street','barangay','city','province'])
          ->email('email')
          ->maxLength('first_name', 100)
          ->maxLength('last_name', 100)
          ->maxLength('email', 255)
          ->passwordStrength('password')
          ->matches('password_confirmation', 'password')
          ->philippinePhone('contact_number');

        // Preserve form data on re-display (never include passwords)
        $old = array_diff_key($v->validated(), array_flip(['password','password_confirmation']));

        if ($v->fails()) {
            SessionHelper::flash('reg_errors', $v->errors());
            SessionHelper::flash('reg_old', $old);
            UrlHelper::redirect('/register');
        }

        // Terms and Conditions acceptance (server-side enforcement — no DB change)
        if (($_POST['terms_accepted'] ?? '') !== '1') {
            $v->addError('terms_accepted', 'You must agree to the Terms and Conditions and Privacy Policy before creating an account.');
            SessionHelper::flash('reg_errors', $v->errors());
            SessionHelper::flash('reg_old', $old);
            UrlHelper::redirect('/register');
        }

        try {
            $userModel = new User();

            // G2: normalize email to lowercase before duplicate check and INSERT
            // (utf8mb4_unicode_ci handles case-insensitive matching at DB level,
            //  but we enforce lowercase at the application layer for consistency)
            $normalizedEmail = strtolower($v->get('email'));

            // Duplicate email check (Req 1.3)
            if ($userModel->findByEmail($normalizedEmail)) {
                SessionHelper::flash('reg_errors', [
                    'email' => 'This email address is already registered. Please sign in or use a different email.',
                ]);
                SessionHelper::flash('reg_old', $old);
                UrlHelper::redirect('/register');
            }

            // Hash password (Req 1.4 - never store plain text)
            $passwordHash = password_hash($v->get('password'), PASSWORD_DEFAULT);
            $address = implode(', ', array_filter([$v->get('house_number'), $v->get('street'), 'Brgy. '.$v->get('barangay'), $v->get('city'), $v->get('province')]));


            // Insert user — role is stored directly as 'resident' (Req 1, role ENUM on users table)
            $userId = $userModel->insert([
                'role'           => 'resident',
                'first_name'     => $v->get('first_name'),
                'last_name'      => $v->get('last_name'),
                'email'          => $normalizedEmail,
                'password_hash'  => $passwordHash,
                'contact_number' => $v->get('contact_number'),
                'address'        => $address,
                'is_active'      => 1,
            ]);

            // Audit log (Req 13.1)
            AuditLogService::log($userId, 'resident', AuditLog::ACTION_ACCOUNT_CREATED, 'users',
                'users', $userId, null, ['email' => $normalizedEmail],
                "New resident account created: {$normalizedEmail}");

            // Registration confirmation email (Req 1.6, 1.7)
            // EmailService catches and logs failures - never throws, never rolls back (Req 10.4)
            try {
                $newUser = $userModel->findActiveById($userId);
                if ($newUser) {
                    EmailService::sendRegistrationConfirmation($newUser);
                }
            } catch (\Throwable $emailEx) {
                Logger::error('Registration email error: ' . $emailEx->getMessage());
                // Account creation already succeeded - do not roll back (Req 1.7)
            }

            SessionHelper::flash('success', 'Your account has been created. Please sign in to continue.');
            UrlHelper::redirect('/login');

        } catch (\Throwable $e) {
            Logger::error('Registration error: ' . $e->getMessage());
            SessionHelper::flash('reg_errors', ['form' => 'A system error occurred during registration. Please try again.']);
            SessionHelper::flash('reg_old', $old);
            UrlHelper::redirect('/register');
        }
    }

    // -------------------------------------------------------------------------
    // LOGOUT
    // -------------------------------------------------------------------------

    /** POST /logout */
    public function logout(): void
    {
        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            UrlHelper::redirect('/');
        }

        $userId   = AuthMiddleware::userId();
        $userRole = AuthMiddleware::getRole();

        if ($userId) {
            AuditLogService::log($userId, $userRole, AuditLog::ACTION_USER_LOGOUT, 'auth',
                'users', $userId, null, null, "User ID {$userId} logged out");
        }

        // Destroy session (Req 2.8)
        AuthMiddleware::logoutUser();

        UrlHelper::redirect('/');
    }
}

