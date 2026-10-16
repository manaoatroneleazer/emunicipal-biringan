<?php

namespace Emunicipal\Controllers\Resident;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Validators\Validator;
use Emunicipal\Models\User;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Core\Logger;

/**
 * Resident ProfileController — view/edit profile and change password.
 * Req 4, design.md §8.x
 */
class ProfileController extends BaseController
{
    /** GET /resident/profile */
    public function index(): void
    {
        AuthMiddleware::requireRole('resident');
        $userId = AuthMiddleware::userId();

        $user = [];
        try {
            $user = (new User())->findActiveById($userId) ?: [];
        } catch (\Throwable $e) {
            Logger::error('Profile load error: ' . $e->getMessage());
        }

        $this->renderWithLayout('resident/profile/index', [
            'title'      => 'My Profile — eMunicipal',
            'activePage' => 'profile',
            'user'       => $user,
            'csrfToken'  => SecurityHelper::getCsrfToken(),
            'errors'     => SessionHelper::getFlash('profile_errors', []),
            'pwErrors'   => SessionHelper::getFlash('pw_errors', []),
        ], 'layouts/resident');
    }

    /** POST /resident/profile */
    public function update(): void
    {
        AuthMiddleware::requireRole('resident');
        $userId = AuthMiddleware::userId();

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('profile_errors', ['form' => 'Invalid security token.']);
            UrlHelper::redirect('/resident/profile');
        }

        $v = new Validator($_POST);
        $v->required(['first_name','last_name','contact_number','address'])
          ->maxLength('first_name', 100)
          ->maxLength('last_name', 100)
          ->philippinePhone('contact_number');

        if ($v->fails()) {
            SessionHelper::flash('profile_errors', $v->errors());
            UrlHelper::redirect('/resident/profile');
        }

        try {
            $userModel = new User();
            $userModel->update($userId, [
                'first_name'     => $v->get('first_name'),
                'last_name'      => $v->get('last_name'),
                'contact_number' => $v->get('contact_number'),
                'address'        => $v->get('address'),
            ]);

            AuditLogService::log($userId, 'resident', AuditLog::ACTION_ACCOUNT_CREATED, 'users',
                'users', $userId, null, ['action' => 'profile_updated'], 'Resident profile updated');

            // Update session name
            SessionHelper::set('user_first_name', $v->get('first_name'));
            SessionHelper::set('user_last_name',  $v->get('last_name'));

            SessionHelper::flash('success', 'Your profile has been updated.');
        } catch (\Throwable $e) {
            Logger::error('Profile update error: ' . $e->getMessage());
            SessionHelper::flash('profile_errors', ['form' => 'Unable to save your profile. Please try again.']);
        }
        UrlHelper::redirect('/resident/profile');
    }

    /** POST /resident/profile/password */
    public function changePassword(): void
    {
        AuthMiddleware::requireRole('resident');
        $userId = AuthMiddleware::userId();

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('pw_errors', ['form' => 'Invalid security token.']);
            UrlHelper::redirect('/resident/profile');
        }

        $v = new Validator($_POST);
        $v->required(['current_password','new_password','new_password_confirmation'])
          ->passwordStrength('new_password')
          ->matches('new_password_confirmation', 'new_password');

        if ($v->fails()) {
            SessionHelper::flash('pw_errors', $v->errors());
            UrlHelper::redirect('/resident/profile');
        }

        try {
            $userModel = new User();
            $user      = $userModel->findActiveById($userId);
            if (!$user) {
                UrlHelper::redirect('/login');
            }

            // Verify current password (Req 4.5)
            if (!password_verify($v->get('current_password'), $user['password_hash'])) {
                SessionHelper::flash('pw_errors', ['current_password' => 'Your current password is incorrect.']);
                UrlHelper::redirect('/resident/profile');
            }

            $newHash = password_hash($v->get('new_password'), PASSWORD_DEFAULT);
            $userModel->update($userId, ['password_hash' => $newHash]);

            AuditLogService::log($userId, 'resident', AuditLog::ACTION_PASSWORD_CHANGED, 'users',
                'users', $userId, null, null, 'Resident changed their password');

            SessionHelper::flash('success', 'Your password has been updated successfully.');
        } catch (\Throwable $e) {
            Logger::error('Password change error: ' . $e->getMessage());
            SessionHelper::flash('pw_errors', ['form' => 'Unable to change password. Please try again.']);
        }
        UrlHelper::redirect('/resident/profile');
    }
}
