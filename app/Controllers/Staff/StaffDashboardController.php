<?php

namespace Emunicipal\Controllers\Staff;

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
use Emunicipal\Models\Request as RequestModel;

/**
 * StaffDashboardController — staff dashboard and profile.
 * Design.md §9.1
 */
class StaffDashboardController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::requireRole('staff');

        // Request counts by status for dashboard overview (Req 9.7)
        $counts = [];
        try {
            $db = \Emunicipal\Core\Database::getInstance();
            $stmt = $db->query(
                "SELECT status, COUNT(*) AS cnt FROM requests GROUP BY status"
            );
            foreach ($stmt->fetchAll() as $row) {
                $counts[$row['status']] = (int)$row['cnt'];
            }
        } catch (\Throwable $e) {
            // graceful degradation
        }

        $this->renderWithLayout('staff/dashboard/index', [
            'title'      => 'Staff Dashboard — eMunicipal',
            'activePage' => 'dashboard',
            'counts'     => $counts,
        ], 'layouts/auth');
    }

    public function profile(): void
    {
        AuthMiddleware::requireRole('staff');
        $userId = AuthMiddleware::userId();

        $user = [];
        try {
            $user = (new User())->findActiveById($userId) ?: [];
        } catch (\Throwable $e) {}

        $this->renderWithLayout('staff/profile/index', [
            'title'      => 'My Profile — eMunicipal',
            'activePage' => 'profile',
            'user'       => $user,
            'csrfToken'  => SecurityHelper::getCsrfToken(),
            'errors'     => SessionHelper::getFlash('profile_errors', []),
            'pwErrors'   => SessionHelper::getFlash('pw_errors', []),
        ], 'layouts/auth');
    }

    public function updateProfile(): void
    {
        AuthMiddleware::requireRole('staff');
        $userId = AuthMiddleware::userId();

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('profile_errors', ['form' => 'Invalid security token.']);
            UrlHelper::redirect('/staff/profile');
        }

        $v = new Validator($_POST);
        $v->required(['first_name','last_name'])
          ->maxLength('first_name', 100)
          ->maxLength('last_name', 100);

        if ($v->fails()) {
            SessionHelper::flash('profile_errors', $v->errors());
            UrlHelper::redirect('/staff/profile');
        }

        try {
            (new User())->update($userId, [
                'first_name' => $v->get('first_name'),
                'last_name'  => $v->get('last_name'),
            ]);
            SessionHelper::set('user_first_name', $v->get('first_name'));
            SessionHelper::set('user_last_name',  $v->get('last_name'));
            SessionHelper::flash('success', 'Profile updated.');
        } catch (\Throwable $e) {
            Logger::error('Staff profile update: ' . $e->getMessage());
            SessionHelper::flash('profile_errors', ['form' => 'Unable to save profile.']);
        }
        UrlHelper::redirect('/staff/profile');
    }

    public function changePassword(): void
    {
        AuthMiddleware::requireRole('staff');
        $this->handlePasswordChange('staff', '/staff/profile');
    }

    private function handlePasswordChange(string $role, string $redirectPath): void
    {
        $userId = AuthMiddleware::userId();

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('pw_errors', ['form' => 'Invalid security token.']);
            UrlHelper::redirect($redirectPath);
        }

        $v = new Validator($_POST);
        $v->required(['current_password','new_password','new_password_confirmation'])
          ->passwordStrength('new_password')
          ->matches('new_password_confirmation', 'new_password');

        if ($v->fails()) {
            SessionHelper::flash('pw_errors', $v->errors());
            UrlHelper::redirect($redirectPath);
        }

        try {
            $userModel = new User();
            $user      = $userModel->findActiveById($userId);
            if (!$user || !password_verify($v->get('current_password'), $user['password_hash'])) {
                SessionHelper::flash('pw_errors', ['current_password' => 'Current password is incorrect.']);
                UrlHelper::redirect($redirectPath);
            }
            $userModel->update($userId, ['password_hash' => password_hash($v->get('new_password'), PASSWORD_DEFAULT)]);
            AuditLogService::log($userId, $role, AuditLog::ACTION_PASSWORD_CHANGED, 'users',
                'users', $userId, null, null, "{$role} changed password");
            SessionHelper::flash('success', 'Password updated successfully.');
        } catch (\Throwable $e) {
            Logger::error('Password change: ' . $e->getMessage());
            SessionHelper::flash('pw_errors', ['form' => 'Unable to change password.']);
        }
        UrlHelper::redirect($redirectPath);
    }
}
