<?php
/**
 * Admin Requirements List — design.md §10.4, Req 5.6–5.8
 * Variables: $docType (array), $requirements (array)
 */
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$csrfToken = \Emunicipal\Helpers\SecurityHelper::getCsrfToken();
$typeId    = (int)($docType['id'] ?? 0);
$breadcrumbs = [
    ['label' => 'Dashboard',      'url' => $appUrl . '/admin/dashboard'],
    ['label' => 'Document Types', 'url' => $appUrl . '/admin/document-types'],
    ['label' => 'Requirements: ' . ($docType['name'] ?? '')],
];
?>

<!-- Page header -->

<div class="d-flex justify-content-between align-items-start mb-4 mt-1 flex-wrap gap-2">
    <div>
        <h1 class="mb-1" style="font-size:1.5rem;font-weight:700;">
            Requirements
        </h1>
        <p class="text-muted mb-0" style="font-size:0.9rem;">
            Document type:
            <strong><?= $e($docType['name'] ?? '') ?></strong>
            <?php if ($docType['is_active'] ?? false): ?>
            <span class="status-badge status-approved ms-2">
                <i class="bi bi-circle-fill" aria-hidden="true"></i> Active
            </span>
            <?php else: ?>
            <span class="status-badge status-cancelled ms-2">
                <i class="bi bi-circle" aria-hidden="true"></i> Inactive
            </span>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/requirements/create"
           class="btn btn-primary">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Add Requirement
        </a>
        <a href="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/edit"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit Document Type
        </a>
    </div>
</div>

<?php if (!empty($docType['description'])): ?>
<div class="alert alert-light border mb-4" style="font-size:0.875rem;">
    <i class="bi bi-info-circle me-2 text-primary" aria-hidden="true"></i>
    <?= $e($docType['description']) ?>
</div>
<?php endif; ?>

<!-- Requirements table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="Requirements for <?= $e($docType['name'] ?? '') ?>">
                <thead>
                    <tr>
                        <th scope="col" style="width:3rem;">Order</th>
                        <th scope="col">Requirement Name</th>
                        <th scope="col">Description</th>
                        <th scope="col" style="width:7rem;">Mandatory</th>
                        <th scope="col" style="width:8rem;">Status</th>
                        <th scope="col" class="table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requirements)): ?>
                    <tr>
                        <td colspan="6" class="p-0">
                            <div class="empty-state">
                                <i class="bi bi-list-check" aria-hidden="true"></i>
                                <h5>No requirements yet</h5>
                                <p>Add requirements that residents must upload when requesting this document.</p>
                                <a href="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/requirements/create"
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Add Requirement
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($requirements as $req): ?>
                    <tr class="<?= !$req['is_active'] ? 'text-muted' : '' ?>">
                        <!-- Sort order -->
                        <td class="text-center">
                            <span class="badge bg-secondary"><?= (int)$req['sort_order'] ?></span>
                        </td>

                        <!-- Name -->
                        <td>
                            <strong><?= $e($req['name']) ?></strong>
                        </td>

                        <!-- Description (truncated) -->
                        <td style="max-width:240px;">
                            <?php if (!empty($req['description'])): ?>
                            <span title="<?= $e($req['description']) ?>"
                                  style="font-size:0.8125rem;">
                                <?= $e(mb_substr($req['description'], 0, 70)) ?>
                                <?= mb_strlen($req['description']) > 70 ? '…' : '' ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted-sm">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Mandatory -->
                        <td>
                            <?php if ($req['is_required']): ?>
                            <span class="status-badge status-approved" style="font-size:0.7rem;">
                                <i class="bi bi-check" aria-hidden="true"></i> Required
                            </span>
                            <?php else: ?>
                            <span class="status-badge status-pending" style="font-size:0.7rem;">
                                Optional
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- Status -->
                        <td>
                            <?php if ($req['is_active']): ?>
                            <span class="status-badge status-approved">
                                <i class="bi bi-circle-fill" aria-hidden="true"></i> Active
                            </span>
                            <?php else: ?>
                            <span class="status-badge status-cancelled">
                                <i class="bi bi-circle" aria-hidden="true"></i> Inactive
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- Actions -->
                        <td class="table-actions">
                            <div class="d-flex gap-1 justify-content-end">
                                <!-- Edit -->
                                <a href="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/requirements/<?= (int)$req['id'] ?>/edit"
                                   class="btn btn-sm btn-outline-primary"
                                   aria-label="Edit <?= $e($req['name']) ?>">
                                    <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                                </a>

                                <!-- Toggle active -->
                                <form method="POST"
                                      action="<?= $e($appUrl) ?>/admin/document-types/<?= $typeId ?>/requirements/<?= (int)$req['id'] ?>/toggle"
                                      class="d-inline"
                                      data-confirm="<?= $req['is_active']
                                          ? 'Deactivate this requirement? It will be hidden from new requests.'
                                          : 'Activate this requirement? It will appear in new requests.' ?>">
                                    <input type="hidden" name="_csrf_token"
                                           value="<?= $e($csrfToken) ?>">
                                    <button type="submit"
                                            class="btn btn-sm <?= $req['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                            aria-label="<?= $req['is_active'] ? 'Deactivate' : 'Activate' ?> <?= $e($req['name']) ?>">
                                        <?php if ($req['is_active']): ?>
                                        <i class="bi bi-eye-slash" aria-hidden="true"></i> Deactivate
                                        <?php else: ?>
                                        <i class="bi bi-eye" aria-hidden="true"></i> Activate
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<p class="text-muted-sm mt-3">
    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
    Deactivating a requirement hides it from new requests. Existing request records are not affected (Req 5.8).
</p>

