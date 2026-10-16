<?php
/**
 * Admin System Settings — Req 20
 * Variables: $settings (array), $csrfToken (string), $errors (array)
 */
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$settings  = $settings ?? [];
$csrfToken = $csrfToken ?? '';
$errors    = $errors    ?? [];

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/admin/dashboard'],
    ['label' => 'System Settings'],
];

$v = fn(string $key) => $e($settings[$key] ?? '');
$err = fn(string $k) => $errors[$k] ?? null;
?>

<!-- Page header -->
<div class="b2-page-header mt-1 mb-4">
    <div class="b2-page-header-left">
        <div class="b2-page-header-accent"></div>
        <h1>System Settings</h1>
        <p class="b2-page-sub">Manage Biringan City Administration configuration.</p>
    </div>
</div>

<?php if (!empty($errors['form'])): ?>
<div class="alert alert-danger d-flex gap-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
    <span><?= $e($errors['form']) ?></span>
</div>
<?php endif; ?>

<form method="POST" action="<?= $e($appUrl) ?>/admin/settings" data-loading="Saving…"
      aria-label="System Settings form">
    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">

    <!-- ── Office Information ──────────────────────────────────────── -->
    <div class="b2-card mb-4">
        <div class="b2-card-header">
            <div class="d-flex align-items-center gap-2">
                <div class="b2-card-header-icon"><i class="bi bi-building" aria-hidden="true"></i></div>
                <h2>Office Information</h2>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">

                <!-- Office Name -->
                <div class="col-12 col-md-6">
                    <label for="office_name" class="form-label">
                        Office Name <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="text" id="office_name" name="settings[office_name]"
                           class="form-control <?= $err('office_name') ? 'is-invalid' : '' ?>"
                           value="<?= $v('office_name') ?>"
                           maxlength="255" required
                           aria-describedby="office_name_err">
                    <?php if ($err('office_name')): ?>
                    <div id="office_name_err" class="invalid-feedback"><?= $e($err('office_name')) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Office Email -->
                <div class="col-12 col-md-6">
                    <label for="office_email" class="form-label">
                        Office Email <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="email" id="office_email" name="settings[office_email]"
                           class="form-control <?= $err('office_email') ? 'is-invalid' : '' ?>"
                           value="<?= $v('office_email') ?>"
                           maxlength="255" required
                           aria-describedby="office_email_err">
                    <?php if ($err('office_email')): ?>
                    <div id="office_email_err" class="invalid-feedback"><?= $e($err('office_email')) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Office Address -->
                <div class="col-12 col-md-8">
                    <label for="office_address" class="form-label">
                        Office Address <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="text" id="office_address" name="settings[office_address]"
                           class="form-control <?= $err('office_address') ? 'is-invalid' : '' ?>"
                           value="<?= $v('office_address') ?>"
                           maxlength="500" required
                           aria-describedby="office_address_err">
                    <?php if ($err('office_address')): ?>
                    <div id="office_address_err" class="invalid-feedback"><?= $e($err('office_address')) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Office Contact Number -->
                <div class="col-12 col-md-4">
                    <label for="office_contact_number" class="form-label">Office Contact Number</label>
                    <input type="text" id="office_contact_number"
                           name="settings[office_contact_number]"
                           class="form-control <?= $err('office_contact_number') ? 'is-invalid' : '' ?>"
                           value="<?= $v('office_contact_number') ?>"
                           maxlength="50"
                           aria-describedby="office_contact_err">
                    <?php if ($err('office_contact_number')): ?>
                    <div id="office_contact_err" class="invalid-feedback"><?= $e($err('office_contact_number')) ?></div>
                    <?php endif; ?>
                </div>

            </div><!-- /.row -->
        </div>
    </div>

    <!-- ── System Parameters ───────────────────────────────────────── -->
    <div class="b2-card mb-4">
        <div class="b2-card-header">
            <div class="d-flex align-items-center gap-2">
                <div class="b2-card-header-icon"><i class="bi bi-gear" aria-hidden="true"></i></div>
                <h2>System Parameters</h2>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">

                <!-- Max Upload Size -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="max_upload_size_mb" class="form-label">
                        Maximum Upload Size (MB)
                        <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <div class="input-group">
                        <input type="number" id="max_upload_size_mb"
                               name="settings[max_upload_size_mb]"
                               class="form-control <?= $err('max_upload_size_mb') ? 'is-invalid' : '' ?>"
                               value="<?= $v('max_upload_size_mb') ?>"
                               min="1" max="20" required
                               aria-describedby="max_upload_err">
                        <span class="input-group-text">MB</span>
                        <?php if ($err('max_upload_size_mb')): ?>
                        <div id="max_upload_err" class="invalid-feedback"><?= $e($err('max_upload_size_mb')) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-text">Range: 1–20 MB per file.</div>
                </div>

                <!-- Session Timeout -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="session_timeout_minutes" class="form-label">
                        Session Timeout (Minutes)
                        <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <div class="input-group">
                        <input type="number" id="session_timeout_minutes"
                               name="settings[session_timeout_minutes]"
                               class="form-control <?= $err('session_timeout_minutes') ? 'is-invalid' : '' ?>"
                               value="<?= $v('session_timeout_minutes') ?>"
                               min="5" max="120" required
                               aria-describedby="session_timeout_err">
                        <span class="input-group-text">min</span>
                        <?php if ($err('session_timeout_minutes')): ?>
                        <div id="session_timeout_err" class="invalid-feedback"><?= $e($err('session_timeout_minutes')) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-text">Range: 5–120 minutes. Applies to all sessions.</div>
                </div>

                <!-- Records Per Page -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="records_per_page" class="form-label">
                        Records Per Page
                        <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <select id="records_per_page" name="settings[records_per_page]"
                            class="form-select <?= $err('records_per_page') ? 'is-invalid' : '' ?>"
                            aria-describedby="per_page_err">
                        <?php foreach ([10, 20, 50] as $opt): ?>
                        <option value="<?= $opt ?>"
                            <?= ($settings['records_per_page'] ?? '20') == $opt ? 'selected' : '' ?>>
                            <?= $opt ?> per page
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($err('records_per_page')): ?>
                    <div id="per_page_err" class="invalid-feedback"><?= $e($err('records_per_page')) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Applies to paginated list views.</div>
                </div>

            </div><!-- /.row -->
        </div>
    </div>

    <!-- Save button -->
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy me-1" aria-hidden="true"></i> Save Settings
        </button>
        <a href="<?= $e($appUrl) ?>/admin/dashboard" class="btn btn-outline-secondary">
            Cancel
        </a>
    </div>

</form>