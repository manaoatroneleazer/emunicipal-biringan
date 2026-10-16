<?php
/**
 * Admin Users List — Req 11.1, 11.8, 12.5
 * Variables: $users, $search, $roleFilter, $totalUsers,
 *            $totalPages, $currentPage, $perPage, $csrfToken
 */
$appUrl      = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e           = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$csrfToken   = $csrfToken   ?? \Emunicipal\Helpers\SecurityHelper::getCsrfToken();
$users       = $users       ?? [];
$search      = $search      ?? '';
$roleFilter  = $roleFilter  ?? '';
$totalUsers  = (int)($totalUsers  ?? 0);
$totalPages  = (int)($totalPages  ?? 1);
$currentPage = (int)($currentPage ?? 1);
$perPage     = (int)($perPage     ?? 20);

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/admin/dashboard'],
    ['label' => 'Users'],
];

function userPageUrl(string $appUrl, string $search, string $roleFilter, int $page): string {
    $q = [];
    if ($search !== '')     $q['search'] = $search;
    if ($roleFilter !== '') $q['role']   = $roleFilter;
    $q['page'] = $page;
    return $appUrl . '/admin/users?' . http_build_query($q);
}

$hasFilters = $search !== '' || $roleFilter !== '';
?>

<!-- Header -->
<div class="b2-page-header mt-1">
    <div class="b2-page-header-left">
        <div class="b2-page-header-accent"></div>
        <h1>User Management
            <?php if ($totalUsers > 0): ?>
            <span class="badge bg-secondary ms-2" style="font-size:0.75rem;vertical-align:middle;"><?= number_format($totalUsers) ?></span>
            <?php endif; ?>
        </h1>
        <p class="b2-page-sub">Manage resident and staff accounts.</p>
    </div>
    <a href="<?= $e($appUrl) ?>/admin/users/create" class="btn btn-primary">
        <i class="bi bi-person-plus me-1" aria-hidden="true"></i> Create Staff Account
    </a>
</div>
<!-- Search / filter form (Req 11.8) -->
<form method="GET" action="<?= $e($appUrl) ?>/admin/users" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-sm-6 col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white">
                    <i class="bi bi-search text-muted" aria-hidden="true"></i>
                </span>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by name or email…"
                       value="<?= $e($search) ?>"
                       aria-label="Search users">
            </div>
        </div>
        <div class="col-6 col-sm-3 col-md-2">
            <select name="role" class="form-select form-select-sm" aria-label="Filter by role">
                <option value="">All Roles</option>
                <option value="resident" <?= $roleFilter === 'resident' ? 'selected' : '' ?>>Resident</option>
                <option value="staff"    <?= $roleFilter === 'staff'    ? 'selected' : '' ?>>Staff</option>
                <option value="admin"    <?= $roleFilter === 'admin'    ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div class="col-6 col-sm-auto">
            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
                <?php if ($hasFilters): ?>
                <a href="<?= $e($appUrl) ?>/admin/users" class="btn btn-sm btn-outline-danger"
                   aria-label="Clear search filters">
                    <i class="bi bi-x" aria-hidden="true"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<!-- User table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive" id="admin-users-table-wrap">
            <table class="table table-hover mb-0" aria-label="User accounts">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col">Registered</th>
                        <th scope="col">Last Login</th>
                        <th scope="col" class="table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="p-0">
                            <div class="empty-state">
                                <i class="bi bi-people" aria-hidden="true"></i>
                                <h5>No users found</h5>
                                <p>
                                    <?php if ($hasFilters): ?>
                                    No users match the current search/filter.
                                    <a href="<?= $e($appUrl) ?>/admin/users">View all users</a>
                                    <?php else: ?>
                                    No user accounts exist yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $u):
                        $isAdmin      = $u['role_name'] === 'admin';
                        $isSelf       = (int)$u['id'] === (int)\Emunicipal\Middleware\AuthMiddleware::userId();
                        // Can toggle: not self, and not the last active admin
                        $canToggle    = !$isSelf;
                        $toggleModalId = 'toggleModal-' . (int)$u['id'];
                    ?>
                    <tr>
                        <td>
                            <strong><?= $e($u['first_name'] . ' ' . $u['last_name']) ?></strong>
                            <?php if ($isSelf): ?>
                            <span class="badge bg-secondary ms-1" style="font-size:0.65rem;">You</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.875rem;"><?= $e($u['email']) ?></td>
                        <td>
                            <span class="role-badge role-badge-<?= $e($u['role_name']) ?>">
                                <?= $e(ucfirst($u['role_name'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['is_active']): ?>
                            <span class="account-badge-active">Active</span>
                            <?php else: ?>
                            <span class="account-badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted-sm">
                            <?= $e(date('M j, Y', strtotime($u['created_at']))) ?>
                        </td>
                        <td class="text-muted-sm">
                            <?= !empty($u['last_login_at'])
                                ? $e(date('M j, Y', strtotime($u['last_login_at'])))
                                : '—' ?>
                        </td>
                        <td class="table-actions">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="<?= $e($appUrl) ?>/admin/users/<?= (int)$u['id'] ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   aria-label="View <?= $e($u['first_name']) ?>">
                                    <i class="bi bi-eye" aria-hidden="true"></i> View
                                </a>
                                <?php if ($canToggle): ?>
                                <button type="button"
                                        class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#<?= $e($toggleModalId) ?>"
                                        aria-label="<?= $u['is_active'] ? 'Deactivate' : 'Reactivate' ?> <?= $e($u['first_name']) ?>">
                                    <?php if ($u['is_active']): ?>
                                    <i class="bi bi-person-dash" aria-hidden="true"></i> Deactivate
                                    <?php else: ?>
                                    <i class="bi bi-person-check" aria-hidden="true"></i> Reactivate
                                    <?php endif; ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination (Req 12.5) -->
        <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="text-muted-sm">
                Showing <?= number_format(min(($currentPage - 1) * $perPage + 1, $totalUsers)) ?>–<?= number_format(min($currentPage * $perPage, $totalUsers)) ?>
                of <?= number_format($totalUsers) ?> users
            </span>
            <nav aria-label="User list pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $e(userPageUrl($appUrl, $search, $roleFilter, $currentPage - 1)) ?>"
                           aria-label="Previous page">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </a>
                    </li>
                    <?php
                    $pStart = max(1, $currentPage - 2); $pEnd = min($totalPages, $currentPage + 2);
                    if ($pStart > 1): ?><li class="page-item"><a class="page-link" href="<?= $e(userPageUrl($appUrl,$search,$roleFilter,1)) ?>">1</a></li><?php if ($pStart > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; endif; ?>
                    <?php for ($p = $pStart; $p <= $pEnd; $p++): ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>" <?= $p === $currentPage ? 'aria-current="page"' : '' ?>>
                        <a class="page-link" href="<?= $e(userPageUrl($appUrl,$search,$roleFilter,$p)) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($pEnd < $totalPages): ?><?php if ($pEnd < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?><li class="page-item"><a class="page-link" href="<?= $e(userPageUrl($appUrl,$search,$roleFilter,$totalPages)) ?>"><?= $totalPages ?></a></li><?php endif; ?>
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $e(userPageUrl($appUrl, $search, $roleFilter, $currentPage + 1)) ?>"
                           aria-label="Next page">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php elseif (!empty($users)): ?>
        <div class="p-3 border-top text-muted-sm">
            <?= number_format($totalUsers) ?> user<?= $totalUsers !== 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<p class="text-muted-sm mt-3">
    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
    Accounts cannot be deleted. Deactivation prevents login while preserving all historical data.
</p>

<!-- Confirmation modals for activate/deactivate -->
<?php foreach ($users as $u):
    $isSelf    = (int)$u['id'] === (int)\Emunicipal\Middleware\AuthMiddleware::userId();
    if ($isSelf) continue;
    $toggleModalId = 'toggleModal-' . (int)$u['id'];
    $actionLabel   = $u['is_active'] ? 'Deactivate' : 'Reactivate';
    $confirmMsg    = $u['is_active']
        ? 'Deactivate this account? The user will no longer be able to log in.'
        : 'Reactivate this account? The user will be able to log in again.';
    $btnCls = $u['is_active'] ? 'btn-danger' : 'btn-success';
?>
<div class="modal fade" id="<?= $e($toggleModalId) ?>" tabindex="-1"
     aria-labelledby="<?= $e($toggleModalId) ?>-label" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= $e($toggleModalId) ?>-label">
                    <?= $e($actionLabel) ?> Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close dialog"></button>
            </div>
            <div class="modal-body">
                <p><?= $e($confirmMsg) ?></p>
                <p class="mb-0">
                    <strong><?= $e($u['first_name'] . ' ' . $u['last_name']) ?></strong>
                    <span class="role-badge role-badge-<?= $e($u['role_name']) ?> ms-1">
                        <?= $e(ucfirst($u['role_name'])) ?>
                    </span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Cancel</button>
                <form method="POST"
                      action="<?= $e($appUrl) ?>/admin/users/<?= (int)$u['id'] ?>/toggle"
                      class="d-inline" data-loading="Saving…">
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                    <button type="submit" class="btn <?= $e($btnCls) ?>">
                        <?= $e($actionLabel) ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
