<?php
/**
 * Admin Edit Requirement — design.md §10.4, Req 5.7
 * Variables: $docType (array), $requirement (array), $csrfToken, $errors, $old
 */
$appUrl      = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e           = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$errors      = $errors      ?? [];
$old         = $old         ?? [];
$csrfToken   = $csrfToken   ?? '';
$docType     = $docType     ?? [];
$requirement = $requirement ?? [];
$typeId      = (int)($docType['id'] ?? 0);
$reqId       = (int)($requirement['id'] ?? 0);

// Use $old values when re-displaying after validation failure; otherwise use DB record
$val = function(string $key) use ($old, $requirement): string {
    if (!empty($old)) {
        return (string)($old[$key] ?? '');
    }
    return (string)($requirement[$key] ?? '');
};
$inv = fn(string $k) => !empty($errors[$k]) ? ' is-invalid' : '';

// Determine which radio should be checked for is_required
$isRequired = !empty($old)
    ? (($old['is_required'] ?? '1') === '1')
    : (bool)($requirement['is_required'] ?? true);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-1">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
            <a href="<?= $e($appUrl) ?>/admin/dashboard">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="<?= $e($appUrl) ?>/admin/document-types">Document Types</a>
        </li>
        <li class="breadcrumb-item">
            <a href="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/requirements">
                Requirements: <?= $e($docType['name'] ?? '') ?>
            </a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Edit Requirement</li>
    </ol>
</nav>
<h1 class="mb-4 mt-1" style="font-size:1.5rem;font-weight:700;">Edit Requirement</h1>

<div class="row">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0" style="font-size:0.9375rem;">
                    Requirement for:
                    <span class="text-primary"><?= $e($docType['name'] ?? '') ?></span>
                </h2>
                <?php if ($requirement['is_active'] ?? false): ?>
                <span class="status-badge status-approved">
                    <i class="bi bi-circle-fill" aria-hidden="true"></i> Active
                </span>
                <?php else: ?>
                <span class="status-badge status-cancelled">
                    <i class="bi bi-circle" aria-hidden="true"></i> Inactive
                </span>
                <?php endif; ?>
            </div>
            <div class="card-body">

                <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
                    <span><?= $e($errors['form']) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST"
                      action="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/requirements/<?= $reqId ?>"
                      novalidate>
                    <input type="hidden" name="_csrf_token"
                           value="<?= $e($csrfToken) ?>">

                    <!-- Requirement Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Requirement Name
                            <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="name" name="name"
                               class="form-control<?= $inv('name') ?>"
                               value="<?= $e($val('name')) ?>"
                               maxlength="200" required
                               aria-describedby="<?= !empty($errors['name']) ? 'name-error' : '' ?>">
                        <?php if (!empty($errors['name'])): ?>
                        <div id="name-error" class="invalid-feedback" role="alert">
                            <?= $e($errors['name']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="form-control<?= $inv('description') ?>"
                                  aria-describedby="<?= !empty($errors['description']) ? 'desc-error' : '' ?>"><?= $e($val('description')) ?></textarea>
                        <?php if (!empty($errors['description'])): ?>
                        <div id="desc-error" class="invalid-feedback" role="alert">
                            <?= $e($errors['description']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sort Order -->
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Display Order</label>
                        <input type="number" id="sort_order" name="sort_order"
                               class="form-control<?= $inv('sort_order') ?>"
                               value="<?= $e($val('sort_order')) ?>"
                               min="0" step="1" style="max-width:120px;"
                               aria-describedby="sort-help <?= !empty($errors['sort_order']) ? 'sort-error' : '' ?>">
                        <div id="sort-help" class="form-text">
                            Lower numbers appear first.
                        </div>
                        <?php if (!empty($errors['sort_order'])): ?>
                        <div id="sort-error" class="invalid-feedback d-block" role="alert">
                            <?= $e($errors['sort_order']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mandatory / Optional -->
                    <div class="mb-4">
                        <fieldset>
                            <legend class="form-label mb-2">Requirement Type</legend>
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="is_required" id="req_mandatory" value="1"
                                       <?= $isRequired ? 'checked' : '' ?>>
                                <label class="form-check-label" for="req_mandatory">
                                    <strong>Mandatory</strong> — Resident must provide this document
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="is_required" id="req_optional" value="0"
                                       <?= !$isRequired ? 'checked' : '' ?>>
                                <label class="form-check-label" for="req_optional">
                                    <strong>Optional</strong> — Resident may provide this if applicable
                                </label>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i> Save Changes
                        </button>
                        <a href="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/requirements"
                           class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Side panel: toggle status -->
    <div class="col-12 col-lg-4 col-xl-5 mt-4 mt-lg-0">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">Status</h2>
            </div>
            <div class="card-body">
                <p class="mb-3" style="font-size:0.875rem;">
                    <?php if ($requirement['is_active'] ?? false): ?>
                    This requirement is currently <strong>Active</strong> and shown to residents submitting new requests.
                    <?php else: ?>
                    This requirement is <strong>Inactive</strong> and hidden from new requests.
                    <?php endif; ?>
                </p>
                <form method="POST"
                      action="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/requirements/<?= $reqId ?>/toggle"
                      data-confirm="<?= ($requirement['is_active'] ?? false)
                          ? 'Deactivate this requirement? It will be hidden from new requests.'
                          : 'Activate this requirement? It will appear in new requests.' ?>">
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                    <button type="submit"
                            class="btn btn-sm w-100 <?= ($requirement['is_active'] ?? false) ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                        <?php if ($requirement['is_active'] ?? false): ?>
                        <i class="bi bi-eye-slash me-1" aria-hidden="true"></i> Deactivate
                        <?php else: ?>
                        <i class="bi bi-eye me-1" aria-hidden="true"></i> Activate
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
