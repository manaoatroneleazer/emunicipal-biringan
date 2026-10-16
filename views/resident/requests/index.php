<?php
/**
 * Resident My Requests list — design.md §8.3, Req 8.1
 * Variables: $requests (array)
 */
$appUrl   = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e        = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$requests = $requests ?? [];

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/resident/dashboard'],
    ['label' => 'My Requests'],
];

// Status badge helper (reused across views)
function reqStatusBadge(string $status): string {
    $icons = [
        'pending'                  => 'bi-clock',
        'under_verification'       => 'bi-search',
        'additional_info_required' => 'bi-exclamation-circle',
        'approved'                 => 'bi-check-circle',
        'rejected'                 => 'bi-x-circle',
        'ready_for_claiming'       => 'bi-building',
        'completed'                => 'bi-check-circle-fill',
        'cancelled'                => 'bi-slash-circle',
    ];
    $labels = [
        'pending'                  => 'Pending',
        'under_verification'       => 'Under Verification',
        'additional_info_required' => 'Additional Info Required',
        'approved'                 => 'Approved',
        'rejected'                 => 'Rejected',
        'ready_for_claiming'       => 'Ready for Claiming',
        'completed'                => 'Completed',
        'cancelled'                => 'Cancelled',
    ];
    $icon  = $icons[$status]  ?? 'bi-circle';
    $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    return "<span class=\"status-badge status-{$status}\"><i class=\"bi {$icon}\" aria-hidden=\"true\"></i> "
         . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</span>";
}
?>

<div class="b2-page-header">
    <div class="b2-page-header-left">
        <div class="b2-page-header-accent"></div>
        <h1>My Requests</h1>
        <p class="b2-page-sub">Track the status of your document requests.</p>
    </div>
    <a href="<?= $e($appUrl) ?>/resident/requests/new" class="btn btn-primary flex-shrink-0">
        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> New Request
    </a>
</div>

<div class="b2-card">
    <div class="b2-card-header">
        <div class="d-flex align-items-center gap-2">
            <div class="b2-card-header-icon"><i class="bi bi-list-ul" aria-hidden="true"></i></div>
            <h2>All Requests</h2>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table b2-table mb-0" aria-label="My document requests">
            <thead>
                <tr>
                    <th scope="col">Reference No.</th>
                    <th scope="col">Document Type</th>
                    <th scope="col">Date Submitted</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="table-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                <tr>
                    <td colspan="5" class="p-0">
                        <div class="b2-empty">
                            <div class="b2-empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                            <h5>No requests yet</h5>
                            <p>You haven't submitted any document requests yet.</p>
                            <a href="<?= $e($appUrl) ?>/resident/requests/new" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Submit New Request
                            </a>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($requests as $req): ?>
                <tr>
                    <td>
                        <span class="ref-cell">
                            <?= $e($req['reference_number']) ?>
                        </span>
                    </td>
                    <td><?= $e($req['document_type_name'] ?? '') ?></td>
                    <td class="text-muted-sm"><?= $e(date('M j, Y', strtotime($req['created_at']))) ?></td>
                    <td><?= reqStatusBadge($req['status']) ?></td>
                    <td class="table-actions">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="<?= $e($appUrl) ?>/resident/requests/<?= (int)$req['id'] ?>"
                               class="btn btn-sm btn-outline-primary"
                               aria-label="View request <?= $e($req['reference_number']) ?>">
                                View
                            </a>
                            <a href="<?= $e($appUrl) ?>/resident/track?ref=<?= urlencode($req['reference_number']) ?>"
                               class="btn btn-sm btn-outline-secondary"
                               aria-label="Track request <?= $e($req['reference_number']) ?>">
                                Track
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
