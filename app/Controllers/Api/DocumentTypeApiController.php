<?php

namespace Emunicipal\Controllers\Api;

use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Validators\Validator;
use Emunicipal\Models\DocumentType;
use Emunicipal\Models\DocumentRequirement;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Core\Logger;

/**
 * DocumentTypeApiController — Document Types resource API.
 *
 * GET    /api/document-types            — Any auth: list active types
 * GET    /api/document-types/{id}       — Any auth: single type + requirements
 * POST   /api/document-types            — Admin: create
 * PUT    /api/document-types/{id}       — Admin: update
 * PATCH  /api/document-types/{id}/status — Admin: toggle active
 *
 * Per Req 15.1, Req 5.
 */
class DocumentTypeApiController extends BaseApiController
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/document-types
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): void
    {
        $this->requireAuth();

        try {
            $types = (new DocumentType())->allActive();
            $this->json($types);
        } catch (\Throwable $e) {
            Logger::error('DocumentTypeApiController::index: ' . $e->getMessage());
            $this->error('Failed to load document types.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/document-types/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(string $id): void
    {
        $this->requireAuth();

        $typeId = (int)$id;
        try {
            $dt = (new DocumentType())->find($typeId);
            if (!$dt) {
                $this->error('Document type not found.', 404);
            }

            $requirements = (new DocumentRequirement())->activeForDocumentType($typeId);
            $dt['requirements'] = $requirements;

            $this->json($dt);
        } catch (\Throwable $e) {
            Logger::error('DocumentTypeApiController::show: ' . $e->getMessage());
            $this->error('Failed to load document type.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/document-types
    // ─────────────────────────────────────────────────────────────────────────

    public function store(): void
    {
        $adminId = $this->requireRole('admin');

        $data = $this->body();
        $v    = new Validator($data);
        $v->required(['name','description','processing_fee'])
          ->maxLength('name', 150)
          ->nonNegativeDecimal('processing_fee');

        if ($v->fails()) {
            $this->error('Validation failed.', 422, $v->errors());
        }

        try {
            $model = new DocumentType();
            if ($model->findByName($v->get('name'))) {
                $this->error('Validation failed.', 422,
                    ['name' => 'A document type with this name already exists.']);
            }

            $newId = $model->insert([
                'name'           => $v->get('name'),
                'description'    => $v->get('description'),
                'processing_fee' => number_format((float)$v->get('processing_fee'), 2, '.', ''),
                'is_active'      => 1,
                'created_by'     => $adminId,
            ]);

            AuditLogService::log($adminId, 'admin', AuditLog::ACTION_DOC_TYPE_CREATED, 'documents',
                'document_types', $newId, null,
                ['name' => $v->get('name')],
                "Document type '{$v->get('name')}' created via API by admin ID {$adminId}");

            $created = $model->find($newId);
            $this->json($created, 'Document type created.', 201);

        } catch (\Throwable $e) {
            Logger::error('DocumentTypeApiController::store: ' . $e->getMessage());
            $this->error('Failed to create document type.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/document-types/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function update(string $id): void
    {
        $adminId = $this->requireRole('admin');
        $typeId  = (int)$id;

        $data = $this->body();
        $v    = new Validator($data);
        $v->required(['name','description','processing_fee'])
          ->maxLength('name', 150)
          ->nonNegativeDecimal('processing_fee');

        if ($v->fails()) {
            $this->error('Validation failed.', 422, $v->errors());
        }

        try {
            $model = new DocumentType();
            $dt    = $model->find($typeId);
            if (!$dt) {
                $this->error('Document type not found.', 404);
            }

            if ($model->findByName($v->get('name'), $typeId)) {
                $this->error('Validation failed.', 422,
                    ['name' => 'Another document type with this name already exists.']);
            }

            $newFee = number_format((float)$v->get('processing_fee'), 2, '.', '');
            $model->update($typeId, [
                'name'           => $v->get('name'),
                'description'    => $v->get('description'),
                'processing_fee' => $newFee,
                'updated_by'     => $adminId,
            ]);

            AuditLogService::log($adminId, 'admin', AuditLog::ACTION_DOC_TYPE_MODIFIED, 'documents',
                'document_types', $typeId,
                ['name' => $dt['name'], 'processing_fee' => $dt['processing_fee']],
                ['name' => $v->get('name'), 'processing_fee' => $newFee],
                "Document type ID {$typeId} updated via API by admin ID {$adminId}");

            $this->json($model->find($typeId), 'Document type updated.');

        } catch (\Throwable $e) {
            Logger::error('DocumentTypeApiController::update: ' . $e->getMessage());
            $this->error('Failed to update document type.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PATCH /api/document-types/{id}/status
    // ─────────────────────────────────────────────────────────────────────────

    public function toggleStatus(string $id): void
    {
        $adminId = $this->requireRole('admin');
        $typeId  = (int)$id;

        try {
            $model = new DocumentType();
            $dt    = $model->find($typeId);
            if (!$dt) {
                $this->error('Document type not found.', 404);
            }

            $newActive = $dt['is_active'] ? 0 : 1;
            $verb      = $newActive ? 'activated' : 'deactivated';

            $model->update($typeId, ['is_active' => $newActive, 'updated_by' => $adminId]);

            AuditLogService::log($adminId, 'admin', AuditLog::ACTION_DOC_TYPE_MODIFIED, 'documents',
                'document_types', $typeId,
                ['is_active' => (int)$dt['is_active']],
                ['is_active' => $newActive],
                "Document type ID {$typeId} {$verb} via API by admin ID {$adminId}");

            $this->json(['id' => $typeId, 'is_active' => $newActive],
                "Document type {$verb}.");

        } catch (\Throwable $e) {
            Logger::error('DocumentTypeApiController::toggleStatus: ' . $e->getMessage());
            $this->error('Failed to update document type status.', 500);
        }
    }
}
