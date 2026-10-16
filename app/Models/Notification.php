<?php

namespace Emunicipal\Models;

/**
 * Notification model — maps to `notifications` (database.md Section 4.8).
 *
 * In-system notifications for Residents only (per Req 16).
 * Columns: id, user_id, request_id, reference_number, type,
 *          title, message, is_read, read_at, created_at
 */
class Notification extends BaseModel
{
    protected string $table = 'notifications';

    /** Notification type constants — match database.md Section 9.2 */
    public const TYPE_REQUEST_SUBMITTED         = 'request_submitted';
    public const TYPE_UNDER_VERIFICATION        = 'under_verification';
    public const TYPE_ADDITIONAL_INFO_REQUIRED  = 'additional_info_required';
    public const TYPE_APPROVED                  = 'approved';
    public const TYPE_REJECTED                  = 'rejected';
    public const TYPE_READY_FOR_CLAIMING        = 'ready_for_claiming';
    public const TYPE_COMPLETED                 = 'completed';
    public const TYPE_CANCELLED                 = 'cancelled';

    /** Staff/Admin operational notification types (Req 25.1, 25.2, 25.14) */
    public const TYPE_NEW_REQUEST_STAFF             = 'new_request_staff';
    public const TYPE_RESIDENT_CANCELLED            = 'resident_cancelled';
    public const TYPE_ADDITIONAL_INFO_SUBMITTED     = 'additional_info_submitted';

    /** Payment notification type (Phase 2 Cash Payment) */
    public const TYPE_PAYMENT_RECEIVED = 'payment_received';

    /** Online payment initiated notification (Phase 3 Demo) */
    public const TYPE_ONLINE_PAYMENT_INITIATED = 'online_payment_initiated';

    /**
     * Count unread notifications for a user (per Req 16.2).
     * Uses the composite index (user_id, is_read).
     */
    public function countUnread(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0"
        );
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Return paginated notifications for a user, newest first (per Req 16.3).
     */
    public function forUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim',     $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':off',     $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Mark a notification as read for a specific user (per Req 16.4).
     * user_id check prevents cross-user manipulation.
     */
    public function markRead(int $notificationId, int $userId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications
             SET is_read = 1, read_at = NOW()
             WHERE id = :id AND user_id = :user_id"
        );
        $stmt->execute([':id' => $notificationId, ':user_id' => $userId]);
        return $stmt->rowCount();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllRead(int $userId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications
             SET is_read = 1, read_at = NOW()
             WHERE user_id = :user_id AND is_read = 0"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->rowCount();
    }
}
