<?php
/**
 * Staff Dashboard — B2 Professional UI/UX Redesign
 * Operational request-processing workspace optimized for workload management
 * Variables: $counts (array status => count)
 */
use Emunicipal\Middleware\AuthMiddleware;

$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$firstName = htmlspecialchars(AuthMiddleware::firstName() ?? '', ENT_QUOTES, 'UTF-8');

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

// Calculate metrics
$totalActive = (int)($counts['pending'] ?? 0) + 
               (int)($counts['under_verification'] ?? 0) + 
               (int)($counts['additional_info_required'] ?? 0) +
               (int)($counts['approved'] ?? 0) +
               (int)($counts['ready_for_claiming'] ?? 0);

$needsAction = (int)($counts['pending'] ?? 0) + 
               (int)($counts['additional_info_required'] ?? 0);

$statusGroups = [
    'primary' => [
        ['key' => 'pending',                  'label' => 'Pending',             'icon' => 'bi-clock',              'ic' => 'ic-pending'],
        ['key' => 'under_verification',       'label' => 'Under Verification',   'icon' => 'bi-search',             'ic' => 'ic-info'],
        ['key' => 'additional_info_required', 'label' => 'Additional Info Req.', 'icon' => 'bi-exclamation-circle', 'ic' => 'ic-rose'],
    ],
    'secondary' => [
        ['key' => 'approved',                 'label' => 'Approved',             'icon' => 'bi-check-circle',       'ic' => 'ic-done'],
        ['key' => 'ready_for_claiming',       'label' => 'Ready for Claiming',   'icon' => 'bi-building',           'ic' => 'ic-gold'],
        ['key' => 'completed',                'label' => 'Completed',            'icon' => 'bi-check-circle-fill',  'ic' => 'ic-done'],
    ],
];
?>

<!-- Dashboard Header -->
<div class="b2-dashboard-hero mb-4">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="b2-hero-content">
                <h1 class="b2-hero-title"><?= $greeting ?>, <?= $firstName ?>!</h1>
                <p class="b2-hero-subtitle">Monitor and process resident document requests.</p>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <a href="<?= $e($appUrl) ?>/staff/requests" 
               class="btn btn-primary btn-lg b2-btn-cta">
                <i class="bi bi-inbox me-2" aria-hidden="true"></i>
                View All Requests
            </a>
        </div>
    </div>
</div>

<!-- Key Metrics -->
<div class="row g-3 g-lg-4 mb-4">
    <div class="col-6 col-lg-4">
        <div class="b2-metric b2-metric-featured">
            <div class="b2-metric-icon ic-pending">
                <i class="bi bi-clipboard-check" aria-hidden="true"></i>
            </div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">Total Active Requests</div>
                <div class="b2-metric-value"><?= $totalActive ?></div>
                <div class="b2-metric-sub">Currently in system</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="b2-metric b2-metric-featured">
            <div class="b2-metric-icon ic-rose">
                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            </div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">Needs Attention</div>
                <div class="b2-metric-value"><?= $needsAction ?></div>
                <div class="b2-metric-sub">Pending + Add. Info Required</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="b2-metric b2-metric-featured">
            <div class="b2-metric-icon ic-done">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            </div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">Completed Today</div>
                <div class="b2-metric-value"><?= (int)($counts['completed'] ?? 0) ?></div>
                <div class="b2-metric-sub">Successfully processed</div>
            </div>
        </div>
    </div>
</div>

<!-- Workload Overview -->
<div class="b2-card mb-4">
    <div class="b2-card-header">
        <div class="d-flex align-items-center gap-2">
            <div class="b2-card-header-icon">
                <i class="bi bi-bar-chart" aria-hidden="true"></i>
            </div>
            <h2>Workload Overview</h2>
        </div>
    </div>
    <div class="card-body">
        <!-- Primary Status Group -->
        <div class="mb-4">
            <h6 class="b2-status-group-title">
                <i class="bi bi-hourglass-split me-2" aria-hidden="true"></i>
                Requires Action
            </h6>
            <div class="row g-3">
                <?php foreach ($statusGroups['primary'] as $item): ?>
                <div class="col-12 col-md-4">
                    <a href="<?= $e($appUrl) ?>/staff/requests?status=<?= urlencode($item['key']) ?>" 
                       class="b2-status-metric-card">
                        <div class="b2-status-metric-icon <?= $e($item['ic']) ?>">
                            <i class="bi <?= $e($item['icon']) ?>" aria-hidden="true"></i>
                        </div>
                        <div class="b2-status-metric-body">
                            <div class="b2-status-metric-value"><?= (int)($counts[$item['key']] ?? 0) ?></div>
                            <div class="b2-status-metric-label"><?= $e($item['label']) ?></div>
                        </div>
                        <div class="b2-status-metric-arrow">
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Secondary Status Group -->
        <div>
            <h6 class="b2-status-group-title">
                <i class="bi bi-check-all me-2" aria-hidden="true"></i>
                Processed & Completed
            </h6>
            <div class="row g-3">
                <?php foreach ($statusGroups['secondary'] as $item): ?>
                <div class="col-12 col-md-4">
                    <a href="<?= $e($appUrl) ?>/staff/requests?status=<?= urlencode($item['key']) ?>" 
                       class="b2-status-metric-card">
                        <div class="b2-status-metric-icon <?= $e($item['ic']) ?>">
                            <i class="bi <?= $e($item['icon']) ?>" aria-hidden="true"></i>
                        </div>
                        <div class="b2-status-metric-body">
                            <div class="b2-status-metric-value"><?= (int)($counts[$item['key']] ?? 0) ?></div>
                            <div class="b2-status-metric-label"><?= $e($item['label']) ?></div>
                        </div>
                        <div class="b2-status-metric-arrow">
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3">
    <div class="col-12 col-md-6">
        <div class="b2-action-card b2-action-card-primary">
            <div class="b2-action-card-icon">
                <i class="bi bi-inbox-fill" aria-hidden="true"></i>
            </div>
            <div class="b2-action-card-content">
                <h3>Process Requests</h3>
                <p>View and manage all submitted document requests</p>
                <a href="<?= $e($appUrl) ?>/staff/requests" class="btn btn-light btn-sm">
                    View Requests <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="b2-action-card b2-action-card-secondary">
            <div class="b2-action-card-icon">
                <i class="bi bi-bell-fill" aria-hidden="true"></i>
            </div>
            <div class="b2-action-card-content">
                <h3>Notifications</h3>
                <p>Check updates on request status changes and alerts</p>
                <a href="<?= $e($appUrl) ?>/staff/notifications" class="btn btn-light btn-sm">
                    View Notifications <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Staff Tips -->
<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="b2-info-card b2-info-card-compact">
            <div class="d-flex align-items-start gap-3">
                <div style="font-size:2rem;color:var(--biringan-gold);flex-shrink:0;">
                    <i class="bi bi-lightbulb" aria-hidden="true"></i>
                </div>
                <div>
                    <h6 class="mb-1" style="font-weight:600;color:var(--biringan-text);">Processing Tips</h6>
                    <p class="mb-0" style="font-size:0.875rem;color:var(--biringan-muted);line-height:1.6;">
                        <strong>Pending requests</strong> should be moved to "Under Verification" when review begins. 
                        <strong>Additional info required</strong> alerts the resident to submit missing documents. 
                        Always provide clear remarks when changing status.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>