<?php
/**
 * Shared profile edit partial.
 * Variables: $user, $csrfToken, $errors, $pwErrors, $formAction, $passwordAction, $isResident
 */
$e      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$inv    = fn(string $k) => !empty($errors[$k]) ? ' is-invalid' : '';
$pinv   = fn(string $k) => !empty($pwErrors[$k]) ? ' is-invalid' : '';
$isRes  = $isResident ?? false;
?>

<!-- Profile Update Form -->
<div class="card mb-4">
    <div class="b2-card-header" style="border-radius:10px 10px 0 0;">
        <div class="d-flex align-items-center gap-2">
            <div class="b2-card-header-icon"><i class="bi bi-person" aria-hidden="true"></i></div>
            <h2>Profile Information</h2>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($errors['form'])): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
            <?= $e($errors['form']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= $e($formAction) ?>">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">

            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-6">
                    <label for="first_name" class="form-label">First Name <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="text" id="first_name" name="first_name"
                           class="form-control<?= $inv('first_name') ?>"
                           value="<?= $e($user['first_name'] ?? '') ?>"
                           maxlength="100" required>
                    <?php if (!empty($errors['first_name'])): ?>
                    <div class="invalid-feedback" role="alert"><?= $e($errors['first_name']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-sm-6">
                    <label for="last_name" class="form-label">Last Name <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="text" id="last_name" name="last_name"
                           class="form-control<?= $inv('last_name') ?>"
                           value="<?= $e($user['last_name'] ?? '') ?>"
                           maxlength="100" required>
                    <?php if (!empty($errors['last_name'])): ?>
                    <div class="invalid-feedback" role="alert"><?= $e($errors['last_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isRes): ?>
            <div class="mb-3">
                <label for="contact_number" class="form-label">Contact Number <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="tel" id="contact_number" name="contact_number"
                       class="form-control<?= $inv('contact_number') ?>"
                       value="<?= $e($user['contact_number'] ?? '') ?>"
                       maxlength="20">
                <?php if (!empty($errors['contact_number'])): ?>
                <div class="invalid-feedback" role="alert"><?= $e($errors['contact_number']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Home Address <span class="text-danger" aria-hidden="true">*</span></label>
                <textarea id="address" name="address" rows="2"
                          class="form-control<?= $inv('address') ?>"><?= $e($user['address'] ?? '') ?></textarea>
                <?php if (!empty($errors['address'])): ?>
                <div class="invalid-feedback" role="alert"><?= $e($errors['address']) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Email is read-only per Req 4.6 -->
            <div class="mb-4">
                <label for="email_display" class="form-label">Email Address</label>
                <input type="email" id="email_display"
                       class="form-control" style="background:#f8f9fa;"
                       value="<?= $e($user['email'] ?? '') ?>"
                       disabled aria-describedby="email-note">
                <div id="email-note" class="form-text">
                    Email cannot be changed through this page. Contact the Administrator.
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1" aria-hidden="true"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<!-- Change Password Form -->
<div class="card">
    <div class="b2-card-header" style="border-radius:10px 10px 0 0;">
        <div class="d-flex align-items-center gap-2">
            <div class="b2-card-header-icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></div>
            <h2>Change Password</h2>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($pwErrors['form'])): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
            <?= $e($pwErrors['form']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= $e($passwordAction) ?>">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">

            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="password" id="current_password" name="current_password"
                       class="form-control<?= $pinv('current_password') ?>"
                       autocomplete="current-password" required>
                <?php if (!empty($pwErrors['current_password'])): ?>
                <div class="invalid-feedback" role="alert"><?= $e($pwErrors['current_password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label">New Password <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="password" id="new_password" name="new_password"
                       class="form-control<?= $pinv('new_password') ?>"
                       autocomplete="new-password" required
                       aria-describedby="new-pw-help">
                <div id="new-pw-help" class="form-text">
                    At least 8 characters with 1 uppercase, 1 lowercase, and 1 number.
                </div>
                <?php if (!empty($pwErrors['new_password'])): ?>
                <div class="invalid-feedback" role="alert"><?= $e($pwErrors['new_password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label for="new_password_confirmation" class="form-label">Confirm New Password <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                       class="form-control<?= $pinv('new_password_confirmation') ?>"
                       autocomplete="new-password" required>
                <?php if (!empty($pwErrors['new_password_confirmation'])): ?>
                <div class="invalid-feedback" role="alert"><?= $e($pwErrors['new_password_confirmation']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-shield-lock me-1" aria-hidden="true"></i> Update Password
            </button>
        </form>
    </div>
</div>
