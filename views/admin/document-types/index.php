<?php
/**
 * Admin Document Types List — design.md §10.3, Req 5
 * Variables: $documentTypes (array)
 */
$appUrl        = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e             = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$csrfToken     = \Emunicipal\Helpers\SecurityHelper::getCsrfToken();
$documentTypes = $documentTypes ?? [];
$breadcrumbs   = [
    ['label' => 'Dashboard',       'url' => $appUrl . '/admin/dashboard'],
    ['label' => 'Document Types'],
];
?>

<!-- Page header -->
<div class="b2-page-header">
    <div class="b2-page-header-left">
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $e($appUrl) ?>/admin/dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Document Types</li>
            </ol>
        </nav>
        <div class="b2-page-header-accent"></div>
        <h1>Document Types</h1>
        <p class="b2-page-sub">Manage the fictional document catalog for Biringan City.</p>
    </div>
    <a href="<?= $e($appUrl) ?>/admin/document-types/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Add Document Type
    </a>
</div>

<!-- Document types table -->
<div class="b2-card">
    <div class="table-responsive">
        <table class="table b2-table mb-0" aria-label="Document types list">
                <thead>
                    <tr>
                        <th scope="col">Document Type</th>
                        <th scope="col">Description</th>
                        <th scope="col">Fee</th>
                        <th scope="col">Requirements</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documentTypes)): ?>
                    <tr>
                        <td colspan="6" class="p-0">
                            <div class="b2-empty">
                                <div class="b2-empty-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></div>
                                <h5>No document types yet</h5>
                                <p>Add the first document type to make it available for community members to request.</p>
                                <a href="<?= $e($appUrl) ?>/admin/document-types/create" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Add Document Type
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($documentTypes as $dt): ?>
                    <tr>
                        <!-- Name -->
                        <td>
                            <strong><?= $e($dt['name']) ?></strong>
                            <?php if (!empty($dt['creator_first_name'])): ?>
                            <div class="text-muted-sm mt-1">
                                Created by <?= $e($dt['creator_first_name'] . ' ' . $dt['creator_last_name']) ?>
                            </div>
                            <?php endif; ?>
                        </td>

                        <!-- Description (truncated) -->
                        <td style="max-width:280px;">
                            <span title="<?= $e($dt['description']) ?>">
                                <?= $e(mb_substr($dt['description'], 0, 80)) ?>
                                <?= mb_strlen($dt['description']) > 80 ? '…' : '' ?>
                            </span>
                        </td>

                        <!-- Processing fee -->
                        <td class="text-nowrap">
                            &#8369;<?= $e(number_format((float)$dt['processing_fee'], 2)) ?>
                        </td>

                        <!-- Active requirements count -->
                        <td>
                            <?php $reqCount = (int)($dt['active_requirement_count'] ?? 0); ?>
                            <a href="<?= $e($appUrl) ?>/admin/document-types/<?= (int)$dt['id'] ?>/requirements"
                               class="text-decoration-none">
                                <?= $reqCount ?>
                                <?= $reqCount === 1 ? 'active' : 'active' ?>
                                <i class="bi bi-arrow-right ms-1" style="font-size:0.7rem;" aria-hidden="true"></i>
                            </a>
                        </td>

                        <!-- Status badge -->
                        <td>
                            <?php if ($dt['is_active']): ?>
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
                                <a href="<?= $e($appUrl) ?>/admin/document-types/<?= (int)$dt['id'] ?>/edit"
                                   class="btn btn-sm btn-outline-primary"
                                   aria-label="Edit <?= $e($dt['name']) ?>">
                                    <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                                </a>

                                <!-- Requirements -->
                                <a href="<?= $e($appUrl) ?>/admin/document-types/<?= (int)$dt['id'] ?>/requirements"
                                   class="btn btn-sm btn-outline-secondary"
                                   aria-label="Manage requirements for <?= $e($dt['name']) ?>">
                                    <i class="bi bi-list-check" aria-hidden="true"></i> Requirements
                                </a>

                                <!-- Toggle active / inactive -->
                                <form method="POST"
                                      action="<?= $e($appUrl) ?>/admin/document-types/<?= (int)$dt['id'] ?>/toggle"
                                      class="d-inline"
                                      data-confirm="<?= $dt['is_active']
                                          ? 'Deactivate this document type? Residents will no longer be able to submit new requests for it.'
                                          : 'Activate this document type? It will become available for new requests.' ?>">
                                    <input type="hidden" name="_csrf_token"
                                           value="<?= $e($csrfToken) ?>">
                                    <input type="hidden" name="_method" value="POST">
                                    <button type="submit"
                                            class="btn btn-sm <?= $dt['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                            aria-label="<?= $dt['is_active'] ? 'Deactivate' : 'Activate' ?> <?= $e($dt['name']) ?>">
                                        <?php if ($dt['is_active']): ?>
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

<!-- Info note -->
<p class="text-muted-sm mt-3">
    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
    Deactivating a document type prevents new requests from being submitted for it.
    Existing in-progress requests are not affected.
</p>


