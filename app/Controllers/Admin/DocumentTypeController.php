<?php

namespace Emunicipal\Controllers\Admin;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Validators\Validator;
use Emunicipal\Models\DocumentType;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Core\Logger;

/**
 * DocumentTypeController — Admin document type management.
 *
 * Implements Req 5 (Document Type Management):
 *  5.1  — All types stored in document_types table, none hard-coded
 *  5.2  — Admin UI for create, edit, activate, deactivate
 *  5.3  — Required fields: name, description, processing_fee
 *  5.4  — Deactivation prevents new requests; existing requests unaffected
 *  5.5  — Activation makes type available again
 *  5.9  — Only active types shown to residents
 *  5.10 — Audit log on every change
 *
 * Routes (all defined in routes/web.php):
 *  GET  /admin/document-types
 *  GET  /admin/document-types/create
 *  POST /admin/document-types
 *  GET  /admin/document-types/{id}/edit
 *  POST /admin/document-types/{id}
 *  POST /admin/document-types/{id}/toggle
 */
class DocumentTypeController extends BaseController
{
    // -------------------------------------------------------------------------
    // LIST
    // -------------------------------------------------------------------------

    /**
     * GET /admin/document-types
     * Display all document types with requirement counts and status.
     */
    public function index(): void
    {
        AuthMiddleware::requireRole('admin');

        $documentTypes = [];
        try {
            $documentTypes = (new DocumentType())->allWithRequirementCount();
        } catch (\Throwable $e) {
            Logger::error('DocumentType index error: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load document types. Please try again.');
        }

        $this->renderWithLayout('admin/document-types/index', [
            'title'         => 'Document Types — eMunicipal',
            'activePage'    => 'document-types',
            'documentTypes' => $documentTypes,
        ], 'layouts/auth');
    }

    // -------------------------------------------------------------------------
    // CREATE
    // -------------------------------------------------------------------------

    /**
     * GET /admin/document-types/create
     */
    public function create(): void
    {
        AuthMiddleware::requireRole('admin');

        $this->renderWithLayout('admin/document-types/create', [
            'title'      => 'Add Document Type — eMunicipal',
            'activePage' => 'document-types',
            'csrfToken'  => SecurityHelper::getCsrfToken(),
            'errors'     => SessionHelper::getFlash('dt_errors', []),
            'old'        => SessionHelper::getFlash('dt_old', []),
        ], 'layouts/auth');
    }

    /**
     * POST /admin/document-types
     */
    public function store(): void
    {
        AuthMiddleware::requireRole('admin');

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('dt_errors', ['form' => 'Invalid security token. Please try again.']);
            UrlHelper::redirect('/admin/document-types/create');
        }

        $v = new Validator($_POST);
        $v->required(['name', 'description', 'processing_fee'])
          ->maxLength('name', 150)
          ->nonNegativeDecimal('processing_fee');

        $old = [
            'name'           => $v->get('name', ''),
            'description'    => $v->get('description', ''),
            'processing_fee' => $v->get('processing_fee', ''),
        ];

        if ($v->fails()) {
            SessionHelper::flash('dt_errors', $v->errors());
            SessionHelper::flash('dt_old', $old);
            UrlHelper::redirect('/admin/document-types/create');
        }

        try {
            $model = new DocumentType();

            // Uniqueness check (Req 5.3 — name must be unique, database.md §4.3)
            if ($model->findByName($v->get('name'))) {
                SessionHelper::flash('dt_errors', [
                    'name' => 'A document type with this name already exists.',
                ]);
                SessionHelper::flash('dt_old', $old);
                UrlHelper::redirect('/admin/document-types/create');
            }

            $userId = AuthMiddleware::userId();

            $newId = $model->insert([
                'name'           => $v->get('name'),
                'description'    => $v->get('description'),
                'processing_fee' => number_format((float)$v->get('processing_fee'), 2, '.', ''),
                'is_active'      => 1,
                'created_by'     => $userId,
            ]);

            // Audit log (Req 5.10, 13.1)
            AuditLogService::log(
                $userId, 'admin',
                AuditLog::ACTION_DOC_TYPE_CREATED,
                'documents',
                'document_types', $newId,
                null,
                ['name' => $v->get('name'), 'processing_fee' => $v->get('processing_fee')],
                "Document type '{$v->get('name')}' created by admin ID {$userId}"
            );

            SessionHelper::flash('success', "Document type '{$v->get('name')}' has been created.");
            UrlHelper::redirect('/admin/document-types');

        } catch (\Throwable $e) {
            Logger::error('DocumentType store error: ' . $e->getMessage());
            SessionHelper::flash('dt_errors', ['form' => 'Unable to save the document type. Please try again.']);
            SessionHelper::flash('dt_old', $old);
            UrlHelper::redirect('/admin/document-types/create');
        }
    }

    // -------------------------------------------------------------------------
    // EDIT
    // -------------------------------------------------------------------------

    /**
     * GET /admin/document-types/{id}/edit
     */
    public function edit(string $id): void
    {
        AuthMiddleware::requireRole('admin');

        $documentType = $this->findOrRedirect((int)$id);

        $this->renderWithLayout('admin/document-types/edit', [
            'title'        => 'Edit Document Type — eMunicipal',
            'activePage'   => 'document-types',
            'documentType' => $documentType,
            'csrfToken'    => SecurityHelper::getCsrfToken(),
            'errors'       => SessionHelper::getFlash('dt_errors', []),
            'old'          => SessionHelper::getFlash('dt_old', []),
        ], 'layouts/auth');
    }

    /**
     * POST /admin/document-types/{id}
     */
    public function update(string $id): void
    {
        AuthMiddleware::requireRole('admin');

        $typeId = (int)$id;
        $documentType = $this->findOrRedirect($typeId);

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('dt_errors', ['form' => 'Invalid security token. Please try again.']);
            UrlHelper::redirect("/admin/document-types/{$typeId}/edit");
        }

        $v = new Validator($_POST);
        $v->required(['name', 'description', 'processing_fee'])
          ->maxLength('name', 150)
          ->nonNegativeDecimal('processing_fee');

        $old = [
            'name'           => $v->get('name', ''),
            'description'    => $v->get('description', ''),
            'processing_fee' => $v->get('processing_fee', ''),
        ];

        if ($v->fails()) {
            SessionHelper::flash('dt_errors', $v->errors());
            SessionHelper::flash('dt_old', $old);
            UrlHelper::redirect("/admin/document-types/{$typeId}/edit");
        }

        try {
            $model = new DocumentType();

            // Uniqueness check — exclude current record (Req 5.3, database.md §4.3 UNIQUE)
            if ($model->findByName($v->get('name'), $typeId)) {
                SessionHelper::flash('dt_errors', [
                    'name' => 'Another document type with this name already exists.',
                ]);
                SessionHelper::flash('dt_old', $old);
                UrlHelper::redirect("/admin/document-types/{$typeId}/edit");
            }

            $userId = AuthMiddleware::userId();

            // Build old/new value snapshots for audit (Req 5.10, 13.2)
            $changedFields = [];
            if ($documentType['name'] !== $v->get('name')) {
                $changedFields['name'] = ['old' => $documentType['name'], 'new' => $v->get('name')];
            }
            if ($documentType['description'] !== $v->get('description')) {
                $changedFields['description'] = ['old' => 'changed', 'new' => 'changed'];
            }
            $oldFee = number_format((float)$documentType['processing_fee'], 2, '.', '');
            $newFee = number_format((float)$v->get('processing_fee'), 2, '.', '');
            if ($oldFee !== $newFee) {
                $changedFields['processing_fee'] = ['old' => $oldFee, 'new' => $newFee];
            }

            $model->update($typeId, [
                'name'           => $v->get('name'),
                'description'    => $v->get('description'),
                'processing_fee' => $newFee,
                'updated_by'     => $userId,
            ]);

            // Audit log (Req 5.10)
            AuditLogService::log(
                $userId, 'admin',
                AuditLog::ACTION_DOC_TYPE_MODIFIED,
                'documents',
                'document_types', $typeId,
                empty($changedFields) ? null : array_map(fn($c) => $c['old'], $changedFields),
                empty($changedFields) ? null : array_map(fn($c) => $c['new'], $changedFields),
                "Document type ID {$typeId} updated by admin ID {$userId}"
            );

            SessionHelper::flash('success', "Document type '{$v->get('name')}' has been updated.");
            UrlHelper::redirect('/admin/document-types');

        } catch (\Throwable $e) {
            Logger::error('DocumentType update error: ' . $e->getMessage());
            SessionHelper::flash('dt_errors', ['form' => 'Unable to update the document type. Please try again.']);
            SessionHelper::flash('dt_old', $old);
            UrlHelper::redirect("/admin/document-types/{$typeId}/edit");
        }
    }

    // -------------------------------------------------------------------------
    // TOGGLE ACTIVE / INACTIVE
    // -------------------------------------------------------------------------

    /**
     * POST /admin/document-types/{id}/toggle
     * Activate or deactivate a document type (Req 5.4, 5.5).
     */
    public function toggleActive(string $id): void
    {
        AuthMiddleware::requireRole('admin');

        $typeId = (int)$id;
        $documentType = $this->findOrRedirect($typeId);

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('error', 'Invalid security token.');
            UrlHelper::redirect('/admin/document-types');
        }

        try {
            $userId     = AuthMiddleware::userId();
            $isCurrentlyActive = (bool)$documentType['is_active'];
            $newActive  = $isCurrentlyActive ? 0 : 1;
            $action     = $newActive ? 'activated' : 'deactivated';

            (new DocumentType())->update($typeId, [
                'is_active'  => $newActive,
                'updated_by' => $userId,
            ]);

            // Audit log (Req 5.10, 13.1)
            AuditLogService::log(
                $userId, 'admin',
                AuditLog::ACTION_DOC_TYPE_MODIFIED,
                'documents',
                'document_types', $typeId,
                ['is_active' => (int)$isCurrentlyActive],
                ['is_active' => $newActive],
                "Document type '{$documentType['name']}' {$action} by admin ID {$userId}"
            );

            $label = htmlspecialchars($documentType['name'], ENT_QUOTES, 'UTF-8');
            SessionHelper::flash('success', "Document type '{$label}' has been {$action}.");

        } catch (\Throwable $e) {
            Logger::error('DocumentType toggle error: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to change the document type status. Please try again.');
        }

        UrlHelper::redirect('/admin/document-types');
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    /**
     * Load a document type by ID, or redirect to list with an error if not found.
     */
    private function findOrRedirect(int $id): array
    {
        try {
            $record = (new DocumentType())->find($id);
        } catch (\Throwable $e) {
            Logger::error('DocumentType findOrRedirect DB error: ' . $e->getMessage());
            SessionHelper::flash('error', 'A database error occurred. Please try again.');
            UrlHelper::redirect('/admin/document-types');
        }

        if (!$record) {
            SessionHelper::flash('error', 'Document type not found.');
            UrlHelper::redirect('/admin/document-types');
        }

        return $record;
    }
}
