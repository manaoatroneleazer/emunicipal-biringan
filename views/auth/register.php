<?php
/**
 * Register view — compact two-column layout
 * Layout: layouts/minimal
 * Variables: $csrfToken, $errors (array), $old (array of previous values)
 */
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$errors    = $errors ?? [];
$old       = $old ?? [];
$csrfToken = $csrfToken ?? '';

$o   = fn(string $k) => htmlspecialchars($old[$k] ?? '', ENT_QUOTES, 'UTF-8');
$inv = fn(string $k) => !empty($errors[$k]) ? ' is-invalid' : '';
$err = fn(string $k, string $id) => !empty($errors[$k])
    ? '<div id="' . $id . '" class="invalid-feedback" role="alert">'
      . htmlspecialchars($errors[$k], ENT_QUOTES, 'UTF-8') . '</div>'
    : '';
?>

<h1 style="font-size:1.375rem;font-weight:700;color:var(--emu-text-primary);margin-bottom:0.2rem;">
    Create your account
</h1>
<p style="font-size:0.8125rem;color:var(--emu-text-muted);margin-bottom:1rem;">
    Fill in your information to get started.
</p>

<!-- Form-level error container -->
<?php if (!empty($errors['form'])): ?>
<div id="registerErrorContainer" class="alert alert-danger d-flex align-items-start gap-2 py-2 mb-3"
     role="alert" aria-live="assertive">
    <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
    <span><?= htmlspecialchars($errors['form'], ENT_QUOTES, 'UTF-8') ?></span>
</div>
<?php elseif (!empty($errors)): ?>
<?php
    // Multi-field error summary — counts all per-field errors and names each one
    $fieldErrors = array_filter($errors, fn($k) => $k !== 'form', ARRAY_FILTER_USE_KEY);
    $errorCount  = count($fieldErrors);
?>
<div id="registerErrorContainer"
     class="alert alert-danger py-2 mb-3"
     role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
        <div>
            <strong>Please fix <?= $errorCount === 1 ? '1 error' : "{$errorCount} errors" ?> before continuing:</strong>
            <ul class="mb-0 ps-3 mt-1" style="font-size:0.875rem;">
                <?php foreach ($fieldErrors as $errMsg): ?>
                <li><?= htmlspecialchars($errMsg, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php else: ?>
<div id="registerErrorContainer" role="alert" aria-live="assertive"></div>
<?php endif; ?>

<form id="registerForm" method="POST" action="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>/register" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <!-- First Name / Last Name -->
    <div class="row g-2 mb-2">
        <div class="col-6">
            <label for="first_name" class="form-label mb-1" style="font-size:0.8125rem;">First Name <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="text" id="first_name" name="first_name"
                   class="form-control form-control-sm<?= $inv('first_name') ?>"
                   value="<?= $o('first_name') ?>" autocomplete="given-name" maxlength="100" required
                   aria-describedby="<?= !empty($errors['first_name']) ? 'fn-err' : '' ?>">
            <?= $err('first_name', 'fn-err') ?>
        </div>
        <div class="col-6">
            <label for="last_name" class="form-label mb-1" style="font-size:0.8125rem;">Last Name <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="text" id="last_name" name="last_name"
                   class="form-control form-control-sm<?= $inv('last_name') ?>"
                   value="<?= $o('last_name') ?>" autocomplete="family-name" maxlength="100" required
                   aria-describedby="<?= !empty($errors['last_name']) ? 'ln-err' : '' ?>">
            <?= $err('last_name', 'ln-err') ?>
        </div>
    </div>

    <!-- Email + Contact Number -->
    <div class="row g-2 mb-2">
        <div class="col-12 col-sm-7">
            <label for="email" class="form-label mb-1" style="font-size:0.8125rem;">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
            <div class="input-group input-group-sm">
                <span class="input-group-text" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                <input type="email" id="email" name="email"
                       class="form-control<?= $inv('email') ?>"
                       value="<?= $o('email') ?>" autocomplete="email" maxlength="255" required
                       placeholder="email@example.com"
                       aria-describedby="<?= !empty($errors['email']) ? 'em-err' : '' ?>">
                <?= $err('email', 'em-err') ?>
            </div>
        </div>
        <div class="col-12 col-sm-5">
            <label for="contact_number" class="form-label mb-1" style="font-size:0.8125rem;">Contact No. <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="tel" id="contact_number" name="contact_number"
                   class="form-control form-control-sm<?= $inv('contact_number') ?>"
                   value="<?= $o('contact_number') ?>" autocomplete="tel" maxlength="20" required
                   placeholder="09XXXXXXXXX"
                   aria-describedby="<?= !empty($errors['contact_number']) ? 'cn-err' : '' ?>">
            <?= $err('contact_number', 'cn-err') ?>
        </div>
    </div>

    <!-- Password + Confirm Password -->
    <div class="row g-2 mb-0">
        <div class="col-12 col-sm-6">
            <label for="password" class="form-label mb-1" style="font-size:0.8125rem;">Password <span class="text-danger" aria-hidden="true">*</span></label>
            <div class="input-group input-group-sm">
                <span class="input-group-text" aria-hidden="true"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password"
                       class="form-control<?= $inv('password') ?>"
                       autocomplete="new-password" required
                       aria-describedby="pwChecklist <?= !empty($errors['password']) ? 'pw-err' : '' ?>">
                <button class="btn btn-outline-secondary btn-sm" type="button"
                        data-pw-toggle="password" aria-label="Show password">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
                <?= $err('password', 'pw-err') ?>
            </div>
        </div>
        <div class="col-12 col-sm-6">
            <label for="password_confirmation" class="form-label mb-1" style="font-size:0.8125rem;">Confirm Password <span class="text-danger" aria-hidden="true">*</span></label>
            <div class="input-group input-group-sm">
                <span class="input-group-text" aria-hidden="true"><i class="bi bi-lock"></i></span>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control<?= $inv('password_confirmation') ?>"
                       autocomplete="new-password" required
                       aria-describedby="<?= !empty($errors['password_confirmation']) ? 'pc-err' : '' ?>">
                <button class="btn btn-outline-secondary btn-sm" type="button"
                        data-pw-toggle="password_confirmation" aria-label="Show confirm password">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
                <?= $err('password_confirmation', 'pc-err') ?>
            </div>
            <div id="pwMatchMsg" class="mt-1" style="font-size:0.75rem;" aria-live="polite"></div>
        </div>
    </div>

    <!-- Password checklist — matches Validator::passwordStrength() rules exactly -->
    <ul class="pw-checklist list-unstyled mt-1 mb-2 row g-0" id="pwChecklist" aria-live="polite">
        <li id="pwc-length"  class="pwc-item pwc-invalid col-12 col-sm-6"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span>At least 8 characters</span></li>
        <li id="pwc-upper"   class="pwc-item pwc-invalid col-12 col-sm-6"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span>One uppercase letter</span></li>
        <li id="pwc-lower"   class="pwc-item pwc-invalid col-12 col-sm-6"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span>One lowercase letter</span></li>
        <li id="pwc-number"  class="pwc-item pwc-invalid col-12 col-sm-6"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span>One number</span></li>
    </ul>

    <!-- Address: House Number / Street -->
    <div class="row g-2 mb-2">
        <div class="col-5">
            <label for="house_number" class="form-label mb-1" style="font-size:0.8125rem;">House No. <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="text" id="house_number" name="house_number"
                   class="form-control form-control-sm<?= $inv('house_number') ?>"
                   value="<?= $o('house_number') ?>" maxlength="50" required
                   placeholder="e.g. 123"
                   aria-describedby="<?= !empty($errors['house_number']) ? 'hn-err' : '' ?>">
            <?= $err('house_number', 'hn-err') ?>
        </div>
        <div class="col-7">
            <label for="street" class="form-label mb-1" style="font-size:0.8125rem;">Street <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="text" id="street" name="street"
                   class="form-control form-control-sm<?= $inv('street') ?>"
                   value="<?= $o('street') ?>" maxlength="150" required
                   placeholder="e.g. Mabini Street"
                   aria-describedby="<?= !empty($errors['street']) ? 'st-err' : '' ?>">
            <?= $err('street', 'st-err') ?>
        </div>
    </div>

    <!-- Address: Barangay / City -->
    <div class="row g-2 mb-2">
        <div class="col-6">
            <label for="barangay" class="form-label mb-1" style="font-size:0.8125rem;">Barangay <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="text" id="barangay" name="barangay"
                   class="form-control form-control-sm<?= $inv('barangay') ?>"
                   value="<?= $o('barangay') ?>" maxlength="150" required
                   placeholder="e.g. Barangay Liwanag"
                   aria-describedby="<?= !empty($errors['barangay']) ? 'brgy-err' : '' ?>">
            <?= $err('barangay', 'brgy-err') ?>
        </div>
        <div class="col-6">
            <label for="city" class="form-label mb-1" style="font-size:0.8125rem;">City/Municipality <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="text" id="city" name="city"
                   class="form-control form-control-sm<?= $inv('city') ?>"
                   value="<?= $o('city') ?>" maxlength="100" required
                   placeholder="e.g. Biringan City"
                   aria-describedby="<?= !empty($errors['city']) ? 'city-err' : '' ?>">
            <?= $err('city', 'city-err') ?>
        </div>
    </div>

    <!-- Address: Province -->
    <div class="mb-3">
        <label for="province" class="form-label mb-1" style="font-size:0.8125rem;">Province <span class="text-danger" aria-hidden="true">*</span></label>
        <input type="text" id="province" name="province"
               class="form-control form-control-sm<?= $inv('province') ?>"
               value="<?= $o('province') ?>" maxlength="100" required
               placeholder="e.g. Province of Elaria"
               aria-describedby="<?= !empty($errors['province']) ? 'prov-err' : '' ?>">
        <?= $err('province', 'prov-err') ?>
    </div>

    <!-- Terms and Conditions + Privacy Policy acceptance -->
    <div class="mb-3<?= $inv('terms_accepted') ?>">
        <div class="form-check">
            <input class="form-check-input<?= $inv('terms_accepted') ?>" type="checkbox"
                   id="terms_accepted" name="terms_accepted" value="1"
                   <?= !empty($old['terms_accepted']) ? 'checked' : '' ?>
                   aria-describedby="tc-err" required>
            <label class="form-check-label" for="terms_accepted" style="font-size:0.8125rem;">
                I have read and agree to the
                <a href="<?= $appUrl ?>/policy/terms" target="_blank" rel="noopener noreferrer"
                   style="color:var(--emu-primary);">Terms and Conditions</a>
                and
                <a href="<?= $appUrl ?>/policy/privacy" target="_blank" rel="noopener noreferrer"
                   style="color:var(--emu-primary);">Privacy Policy</a>.
                <span class="text-danger" aria-hidden="true">*</span>
            </label>
        </div>
        <?= $err('terms_accepted', 'tc-err') ?>
    </div>

    <button type="submit" id="registerSubmitBtn"
            class="btn btn-primary w-100 mb-2"
            style="padding:0.55rem;font-weight:600;font-size:0.9375rem;">
        <i class="bi bi-person-check me-1" aria-hidden="true"></i> Create Account
    </button>

    <p class="text-center mb-0" style="font-size:0.8125rem;color:var(--emu-text-muted);">
        Already have an account?
        <a href="<?= $appUrl ?>/login" class="fw-600" style="color:var(--emu-primary);">Sign In</a>
    </p>

    <div class="text-center mt-3 pt-3" style="border-top:1px solid var(--emu-border,#E8E0E8);">
        <a href="<?= $appUrl ?>/" style="font-size:0.8125rem;color:var(--emu-text-muted);text-decoration:none;" class="d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Back to Landing Page
        </a>
    </div>
</form>
