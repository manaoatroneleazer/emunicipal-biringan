<?php
/**
 * Admin Audit Log Viewer — Req 12.4, 12.6, 13.3
 * Variables: $filters, $logs, $totalRows, $totalPages, $currentPage,
 *            $perPage, $userList, $validActions
 */
$appUrl       = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e            = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$filters      = $filters      ?? [];
$logs         = $logs         ?? [];
$totalRows    = (int)($totalRows    ?? 0);
$totalPages   = (int)($totalPages   ?? 1);
$currentPage  = (int)($currentPage  ?? 1);
$perPage      = (int)($perPage      ?? 20);
$userList     = $userList     ?? [];
$validActions = $validActions ?? [];

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/admin/dashboard'],
    ['label' => 'Audit Logs'],
];

/**
 * Build a query string that preserves active filters when changing page.
 */
function auditPageUrl(string $appUrl, array $filters, int $page): string {
    $q = [];
    if (!empty($filters['user_id']))   $q['user_id']   = $filters['user_id'];
    if (!empty($filters['action']))    $q['action']     = $filters['action'];
    if (!empty($filters['date_from'])) $q['date_from']  = $filters['date_from'];
    if (!empty($filters['date_to']))   $q['date_to']    = $filters['date_to'];
    $q['page'] = $page;
    return $appUrl . '/admin/audit-logs?' . http_build_query($q);
}

// Human-readable labels for action constants
$actionLabels = [
    'user_login'            => 'Login',
    'user_logout'           => 'Logout',
    'login_failed'          => 'Failed Login',
    'account_locked'        => 'Account Locked',
    'account_created'       => 'Account Created',
    'account_deactivated'   => 'Account Deactivated',
    'account_reactivated'   => 'Account Reactivated',
    'password_changed'      => 'Password Changed',
    'document_type_created' => 'Document Type Created',
    'document_type_modified'=> 'Document Type Modified',
    'requirement_created'   => 'Requirement Created',
    'requirement_modified'  => 'Requirement Modified',
    'request_submitted'     => 'Request Submitted',
    'request_status_changed'=> 'Status Changed',
    'file_uploaded'         => 'File Uploaded',
    'file_downloaded'       => 'File Downloaded',
    'report_generated'      => 'Report Generated',
    'setting_changed'       => 'Setting Changed',
];

// Module → icon mapping
$moduleIcons = [
    'auth'      => 'bi-shield-lock',
    'users'     => 'bi-person',
    'requests'  => 'bi-inbox',
    'documents' => 'bi-file-text',
    'files'     => 'bi-paperclip',
    'reports'   => 'bi-bar-chart',
    'settings'  => 'bi-gear',
];

$hasFilters = (!empty($filters['user_id']) && $filters['user_id'] > 0)
           || !empty($filters['action'])
           || !empty($filters['date_from'])
           || !empty($filters['date_to']);
?>

<div class="b2-page-header mt-1">
    <div class="b2-page-header-left">
        <div class="b2-page-header-accent"></div>
        <h1>Audit Logs
            <?php if ($totalRows > 0): ?>
            <span class="badge bg-secondary ms-2" style="font-size:0.75rem;vertical-align:middle;">
                <?= number_format($totalRows) ?> record<?= $totalRows !== 1 ? 's' : '' ?>
            </span>
            <?php endif; ?>
        </h1>
        <p class="b2-page-sub">System activity log — all actions are recorded for accountability.</p>
    </div>
</div>

<!-- Filter form (Req 12.4) -->
<div class="card mb-4">
    <div class="card-header">
        <h2 class="mb-0" style="font-size:0.9375rem;">
            <i class="bi bi-funnel me-1" aria-hidden="true"></i> Filter Audit Logs
        </h2>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= $e($appUrl) ?>/admin/audit-logs" id="auditFilterForm">
            <div class="row g-3 align-items-end">

                <!-- Actor / User -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="user_id" class="form-label">Actor</label>
                    <select id="user_id" name="user_id" class="form-select form-select-sm"
                            aria-label="Filter by user">
                        <option value="0">All Users</option>
                        <?php foreach ($userList as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"
                            <?= ((int)($filters['user_id'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>>
                            <?= $e($u['first_name'] . ' ' . $u['last_name']) ?>
                            (<?= $e($u['role_name']) ?>)
                        </option>
                        <?php endforeach; ?>
                        <option value="-1"
                            <?= ((int)($filters['user_id'] ?? 0) === -1) ? 'selected' : '' ?>>
                            System (no user)
                        </option>
                    </select>
                </div>

                <!-- Action -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="action" class="form-label">Action</label>
                    <select id="action" name="action" class="form-select form-select-sm"
                            aria-label="Filter by action type">
                        <option value="">All Actions</option>
                        <?php foreach ($validActions as $act): ?>
                        <option value="<?= $e($act) ?>"
                            <?= (($filters['action'] ?? '') === $act) ? 'selected' : '' ?>>
                            <?= $e($actionLabels[$act] ?? ucwords(str_replace('_', ' ', $act))) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date From -->
                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" id="date_from" name="date_from"
                           class="form-control form-control-sm"
                           value="<?= $e($filters['date_from'] ?? '') ?>"
                           aria-label="Filter from date">
                </div>

                <!-- Date To -->
                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" id="date_to" name="date_to"
                           class="form-control form-control-sm"
                           value="<?= $e($filters['date_to'] ?? '') ?>"
                           aria-label="Filter to date">
                </div>

                <!-- Buttons -->
                <div class="col-12 col-lg-2">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-search me-1" aria-hidden="true"></i> Filter
                        </button>
                        <?php if ($hasFilters): ?>
                        <a href="<?= $e($appUrl) ?>/admin/audit-logs"
                           class="btn btn-sm btn-outline-secondary"
                           aria-label="Clear filters">
                            <i class="bi bi-x" aria-hidden="true"></i> Clear
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- Audit log table (Req 12.6) -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" aria-label="Audit log entries">
                <thead>
                    <tr>
                        <th scope="col" style="width:160px;">Timestamp</th>
                        <th scope="col">Actor</th>
                        <th scope="col">Action</th>
                        <th scope="col">Module / Record</th>
                        <th scope="col" style="width:120px;">IP Address</th>
                        <th scope="col">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="p-0">
                            <div class="empty-state py-5">
                                <i class="bi bi-journal-text" aria-hidden="true"></i>
                                <h5>No audit entries found</h5>
                                <p>
                                    <?php if ($hasFilters): ?>
                                    No entries match the selected filters.
                                    <a href="<?= $e($appUrl) ?>/admin/audit-logs">Clear filters</a>
                                    <?php else: ?>
                                    No audit log entries have been recorded yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <!-- Timestamp -->
                        <td class="text-muted-sm" style="white-space:nowrap;font-size:0.78rem;">
                            <?= $e(date('M j, Y', strtotime($log['created_at']))) ?><br>
                            <span style="color:var(--emu-text-muted);">
                                <?= $e(date('H:i:s', strtotime($log['created_at']))) ?>
                            </span>
                        </td>

                        <!-- Actor (Req 12.6 — acting user) -->
                        <td style="font-size:0.8125rem;">
                            <?php if (!empty($log['first_name'])): ?>
                            <span class="fw-500">
                                <?= $e($log['first_name'] . ' ' . $log['last_name']) ?>
                            </span><br>
                            <span class="text-muted-sm"><?= $e(ucfirst($log['user_role'] ?? '')) ?></span>
                            <?php elseif ($log['user_id'] === null): ?>
                            <span class="text-muted-sm"><em>System</em></span>
                            <?php else: ?>
                            <span class="text-muted-sm">User #<?= (int)$log['user_id'] ?></span><br>
                            <span class="text-muted-sm"><?= $e(ucfirst($log['user_role'] ?? '')) ?></span>
                            <?php endif; ?>
                        </td>

                        <!-- Action (Req 12.6 — action type) -->
                        <td style="font-size:0.8125rem;">
                            <?php
                            $act = $log['action'] ?? '';
                            $icon = match(true) {
                                str_starts_with($act, 'user_login') || str_starts_with($act, 'user_logout')
                                    => 'bi-box-arrow-in-right',
                                str_starts_with($act, 'login_failed') || str_starts_with($act, 'account_locked')
                                    => 'bi-shield-exclamation text-danger',
                                str_starts_with($act, 'account_')
                                    => 'bi-person-check',
                                str_starts_with($act, 'password_')
                                    => 'bi-key',
                                str_starts_with($act, 'document_type')
                                    => 'bi-file-text',
                                str_starts_with($act, 'requirement')
                                    => 'bi-list-check',
                                str_starts_with($act, 'request_submitted')
                                    => 'bi-send',
                                str_starts_with($act, 'request_status')
                                    => 'bi-arrow-repeat',
                                str_starts_with($act, 'file_')
                                    => 'bi-paperclip',
                                str_starts_with($act, 'report_')
                                    => 'bi-bar-chart',
                                str_starts_with($act, 'setting_')
                                    => 'bi-gear',
                                default => 'bi-dot',
                            };
                            $label = $actionLabels[$act] ?? ucwords(str_replace('_', ' ', $act));
                            ?>
                            <i class="bi <?= $e($icon) ?>" aria-hidden="true"></i>
                            <?= $e($label) ?>
                        </td>

                        <!-- Module / Record (Req 12.6 — affected record identifier) -->
                        <td class="text-muted-sm" style="font-size:0.8rem;">
                            <?php if (!empty($log['module'])): ?>
                            <span class="badge bg-light text-dark border" style="font-size:0.7rem;">
                                <?= $e(ucfirst($log['module'])) ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($log['record_table'])): ?>
                            <br><span><?= $e($log['record_table']) ?></span>
                            <?php if (!empty($log['record_id'])): ?>
                            <span class="text-muted"> #<?= (int)$log['record_id'] ?></span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>

                        <!-- IP Address (Req 12.6) -->
                        <td class="text-muted-sm" style="font-size:0.8rem;font-family:monospace;">
                            <?= $log['ip_address'] ? $e($log['ip_address']) : '—' ?>
                        </td>

                        <!-- Description -->
                        <td style="font-size:0.8rem;max-width:320px;">
                            <?php if (!empty($log['description'])): ?>
                            <span class="text-truncate d-block"
                                  style="max-width:300px;"
                                  title="<?= $e($log['description']) ?>">
                                <?= $e($log['description']) ?>
                            </span>
                            <?php endif; ?>
                            <?php
                            // Show new_value snippet if present and non-trivial
                            $newVal = $log['new_value'] ?? null;
                            if ($newVal && $newVal !== 'null' && strlen($newVal) < 200): ?>
                            <small class="text-muted d-block mt-1" style="font-family:monospace;font-size:0.72rem;">
                                <?= $e($newVal) ?>
                            </small>
                            <?php endif; ?>
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
                Showing <?= number_format(min(($currentPage - 1) * $perPage + 1, $totalRows)) ?>–<?= number_format(min($currentPage * $perPage, $totalRows)) ?>
                of <?= number_format($totalRows) ?> entries
            </span>
            <nav aria-label="Audit log pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="<?= $e(auditPageUrl($appUrl, $filters, $currentPage - 1)) ?>"
                           aria-label="Previous page">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </a>
                    </li>
                    <?php
                    $start = max(1, $currentPage - 3);
                    $end   = min($totalPages, $currentPage + 3);
                    if ($start > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $e(auditPageUrl($appUrl, $filters, 1)) ?>">1</a>
                    </li>
                    <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>"
                        <?= $p === $currentPage ? 'aria-current="page"' : '' ?>>
                        <a class="page-link" href="<?= $e(auditPageUrl($appUrl, $filters, $p)) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $e(auditPageUrl($appUrl, $filters, $totalPages)) ?>"><?= $totalPages ?></a>
                    </li>
                    <?php endif; ?>
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="<?= $e(auditPageUrl($appUrl, $filters, $currentPage + 1)) ?>"
                           aria-label="Next page">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php elseif (!empty($logs)): ?>
        <div class="p-3 border-top text-muted-sm">
            <?= number_format($totalRows) ?> entr<?= $totalRows !== 1 ? 'ies' : 'y' ?>
        </div>
        <?php endif; ?>

    </div><!-- /.card-body -->
</div><!-- /.card -->