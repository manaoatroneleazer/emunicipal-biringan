<?php

namespace Emunicipal\Controllers\Resident;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Models\Notification;

/**
 * NotificationController — resident notifications list and mark-read.
 * Full UI wired in Module K; routing stubs here.
 */
class NotificationController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::requireRole('resident');
        $userId = AuthMiddleware::userId();

        $notifications = [];
        $total = 0;
        try {
            $model = new Notification();
            $notifications = $model->forUser($userId, 20, 0);
            $total = $model->count(['user_id' => $userId]);
        } catch (\Throwable $e) {}

        $this->renderWithLayout('resident/notifications/index', [
            'title'         => 'Notifications — eMunicipal',
            'activePage'    => 'notifications',
            'notifications' => $notifications,
            'total'         => $total,
            'csrfToken'     => SecurityHelper::getCsrfToken(),
        ], 'layouts/resident');
    }

    public function markRead(string $id): void
    {
        AuthMiddleware::requireRole('resident');
        $userId = AuthMiddleware::userId();

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            UrlHelper::redirect('/resident/notifications');
        }
        try {
            (new Notification())->markRead((int)$id, $userId);
        } catch (\Throwable $e) {}

        // Support optional redirect after marking read (used by "View Request" link)
        $redirect = trim($_POST['_redirect'] ?? '');
        if ($redirect !== '' && str_starts_with($redirect, '/resident/')) {
            UrlHelper::redirect($redirect);
        }

        UrlHelper::redirect('/resident/notifications');
    }

    public function markAllRead(): void
    {
        AuthMiddleware::requireRole('resident');
        $userId = AuthMiddleware::userId();

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            UrlHelper::redirect('/resident/notifications');
        }
        try {
            (new Notification())->markAllRead($userId);
        } catch (\Throwable $e) {}
        SessionHelper::flash('success', 'All notifications marked as read.');
        UrlHelper::redirect('/resident/notifications');
    }
}
