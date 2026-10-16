<?php

namespace Emunicipal\Controllers\Admin;

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
 * AdminDashboardController - admin dashboard and profile.
 * Design.md Section10.1
 */
class AdminDashboardController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::requireRole('admin');

        $metrics = [
            'total_requests'     => 0,
            'total_residents'    => 0,
            'total_staff'        => 0,
            'requests_this_month'=> 0,
            'by_status'          => [],
        ];

        try {
            $db = \Emunicipal\Core\Database::getInstance();

            // Requests by status
            $stmt = $db->query("SELECT status, COUNT(*) AS cnt FROM requests GROUP BY status");
            $byStatus = [];
            $total = 0;
            foreach ($stmt->fetchAll() as $row) {
                $byStatus[$row['status']] = (int)$row['cnt'];
                $total += (int)$row['cnt'];
            }
            $metrics['by_status']      = $byStatus;
            $metrics['total_requests'] = $total;

            // Resident count
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM users WHERE role = 'resident'"
            );
            $stmt->execute();
            $metrics['total_residents'] = (int)$stmt->fetchColumn();

            // Staff count
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM users WHERE role = 'staff'"
            );
            $stmt->execute();
            $metrics['total_staff'] = (int)$stmt->fetchColumn();

            // Requests this month
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM requests
                 WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())"
            );
            $stmt->execute();
            $metrics['requests_this_month'] = (int)$stmt->fetchColumn();


            // Recent requests (last 5)
            $stmt = $db->prepare(
                "SELECT r.id, r.reference_number, r.status, r.created_at,
                        dt.name AS document_type_name,
                        u.first_name, u.last_name
                 FROM requests r
                 JOIN document_types dt ON r.document_type_id = dt.id
                 JOIN users u ON r.user_id = u.id
                 ORDER BY r.created_at DESC
                 LIMIT 5"
            );
            $stmt->execute();
            $recentRequests = $stmt->fetchAll();
        } catch (\Throwable $e) {
            // graceful degradation
        }

        $this->renderWithLayout('admin/dashboard/index', [
            'title'      => 'Admin Dashboard - eMunicipal',
            'activePage' => 'dashboard',
            'metrics'    => $metrics,
            'recentRequests' => $recentRequests ?? [],
        ], 'layouts/auth');
    }

    public function profile(): void
    {
        AuthMiddleware::requireRole('admin');
        $userId = AuthMiddleware::userId();

        $user = [];
        try {
            $user = (new User())->findActiveById($userId) ?: [];
        } catch (\Throwable $e) {}

        $this->renderWithLayout('admin/profile/index', [
            'title'      => 'My Profile - eMunicipal',
            'activePage' => 'profile',
            'user'       => $user,
            'csrfToken'  => SecurityHelper::getCsrfToken(),
            'errors'     => SessionHelper::getFlash('profile_errors', []),
            'pwErrors'   => SessionHelper::getFlash('pw_errors', []),
        ], 'layouts/auth');
    }

    public function updateProfile(): void
    {
        AuthMiddleware::requireRole('admin');
        $userId = AuthMiddleware::userId();

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('profile_errors', ['form' => 'Invalid security token.']);
            UrlHelper::redirect('/admin/profile');
        }

        $v = new Validator($_POST);
        $v->required(['first_name','last_name'])
          ->maxLength('first_name', 100)
          ->maxLength('last_name', 100);

        if ($v->fails()) {
            SessionHelper::flash('profile_errors', $v->errors());
            UrlHelper::redirect('/admin/profile');
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
            Logger::error('Admin profile update: ' . $e->getMessage());
            SessionHelper::flash('profile_errors', ['form' => 'Unable to save profile.']);
        }
        UrlHelper::redirect('/admin/profile');
    }

    public function changePassword(): void
    {
        AuthMiddleware::requireRole('admin');
        $userId = AuthMiddleware::userId();

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('pw_errors', ['form' => 'Invalid security token.']);
            UrlHelper::redirect('/admin/profile');
        }

        $v = new Validator($_POST);
        $v->required(['current_password','new_password','new_password_confirmation'])
          ->passwordStrength('new_password')
          ->matches('new_password_confirmation', 'new_password');

        if ($v->fails()) {
            SessionHelper::flash('pw_errors', $v->errors());
            UrlHelper::redirect('/admin/profile');
        }

        try {
            $userModel = new User();
            $user      = $userModel->findActiveById($userId);
            if (!$user || !password_verify($v->get('current_password'), $user['password_hash'])) {
                SessionHelper::flash('pw_errors', ['current_password' => 'Current password is incorrect.']);
                UrlHelper::redirect('/admin/profile');
            }
            $userModel->update($userId, ['password_hash' => password_hash($v->get('new_password'), PASSWORD_DEFAULT)]);
            AuditLogService::log($userId, 'admin', AuditLog::ACTION_PASSWORD_CHANGED, 'users',
                'users', $userId, null, null, 'Admin changed password');
            SessionHelper::flash('success', 'Password updated successfully.');
        } catch (\Throwable $e) {
            Logger::error('Admin password change: ' . $e->getMessage());
            SessionHelper::flash('pw_errors', ['form' => 'Unable to change password.']);
        }
        UrlHelper::redirect('/admin/profile');
    }
}
