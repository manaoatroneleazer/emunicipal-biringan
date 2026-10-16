<?php

namespace Emunicipal\Controllers\Staff;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Models\Notification;

/**
 * StaffNotificationController — in-system notifications for Staff users.
 *
 * Implements Req 25.4, 25.6–25.12 for the Staff role.
 *
 * Security:
 *  - requireRole('staff') on every action
 *  - user_id always from AuthMiddleware::userId() — never from request input (Req 25.8)
 *  - markRead scoped to authenticated user via Notification::markRead($id, $userId)
 */
class StaffNotificationController extends BaseController
{
    /**
     * GET /staff/notifications
     * Display all notifications for the authenticated staff user. (Req 25.4)
     */
    public function index(): void
    {
        AuthMiddleware::requireRole('staff');
        $userId = AuthMiddleware::userId();

        $notifications = [];
        $total         = 0;
        try {
            $model         = new Notification();
            $notifications = $model->forUser($userId, 50, 0);
            $total         = $model->count(['user_id' => $userId]);
        } catch (\Throwable $e) {
            // Graceful degradation — page renders with empty list
        }

        $this->renderWithLayout('staff/notifications/index', [
            'title'         => 'Notifications — eMunicipal',
            'activePage'    => 'notifications',
            'notifications' => $notifications,
            'total'         => $total,
            'csrfToken'     => SecurityHelper::getCsrfToken(),
        ], 'layouts/auth');
    }

    /**
     * POST /staff/notifications/{id}/read
     * Mark a single notification as read. (Req 25.8)
     */
    public function markRead(string $id): void
    {
        AuthMiddleware::requireRole('staff');
        $userId = AuthMiddleware::userId();

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            UrlHelper::redirect('/staff/notifications');
        }

        try {
            (new Notification())->markRead((int)$id, $userId);
        } catch (\Throwable $e) {
            // Non-fatal
        }

        // Support optional redirect to request detail after marking read (Req 25.7)
        $redirect = trim($_POST['_redirect'] ?? '');
        if ($redirect !== '' && str_starts_with($redirect, '/staff/requests/')) {
            UrlHelper::redirect($redirect);
        }

        UrlHelper::redirect('/staff/notifications');
    }

    /**
     * POST /staff/notifications/read-all
     * Mark all notifications as read for the authenticated staff user. (Req 25.9)
     */
    public function markAllRead(): void
    {
        AuthMiddleware::requireRole('staff');
        $userId = AuthMiddleware::userId();

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            UrlHelper::redirect('/staff/notifications');
        }

        try {
            (new Notification())->markAllRead($userId);
        } catch (\Throwable $e) {
            // Non-fatal
        }

        SessionHelper::flash('success', 'All notifications marked as read.');
        UrlHelper::redirect('/staff/notifications');
    }
}
