<?php

namespace Emunicipal\Controllers\Api;

use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Validators\Validator;
use Emunicipal\Models\DocumentRequirement;
use Emunicipal\Models\DocumentType;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Core\Logger;

/**
 * RequirementApiController — Requirements resource API.
 *
 * GET   /api/document-types/{typeId}/requirements  — Any auth: active requirements
 * POST  /api/document-types/{typeId}/requirements  — Admin: create
 * PUT   /api/requirements/{id}                     — Admin: update
 * PATCH /api/requirements/{id}/status              — Admin: toggle active
 *
 * Per Req 15.1, Req 5.6–5.8.
 */
class RequirementApiController extends BaseApiController
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/document-types/{typeId}/requirements
    // ─────────────────────────────────────────────────────────────────────────

    public function index(string $typeId): void
    {
        $this->requireAuth();
        $dtId = (int)$typeId;

        try {
            $dt = (new DocumentType())->find($dtId);
            if (!$dt) {
                $this->error('Document type not found.', 404);
            }

            // Return active requirements to all; admin sees all
            $role = AuthMiddleware::getRole();
            $reqs = $role === 'admin'
                ? (new DocumentRequirement())->allForDocumentType($dtId)
                : (new DocumentRequirement())->activeForDocumentType($dtId);

            $this->json($reqs);

        } catch (\Throwable $e) {
            Logger::error('RequirementApiController::index: ' . $e->getMessage());
            $this->error('Failed to load requirements.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/document-types/{typeId}/requirements
    // ─────────────────────────────────────────────────────────────────────────

    public function store(string $typeId): void
    {
        $adminId = $this->requireRole('admin');
        $dtId    = (int)$typeId;

        try {
            $dt = (new DocumentType())->find($dtId);
            if (!$dt) {
                $this->error('Document type not found.', 404);
            }
        } catch (\Throwable $e) {
            $this->error('Document type not found.', 404);
        }

        $data = $this->body();
        $v    = new Validator($data);
        $v->required(['name'])->maxLength('name', 200);

        $sortRaw = trim((string)($data['sort_order'] ?? '0'));
        if ($sortRaw !== '' && (!ctype_digit($sortRaw) || (int)$sortRaw < 0)) {
            $v->addError('sort_order', 'Sort order must be a non-negative integer.');
        }

        if ($v->fails()) {
            $this->error('Validation failed.', 422, $v->errors());
        }

        $sortOrder  = ctype_digit($sortRaw) ? (int)$sortRaw : 0;
        $isRequired = isset($data['is_required']) ? ((bool)$data['is_required'] ? 1 : 0) : 1;

        try {
            $model = new DocumentRequirement();
            $newId = $model->insert([
                'document_type_id' => $dtId,
                'name'             => $v->get('name'),
                'description'      => $v->get('description') ?: null,
                'is_required'      => $isRequired,
                'sort_order'       => $sortOrder,
                'is_active'        => 1,
                'created_by'       => $adminId,
            ]);

            AuditLogService::log($adminId, 'admin', AuditLog::ACTION_REQ_CREATED, 'documents',
                'document_requirements', $newId, null,
                ['name' => $v->get('name'), 'document_type_id' => $dtId],
                "Requirement '{$v->get('name')}' created via API for doc type {$dtId}");

            $this->json($model->find($newId), 'Requirement created.', 201);

        } catch (\Throwable $e) {
            Logger::error('RequirementApiController::store: ' . $e->getMessage());
            $this->error('Failed to create requirement.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/requirements/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function update(string $id): void
    {
        $adminId = $this->requireRole('admin');
        $reqId   = (int)$id;

        $data = $this->body();
        $v    = new Validator($data);
        $v->required(['name'])->maxLength('name', 200);

        $sortRaw = trim((string)($data['sort_order'] ?? '0'));
        if ($sortRaw !== '' && (!ctype_digit($sortRaw) || (int)$sortRaw < 0)) {
            $v->addError('sort_order', 'Sort order must be a non-negative integer.');
        }

        if ($v->fails()) {
            $this->error('Validation failed.', 422, $v->errors());
        }

        try {
            $model = new DocumentRequirement();
            $req   = $model->find($reqId);
            if (!$req) {
                $this->error('Requirement not found.', 404);
            }

            $sortOrder  = ctype_digit($sortRaw) ? (int)$sortRaw : (int)$req['sort_order'];
            $isRequired = isset($data['is_required']) ? ((bool)$data['is_required'] ? 1 : 0) : (int)$req['is_required'];

            $model->update($reqId, [
                'name'        => $v->get('name'),
                'description' => $v->get('description') ?: null,
                'is_required' => $isRequired,
                'sort_order'  => $sortOrder,
                'updated_by'  => $adminId,
            ]);

            AuditLogService::log($adminId, 'admin', AuditLog::ACTION_REQ_MODIFIED, 'documents',
                'document_requirements', $reqId,
                ['name' => $req['name']],
                ['name' => $v->get('name')],
                "Requirement ID {$reqId} updated via API by admin ID {$adminId}");

            $this->json($model->find($reqId), 'Requirement updated.');

        } catch (\Throwable $e) {
            Logger::error('RequirementApiController::update: ' . $e->getMessage());
            $this->error('Failed to update requirement.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PATCH /api/requirements/{id}/status
    // ─────────────────────────────────────────────────────────────────────────

    public function toggleStatus(string $id): void
    {
        $adminId = $this->requireRole('admin');
        $reqId   = (int)$id;

        try {
            $model = new DocumentRequirement();
            $req   = $model->find($reqId);
            if (!$req) {
                $this->error('Requirement not found.', 404);
            }

            $newActive = $req['is_active'] ? 0 : 1;
            $verb      = $newActive ? 'activated' : 'deactivated';

            $model->update($reqId, ['is_active' => $newActive, 'updated_by' => $adminId]);

            AuditLogService::log($adminId, 'admin', AuditLog::ACTION_REQ_MODIFIED, 'documents',
                'document_requirements', $reqId,
                ['is_active' => (int)$req['is_active']],
                ['is_active' => $newActive],
                "Requirement '{$req['name']}' {$verb} via API by admin ID {$adminId}");

            $this->json(['id' => $reqId, 'is_active' => $newActive],
                "Requirement {$verb}.");

        } catch (\Throwable $e) {
            Logger::error('RequirementApiController::toggleStatus: ' . $e->getMessage());
            $this->error('Failed to update requirement status.', 500);
        }
    }
}
