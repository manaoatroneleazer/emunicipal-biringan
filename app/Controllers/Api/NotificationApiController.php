<?php

namespace Emunicipal\Controllers\Api;

use Emunicipal\Models\Notification;
use Emunicipal\Core\Logger;

/**
 * NotificationApiController — Notifications resource API.
 *
 * GET   /api/notifications          — Resident: own notifications (paginated)
 * PATCH /api/notifications/{id}/read — Resident: mark one as read
 *
 * Per Req 15.1, Req 16 (in-system notifications).
 * Ownership is enforced at the model level: markRead(id, userId) requires matching user_id.
 * A user can never read or modify another user's notifications.
 */
class NotificationApiController extends BaseApiController
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/notifications
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): void
    {
        $userId = $this->requireRole('resident');

        [$page, $perPage] = $this->pagination(20);
        $offset = ($page - 1) * $perPage;

        try {
            $model         = new Notification();
            $notifications = $model->forUser($userId, $perPage, $offset);
            $total         = $model->count(['user_id' => $userId]);
            $unreadCount   = $model->countUnread($userId);

            $this->json(array_merge(
                $this->paginated($notifications, $total, $page, $perPage),
                ['unread_count' => $unreadCount]
            ));

        } catch (\Throwable $e) {
            Logger::error('NotificationApiController::index: ' . $e->getMessage());
            $this->error('Failed to load notifications.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PATCH /api/notifications/{id}/read
    // ─────────────────────────────────────────────────────────────────────────

    public function markRead(string $id): void
    {
        $userId = $this->requireRole('resident');
        $notifId = (int)$id;

        try {
            $model   = new Notification();
            $affected = $model->markRead($notifId, $userId);

            if ($affected === 0) {
                // Either doesn't exist or belongs to another user — 404 in both cases
                $this->error('Notification not found.', 404);
            }

            $this->json(['id' => $notifId, 'is_read' => true], 'Notification marked as read.');

        } catch (\Throwable $e) {
            Logger::error('NotificationApiController::markRead: ' . $e->getMessage());
            $this->error('Failed to mark notification as read.', 500);
        }
    }
}
