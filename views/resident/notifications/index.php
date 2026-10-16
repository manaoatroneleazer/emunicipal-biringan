<?php
/**
 * Resident Notifications Page — design.md §6.8 (Notification UI), Req 16.1–16.5
 * Variables: $notifications (array), $total (int), $csrfToken (string)
 */
$appUrl        = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e             = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$notifications = $notifications ?? [];
$total         = (int)($total    ?? 0);
$csrfToken     = $csrfToken      ?? '';

// Notification type → icon + label mapping (design.md §6.8)
$typeMap = [
    'request_submitted'         => ['icon' => 'bi-send',            'label' => 'Request Submitted',                  'color' => 'text-primary'],
    'under_verification'        => ['icon' => 'bi-search',           'label' => 'Under Verification',                 'color' => 'text-info'],
    'additional_info_required'  => ['icon' => 'bi-exclamation-circle','label' => 'Additional Information Required',   'color' => 'text-warning'],
    'approved'                  => ['icon' => 'bi-check-circle',     'label' => 'Request Approved',                   'color' => 'text-success'],
    'rejected'                  => ['icon' => 'bi-x-circle',         'label' => 'Request Rejected',                   'color' => 'text-danger'],
    'ready_for_claiming'        => ['icon' => 'bi-building',         'label' => 'Ready for Claiming',                 'color' => 'text-teal'],
    'completed'                 => ['icon' => 'bi-check-circle-fill','label' => 'Completed',                          'color' => 'text-success'],
    'cancelled'                 => ['icon' => 'bi-slash-circle',     'label' => 'Cancelled',                          'color' => 'text-secondary'],
];
$defaultType = ['icon' => 'bi-bell', 'label' => 'Notification', 'color' => 'text-secondary'];

// Count unread
$unreadCount = 0;
foreach ($notifications as $n) {
    if (!(bool)$n['is_read']) $unreadCount++;
}
?>

<!-- Breadcrumb + header -->
<nav aria-label="breadcrumb" class="mb-1">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $e($appUrl) ?>/resident/dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Notifications</li>
    </ol>
</nav>

<div class="b2-page-header mt-1">
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
    <form method="POST" action="<?= $e($appUrl) ?>/resident/notifications/read-all">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
        <button type="submit" class="btn btn-outline-secondary btn-sm" aria-label="Mark all notifications as read">
            <i class="bi bi-check2-all me-1" aria-hidden="true"></i> Mark All as Read
        </button>
    </form>
    <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
<div class="b2-card">
    <div class="b2-empty py-5">
        <div class="b2-empty-icon"><i class="bi bi-bell" aria-hidden="true"></i></div>
        <h5>No notifications yet</h5>
        <p>You'll receive notifications here when the status of your requests changes.</p>
        <a href="<?= $e($appUrl) ?>/resident/requests/new" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Submit a New Request
        </a>
    </div>
</div>

<?php else: ?>
<div class="b2-card">
    <ul class="list-unstyled mb-0" aria-label="Notification list" role="list">
        <?php foreach ($notifications as $notif): ?>
        <?php
        $isUnread  = !(bool)$notif['is_read'];
        $typeInfo  = $typeMap[$notif['type']] ?? $defaultType;
        $refNum    = $notif['reference_number'] ?? '';
        $requestId = (int)($notif['request_id'] ?? 0);
        $dateStr   = !empty($notif['created_at']) ? date('M j, Y \a\t g:i A', strtotime($notif['created_at'])) : '';
        $linkTarget = ($requestId > 0) ? $appUrl . '/resident/requests/' . $requestId : $appUrl . '/resident/requests';

        // Map type → icon class
        $niClass = match($notif['type']) {
            'approved','completed'       => 'ni-success',
            'rejected','cancelled'       => 'ni-danger',
            'additional_info_required'   => 'ni-warning',
            'under_verification'         => 'ni-info',
            'ready_for_claiming'         => 'ni-warning',
            default                      => 'ni-primary',
        };
        ?>
        <li class="b2-notif-item <?= $isUnread ? 'unread' : 'read' ?>" role="listitem">
            <div class="d-flex align-items-start gap-3">
                <!-- Icon -->
                <div class="b2-notif-icon <?= $e($niClass) ?>" aria-hidden="true">
                    <i class="bi <?= $e($typeInfo['icon']) ?>"></i>
                </div>

                <!-- Content -->
                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-1">
                        <div>
                            <span class="b2-notif-title"><?= $e($typeInfo['label']) ?></span>
                            <?php if ($refNum): ?>
                            <span class="text-reference ms-2" style="font-size:0.78rem;color:var(--biringan-primary);"><?= $e($refNum) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="b2-notif-meta flex-shrink-0"><?= $e($dateStr) ?></span>
                    </div>
                    <p class="b2-notif-msg"><?= $e($notif['message'] ?? '') ?></p>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <?php if ($requestId > 0): ?>
                        <?php if ($isUnread): ?>
                        <form method="POST" action="<?= $e($appUrl) ?>/resident/notifications/<?= (int)$notif['id'] ?>/read" class="d-inline">
                            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                            <input type="hidden" name="_redirect" value="<?= $e('/resident/requests/' . $requestId) ?>">
                            <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none" style="color:var(--biringan-rose);font-size:0.8rem;">
                                <i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i>View Request
                            </button>
                        </form>
                        <?php else: ?>
                        <a href="<?= $e($linkTarget) ?>" class="btn btn-sm btn-link p-0 text-decoration-none" style="color:var(--biringan-rose);font-size:0.8rem;">
                            <i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i>View Request
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($isUnread): ?>
                        <form method="POST" action="<?= $e($appUrl) ?>/resident/notifications/<?= (int)$notif['id'] ?>/read" class="d-inline">
                            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                            <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none text-muted" style="font-size:0.8rem;" aria-label="Mark as read">
                                <i class="bi bi-check2 me-1" aria-hidden="true"></i>Mark as read
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="b2-notif-meta"><i class="bi bi-check2-all me-1" aria-hidden="true"></i>Read</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Unread dot -->
                <?php if ($isUnread): ?>
                <div class="b2-unread-dot" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($total > 20): ?>
    <div class="p-3 border-top text-center" style="font-size:0.875rem;color:var(--biringan-muted);">
        Showing 20 most recent of <?= $total ?> total notifications.
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- notification list rendered by header replacement above -->
