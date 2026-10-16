<?php
/**
 * Admin Dashboard — B2 Professional UI/UX Redesign
 * System management console optimized for analytics and administration
 * Variables: $metrics (array), $recentRequests (array)
 */
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$metrics   = $metrics ?? [];
$byStatus  = $metrics['by_status'] ?? [];
$recentRequests = $recentRequests ?? [];

$statusBadge = function(string $s) use ($e): string {
    $cfg = [
        'pending'                  => ['Pending',             'bi-clock',              'status-pending'],
        'under_verification'       => ['Under Verification',  'bi-search',             'status-under_verification'],
        'additional_info_required' => ['Add. Info Req.',      'bi-exclamation-circle', 'status-additional_info_required'],
        'approved'                 => ['Approved',            'bi-check-circle',       'status-approved'],
        'rejected'                 => ['Rejected',            'bi-x-circle',           'status-rejected'],
        'ready_for_claiming'       => ['Ready for Claiming',  'bi-building',           'status-ready_for_claiming'],
        'completed'                => ['Completed',           'bi-check-circle-fill',  'status-completed'],
        'cancelled'                => ['Cancelled',           'bi-slash-circle',       'status-cancelled'],
    ];
    [$label, $icon, $cls] = $cfg[$s] ?? [ucfirst(str_replace('_', ' ', $s)), 'bi-circle', ''];
    return "<span class=\"status-badge {$cls}\"><i class=\"bi {$icon}\" aria-hidden=\"true\"></i> " . $e($label) . "</span>";
};

$totalRequests     = (int)($metrics['total_requests'] ?? array_sum($byStatus));
$totalResidents    = (int)($metrics['total_residents'] ?? 0);
$totalStaff        = (int)($metrics['total_staff'] ?? 0);
$requestsThisMonth = (int)($metrics['requests_this_month'] ?? 0);

// Calculate active requests
$activeRequests = (int)($byStatus['pending'] ?? 0) + 
                  (int)($byStatus['under_verification'] ?? 0) + 
                  (int)($byStatus['additional_info_required'] ?? 0) +
                  (int)($byStatus['approved'] ?? 0) +
                  (int)($byStatus['ready_for_claiming'] ?? 0);
?>

<!-- Dashboard Header -->
<div class="b2-dashboard-hero b2-dashboard-hero-admin mb-4">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="b2-hero-content">
                <h1 class="b2-hero-title">System Overview</h1>
                <p class="b2-hero-subtitle">Monitor eMunicipal operations, requests, users, and document services.</p>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <a href="<?= $e($appUrl) ?>/admin/requests" 
               class="btn btn-primary btn-lg b2-btn-cta">
                <i class="bi bi-gear me-2" aria-hidden="true"></i>
                Manage System
            </a>
        </div>
    </div>
</div>

<!-- Key Performance Metrics -->
<div class="row g-3 g-lg-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="b2-metric b2-metric-featured">
            <div class="b2-metric-icon ic-total">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
            </div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">Total Requests</div>
                <div class="b2-metric-value"><?= $totalRequests ?></div>
                <div class="b2-metric-sub"><?= $requestsThisMonth ?> this month</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="b2-metric b2-metric-featured">
            <div class="b2-metric-icon ic-pending">
                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
            </div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">Active Requests</div>
                <div class="b2-metric-value"><?= $activeRequests ?></div>
                <div class="b2-metric-sub">Being processed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="b2-metric b2-metric-featured">
            <div class="b2-metric-icon ic-info">
                <i class="bi bi-people" aria-hidden="true"></i>
            </div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">Registered Residents</div>
                <div class="b2-metric-value"><?= $totalResidents ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="b2-metric b2-metric-featured">
            <div class="b2-metric-icon ic-rose">
                <i class="bi bi-person-badge" aria-hidden="true"></i>
            </div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">Staff Members</div>
                <div class="b2-metric-value"><?= $totalStaff ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Two-Column Layout -->
<div class="row g-4">
    <!-- Left Column: Recent Requests -->
    <div class="col-12 col-lg-7">
        <div class="b2-card mb-4">
            <div class="b2-card-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="b2-card-header-icon">
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                    </div>
                    <h2>Recent Requests</h2>
                </div>
                <a href="<?= $e($appUrl) ?>/admin/requests" class="btn btn-sm btn-outline-primary">
                    View All <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                </a>
            </div>
            <div>
                <?php if (empty($recentRequests)): ?>
                <div class="b2-empty">
                    <div class="b2-empty-icon">
                        <i class="bi bi-inbox" aria-hidden="true"></i>
                    </div>
                    <h5 class="b2-empty-title">No requests yet</h5>
                    <p class="b2-empty-text">No document requests have been submitted to the system.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table b2-table mb-0" aria-label="Recent document requests">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 22%;">Reference</th>
                                <th scope="col" style="width: 23%;">Resident</th>
                                <th scope="col" style="width: 32%;">Document Type</th>
                                <th scope="col" style="width: 23%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRequests as $req): ?>
                            <tr class="b2-table-row-clickable">
                                <td>
                                    <a href="<?= $e($appUrl) ?>/admin/requests/<?= (int)$req['id'] ?>"
                                       class="b2-ref-link">
                                        <?= $e($req['reference_number']) ?>
                                    </a>
                                </td>
                                <td style="font-size:0.875rem;">
                                    <?= $e(($req['first_name'] ?? '') . ' ' . ($req['last_name'] ?? '')) ?>
                                </td>
                                <td style="font-size:0.875rem;">
                                    <?= $e($req['document_type_name'] ?? 'N/A') ?>
                                </td>
                                <td>
                                    <?= $statusBadge($req['status']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Request Status Distribution -->
    <div class="col-12 col-lg-5">
        <div class="b2-card mb-4">
            <div class="b2-card-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="b2-card-header-icon">
                        <i class="bi bi-pie-chart" aria-hidden="true"></i>
                    </div>
                    <h2>Request Status Distribution</h2>
                </div>
            </div>
            <div class="card-body">
                <?php
                $statusList = [
                    'pending'                  => ['Pending',             'ic-pending'],
                    'under_verification'       => ['Under Verification',  'ic-info'],
                    'additional_info_required' => ['Add. Info Required',  'ic-rose'],
                    'approved'                 => ['Approved',            'ic-done'],
                    'ready_for_claiming'       => ['Ready for Claiming',  'ic-gold'],
                    'completed'                => ['Completed',           'ic-done'],
                ];
                foreach ($statusList as $key => [$label, $iconClass]):
                    $count = (int)($byStatus[$key] ?? 0);
                    $percent = $totalRequests > 0 ? round(($count / $totalRequests) * 100, 1) : 0;
                ?>
                <div class="b2-status-distribution-item">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="b2-status-dot <?= $e($iconClass) ?>"></div>
                            <span class="b2-status-dist-label"><?= $e($label) ?></span>
                        </div>
                        <div class="b2-status-dist-value"><?= $count ?></div>
                    </div>
                    <div class="b2-status-dist-bar">
                        <div class="b2-status-dist-fill <?= $e($iconClass) ?>" style="width: <?= $percent ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Access Links -->
<div class="b2-card mb-4">
    <div class="b2-card-header">
        <div class="d-flex align-items-center gap-2">
            <div class="b2-card-header-icon">
                <i class="bi bi-lightning" aria-hidden="true"></i>
            </div>
            <h2>Quick Access</h2>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <a href="<?= $e($appUrl) ?>/admin/document-types" class="b2-quick-link">
                    <div class="b2-quick-link-icon">
                        <i class="bi bi-file-text" aria-hidden="true"></i>
                    </div>
                    <div class="b2-quick-link-label">Document Types</div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="<?= $e($appUrl) ?>/admin/users" class="b2-quick-link">
                    <div class="b2-quick-link-icon">
                        <i class="bi bi-people" aria-hidden="true"></i>
                    </div>
                    <div class="b2-quick-link-label">Manage Users</div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="<?= $e($appUrl) ?>/admin/requests" class="b2-quick-link">
                    <div class="b2-quick-link-icon">
                        <i class="bi bi-inbox" aria-hidden="true"></i>
                    </div>
                    <div class="b2-quick-link-label">All Requests</div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="<?= $e($appUrl) ?>/admin/audit-logs" class="b2-quick-link">
                    <div class="b2-quick-link-icon">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                    </div>
                    <div class="b2-quick-link-label">Audit Logs</div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="<?= $e($appUrl) ?>/admin/reports" class="b2-quick-link">
                    <div class="b2-quick-link-icon">
                        <i class="bi bi-graph-up" aria-hidden="true"></i>
                    </div>
                    <div class="b2-quick-link-label">Reports</div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="<?= $e($appUrl) ?>/admin/settings" class="b2-quick-link">
                    <div class="b2-quick-link-icon">
                        <i class="bi bi-gear" aria-hidden="true"></i>
                    </div>
                    <div class="b2-quick-link-label">System Settings</div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="<?= $e($appUrl) ?>/admin/notifications" class="b2-quick-link">
                    <div class="b2-quick-link-icon">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                    </div>
                    <div class="b2-quick-link-label">Notifications</div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="<?= $e($appUrl) ?>/admin/profile" class="b2-quick-link">
                    <div class="b2-quick-link-icon">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                    </div>
                    <div class="b2-quick-link-label">My Profile</div>
                </a>
            </div>
        </div>
    </div>
</div>
