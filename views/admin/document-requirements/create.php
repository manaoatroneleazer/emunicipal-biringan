<?php
/**
 * Admin Create Requirement — design.md §10.4, Req 5.6–5.7
 * Variables: $docType (array), $csrfToken, $errors (array), $old (array)
 */
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$o         = fn(string $k) => $e($old[$k] ?? '');
$inv       = fn(string $k) => !empty($errors[$k]) ? ' is-invalid' : '';
$errors    = $errors    ?? [];
$old       = $old       ?? [];
$csrfToken = $csrfToken ?? '';
$docType   = $docType   ?? [];
$typeId    = (int)($docType['id'] ?? 0);
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
        <li class="breadcrumb-item active" aria-current="page">Add Requirement</li>
    </ol>
</nav>
<h1 class="mb-4 mt-1" style="font-size:1.5rem;font-weight:700;">Add Requirement</h1>

<div class="row">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">
                    Requirement for:
                    <span class="text-primary"><?= $e($docType['name'] ?? '') ?></span>
                </h2>
            </div>
            <div class="card-body">

                <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
                    <span><?= $e($errors['form']) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST"
                      action="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/requirements"
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
                               value="<?= $o('name') ?>"
                               maxlength="200" required
                               placeholder="e.g. Valid Government-Issued ID"
                               aria-describedby="<?= !empty($errors['name']) ? 'name-error' : 'name-help' ?>">
                        <div id="name-help" class="form-text">
                            Maximum 200 characters.
                        </div>
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
                                  placeholder="Optional additional instructions for residents."
                                  aria-describedby="<?= !empty($errors['description']) ? 'desc-error' : '' ?>"><?= $o('description') ?></textarea>
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
                               value="<?= $o('sort_order') ?: '0' ?>"
                               min="0" step="1" style="max-width:120px;"
                               aria-describedby="sort-help <?= !empty($errors['sort_order']) ? 'sort-error' : '' ?>">
                        <div id="sort-help" class="form-text">
                            Lower numbers appear first. Use 0 for no specific order.
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
                                       <?= (($old['is_required'] ?? '1') === '1') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="req_mandatory">
                                    <strong>Mandatory</strong> — Resident must provide this document
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="is_required" id="req_optional" value="0"
                                       <?= (($old['is_required'] ?? '1') === '0') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="req_optional">
                                    <strong>Optional</strong> — Resident may provide this if applicable
                                </label>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i> Save Requirement
                        </button>
                        <a href="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/requirements"
                           class="btn btn-outline-secondary">Cancel</a>
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
                    <li>New requirements are <strong>Active</strong> by default.</li>
                    <li>Requirements are shown to residents when submitting a new request.</li>
                    <li>Deactivating a requirement hides it from <em>new</em> requests only — existing requests are not affected.</li>
                    <li>Use <strong>Display Order</strong> to control how requirements are listed (lower = higher priority).</li>
                </ul>
            </div>
        </div>
    </div>
</div>
