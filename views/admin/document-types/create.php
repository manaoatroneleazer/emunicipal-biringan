<?php
/**
 * Admin Create Document Type — design.md §10.3, Req 5.2–5.3
 * Variables: $csrfToken, $errors (array), $old (array)
 */
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$o         = fn(string $k) => $e($old[$k] ?? '');
$inv       = fn(string $k) => !empty($errors[$k]) ? ' is-invalid' : '';
$errors    = $errors    ?? [];
$old       = $old       ?? [];
$csrfToken = $csrfToken ?? '';
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
        <li class="breadcrumb-item active" aria-current="page">Add Document Type</li>
    </ol>
</nav>
<h1 class="mb-4 mt-1" style="font-size:1.5rem;font-weight:700;">Add Document Type</h1>

<div class="row">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">Document Type Details</h2>
            </div>
            <div class="card-body">

                <!-- Form-level error -->
                <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
                    <span><?= $e($errors['form']) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= $e($appUrl) ?>/admin/document-types" novalidate>
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
                               value="<?= $o('name') ?>"
                               maxlength="150" required
                               placeholder="e.g. Biringan City Residency Certificate"
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
                                  placeholder="Describe what this document is and when residents need it."
                                  aria-describedby="<?= !empty($errors['description']) ? 'desc-error' : '' ?>"><?= $o('description') ?></textarea>
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
                                   value="<?= $o('processing_fee') ?: '0.00' ?>"
                                   min="0" step="0.01" required
                                   aria-describedby="fee-help <?= !empty($errors['processing_fee']) ? 'fee-error' : '' ?>">
                        </div>
                        <div id="fee-help" class="form-text">
                            Enter 0.00 for free documents.
                        </div>
                        <?php if (!empty($errors['processing_fee'])): ?>
                        <div id="fee-error" class="invalid-feedback d-block" role="alert">
                            <?= $e($errors['processing_fee']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i> Save Document Type
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

    <!-- Help panel -->
    <div class="col-12 col-lg-4 col-xl-5 mt-4 mt-lg-0">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i> Notes
                </h2>
            </div>
            <div class="card-body">
                <ul class="mb-0" style="font-size:0.875rem;line-height:1.7;">
                    <li>New document types are set to <strong>Active</strong> by default and are immediately visible to residents.</li>
                    <li>You can add requirements for this document type after saving.</li>
                    <li>Document type names must be unique across the system.</li>
                    <li>To hide a type from residents without deleting it, use the <strong>Deactivate</strong> action on the list page.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
