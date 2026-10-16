<?php
/**
 * Admin Edit Document Type — design.md §10.3, Req 5.2
 * Variables: $documentType (array), $csrfToken, $errors (array), $old (array)
 */
$appUrl       = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e            = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$errors       = $errors       ?? [];
$old          = $old          ?? [];
$csrfToken    = $csrfToken    ?? '';
$documentType = $documentType ?? [];
$typeId       = (int)($documentType['id'] ?? 0);

// When re-displaying after validation failure, use $old; otherwise use DB values
$val = function(string $key) use ($old, $documentType): string {
    if (!empty($old)) {
        return $old[$key] ?? '';
    }
    return (string)($documentType[$key] ?? '');
};
$inv = fn(string $k) => !empty($errors[$k]) ? ' is-invalid' : '';
?>

<!-- Breadcrumb + page header -->
<nav aria-label="breadcrumb" class="mb-1">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
            <a href="<?= $e($appUrl) ?>/admin/dashboard">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="<?= $e($appUrl) ?>/admin/document-types">Document Types</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
            Edit: <?= $e($documentType['name'] ?? '') ?>
        </li>
    </ol>
</nav>
<h1 class="mb-4 mt-1" style="font-size:1.5rem;font-weight:700;">
    Edit Document Type
</h1>

<div class="row">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0" style="font-size:0.9375rem;">Document Type Details</h2>
                <!-- Current status badge -->
                <?php if ($documentType['is_active'] ?? false): ?>
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

                <!-- Form-level error -->
                <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
                    <span><?= $e($errors['form']) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST"
                      action="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>"
                      novalidate>
                    <input type="hidden" name="_csrf_token"
                           value="<?= $e($csrfToken) ?>">

                    <!-- Document Type Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Document Type Name
                            <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="name" name="name"
                               class="form-control<?= $inv('name') ?>"
                               value="<?= $e($val('name')) ?>"
                               maxlength="150" required
                               aria-describedby="<?= !empty($errors['name']) ? 'name-error' : 'name-help' ?>">
                        <div id="name-help" class="form-text">
                            Must be unique. Maximum 150 characters.
                        </div>
                        <?php if (!empty($errors['name'])): ?>
                        <div id="name-error" class="invalid-feedback" role="alert">
                            <?= $e($errors['name']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            Description
                            <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <textarea id="description" name="description" rows="4"
                                  class="form-control<?= $inv('description') ?>"
                                  required
                                  aria-describedby="<?= !empty($errors['description']) ? 'desc-error' : '' ?>"><?= $e($val('description')) ?></textarea>
                        <?php if (!empty($errors['description'])): ?>
                        <div id="desc-error" class="invalid-feedback" role="alert">
                            <?= $e($errors['description']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Processing Fee -->
                    <div class="mb-4">
                        <label for="processing_fee" class="form-label">
                            Processing Fee (₱)
                            <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group" style="max-width:200px;">
                            <span class="input-group-text" aria-hidden="true">₱</span>
                            <input type="number" id="processing_fee" name="processing_fee"
                                   class="form-control<?= $inv('processing_fee') ?>"
                                   value="<?= $e(number_format((float)$val('processing_fee'), 2, '.', '')) ?>"
                                   min="0" step="0.01" required
                                   aria-describedby="<?= !empty($errors['processing_fee']) ? 'fee-error' : '' ?>">
                        </div>
                        <?php if (!empty($errors['processing_fee'])): ?>
                        <div id="fee-error" class="invalid-feedback d-block" role="alert">
                            <?= $e($errors['processing_fee']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i> Save Changes
                        </button>
                        <a href="<?= $e($appUrl) ?>/admin/document-types"
                           class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Side panel: quick actions -->
    <div class="col-12 col-lg-4 col-xl-5 mt-4 mt-lg-0">

        <!-- Toggle status -->
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">Status</h2>
            </div>
            <div class="card-body">
                <p class="mb-3" style="font-size:0.875rem;">
                    <?php if ($documentType['is_active'] ?? false): ?>
                    This document type is currently <strong>Active</strong> and visible to residents.
                    <?php else: ?>
                    This document type is <strong>Inactive</strong>. Residents cannot submit new requests for it.
                    <?php endif; ?>
                </p>
                <form method="POST"
                      action="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/toggle"
                      data-confirm="<?= ($documentType['is_active'] ?? false)
                          ? 'Deactivate this document type? Residents will no longer be able to submit new requests for it.'
                          : 'Activate this document type? It will become available for new requests.' ?>">
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                    <button type="submit"
                            class="btn btn-sm w-100 <?= ($documentType['is_active'] ?? false) ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                        <?php if ($documentType['is_active'] ?? false): ?>
                        <i class="bi bi-eye-slash me-1" aria-hidden="true"></i> Deactivate
                        <?php else: ?>
                        <i class="bi bi-eye me-1" aria-hidden="true"></i> Activate
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Requirements link -->
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">Requirements</h2>
            </div>
            <div class="card-body">
                <p class="mb-3" style="font-size:0.875rem;">
                    Manage the supporting documents residents must upload when requesting this document type.
                </p>
                <a href="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/requirements"
                   class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-list-check me-1" aria-hidden="true"></i>
                    Manage Requirements
                </a>
            </div>
        </div>

    </div>
</div>
