<?php
/**
 * Admin Payment History
 * Variables: $filters, $rows, $totalRows, $totalPages, $currentPage, $perPage
 */
$appUrl     = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e          = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$filters    = $filters    ?? [];
$rows       = $rows       ?? [];
$totalRows  = (int)($totalRows  ?? 0);
$totalPages = (int)($totalPages ?? 1);
$currentPage = (int)($currentPage ?? 1);
$perPage    = (int)($perPage    ?? 20);

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/admin/dashboard'],
    ['label' => 'Payment History'],
];

// Status badge helper
$statusBadge = function(string $s) use ($e): string {
    $cfg = [
        'pending'         => ['Pending',         'bi-hourglass-split', 'status-pending'],
        'online_pending'  => ['Online Pending',  'bi-hourglass-split', 'status-under_verification'],
        'paid'            => ['Paid',             'bi-check-circle',   'status-approved'],
    ];
    [$label, $icon, $cls] = $cfg[$s] ?? [ucfirst(str_replace('_', ' ', $s)), 'bi-circle', ''];
    return "<span class=\"status-badge {$cls}\"><i class=\"bi {$icon}\" aria-hidden=\"true\"></i> "
         . $e($label) . "</span>";
};

// Method badge helper
$methodBadge = function(string $m) use ($e): string {
    if ($m === 'online') {
        return "<span class=\"badge\" style=\"background:var(--emu-info);font-size:0.72rem;\">"
             . "<i class=\"bi bi-phone me-1\" aria-hidden=\"true\"></i>Online (Demo)</span>";
    }
    return "<span class=\"badge bg-secondary\" style=\"font-size:0.72rem;\">"
         . "<i class=\"bi bi-cash-coin me-1\" aria-hidden=\"true\"></i>Cash</span>";
};

// Page URL builder
$pageUrl = function(int $p) use ($appUrl, $filters): string {
    $q = [];
    if (!empty($filters['search']))   $q['search']    = $filters['search'];
    if (!empty($filters['method']))   $q['method']    = $filters['method'];
    if (!empty($filters['status']))   $q['status']    = $filters['status'];
    if (!empty($filters['dateFrom'])) $q['date_from'] = $filters['dateFrom'];
    if (!empty($filters['dateTo']))   $q['date_to']   = $filters['dateTo'];
    $q['page'] = $p;
    return $appUrl . '/admin/payments?' . http_build_query($q);
};

$hasFilters = !empty($filters['search'])   || !empty($filters['method'])
           || !empty($filters['status'])   || !empty($filters['dateFrom'])
           || !empty($filters['dateTo']);
?>

<!-- Page header -->
<div class="b2-page-header mt-1 mb-3">
    <div class="b2-page-header-left">
        <div class="b2-page-header-accent"></div>
        <h1>Payment History
            <?php if ($totalRows > 0): ?>
            <span class="badge bg-secondary ms-2" style="font-size:0.75rem;vertical-align:middle;">
                <?= number_format($totalRows) ?> record<?= $totalRows !== 1 ? 's' : '' ?>
            </span>
            <?php endif; ?>
        </h1>
        <p class="b2-page-sub">All cash and simulated online payment records.</p>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="<?= $e($appUrl) ?>/admin/payments" class="mb-3" id="paymentFilterForm">
    <div class="row g-2 mb-2">

        <!-- Search -->
        <div class="col-12 col-md-5 col-lg-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white">
                    <i class="bi bi-search text-muted" aria-hidden="true"></i>
                </span>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Transaction ref or request ref…"
                       value="<?= $e($filters['search'] ?? '') ?>"
                       aria-label="Search by reference">
            </div>
        </div>

        <!-- Method -->
        <div class="col-6 col-md-2 col-lg-2">
            <select name="method" class="form-select form-select-sm" aria-label="Filter by method">
                <option value="">All Methods</option>
                <option value="cash"   <?= ($filters['method'] ?? '') === 'cash'   ? 'selected' : '' ?>>Cash</option>
                <option value="online" <?= ($filters['method'] ?? '') === 'online' ? 'selected' : '' ?>>Online (Demo)</option>
            </select>
        </div>

        <!-- Status -->
        <div class="col-6 col-md-2 col-lg-2">
            <select name="status" class="form-select form-select-sm" aria-label="Filter by status">
                <option value="">All Statuses</option>
                <option value="pending"        <?= ($filters['status'] ?? '') === 'pending'        ? 'selected' : '' ?>>Pending</option>
                <option value="paid"           <?= ($filters['status'] ?? '') === 'paid'           ? 'selected' : '' ?>>Paid</option>
                <option value="online_pending" <?= ($filters['status'] ?? '') === 'online_pending' ? 'selected' : '' ?>>Online Pending</option>
            </select>
        </div>

        <!-- Date From -->
        <div class="col-6 col-md-2 col-lg-2">
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="<?= $e($filters['dateFrom'] ?? '') ?>"
                   aria-label="Date from" title="From date">
        </div>

        <!-- Date To -->
        <div class="col-6 col-md-2 col-lg-2">
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="<?= $e($filters['dateTo'] ?? '') ?>"
                   aria-label="Date to" title="To date">
        </div>

        <!-- Submit / Clear -->
        <div class="col-12 col-md-auto">
            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
                <?php if ($hasFilters): ?>
                <a href="<?= $e($appUrl) ?>/admin/payments"
                   class="btn btn-sm btn-outline-danger" aria-label="Clear filters">
                    <i class="bi bi-x" aria-hidden="true"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div id="admin-payments-table-wrap">
            <table class="table table-hover mb-0" aria-label="Payment history">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Transaction Ref</th>
                        <th scope="col">Request Ref</th>
                        <th scope="col">Resident</th>
                        <th scope="col">Document Type</th>
                        <th scope="col">Method</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date</th>
                        <th scope="col">Confirmed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="10" class="p-0">
                            <div class="empty-state">
                                <i class="bi bi-credit-card" aria-hidden="true"></i>
                                <h5>No payment records found</h5>
                                <p>
                                    <?php if ($hasFilters): ?>
                                    No payments match the current filter.
                                    <a href="<?= $e($appUrl) ?>/admin/payments">View all payments</a>
                                    <?php else: ?>
                                    No payment records have been created yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                    <tr>
                        <!-- ID -->
                        <td class="text-muted-sm"><?= (int)$row['payment_id'] ?></td>

                        <!-- Transaction Ref -->
                        <td>
                            <?php if (!empty($row['transaction_ref'])): ?>
                            <code class="text-reference" style="font-size:0.78rem;">
                                <?= $e($row['transaction_ref']) ?>
                            </code>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Request Ref -->
                        <td>
                            <a href="<?= $e($appUrl) ?>/admin/requests/<?= (int)($row['request_id'] ?? 0) ?>"
                               class="text-reference text-decoration-none"
                               style="font-size:0.875rem;">
                                <?= $e($row['reference_number']) ?>
                            </a>
                        </td>

                        <!-- Resident Name -->
                        <td style="font-size:0.875rem;">
                            <?= $e($row['resident_first'] . ' ' . $row['resident_last']) ?>
                        </td>

                        <!-- Document Type -->
                        <td style="font-size:0.875rem;">
                            <?= $e($row['document_type_name']) ?>
                        </td>

                        <!-- Method -->
                        <td><?= $methodBadge($row['payment_method']) ?></td>

                        <!-- Amount -->
                        <td style="font-size:0.875rem;white-space:nowrap;">
                            <strong>&#8369;<?= $e(number_format((float)$row['amount'], 2)) ?></strong>
                        </td>

                        <!-- Status -->
                        <td><?= $statusBadge($row['payment_status']) ?></td>

                        <!-- Date -->
                        <td class="text-muted-sm" style="white-space:nowrap;">
                            <?= !empty($row['payment_date'])
                                ? $e(date('M j, Y', strtotime($row['payment_date'])))
                                : '—' ?>
                        </td>

                        <!-- Confirmed By -->
                        <td class="text-muted-sm" style="font-size:0.875rem;">
                            <?php if (!empty($row['confirmed_first'])): ?>
                                <?= $e($row['confirmed_first'] . ' ' . $row['confirmed_last']) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (!empty($rows)): ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="text-muted-sm">
                <?php if ($totalRows > 0): ?>
                Showing <?= number_format(min(($currentPage - 1) * $perPage + 1, $totalRows)) ?>–<?= number_format(min($currentPage * $perPage, $totalRows)) ?>
                of <?= number_format($totalRows) ?> record<?= $totalRows !== 1 ? 's' : '' ?>
                <?php else: ?>
                0 records
                <?php endif; ?>
            </span>
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Payment history pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $e($pageUrl($currentPage - 1)) ?>" aria-label="Previous">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </a>
                    </li>
                    <?php
                    $pStart = max(1, $currentPage - 2);
                    $pEnd   = min($totalPages, $currentPage + 2);
                    if ($pStart > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= $e($pageUrl(1)) ?>">1</a></li>
                    <?php if ($pStart > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($p = $pStart; $p <= $pEnd; $p++): ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>" <?= $p === $currentPage ? 'aria-current="page"' : '' ?>>
                        <a class="page-link" href="<?= $e($pageUrl($p)) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($pEnd < $totalPages): ?>
                    <?php if ($pEnd < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?= $e($pageUrl($totalPages)) ?>"><?= $totalPages ?></a></li>
                    <?php endif; ?>
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $e($pageUrl($currentPage + 1)) ?>" aria-label="Next">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /.card-body -->
</div><!-- /.card -->

<!-- Scoped styles for this page only -->
<style>
/* Payment History table — scoped to #admin-payments-table-wrap */
#admin-payments-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}
#admin-payments-table-wrap .table { min-width: 980px; }
#admin-payments-table-wrap .table td,
#admin-payments-table-wrap .table th {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}
#admin-payments-table-wrap .table td:nth-child(4),  /* Resident */
#admin-payments-table-wrap .table td:nth-child(5) { /* Doc Type */
    max-width: 160px;
}
#admin-payments-table-wrap .table-actions { min-width: 80px; }
</style>
