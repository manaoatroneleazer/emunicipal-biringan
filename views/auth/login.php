<?php
/**
 * Login view
 * Layout: layouts/minimal
 * Variables: $csrfToken, $errors, $oldEmail
 */
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$errors    = $errors ?? [];
$oldEmail  = isset($oldEmail) ? htmlspecialchars($oldEmail, ENT_QUOTES, 'UTF-8') : '';
$csrfToken = $csrfToken ?? '';
?>

<h1 class="mb-1" style="font-size:1.5rem;font-weight:700;color:var(--emu-text-primary);">
    Welcome back
</h1>
<p class="text-muted mb-4" style="font-size:0.875rem;">Sign in to continue to eMunicipal</p>

<div id="loginErrorContainer" role="alert" aria-live="assertive">
<?php if (!empty($errors['form'])): ?>
<div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
    <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
    <span><?= htmlspecialchars($errors['form'], ENT_QUOTES, 'UTF-8') ?></span>
</div>
<?php endif; ?>
</div>

<form id="loginForm" method="POST" action="<?= $appUrl ?>/login" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label for="email" class="form-label">
            Email Address <span class="text-danger" aria-hidden="true">*</span>
            <span class="visually-hidden">(required)</span>
        </label>
        <div class="input-group mb-3">
            <span class="input-group-text" aria-hidden="true"><i class="bi bi-envelope"></i></span>
            <input type="email" id="email" name="email"
                   class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                   value="<?= $oldEmail ?>"
                   placeholder="Enter your email address"
                   autocomplete="email" required
                   aria-describedby="<?= !empty($errors['email']) ? 'email-error' : '' ?>">
            <?php if (!empty($errors['email'])): ?>
            <div id="email-error" class="invalid-feedback" role="alert">
                <?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-4">
        <label for="password" class="form-label">
            Password <span class="text-danger" aria-hidden="true">*</span>
            <span class="visually-hidden">(required)</span>
        </label>
        <div class="input-group mb-4">
            <span class="input-group-text" aria-hidden="true"><i class="bi bi-lock"></i></span>
            <input type="password" id="password" name="password"
                   class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                   placeholder="Enter your password"
                   autocomplete="current-password" required
                   aria-describedby="<?= !empty($errors['password']) ? 'pw-error' : '' ?>">
            <button class="btn btn-outline-secondary" type="button"
                    data-pw-toggle="password" aria-label="Show password">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
            <?php if (!empty($errors['password'])): ?>
            <div id="pw-error" class="invalid-feedback" role="alert">
                <?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <button id="loginSubmitBtn" type="submit" class="btn btn-primary w-100 mb-3" style="padding:0.625rem;font-weight:600;">
        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Sign In
    </button>
</form>

<p class="text-center mb-0" style="font-size:0.875rem;">
    Don&rsquo;t have an account?
    <a href="<?= $appUrl ?>/register" class="fw-600" style="color:var(--emu-primary);">Create an account</a>
</p>

<div class="text-center mt-3 pt-3" style="border-top:1px solid var(--emu-border,#E8E0E8);">
    <a href="<?= $appUrl ?>/" style="font-size:0.8125rem;color:var(--emu-text-muted);text-decoration:none;" class="d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        Back to Landing Page
    </a>
</div>
