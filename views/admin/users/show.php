<?php
/**
 * Admin User Detail — Req 11
 * Variables: $user (array), $csrfToken
 */
$appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$u      = $user ?? [];
$roleCfg = [
    'admin'    => ['label' => 'Administrator', 'bg' => '#c8922a', 'color' => '#1a202c'],
    'staff'    => ['label' => 'Staff',         'bg' => '#1a3a5c', 'color' => '#fff'],
    'resident' => ['label' => 'Resident',      'bg' => '#4b5563', 'color' => '#fff'],
];
$role = $roleCfg[$u['role_name'] ?? ''] ?? ['label' => ucfirst($u['role_name'] ?? '?'), 'bg' => '#9ca3af', 'color' => '#fff'];
?>

<nav aria-label="breadcrumb" class="mb-1">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $e($appUrl) ?>/admin/dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= $e($appUrl) ?>/admin/users">Users</a></li>
        <li class="breadcrumb-item active" aria-current="page">
            <?= $e(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?>
        </li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-4 mt-1 flex-wrap gap-2">
    <h1 class="mb-0" style="font-size:1.5rem;font-weight:700;">
        <?= $e(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?>
    </h1>
    <a href="<?= $e($appUrl) ?>/admin/users" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to Users
    </a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">Account Information</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:0.9rem;">
                    <dt class="col-sm-4 text-label">Full Name</dt>
                    <dd class="col-sm-8"><?= $e(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></dd>

                    <dt class="col-sm-4 text-label">Email</dt>
                    <dd class="col-sm-8"><?= $e($u['email'] ?? '') ?></dd>

                    <dt class="col-sm-4 text-label">Role</dt>
                    <dd class="col-sm-8">
                        <span class="badge" style="background:<?= $e($role['bg']) ?>;color:<?= $e($role['color']) ?>;">
                            <?= $e($role['label']) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-4 text-label">Status</dt>
                    <dd class="col-sm-8">
                        <?php if ($u['is_active'] ?? false): ?>
                        <span class="status-badge status-approved">
                            <i class="bi bi-circle-fill" aria-hidden="true"></i> Active
                        </span>
                        <?php else: ?>
                        <span class="status-badge status-cancelled">
                            <i class="bi bi-circle" aria-hidden="true"></i> Inactive
                        </span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4 text-label">Contact Number</dt>
                    <dd class="col-sm-8"><?= !empty($u['contact_number']) ? $e($u['contact_number']) : '—' ?></dd>

                    <dt class="col-sm-4 text-label">Address</dt>
                    <dd class="col-sm-8"><?= !empty($u['address']) ? $e($u['address']) : '—' ?></dd>

                    <dt class="col-sm-4 text-label">Registered</dt>
                    <dd class="col-sm-8">
                        <?= !empty($u['created_at'])
                            ? $e(date('F j, Y \a\t g:i A', strtotime($u['created_at'])))
                            : '—' ?>
                    </dd>

                    <dt class="col-sm-4 text-label">Last Login</dt>
                    <dd class="col-sm-8">
                        <?= !empty($u['last_login_at'])
                            ? $e(date('F j, Y \a\t g:i A', strtotime($u['last_login_at'])))
                            : 'Never' ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <?php
    $isSelf    = (int)($u['id'] ?? 0) === (int)\Emunicipal\Middleware\AuthMiddleware::userId();
    $isRootAdmin = ($u['role_name'] ?? '') === 'admin' && (int)($u['id'] ?? 0) === 1;
    $canToggle = !$isSelf && !$isRootAdmin;
    ?>
    <?php if ($canToggle): ?>
    <div class="col-12 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">Account Actions</h2>
            </div>
            <div class="card-body">
                <p style="font-size:0.875rem;" class="text-muted mb-3">
                    <?php if ($u['is_active'] ?? false): ?>
                    Deactivating this account prevents the user from logging in. All historical data is preserved.
                    <?php else: ?>
                    Reactivating this account allows the user to log in again.
                    <?php endif; ?>
                </p>
                <button type="button"
                        class="btn w-100 <?= ($u['is_active'] ?? false) ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                        data-bs-toggle="modal"
                        data-bs-target="#userToggleModal"
                        aria-haspopup="dialog">
                    <?php if ($u['is_active'] ?? false): ?>
                    <i class="bi bi-person-dash me-1" aria-hidden="true"></i> Deactivate Account
                    <?php else: ?>
                    <i class="bi bi-person-check me-1" aria-hidden="true"></i> Reactivate Account
                    <?php endif; ?>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($canToggle): ?>
<?php
    $isActive    = (bool)($u['is_active'] ?? false);
    $modalTitle  = $isActive ? 'Deactivate Account?' : 'Reactivate Account?';
    $modalMsg    = $isActive
        ? 'Are you sure you want to deactivate this account?'
        : 'Are you sure you want to reactivate this account?';
    $modalNote   = $isActive
        ? 'The user will no longer be able to log in, but their historical data will be preserved.'
        : 'The user will be able to log in again with their existing credentials.';
    $modalBtnCls = $isActive ? 'btn-danger' : 'btn-success';
    $modalBtnLbl = $isActive ? 'Deactivate Account' : 'Reactivate Account';
?>
<!-- ================================================================
     User Toggle Confirmation Modal
     Follows the existing Bootstrap 5 modal pattern used in index.php.
     Native confirm() intentionally removed — this modal replaces it.
     ================================================================ -->
<div class="modal fade" id="userToggleModal" tabindex="-1"
     aria-labelledby="userToggleModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">

            <div class="modal-header" style="border-bottom:1px solid var(--emu-border);">
                <h5 class="modal-title" id="userToggleModalLabel" style="font-weight:700;">
                    <?php if ($isActive): ?>
                    <i class="bi bi-person-dash-fill me-2 text-danger" aria-hidden="true"></i>
                    <?php else: ?>
                    <i class="bi bi-person-check-fill me-2 text-success" aria-hidden="true"></i>
                    <?php endif; ?>
                    <?= $e($modalTitle) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close dialog"></button>
            </div>

            <div class="modal-body" style="padding:1.5rem;">
                <p style="margin-bottom:0.75rem;font-size:0.9375rem;">
                    <?= $e($modalMsg) ?>
                </p>
                <p class="text-muted mb-3" style="font-size:0.875rem;">
                    <?= $e($modalNote) ?>
                </p>
                <div style="background:var(--emu-bg-alt);border:1px solid var(--emu-border);border-radius:8px;padding:0.75rem 1rem;">
                    <span style="font-weight:600;font-size:0.9rem;">
                        <?= $e(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?>
                    </span>
                    <span class="role-badge role-badge-<?= $e($u['role_name'] ?? '') ?> ms-2">
                        <?= $e(ucfirst($u['role_name'] ?? '')) ?>
                    </span>
                </div>
            </div>

            <div class="modal-footer" style="border-top:1px solid var(--emu-border);gap:0.5rem;">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">
                    Cancel
                </button>
                <form method="POST"
                      action="<?= $e($appUrl) ?>/admin/users/<?= (int)($u['id'] ?? 0) ?>/toggle"
                      class="d-inline" data-loading="Saving…">
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken ?? '') ?>">
                    <button type="submit" class="btn <?= $e($modalBtnCls) ?>">
                        <?php if ($isActive): ?>
                        <i class="bi bi-person-dash me-1" aria-hidden="true"></i>
                        <?php else: ?>
                        <i class="bi bi-person-check me-1" aria-hidden="true"></i>
                        <?php endif; ?>
                        <?= $e($modalBtnLbl) ?>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
<?php endif; ?>
