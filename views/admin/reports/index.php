<?php
/**
 * Admin Reports — Req 12.2–12.3, 12.5
 * Variables: $filters, $documentTypes, $reportRows, $totalRows,
 *            $totalPages, $currentPage, $perPage, $summaryStats
 */
$appUrl        = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e             = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$filters       = $filters       ?? [];
$documentTypes = $documentTypes ?? [];
$reportRows    = $reportRows    ?? [];
$totalRows     = (int)($totalRows    ?? 0);
$totalPages    = (int)($totalPages   ?? 1);
$currentPage   = (int)($currentPage  ?? 1);
$perPage       = (int)($perPage      ?? 20);
$summaryStats  = $summaryStats  ?? ['total' => 0, 'by_status' => [], 'by_doc' => []];

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/admin/dashboard'],
    ['label' => 'Reports'],
];

// Status labels
$statusLabels = [
    'pending'                  => 'Pending',
    'under_verification'       => 'Under Verification',
    'additional_info_required' => 'Add. Info Required',
    'approved'                 => 'Approved',
    'rejected'                 => 'Rejected',
    'ready_for_claiming'       => 'Ready for Claiming',
    'completed'                => 'Completed',
    'cancelled'                => 'Cancelled',
];
$statusBadgeCfg = [
    'pending'                  => 'status-pending',
    'under_verification'       => 'status-under_verification',
    'additional_info_required' => 'status-additional_info_required',
    'approved'                 => 'status-approved',
    'rejected'                 => 'status-rejected',
    'ready_for_claiming'       => 'status-ready_for_claiming',
    'completed'                => 'status-completed',
    'cancelled'                => 'status-cancelled',
];

/**
 * Build a query string that preserves active filters when changing page.
 */
function reportPageUrl(string $appUrl, array $filters, int $page): string {
    $q = [];
    if (!empty($filters['date_from']))        $q['date_from']        = $filters['date_from'];
    if (!empty($filters['date_to']))          $q['date_to']          = $filters['date_to'];
    if (!empty($filters['status']))           $q['status']           = $filters['status'];
    if (!empty($filters['document_type_id'])) $q['document_type_id'] = $filters['document_type_id'];
    $q['page'] = $page;
    return $appUrl . '/admin/reports?' . http_build_query($q);
}

$hasFilters = !empty($filters['date_from']) || !empty($filters['date_to'])
           || !empty($filters['status'])    || !empty($filters['document_type_id']);
?>

<!-- Page header -->
<div class="b2-page-header mt-1">
    <div class="b2-page-header-left">
        <div class="b2-page-header-accent"></div>
        <h1>Reports
            <?php if ($hasFilters && $totalRows > 0): ?>
            <span class="badge bg-secondary ms-2" style="font-size:0.75rem;vertical-align:middle;">
                <?= $totalRows ?> result<?= $totalRows !== 1 ? 's' : '' ?>
            </span>
            <?php endif; ?>
        </h1>
        <p class="b2-page-sub">Generate and export request reports by date, type, or status.</p>
    </div>
</div>

<!-- Filter form (Req 12.2) -->
<div class="card mb-4">
    <div class="card-header">
        <h2 class="mb-0" style="font-size:0.9375rem;">
            <i class="bi bi-funnel me-1" aria-hidden="true"></i> Filter Report
        </h2>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= $e($appUrl) ?>/admin/reports" id="reportFilterForm">
            <div class="row g-3 align-items-end">

                <!-- Date From -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" id="date_from" name="date_from" class="form-control form-control-sm"
                           value="<?= $e($filters['date_from'] ?? '') ?>"
                           aria-label="Filter from date">
                </div>

                <!-- Date To -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" id="date_to" name="date_to" class="form-control form-control-sm"
                           value="<?= $e($filters['date_to'] ?? '') ?>"
                           aria-label="Filter to date">
                </div>

                <!-- Document Type -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="document_type_id" class="form-label">Document Type</label>
                    <select id="document_type_id" name="document_type_id"
                            class="form-select form-select-sm"
                            aria-label="Filter by document type">
                        <option value="">All Document Types</option>
                        <?php foreach ($documentTypes as $dt): ?>
                        <option value="<?= (int)$dt['id'] ?>"
                            <?= ((int)($filters['document_type_id'] ?? 0) === (int)$dt['id']) ? 'selected' : '' ?>>
                            <?= $e($dt['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select form-select-sm"
                            aria-label="Filter by status">
                        <option value="">All Statuses</option>
                        <?php foreach ($statusLabels as $val => $lbl): ?>
                        <option value="<?= $e($val) ?>"
                            <?= (($filters['status'] ?? '') === $val) ? 'selected' : '' ?>>
                            <?= $e($lbl) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div><!-- /.row -->

            <div class="d-flex gap-2 mt-3 flex-wrap">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1" aria-hidden="true"></i> Generate Report
                </button>
                <?php if ($hasFilters): ?>
                <a href="<?= $e($appUrl) ?>/admin/reports" class="btn btn-sm btn-outline-secondary"
                   aria-label="Clear all report filters">
                    <i class="bi bi-x me-1" aria-hidden="true"></i> Clear Filters
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($hasFilters): ?>

<!-- Summary Statistics (Req 12.3) -->
<div class="row g-3 mb-4">

    <!-- Total matching requests -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="b2-metric">
            <div class="b2-metric-icon ic-total"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></div>
            <div class="b2-metric-body">
                <div class="b2-metric-label">Matching Requests</div>
                <div class="b2-metric-value"><?= $summaryStats['total'] ?></div>
            </div>
        </div>
    </div>

    <!-- Top document type breakdown -->
    <?php if (!empty($summaryStats['by_doc'])): ?>
    <div class="col-12 col-sm-6 col-lg-5">
        <div class="b2-card h-100">
            <div class="b2-card-header">
                <h3>Top Document Types</h3>
            </div>
            <div class="card-body py-2">
                <?php $docMax = max(array_column($summaryStats['by_doc'], 'cnt') ?: [1]); ?>
                <?php foreach ($summaryStats['by_doc'] as $row): ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                        <span class="text-truncate me-2"><?= $e($row['doc_type']) ?></span>
                        <span class="badge bg-secondary"><?= (int)$row['cnt'] ?></span>
                    </div>
                    <div class="b2-progress-bar-wrap">
                        <div class="b2-progress-bar-fill" style="width:<?= min(100, round((int)$row['cnt']/$docMax*100)) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status breakdown -->
    <?php if (!empty($summaryStats['by_status'])): ?>
    <div class="col-12 col-lg-4">
        <div class="b2-card h-100">
            <div class="b2-card-header">
                <h3>By Status</h3>
            </div>
            <div class="card-body py-2">
                <?php foreach ($summaryStats['by_status'] as $st => $cnt): ?>
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <span class="status-badge <?= $e($statusBadgeCfg[$st] ?? '') ?>" style="font-size:0.7rem;padding:2px 8px;">
                        <?= $e($statusLabels[$st] ?? ucfirst(str_replace('_', ' ', $st))) ?>
                    </span>
                    <span class="badge bg-secondary"><?= (int)$cnt ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.row summary -->

<!-- Results Table (Req 12.3) -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="Report results">
                <thead>
                    <tr>
                        <th scope="col">Reference No.</th>
                        <th scope="col">Resident</th>
                        <th scope="col">Document Type</th>
                        <th scope="col">Status</th>
                        <th scope="col">Copies</th>
                        <th scope="col">Submitted</th>
                        <th scope="col" class="table-actions">View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportRows)): ?>
                    <tr>
                        <td colspan="7" class="p-0">
                            <div class="empty-state py-5">
                                <i class="bi bi-bar-chart" aria-hidden="true"></i>
                                <h5>No results found</h5>
                                <p>No requests match the selected filters.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($reportRows as $row): ?>
                    <tr>
                        <td>
                            <span class="text-reference" style="font-size:0.875rem;">
                                <?= $e($row['reference_number']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-size:0.875rem;font-weight:500;">
                                <?= $e($row['first_name'] . ' ' . $row['last_name']) ?>
                            </div>
                            <div class="text-muted-sm"><?= $e($row['email']) ?></div>
                        </td>
                        <td style="font-size:0.875rem;"><?= $e($row['document_type_name']) ?></td>
                        <td>
                            <span class="status-badge <?= $e($statusBadgeCfg[$row['status']] ?? '') ?>"
                                  style="font-size:0.72rem;">
                                <?= $e($statusLabels[$row['status']] ?? ucfirst(str_replace('_', ' ', $row['status']))) ?>
                            </span>
                        </td>
                        <td class="text-muted-sm"><?= (int)$row['copies_requested'] ?></td>
                        <td class="text-muted-sm">
                            <?= $e(date('M j, Y', strtotime($row['created_at']))) ?>
                        </td>
                        <td class="table-actions">
                            <a href="<?= $e($appUrl) ?>/admin/requests/<?= (int)$row['id'] ?>"
                               class="btn btn-sm btn-outline-primary"
                               aria-label="View request <?= $e($row['reference_number']) ?>">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination (Req 12.5 — 20 per page) -->
        <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="text-muted-sm">
                Showing <?= min(($currentPage - 1) * $perPage + 1, $totalRows) ?>–<?= min($currentPage * $perPage, $totalRows) ?>
                of <?= $totalRows ?> result<?= $totalRows !== 1 ? 's' : '' ?>
            </span>
            <nav aria-label="Report pagination">
                <ul class="pagination pagination-sm mb-0">
                    <!-- Previous -->
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="<?= $e(reportPageUrl($appUrl, $filters, $currentPage - 1)) ?>"
                           aria-label="Previous page">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </a>
                    </li>

                    <?php
                    // Show at most 7 page links with ellipsis
                    $start = max(1, $currentPage - 3);
                    $end   = min($totalPages, $currentPage + 3);
                    if ($start > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $e(reportPageUrl($appUrl, $filters, 1)) ?>">1</a>
                    </li>
                    <?php if ($start > 2): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>"
                        <?= $p === $currentPage ? 'aria-current="page"' : '' ?>>
                        <a class="page-link" href="<?= $e(reportPageUrl($appUrl, $filters, $p)) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link"
                           href="<?= $e(reportPageUrl($appUrl, $filters, $totalPages)) ?>">
                            <?= $totalPages ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Next -->
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="<?= $e(reportPageUrl($appUrl, $filters, $currentPage + 1)) ?>"
                           aria-label="Next page">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php elseif (!empty($reportRows)): ?>
        <div class="p-3 border-top text-muted-sm">
            <?= $totalRows ?> result<?= $totalRows !== 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>

    </div><!-- /.card-body -->
</div><!-- /.card results -->

<?php else: ?>
<!-- Prompt to apply filters -->
<div class="card">
    <div class="card-body">
        <div class="empty-state py-5">
            <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
            <h5>Generate a Report</h5>
            <p>Use the filters above to select a date range, document type, or status,<br>
               then click <strong>Generate Report</strong> to view results.</p>
        </div>
    </div>
</div>
<?php endif; ?>
