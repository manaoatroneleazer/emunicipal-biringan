<?php
/**
 * Resident Dashboard — B2 Professional UI/UX Redesign
 * Personal service portal optimized for individual document request management
 * Variables: $stats (array), $recentRequests (array)
 */
use Emunicipal\Middleware\AuthMiddleware;

$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$firstName = htmlspecialchars(AuthMiddleware::firstName() ?? '', ENT_QUOTES, 'UTF-8');
$lastName  = htmlspecialchars(AuthMiddleware::lastName() ?? '', ENT_QUOTES, 'UTF-8');

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

$statusConfig = [
    'pending'                  => ['label' => 'Pending',             'icon' => 'bi-clock',              'class' => 'status-pending'],
    'under_verification'       => ['label' => 'Under Verification',  'icon' => 'bi-search',             'class' => 'status-under_verification'],
    'additional_info_required' => ['label' => 'Add. Info Required',  'icon' => 'bi-exclamation-circle', 'class' => 'status-additional_info_required'],
    'approved'                 => ['label' => 'Approved',            'icon' => 'bi-check-circle',       'class' => 'status-approved'],
    'rejected'                 => ['label' => 'Rejected',            'icon' => 'bi-x-circle',           'class' => 'status-rejected'],
    'ready_for_claiming'       => ['label' => 'Ready for Claiming',  'icon' => 'bi-building',           'class' => 'status-ready_for_claiming'],
    'completed'                => ['label' => 'Completed',           'icon' => 'bi-check-circle-fill',  'class' => 'status-completed'],
    'cancelled'                => ['label' => 'Cancelled',           'icon' => 'bi-slash-circle',       'class' => 'status-cancelled'],
];

function renderStatusBadge(string $status, array $config): string {
    $cfg = $config[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'icon' => 'bi-circle', 'class' => ''];
    return sprintf(
        '<span class="status-badge %s"><i class="bi %s" aria-hidden="true"></i> %s</span>',
        htmlspecialchars($cfg['class'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($cfg['icon'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8')
    );
}

$inProgress = (int)($stats['pending'] ?? 0) + 
              (int)($stats['under_verification'] ?? 0) + 
              (int)($stats['additional_info_required'] ?? 0) +
              (int)($stats['approved'] ?? 0);
?>

<!-- Dashboard Header with Greeting -->
<div class="b2-dashboard-hero mb-4">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="b2-hero-content">
                <h1 class="b2-hero-title"><?= $greeting ?>, <?= $firstName ?>!</h1>
                <p class="b2-hero-subtitle">Manage your document requests and track their progress from one place.</p>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <a href="<?= $e($appUrl) ?>/resident/requests/new" 
               class="btn btn-primary btn-lg b2-btn-cta">
                <i class="bi bi-plus-circle me-2" aria-hidden="true"></i>
                Request a Document
            </a>
        </div>
    </div>
</div>

<!-- Primary Metrics -->
<div class="row g-3 g-lg-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="b2-metric b2-metric-featured">
            <div class="b2-metric-icon ic-total">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
            </div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">Total Requests</div>
                <div class="b2-metric-value"><?= (int)($stats['total'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="b2-metric b2-metric-featured">
            <div class="b2-metric-icon ic-pending">
                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
            </div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">In Progress</div>
                <div class="b2-metric-value"><?= $inProgress ?></div>
                <?php if ($inProgress > 0): ?>
                <div class="b2-metric-sub">Being processed</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="b2-metric b2-metric-featured">
            <div class="b2-metric-icon ic-done">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            </div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">Completed</div>
                <div class="b2-metric-value"><?= (int)($stats['completed'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="b2-metric b2-metric-featured">
            <div class="b2-metric-icon ic-gold">
                <i class="bi bi-building" aria-hidden="true"></i>
            </div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">Ready for Claiming</div>
                <div class="b2-metric-value"><?= (int)($stats['ready_for_claiming'] ?? 0) ?></div>
                <?php if ((int)($stats['ready_for_claiming'] ?? 0) > 0): ?>
                <div class="b2-metric-sub">Visit the office</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Status Indicators (if any) -->
<?php 
$needsAttention = (int)($stats['additional_info_required'] ?? 0);
$rejectedCount = (int)($stats['rejected'] ?? 0);
if ($needsAttention > 0 || $rejectedCount > 0): 
?>
<div class="mb-4">
    <div class="row g-2">
        <?php if ($needsAttention > 0): ?>
        <div class="col-auto">
            <div class="b2-alert-pill b2-alert-warning">
                <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>
                <strong><?= $needsAttention ?></strong> request<?= $needsAttention !== 1 ? 's' : '' ?> need<?= $needsAttention === 1 ? 's' : '' ?> additional information
            </div>
        </div>
        <?php endif; ?>
        <?php if ($rejectedCount > 0): ?>
        <div class="col-auto">
            <div class="b2-alert-pill b2-alert-danger">
                <i class="bi bi-x-circle me-1" aria-hidden="true"></i>
                <strong><?= $rejectedCount ?></strong> request<?= $rejectedCount !== 1 ? 's' : '' ?> rejected
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recent Requests Section -->
<div class="b2-card">
    <div class="b2-card-header">
        <div class="d-flex align-items-center gap-2">
            <div class="b2-card-header-icon">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
            </div>
            <h2>Recent Requests</h2>
        </div>
        <a href="<?= $e($appUrl) ?>/resident/requests" 
           class="btn btn-sm btn-outline-primary">
            View All <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
        </a>
    </div>
    <div>
        <?php if (empty($recentRequests)): ?>
        <!-- Empty State -->
        <div class="b2-empty">
            <div class="b2-empty-icon">
                <i class="bi bi-inbox" aria-hidden="true"></i>
            </div>
            <h5 class="b2-empty-title">No requests yet</h5>
            <p class="b2-empty-text">You haven't submitted any document requests. Start your first request now to experience fast and convenient online municipal services.</p>
            <a href="<?= $e($appUrl) ?>/resident/requests/new" 
               class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> 
                Submit Your First Request
            </a>
        </div>
        <?php else: ?>
        <!-- Desktop Table View -->
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table b2-table mb-0" aria-label="Recent document requests">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 18%;">Reference Number</th>
                            <th scope="col" style="width: 35%;">Document Type</th>
                            <th scope="col" style="width: 15%;">Date Requested</th>
                            <th scope="col" style="width: 20%;">Status</th>
                            <th scope="col" style="width: 12%;" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRequests as $req): ?>
                        <tr class="b2-table-row-clickable">
                            <td>
                                <a href="<?= $e($appUrl) ?>/resident/requests/<?= (int)$req['id'] ?>"
                                   class="b2-ref-link">
                                    <?= $e($req['reference_number']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="b2-doc-type-cell">
                                    <?= $e($req['document_type_name'] ?? 'N/A') ?>
                                </div>
                            </td>
                            <td class="text-muted-sm">
                                <?= $e(date('M j, Y', strtotime($req['created_at']))) ?>
                            </td>
                            <td>
                                <?= renderStatusBadge($req['status'], $statusConfig) ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= $e($appUrl) ?>/resident/requests/<?= (int)$req['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="d-md-none">
            <?php foreach ($recentRequests as $req): ?>
            <a href="<?= $e($appUrl) ?>/resident/requests/<?= (int)$req['id'] ?>"
               class="b2-mobile-request-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="b2-ref-mobile"><?= $e($req['reference_number']) ?></div>
                    <?= renderStatusBadge($req['status'], $statusConfig) ?>
                </div>
                <div class="b2-mobile-doc-type mb-2">
                    <?= $e($req['document_type_name'] ?? 'N/A') ?>
                </div>
                <div class="b2-mobile-date">
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                    <?= $e(date('M j, Y', strtotime($req['created_at']))) ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- How Your Request Works (info section) -->
<div class="row g-3 mt-4">
    <div class="col-12">
        <div class="b2-info-card">
            <div class="b2-info-card-header">
                <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                <h3>How Your Request Works</h3>
            </div>
            <div class="b2-info-steps">
                <div class="b2-info-step">
                    <div class="b2-info-step-number">1</div>
                    <div class="b2-info-step-content">
                        <div class="b2-info-step-title">Submit Request</div>
                        <div class="b2-info-step-text">Fill out the form and upload required documents</div>
                    </div>
                </div>
                <div class="b2-info-step">
                    <div class="b2-info-step-number">2</div>
                    <div class="b2-info-step-content">
                        <div class="b2-info-step-title">Verification</div>
                        <div class="b2-info-step-text">Staff reviews your submitted information</div>
                    </div>
                </div>
                <div class="b2-info-step">
                    <div class="b2-info-step-number">3</div>
                    <div class="b2-info-step-content">
                        <div class="b2-info-step-title">Approval</div>
                        <div class="b2-info-step-text">Your request is processed and approved</div>
                    </div>
                </div>
                <div class="b2-info-step">
                    <div class="b2-info-step-number">4</div>
                    <div class="b2-info-step-content">
                        <div class="b2-info-step-title">Claim</div>
                        <div class="b2-info-step-text">Visit the office to receive your document</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
