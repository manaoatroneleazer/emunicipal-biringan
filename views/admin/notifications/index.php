<?php
/**
 * Admin Notifications Page — Req 25.5, 25.6–25.12
 * Variables: $notifications (array), $total (int), $csrfToken (string)
 */
$appUrl        = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e             = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$notifications = $notifications ?? [];
$total         = (int)($total    ?? 0);
$csrfToken     = $csrfToken      ?? '';

// Notification type → icon + label mapping
$typeMap = [
    // Staff/Admin operational types (Req 25)
    'new_request_staff'         => ['icon' => 'bi-bell-fill',         'label' => 'New Request',              'color' => 'text-primary'],
    'resident_cancelled'        => ['icon' => 'bi-slash-circle',      'label' => 'Cancelled by Resident',    'color' => 'text-warning'],
    'additional_info_submitted' => ['icon' => 'bi-upload',            'label' => 'Additional Info Submitted','color' => 'text-warning'],
    // Standard resident-facing types (may be visible if mixed)
    'request_submitted'         => ['icon' => 'bi-send',              'label' => 'Request Submitted',        'color' => 'text-primary'],
    'under_verification'        => ['icon' => 'bi-search',            'label' => 'Under Verification',       'color' => 'text-info'],
    'additional_info_required'  => ['icon' => 'bi-exclamation-circle','label' => 'Additional Info Required', 'color' => 'text-warning'],
    'approved'                  => ['icon' => 'bi-check-circle',      'label' => 'Approved',                 'color' => 'text-success'],
    'rejected'                  => ['icon' => 'bi-x-circle',          'label' => 'Rejected',                 'color' => 'text-danger'],
    'ready_for_claiming'        => ['icon' => 'bi-building',          'label' => 'Ready for Claiming',       'color' => 'text-teal'],
    'completed'                 => ['icon' => 'bi-check-circle-fill', 'label' => 'Completed',                'color' => 'text-success'],
    'cancelled'                 => ['icon' => 'bi-slash-circle',      'label' => 'Cancelled',                'color' => 'text-secondary'],
];
$defaultType = ['icon' => 'bi-bell', 'label' => 'Notification', 'color' => 'text-secondary'];

$unreadCount = 0;
foreach ($notifications as $n) {
    if (!(bool)$n['is_read']) $unreadCount++;
}

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/admin/dashboard'],
    ['label' => 'Notifications'],
];
?>

<div class="b2-page-header mt-1 mb-4">
    <div class="b2-page-header-left">
        <div class="b2-page-header-accent"></div>
        <h1>Notifications
            <?php if ($unreadCount > 0): ?>
            <span class="badge rounded-pill ms-2" style="background:var(--emu-danger);font-size:0.72rem;vertical-align:middle;">
                <?= $unreadCount ?> unread
            </span>
            <?php endif; ?>
        </h1>
    </div>
    <?php if ($unreadCount > 0): ?>
    <form method="POST" action="<?= $e($appUrl) ?>/admin/notifications/read-all">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
        <button type="submit" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-check2-all me-1" aria-hidden="true"></i> Mark All as Read
        </button>
    </form>
    <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
<!-- Empty state -->
<div class="card">
    <div class="card-body">
        <div class="empty-state py-5">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <h5>No notifications yet</h5>
            <p>You'll receive notifications here when residents submit or cancel requests.</p>
            <a href="<?= $e($appUrl) ?>/admin/requests" class="btn btn-primary btn-sm">
                <i class="bi bi-inbox me-1" aria-hidden="true"></i> View All Requests
            </a>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Notification list — reverse chronological, read vs unread (Req 25.5) -->
<div class="card">
    <div class="card-body p-0">
        <ul class="list-unstyled mb-0" aria-label="Notification list" role="list">
            <?php foreach ($notifications as $idx => $notif): ?>
            <?php
            $isUnread  = !(bool)$notif['is_read'];
            $typeInfo  = $typeMap[$notif['type']] ?? $defaultType;
            $refNum    = $notif['reference_number'] ?? '';
            $requestId = (int)($notif['request_id'] ?? 0);
            $dateStr   = !empty($notif['created_at'])
                         ? date('M j, Y \a\t g:i A', strtotime($notif['created_at']))
                         : '';
            // Admin view request links go to /admin/requests/{id} (Req 25.7)
            $viewTarget = ($requestId > 0)
                ? '/admin/requests/' . $requestId
                : '/admin/requests';
            ?>
            <li class="<?= $isUnread ? 'notif-item-unread' : 'notif-item-read' ?>"
                style="padding:14px 20px;<?= $idx > 0 ? 'border-top:1px solid var(--emu-divider);' : '' ?>"
                role="listitem">
                <div class="d-flex align-items-start gap-3">
                    <!-- Type icon -->
                    <div class="flex-shrink-0 mt-1">
                        <i class="bi <?= $e($typeInfo['icon']) ?> <?= $e($typeInfo['color']) ?>"
                           style="font-size:1.2rem;" aria-hidden="true"></i>
                    </div>

                    <!-- Content -->
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                            <div>
                                <span class="<?= $isUnread ? 'fw-600' : '' ?>"
                                      style="font-size:0.9rem;color:<?= $isUnread ? 'var(--emu-primary)' : 'var(--emu-text-secondary)' ?>;">
                                    <?= $e($typeInfo['label']) ?>
                                </span>
                                <?php if ($refNum): ?>
                                <span class="text-reference ms-2"
                                      style="font-size:0.8rem;color:var(--emu-primary);">
                                    <?= $e($refNum) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted-sm flex-shrink-0"><?= $e($dateStr) ?></div>
                        </div>
                        <p class="mb-2 mt-1"
                           style="font-size:0.875rem;color:var(--emu-text-secondary);line-height:1.5;">
                            <?= $e($notif['message'] ?? '') ?>
                        </p>
                        <div class="d-flex align-items-center gap-3">
                            <!-- View Request (Req 25.7) — marks as read AND navigates -->
                            <?php if ($requestId > 0): ?>
                            <?php if ($isUnread): ?>
                            <form method="POST"
                                  action="<?= $e($appUrl) ?>/admin/notifications/<?= (int)$notif['id'] ?>/read"
                                  class="d-inline">
                                <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                                <input type="hidden" name="_redirect"
                                       value="<?= $e($viewTarget) ?>">
                                <button type="submit"
                                        class="btn btn-sm btn-link p-0 text-decoration-none"
                                        style="color:var(--emu-text-link);font-size:0.8rem;">
                                    <i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i>
                                    View Request
                                </button>
                            </form>
                            <?php else: ?>
                            <a href="<?= $e($appUrl . $viewTarget) ?>"
                               class="btn btn-sm btn-link p-0 text-decoration-none"
                               style="color:var(--emu-text-link);font-size:0.8rem;">
                                <i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i>
                                View Request
                            </a>
                            <?php endif; ?>
                            <?php endif; ?>

                            <!-- Mark as read (unread only) (Req 25.8) -->
                            <?php if ($isUnread): ?>
                            <form method="POST"
                                  action="<?= $e($appUrl) ?>/admin/notifications/<?= (int)$notif['id'] ?>/read"
                                  class="d-inline">
                                <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                                <button type="submit"
                                        class="btn btn-sm btn-link p-0 text-decoration-none text-muted"
                                        style="font-size:0.8rem;"
                                        aria-label="Mark notification as read">
                                    <i class="bi bi-check2 me-1" aria-hidden="true"></i>
                                    Mark as read
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted-sm" style="font-size:0.75rem;">
                                <i class="bi bi-check2-all me-1" aria-hidden="true"></i>Read
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Unread indicator dot -->
                    <?php if ($isUnread): ?>
                    <div class="flex-shrink-0 mt-2"
                         style="width:8px;height:8px;border-radius:50%;background:var(--emu-danger);"
                         aria-hidden="true">
                    </div>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($total > 50): ?>
    <div class="card-footer text-center" style="font-size:0.875rem;color:var(--emu-text-muted);">
        Showing 50 most recent notifications. <?= $total ?> total.
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
