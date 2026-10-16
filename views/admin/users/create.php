<?php
/**
 * Admin Create Staff Account — Req 11.2–11.3
 * Variables: $csrfToken, $errors, $old
 */
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$csrfToken = $csrfToken ?? '';
$errors    = $errors    ?? [];
$old       = $old       ?? [];

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/admin/dashboard'],
    ['label' => 'Users',     'url' => $appUrl . '/admin/users'],
    ['label' => 'Create Staff Account'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-1">
    <h1 class="mb-0" style="font-size:1.5rem;font-weight:700;">Create Staff Account</h1>
    <a href="<?= $e($appUrl) ?>/admin/users" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to Users
    </a>
</div>

<?php if (!empty($errors['form'])): ?>
<div class="alert alert-danger d-flex gap-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
    <span><?= $e($errors['form']) ?></span>
</div>
<?php endif; ?>

<div class="card" style="max-width:600px;">
    <div class="card-header">
        <h2 class="mb-0" style="font-size:0.9375rem;">
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i> New Staff Member
        </h2>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= $e($appUrl) ?>/admin/users"
              data-loading="Creating account…" aria-label="Create staff account form">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">

            <div class="row g-3">
                <!-- First name -->
                <div class="col-12 col-sm-6">
                    <label for="first_name" class="form-label">
                        First Name <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="text" id="first_name" name="first_name"
                           class="form-control <?= !empty($errors['first_name']) ? 'is-invalid' : '' ?>"
                           value="<?= $e($old['first_name'] ?? '') ?>"
                           maxlength="100" required
                           aria-describedby="first_name_err">
                    <?php if (!empty($errors['first_name'])): ?>
                    <div id="first_name_err" class="invalid-feedback"><?= $e($errors['first_name']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Last name -->
                <div class="col-12 col-sm-6">
                    <label for="last_name" class="form-label">
                        Last Name <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="text" id="last_name" name="last_name"
                           class="form-control <?= !empty($errors['last_name']) ? 'is-invalid' : '' ?>"
                           value="<?= $e($old['last_name'] ?? '') ?>"
                           maxlength="100" required
                           aria-describedby="last_name_err">
                    <?php if (!empty($errors['last_name'])): ?>
                    <div id="last_name_err" class="invalid-feedback"><?= $e($errors['last_name']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Email -->
                <div class="col-12">
                    <label for="email" class="form-label">
                        Email Address <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="email" id="email" name="email"
                           class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= $e($old['email'] ?? '') ?>"
                           maxlength="255" required
                           aria-describedby="email_err">
                    <?php if (!empty($errors['email'])): ?>
                    <div id="email_err" class="invalid-feedback"><?= $e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="col-12 col-sm-6">
                    <label for="password" class="form-label">
                        Password <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="password" id="password" name="password"
                           class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                           required autocomplete="new-password"
                           aria-describedby="password_err password_help">
                    <div id="password_help" class="form-text">
                        Min 8 chars, upper + lower + number.
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                    <div id="password_err" class="invalid-feedback"><?= $e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Confirm password -->
                <div class="col-12 col-sm-6">
                    <label for="password_confirmation" class="form-label">
                        Confirm Password <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control <?= !empty($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                           required autocomplete="new-password"
                           aria-describedby="pw_confirm_err">
                    <?php if (!empty($errors['password_confirmation'])): ?>
                    <div id="pw_confirm_err" class="invalid-feedback"><?= $e($errors['password_confirmation']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i> Create Staff Account
                </button>
                <a href="<?= $e($appUrl) ?>/admin/users" class="btn btn-outline-secondary">Cancel</a>
            </div>

            <p class="form-text mt-3 mb-0">
                <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                The account will be assigned the <strong>Staff</strong> role automatically.
                The password is securely hashed and never stored in plaintext.
            </p>
        </form>
    </div>
</div>
