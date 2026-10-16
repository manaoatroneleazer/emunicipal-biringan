<?php

namespace Emunicipal\Controllers\Admin;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Validators\Validator;
use Emunicipal\Models\DocumentRequirement;
use Emunicipal\Models\DocumentType;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Core\Logger;

/**
 * RequirementController — Admin document requirement management.
 *
 * Implements Req 5.6–5.8 (Requirement Management):
 *  5.6  — Requirements stored in document_requirements table
 *  5.7  — Admin can add, edit, activate, deactivate per document type
 *  5.8  — Deactivation removes requirement from new-request checklist;
 *          existing request records are not affected
 *
 * Routes (defined in routes/web.php — all use {typeId} + {id}):
 *  GET  /admin/document-types/{typeId}/requirements
 *  GET  /admin/document-types/{typeId}/requirements/create
 *  POST /admin/document-types/{typeId}/requirements
 *  GET  /admin/document-types/{typeId}/requirements/{id}/edit
 *  POST /admin/document-types/{typeId}/requirements/{id}
 *  POST /admin/document-types/{typeId}/requirements/{id}/toggle
 */
class RequirementController extends BaseController
{
    // -------------------------------------------------------------------------
    // LIST
    // -------------------------------------------------------------------------

    /**
     * GET /admin/document-types/{typeId}/requirements
     */
    public function index(string $typeId): void
    {
        AuthMiddleware::requireRole('admin');

        $docType      = $this->findDocTypeOrRedirect((int)$typeId);
        $requirements = [];

        try {
            $requirements = (new DocumentRequirement())->allForDocumentType((int)$typeId);
        } catch (\Throwable $e) {
            Logger::error('Requirement index error: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load requirements. Please try again.');
        }

        $this->renderWithLayout('admin/document-requirements/index', [
            'title'        => 'Requirements — eMunicipal',
            'activePage'   => 'document-types',
            'docType'      => $docType,
            'requirements' => $requirements,
        ], 'layouts/auth');
    }

    // -------------------------------------------------------------------------
    // CREATE
    // -------------------------------------------------------------------------

    /**
     * GET /admin/document-types/{typeId}/requirements/create
     */
    public function create(string $typeId): void
    {
        AuthMiddleware::requireRole('admin');

        $docType = $this->findDocTypeOrRedirect((int)$typeId);

        $this->renderWithLayout('admin/document-requirements/create', [
            'title'      => 'Add Requirement — eMunicipal',
            'activePage' => 'document-types',
            'docType'    => $docType,
            'csrfToken'  => SecurityHelper::getCsrfToken(),
            'errors'     => SessionHelper::getFlash('req_errors', []),
            'old'        => SessionHelper::getFlash('req_old', []),
        ], 'layouts/auth');
    }

    /**
     * POST /admin/document-types/{typeId}/requirements
     */
    public function store(string $typeId): void
    {
        AuthMiddleware::requireRole('admin');

        $docTypeId = (int)$typeId;
        $docType   = $this->findDocTypeOrRedirect($docTypeId);

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('req_errors', ['form' => 'Invalid security token. Please try again.']);
            UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements/create");
        }

        $v = new Validator($_POST);
        $v->required(['name'])
          ->maxLength('name', 200);

        // Sort order: optional, defaults to 0; if supplied must be integer >= 0
        $sortRaw = trim($_POST['sort_order'] ?? '0');
        if ($sortRaw !== '' && (!ctype_digit($sortRaw) || (int)$sortRaw < 0)) {
            $v->addError('sort_order', 'Sort order must be a whole number of 0 or greater.');
        }
        $sortOrder = ($sortRaw === '' || !ctype_digit($sortRaw)) ? 0 : (int)$sortRaw;

        $old = [
            'name'        => $v->get('name', ''),
            'description' => $v->get('description', ''),
            'sort_order'  => $sortRaw,
            'is_required' => $_POST['is_required'] ?? '1',
        ];

        if ($v->fails()) {
            SessionHelper::flash('req_errors', $v->errors());
            SessionHelper::flash('req_old', $old);
            UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements/create");
        }

        try {
            $userId = AuthMiddleware::userId();

            $newId = (new DocumentRequirement())->insert([
                'document_type_id' => $docTypeId,
                'name'             => $v->get('name'),
                'description'      => $v->get('description', '') ?: null,
                'is_required'      => (($_POST['is_required'] ?? '1') === '1') ? 1 : 0,
                'sort_order'       => $sortOrder,
                'is_active'        => 1,
                'created_by'       => $userId,
            ]);

            // Audit log (Req 13.1)
            AuditLogService::log(
                $userId, 'admin',
                AuditLog::ACTION_REQ_CREATED,
                'documents',
                'document_requirements', $newId,
                null,
                ['name' => $v->get('name'), 'document_type_id' => $docTypeId],
                "Requirement '{$v->get('name')}' created for document type ID {$docTypeId} by admin ID {$userId}"
            );

            $name = htmlspecialchars($v->get('name'), ENT_QUOTES, 'UTF-8');
            SessionHelper::flash('success', "Requirement '{$name}' has been added.");
            UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements");

        } catch (\Throwable $e) {
            Logger::error('Requirement store error: ' . $e->getMessage());
            SessionHelper::flash('req_errors', ['form' => 'Unable to save the requirement. Please try again.']);
            SessionHelper::flash('req_old', $old);
            UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements/create");
        }
    }

    // -------------------------------------------------------------------------
    // EDIT
    // -------------------------------------------------------------------------

    /**
     * GET /admin/document-types/{typeId}/requirements/{id}/edit
     */
    public function edit(string $typeId, string $id): void
    {
        AuthMiddleware::requireRole('admin');

        $docTypeId   = (int)$typeId;
        $requirementId = (int)$id;
        $docType     = $this->findDocTypeOrRedirect($docTypeId);
        $requirement = $this->findRequirementOrRedirect($requirementId, $docTypeId);

        $this->renderWithLayout('admin/document-requirements/edit', [
            'title'       => 'Edit Requirement — eMunicipal',
            'activePage'  => 'document-types',
            'docType'     => $docType,
            'requirement' => $requirement,
            'csrfToken'   => SecurityHelper::getCsrfToken(),
            'errors'      => SessionHelper::getFlash('req_errors', []),
            'old'         => SessionHelper::getFlash('req_old', []),
        ], 'layouts/auth');
    }

    /**
     * POST /admin/document-types/{typeId}/requirements/{id}
     */
    public function update(string $typeId, string $id): void
    {
        AuthMiddleware::requireRole('admin');

        $docTypeId     = (int)$typeId;
        $requirementId = (int)$id;
        $docType       = $this->findDocTypeOrRedirect($docTypeId);
        $requirement   = $this->findRequirementOrRedirect($requirementId, $docTypeId);

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('req_errors', ['form' => 'Invalid security token. Please try again.']);
            UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements/{$requirementId}/edit");
        }

        $v = new Validator($_POST);
        $v->required(['name'])
          ->maxLength('name', 200);

        $sortRaw = trim($_POST['sort_order'] ?? '0');
        if ($sortRaw !== '' && (!ctype_digit($sortRaw) || (int)$sortRaw < 0)) {
            $v->addError('sort_order', 'Sort order must be a whole number of 0 or greater.');
        }
        $sortOrder = ($sortRaw === '' || !ctype_digit($sortRaw)) ? 0 : (int)$sortRaw;

        $old = [
            'name'        => $v->get('name', ''),
            'description' => $v->get('description', ''),
            'sort_order'  => $sortRaw,
            'is_required' => $_POST['is_required'] ?? '1',
        ];

        if ($v->fails()) {
            SessionHelper::flash('req_errors', $v->errors());
            SessionHelper::flash('req_old', $old);
            UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements/{$requirementId}/edit");
        }

        try {
            $userId = AuthMiddleware::userId();

            // Build change snapshot for audit (Req 13.2)
            $oldValues = [];
            $newValues = [];
            if ($requirement['name'] !== $v->get('name')) {
                $oldValues['name'] = $requirement['name'];
                $newValues['name'] = $v->get('name');
            }
            $newSortStr = (string)$sortOrder;
            $oldSortStr = (string)(int)$requirement['sort_order'];
            if ($oldSortStr !== $newSortStr) {
                $oldValues['sort_order'] = $oldSortStr;
                $newValues['sort_order'] = $newSortStr;
            }
            $newIsRequired = (($_POST['is_required'] ?? '1') === '1') ? 1 : 0;
            if ((int)$requirement['is_required'] !== $newIsRequired) {
                $oldValues['is_required'] = (int)$requirement['is_required'];
                $newValues['is_required'] = $newIsRequired;
            }

            (new DocumentRequirement())->update($requirementId, [
                'name'        => $v->get('name'),
                'description' => $v->get('description', '') ?: null,
                'is_required' => $newIsRequired,
                'sort_order'  => $sortOrder,
                'updated_by'  => $userId,
            ]);

            AuditLogService::log(
                $userId, 'admin',
                AuditLog::ACTION_REQ_MODIFIED,
                'documents',
                'document_requirements', $requirementId,
                empty($oldValues) ? null : $oldValues,
                empty($newValues) ? null : $newValues,
                "Requirement ID {$requirementId} updated by admin ID {$userId}"
            );

            $name = htmlspecialchars($v->get('name'), ENT_QUOTES, 'UTF-8');
            SessionHelper::flash('success', "Requirement '{$name}' has been updated.");
            UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements");

        } catch (\Throwable $e) {
            Logger::error('Requirement update error: ' . $e->getMessage());
            SessionHelper::flash('req_errors', ['form' => 'Unable to update the requirement. Please try again.']);
            SessionHelper::flash('req_old', $old);
            UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements/{$requirementId}/edit");
        }
    }

    // -------------------------------------------------------------------------
    // TOGGLE ACTIVE / INACTIVE
    // -------------------------------------------------------------------------

    /**
     * POST /admin/document-types/{typeId}/requirements/{id}/toggle
     * Activate or deactivate a requirement (Req 5.8).
     */
    public function toggleActive(string $typeId, string $id): void
    {
        AuthMiddleware::requireRole('admin');

        $docTypeId     = (int)$typeId;
        $requirementId = (int)$id;

        // Verify parent type exists (error redirects there)
        $this->findDocTypeOrRedirect($docTypeId);
        $requirement = $this->findRequirementOrRedirect($requirementId, $docTypeId);

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('error', 'Invalid security token.');
            UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements");
        }

        try {
            $userId            = AuthMiddleware::userId();
            $isCurrentlyActive = (bool)$requirement['is_active'];
            $newActive         = $isCurrentlyActive ? 0 : 1;
            $verb              = $newActive ? 'activated' : 'deactivated';

            (new DocumentRequirement())->update($requirementId, [
                'is_active'  => $newActive,
                'updated_by' => $userId,
            ]);

            AuditLogService::log(
                $userId, 'admin',
                AuditLog::ACTION_REQ_MODIFIED,
                'documents',
                'document_requirements', $requirementId,
                ['is_active' => (int)$isCurrentlyActive],
                ['is_active' => $newActive],
                "Requirement '{$requirement['name']}' {$verb} by admin ID {$userId}"
            );

            $name = htmlspecialchars($requirement['name'], ENT_QUOTES, 'UTF-8');
            SessionHelper::flash('success', "Requirement '{$name}' has been {$verb}.");

        } catch (\Throwable $e) {
            Logger::error('Requirement toggle error: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to change the requirement status. Please try again.');
        }

        UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements");
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    /**
     * Load a document type by ID, or redirect to the document-types list on failure.
     */
    private function findDocTypeOrRedirect(int $id): array
    {
        try {
            $record = (new DocumentType())->find($id);
        } catch (\Throwable $e) {
            Logger::error('RequirementController findDocType DB error: ' . $e->getMessage());
            SessionHelper::flash('error', 'A database error occurred. Please try again.');
            UrlHelper::redirect('/admin/document-types');
        }

        if (!$record) {
            SessionHelper::flash('error', 'Document type not found.');
            UrlHelper::redirect('/admin/document-types');
        }

        return $record;
    }

    /**
     * Load a requirement by ID, verifying it belongs to the given document type.
     * Redirects to the requirements list on failure.
     */
    private function findRequirementOrRedirect(int $id, int $docTypeId): array
    {
        try {
            $record = (new DocumentRequirement())->find($id);
        } catch (\Throwable $e) {
            Logger::error('RequirementController findRequirement DB error: ' . $e->getMessage());
            SessionHelper::flash('error', 'A database error occurred. Please try again.');
            UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements");
        }

        if (!$record || (int)$record['document_type_id'] !== $docTypeId) {
            SessionHelper::flash('error', 'Requirement not found.');
            UrlHelper::redirect("/admin/document-types/{$docTypeId}/requirements");
        }

        return $record;
    }
}
