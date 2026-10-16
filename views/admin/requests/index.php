<?php
/**
 * Admin Requests List — Req 13.1, 13.2, 17.1, 17.3, 19.1
 * Variables: $requests, $filters, $counts
 */
$appUrl   = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e        = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$requests = $requests ?? [];
$filters  = $filters  ?? [];
$counts   = $counts   ?? [];

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/admin/dashboard'],
    ['label' => 'All Requests'],
];

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
    return "<span class=\"status-badge {$cls}\"><i class=\"bi {$icon}\" aria-hidden=\"true\"></i> "
         . $e($label) . "</span>";
};
?>

<div class="b2-page-header mt-1 mb-3">
    <div class="b2-page-header-left">
        <div class="b2-page-header-accent"></div>
        <h1>All Requests
            <?php if (!empty($counts)): ?>
            <span class="badge bg-secondary ms-2" style="font-size:0.75rem;vertical-align:middle;"><?= array_sum($counts) ?> total</span>
            <?php if (!empty($counts['pending'])): ?>
            <span class="badge ms-1" style="background:var(--emu-danger);font-size:0.75rem;vertical-align:middle;"><?= (int)$counts['pending'] ?> pending</span>
            <?php endif; ?>
            <?php endif; ?>
        </h1>
    </div>
</div>

<!-- Status filter tabs -->
<div class="b2-status-tabs mb-3">
    <?php
    $allStatuses = [
        ''                         => ['All',              'bi-inbox'],
        'pending'                  => ['Pending',          'bi-clock'],
        'under_verification'       => ['Under Verif.',     'bi-search'],
        'additional_info_required' => ['Add. Info Req.',   'bi-exclamation-circle'],
        'approved'                 => ['Approved',         'bi-check-circle'],
        'ready_for_claiming'       => ['Ready to Claim',   'bi-building'],
        'completed'                => ['Completed',        'bi-check-circle-fill'],
        'rejected'                 => ['Rejected',         'bi-x-circle'],
        'cancelled'                => ['Cancelled',        'bi-slash-circle'],
    ];
    $activeFilter = $filters['status'] ?? '';
    foreach ($allStatuses as $val => [$lbl, $ico]):
        $cnt  = ($val === '') ? array_sum($counts) : (int)($counts[$val] ?? 0);
        $href = $appUrl . '/admin/requests' . ($val ? '?status=' . urlencode($val) : '');
    ?>
    <a href="<?= $e($href) ?>" class="b2-tab <?= ($activeFilter === $val) ? 'active' : '' ?>"
       <?= ($activeFilter === $val) ? 'aria-current="page"' : '' ?>>
        <i class="bi <?= $e($ico) ?>" aria-hidden="true"></i>
        <?= $e($lbl) ?>
        <?php if ($cnt > 0): ?>
        <span class="tab-count"><?= $cnt ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Search & Filter form (Req 24.2, 24.3–24.5) -->
<form method="GET" action="<?= $e($appUrl) ?>/admin/requests" class="mb-3" id="adminSearchForm">
    <?php if ($activeFilter): ?>
    <input type="hidden" name="status" value="<?= $e($activeFilter) ?>">
    <?php endif; ?>

    <div class="row g-2 mb-2">
        <!-- Text search: ref, name, email, mobile (Req 24.2) -->
        <div class="col-12 col-md-6 col-lg-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white">
                    <i class="bi bi-search text-muted" aria-hidden="true"></i>
                </span>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="EMU reference, resident name, email or mobile…"
                       value="<?= $e($filters['search'] ?? '') ?>"
                       aria-label="Search requests">
            </div>
        </div>

        <!-- Document type filter (Req 24.4) -->
        <div class="col-6 col-md-3 col-lg-2">
            <select name="document_type_id" class="form-select form-select-sm"
                    aria-label="Filter by document type">
                <option value="">All Document Types</option>
                <?php foreach ($documentTypes ?? [] as $dt): ?>
                <option value="<?= (int)$dt['id'] ?>"
                    <?= ((int)($filters['document_type_id'] ?? 0) === (int)$dt['id']) ? 'selected' : '' ?>>
                    <?= $e($dt['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Date from (Req 24.5) -->
        <div class="col-6 col-md-2 col-lg-2">
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="<?= $e($filters['date_from'] ?? '') ?>"
                   aria-label="Date from" title="From date">
        </div>

        <!-- Date to (Req 24.5) -->
        <div class="col-6 col-md-2 col-lg-2">
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="<?= $e($filters['date_to'] ?? '') ?>"
                   aria-label="Date to" title="To date">
        </div>

        <!-- Submit + Clear -->
        <div class="col-6 col-md-auto">
            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-outline-secondary"
                        aria-label="Apply filters">
                    Search
                </button>
                <?php if (!empty($filters['search']) || !empty($filters['document_type_id']) || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                <a href="<?= $e($appUrl) ?>/admin/requests<?= $activeFilter ? '?status=' . urlencode($activeFilter) : '' ?>"
                   class="btn btn-sm btn-outline-danger"
                   title="Clear filters" aria-label="Clear filters">
                    <i class="bi bi-x" aria-hidden="true"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<!-- Requests table — always rendered; empty state lives inside <tbody> (Req 17.3, 19.1) -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="All document requests">
                <thead>
                    <tr>
                        <th scope="col">Reference No.</th>
                        <th scope="col">Resident</th>
                        <th scope="col">Document Type</th>
                        <th scope="col">Assigned Staff</th>
                        <th scope="col">Status</th>
                        <th scope="col">Submitted</th>
                        <th scope="col" class="table-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="7" class="p-0">
                            <div class="empty-state">
                                <i class="bi bi-inbox" aria-hidden="true"></i>
                                <h5>No requests found</h5>
                                <p>
                                    <?php if (!empty($filters['status']) || !empty($filters['search'])): ?>
                                    No requests match the current filter.
                                    <a href="<?= $e($appUrl) ?>/admin/requests">View all requests</a>
                                    <?php else: ?>
                                    No document requests have been submitted yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                    <tr>
                        <td>
                            <span class="text-reference" style="font-size:0.875rem;">
                                <?= $e($req['reference_number']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-size:0.875rem;font-weight:500;">
                                <?= $e($req['first_name'] . ' ' . $req['last_name']) ?>
                            </div>
                            <div class="text-muted-sm"><?= $e($req['email']) ?></div>
                        </td>
                        <td style="font-size:0.875rem;">
                            <?= $e($req['document_type_name'] ?? '') ?>
                        </td>
                        <td class="text-muted-sm" style="font-size:0.875rem;">
                            <?php
                            $assignedStaff = '';
                            if (!empty($req['staff_first_name']) && !empty($req['staff_last_name'])) {
                                $assignedStaff = $req['staff_first_name'] . ' ' . $req['staff_last_name'];
                            } elseif (!empty($req['assigned_staff_name'])) {
                                $assignedStaff = $req['assigned_staff_name'];
                            }
                            echo $assignedStaff ? $e($assignedStaff) : '<span class="text-muted">—</span>';
                            ?>
                        </td>
                        <td><?= $statusBadge($req['status']) ?></td>
                        <td class="text-muted-sm">
                            <?= $e(date('M j, Y', strtotime($req['created_at']))) ?>
                        </td>
                        <td class="table-actions">
                            <a href="<?= $e($appUrl) ?>/admin/requests/<?= (int)$req['id'] ?>"
                               class="btn btn-sm btn-outline-primary"
                               aria-label="View <?= $e($req['reference_number']) ?>">
                                <i class="bi bi-eye me-1" aria-hidden="true"></i>View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($requests)): ?>
        <?php
        $totalRows   = (int)($totalRows   ?? 0);
        $currentPage = (int)($currentPage ?? 1);
        $perPage     = (int)($perPage     ?? 20);
        $totalPages  = $perPage > 0 ? (int) ceil($totalRows / $perPage) : 1;
        $pageUrl = function(int $p) use ($appUrl, $filters): string {
            $q = [];
            if (!empty($filters['status']))           $q['status']           = $filters['status'];
            if (!empty($filters['search']))           $q['search']           = $filters['search'];
            if (!empty($filters['document_type_id'])) $q['document_type_id'] = $filters['document_type_id'];
            if (!empty($filters['date_from']))        $q['date_from']        = $filters['date_from'];
            if (!empty($filters['date_to']))          $q['date_to']          = $filters['date_to'];
            $q['page'] = $p;
            return $appUrl . '/admin/requests?' . http_build_query($q);
        };
        ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="text-muted-sm">
                <?php if ($totalRows > 0): ?>
                Showing <?= number_format(min(($currentPage - 1) * $perPage + 1, $totalRows)) ?>–<?= number_format(min($currentPage * $perPage, $totalRows)) ?>
                of <?= number_format($totalRows) ?> request<?= $totalRows !== 1 ? 's' : '' ?>
                <?php else: ?>
                0 requests
                <?php endif; ?>
            </span>
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Request list pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $e($pageUrl($currentPage - 1)) ?>" aria-label="Previous page">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </a>
                    </li>
                    <?php
                    $pStart = max(1, $currentPage - 2);
                    $pEnd   = min($totalPages, $currentPage + 2);
                    if ($pStart > 1): ?><li class="page-item"><a class="page-link" href="<?= $e($pageUrl(1)) ?>">1</a></li><?php if ($pStart > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; endif; ?>
                    <?php for ($p = $pStart; $p <= $pEnd; $p++): ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>" <?= $p === $currentPage ? 'aria-current="page"' : '' ?>>
                        <a class="page-link" href="<?= $e($pageUrl($p)) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($pEnd < $totalPages): ?><?php if ($pEnd < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?><li class="page-item"><a class="page-link" href="<?= $e($pageUrl($totalPages)) ?>"><?= $totalPages ?></a></li><?php endif; ?>
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $e($pageUrl($currentPage + 1)) ?>" aria-label="Next page">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
