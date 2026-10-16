<?php

namespace Emunicipal\Controllers\Api;

use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Validators\Validator;
use Emunicipal\Models\Request as RequestModel;
use Emunicipal\Models\RequestDocument;
use Emunicipal\Models\RequestStatusHistory;
use Emunicipal\Models\DocumentType;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Services\FileUploadService;
use Emunicipal\Services\NotificationService;
use Emunicipal\Core\Database;
use Emunicipal\Core\Logger;

/**
 * RequestApiController — Requests resource API.
 *
 * GET    /api/requests              — Staff/Admin: all requests (filterable, paginated)
 * GET    /api/requests/my           — Resident: own requests
 * GET    /api/requests/{id}         — Resident (own) / Staff / Admin: detail
 * POST   /api/requests              — Resident: submit new request
 * POST   /api/requests/{id}/documents — Resident (own): upload additional docs
 * PATCH  /api/requests/{id}/cancel  — Resident (own): cancel
 *
 * Per Req 15.1, Req 6 (submission), Req 8 (tracking), Req 8.6–8.7 (additional upload).
 * Reuses existing Request::findAllForStaff(), insertWithReferenceNumber(), allowedTransitions().
 * Reuses FileUploadService for all file security (Req 14.5–14.7, Req 18).
 */
class RequestApiController extends BaseApiController
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/requests  — Staff / Admin
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): void
    {
        $this->requireStaffOrAdmin();

        [$page, $perPage] = $this->pagination(20);

        $filters = [
            'search'           => $this->query('search', ''),
            'status'           => $this->query('status', ''),
            'document_type_id' => $this->query('document_type_id', ''),
            'date_from'        => $this->query('date_from', ''),
            'date_to'          => $this->query('date_to', ''),
            'page'             => $page,
            'per_page'         => $perPage,
        ];

        // Remove empty filters
        $filters = array_filter($filters, fn($v) => $v !== '' && $v !== null);
        $filters['page']     = $page;
        $filters['per_page'] = $perPage;

        try {
            $model    = new RequestModel();
            $requests = $model->findAllForStaff($filters);
            $total    = $model->lastTotalCount;

            $this->json($this->paginated($requests, $total, $page, $perPage));

        } catch (\Throwable $e) {
            Logger::error('RequestApiController::index: ' . $e->getMessage());
            $this->error('Failed to load requests.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/requests/my  — Resident
    // ─────────────────────────────────────────────────────────────────────────

    public function my(): void
    {
        $userId = $this->requireRole('resident');

        try {
            $requests = (new RequestModel())->findByUserId($userId);
            $this->json($requests);
        } catch (\Throwable $e) {
            Logger::error('RequestApiController::my: ' . $e->getMessage());
            $this->error('Failed to load requests.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/requests/{id}  — Resident (own) / Staff / Admin
    // ─────────────────────────────────────────────────────────────────────────

    public function show(string $id): void
    {
        $authId = $this->requireAuth();
        $reqId  = (int)$id;

        try {
            $model   = new RequestModel();
            $request = $model->findByIdForStaff($reqId);

            if (!$request) {
                $this->error('Request not found.', 404);
            }

            // Residents can only see their own requests (Req 8.8)
            if (AuthMiddleware::getRole() === 'resident' && (int)$request['user_id'] !== $authId) {
                $this->error('Request not found.', 404);
            }

            // Attach documents and history
            $request['documents'] = (new RequestDocument())->forRequest($reqId);
            $request['history']   = (new RequestStatusHistory())->forRequest($reqId);

            $this->json($request);

        } catch (\Throwable $e) {
            Logger::error('RequestApiController::show: ' . $e->getMessage());
            $this->error('Failed to load request.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/requests  — Resident: submit new request
    // ─────────────────────────────────────────────────────────────────────────

    public function store(): void
    {
        $userId = $this->requireRole('resident');

        $data = $this->body();
        $v    = new Validator($data);
        $v->required(['document_type_id','purpose','copies_requested'])
          ->positiveInteger('copies_requested')
          ->maxLength('purpose', 1000);

        if ($v->fails()) {
            $this->error('Validation failed.', 422, $v->errors());
        }

        $docTypeId = (int)$v->get('document_type_id');
        $copies    = (int)$v->get('copies_requested');

        try {
            $dtModel = new DocumentType();
            $docType = $dtModel->find($docTypeId);
        } catch (\Throwable $e) {
            $this->error('A server error occurred.', 500);
        }

        if (!$docType || !(bool)$docType['is_active']) {
            $this->error('Validation failed.', 422,
                ['document_type_id' => 'The selected document type is not available.']);
        }

        // Handle optional file uploads (multipart)
        $validFiles    = [];
        $storageRoot   = $this->resolveStorageRoot();
        $uploadService = new FileUploadService();

        $uploadedFiles = $_FILES['documents'] ?? [];
        if (!empty($uploadedFiles['name']) && is_array($uploadedFiles['name'])) {
            $count = count($uploadedFiles['name']);
            if ($count > FileUploadService::MAX_FILES) {
                $this->error('Too many files. Maximum ' . FileUploadService::MAX_FILES . ' files allowed.', 422);
            }
            for ($i = 0; $i < $count; $i++) {
                if (empty($uploadedFiles['name'][$i]) || $uploadedFiles['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                try {
                    $validFiles[] = $uploadService->store([
                        'name'     => $uploadedFiles['name'][$i],
                        'tmp_name' => $uploadedFiles['tmp_name'][$i],
                        'size'     => $uploadedFiles['size'][$i],
                        'error'    => $uploadedFiles['error'][$i],
                    ], $storageRoot);
                } catch (\InvalidArgumentException $fe) {
                    // Cleanup already stored files
                    foreach ($validFiles as $vf) {
                        $uploadService->delete($storageRoot, $vf['storage_path']);
                    }
                    $this->error($fe->getMessage(), 422);
                } catch (\RuntimeException $fe) {
                    foreach ($validFiles as $vf) {
                        $uploadService->delete($storageRoot, $vf['storage_path']);
                    }
                    Logger::error('API request file store error: ' . $fe->getMessage());
                    $this->error('File could not be stored. Please try again.', 500);
                }
            }
        }

        try {
            $requestModel = new RequestModel();

            $result = $requestModel->insertWithReferenceNumber(
                [
                    'user_id'          => $userId,
                    'document_type_id' => $docTypeId,
                    'purpose'          => $v->get('purpose'),
                    'copies_requested' => $copies,
                ],
                ['previous_status' => null, 'changed_by' => null, 'remark' => null]
            );

            $requestId       = $result['id'];
            $referenceNumber = $result['reference_number'];

            if (!empty($validFiles)) {
                $rdModel = new RequestDocument();
                foreach ($validFiles as $fileData) {
                    $rdModel->insert([
                        'request_id'        => $requestId,
                        'uploaded_by'       => $userId,
                        'original_filename' => $fileData['original_filename'],
                        'stored_filename'   => $fileData['stored_filename'],
                        'storage_path'      => $fileData['storage_path'],
                        'mime_type'         => $fileData['mime_type'],
                        'file_size_bytes'   => $fileData['file_size_bytes'],
                    ]);
                    AuditLogService::log($userId, 'resident', AuditLog::ACTION_FILE_UPLOADED, 'files',
                        'request_documents', null, null,
                        ['request_id' => $requestId, 'filename' => $fileData['original_filename']],
                        "File '{$fileData['original_filename']}' uploaded via API for {$referenceNumber}");
                }
            }

            AuditLogService::log($userId, 'resident', AuditLog::ACTION_REQUEST_SUBMITTED, 'requests',
                'requests', $requestId, null,
                ['reference_number' => $referenceNumber],
                "Request {$referenceNumber} submitted via API by resident ID {$userId}");

            NotificationService::requestSubmitted($userId, $requestId, $referenceNumber,
                $docType['name'] ?? '');

            NotificationService::newRequestForStaffAdmin($requestId, $referenceNumber,
                $docType['name'] ?? '',
                trim((AuthMiddleware::firstName() ?? '') . ' ' . (AuthMiddleware::lastName() ?? '')));

            $created = $requestModel->findByIdForStaff($requestId);
            $this->json($created, 'Request submitted.', 201);

        } catch (\Throwable $e) {
            Logger::error('RequestApiController::store: ' . $e->getMessage());
            foreach ($validFiles as $vf) {
                $uploadService->delete($storageRoot, $vf['storage_path']);
            }
            $this->error('Failed to submit request.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/requests/{id}/documents  — Resident (own): additional upload
    // Replicates uploadAdditionalDocuments logic (Phase 1) for the API.
    // ─────────────────────────────────────────────────────────────────────────

    public function uploadDocuments(string $id): void
    {
        $userId = $this->requireRole('resident');
        $reqId  = (int)$id;

        try {
            $requestModel = new RequestModel();
            $request      = $requestModel->find($reqId);
        } catch (\Throwable $e) {
            $this->error('Failed to load request.', 500);
        }

        if (!$request || (int)$request['user_id'] !== $userId) {
            $this->error('Request not found.', 404);
        }

        if ($request['status'] !== RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED) {
            $this->error(
                'Additional documents can only be uploaded when the request requires additional information.',
                422
            );
        }

        $uploadedFiles = $_FILES['documents'] ?? [];
        $hasFiles = !empty($uploadedFiles['name']) && is_array($uploadedFiles['name'])
            && count(array_filter($uploadedFiles['name'])) > 0;

        if (!$hasFiles) {
            $this->error('Please provide at least one file.', 422);
        }

        $storageRoot   = $this->resolveStorageRoot();
        $uploadService = new FileUploadService();
        $validFiles    = [];
        $count         = count($uploadedFiles['name']);

        if ($count > FileUploadService::MAX_FILES) {
            $this->error('Too many files. Maximum ' . FileUploadService::MAX_FILES . ' allowed.', 422);
        }

        for ($i = 0; $i < $count; $i++) {
            if (empty($uploadedFiles['name'][$i]) || $uploadedFiles['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            try {
                $validFiles[] = $uploadService->store([
                    'name'     => $uploadedFiles['name'][$i],
                    'tmp_name' => $uploadedFiles['tmp_name'][$i],
                    'size'     => $uploadedFiles['size'][$i],
                    'error'    => $uploadedFiles['error'][$i],
                ], $storageRoot);
            } catch (\InvalidArgumentException $fe) {
                foreach ($validFiles as $vf) $uploadService->delete($storageRoot, $vf['storage_path']);
                $this->error($fe->getMessage(), 422);
            } catch (\RuntimeException $fe) {
                foreach ($validFiles as $vf) $uploadService->delete($storageRoot, $vf['storage_path']);
                $this->error('A file could not be stored.', 500);
            }
        }

        if (empty($validFiles)) {
            $this->error('No valid files were provided.', 422);
        }

        try {
            $rdModel         = new RequestDocument();
            $referenceNumber = $request['reference_number'];
            foreach ($validFiles as $fileData) {
                $rdModel->insert([
                    'request_id'        => $reqId,
                    'uploaded_by'       => $userId,
                    'original_filename' => $fileData['original_filename'],
                    'stored_filename'   => $fileData['stored_filename'],
                    'storage_path'      => $fileData['storage_path'],
                    'mime_type'         => $fileData['mime_type'],
                    'file_size_bytes'   => $fileData['file_size_bytes'],
                ]);
                AuditLogService::log($userId, 'resident', AuditLog::ACTION_FILE_UPLOADED, 'files',
                    'request_documents', null, null,
                    ['request_id' => $reqId, 'filename' => $fileData['original_filename']],
                    "Additional doc '{$fileData['original_filename']}' uploaded via API for {$referenceNumber}");
            }
        } catch (\Throwable $e) {
            foreach ($validFiles as $vf) $uploadService->delete($storageRoot, $vf['storage_path']);
            Logger::error('RequestApiController::uploadDocuments persist: ' . $e->getMessage());
            $this->error('Files could not be saved. Please try again.', 500);
        }

        // Staff/Admin notification — non-fatal (Req 25.14)
        try {
            $dt          = (new DocumentType())->find((int)($request['document_type_id'] ?? 0));
            $docTypeName = $dt['name'] ?? '';
        } catch (\Throwable $ignored) { $docTypeName = ''; }

        NotificationService::additionalInfoSubmittedForStaffAdmin(
            $reqId,
            $request['reference_number'],
            trim((AuthMiddleware::firstName() ?? '') . ' ' . (AuthMiddleware::lastName() ?? '')),
            $docTypeName
        );

        $fileCount = count($validFiles);
        $this->json(['uploaded' => $fileCount],
            "{$fileCount} additional document(s) uploaded successfully.", 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PATCH /api/requests/{id}/cancel  — Resident (own)
    // ─────────────────────────────────────────────────────────────────────────

    public function cancel(string $id): void
    {
        $userId = $this->requireRole('resident');
        $reqId  = (int)$id;

        try {
            $requestModel = new RequestModel();
            $request      = $requestModel->find($reqId);

            if (!$request || (int)$request['user_id'] !== $userId) {
                $this->error('Request not found.', 404);
            }

            if (!in_array($request['status'], RequestModel::RESIDENT_CANCELLABLE_STATUSES, true)) {
                $this->error(
                    "This request cannot be cancelled at its current status ('{$request['status']}').",
                    422
                );
            }

            $reason = trim((string)($this->body()['cancellation_reason'] ?? ''));

            $db = Database::getInstance();
            $db->beginTransaction();
            try {
                $requestModel->update($reqId, [
                    'status'              => RequestModel::STATUS_CANCELLED,
                    'cancellation_reason' => $reason ?: null,
                    'cancelled_by'        => $userId,
                ]);
                (new RequestStatusHistory())->insert([
                    'request_id'      => $reqId,
                    'previous_status' => $request['status'],
                    'new_status'      => RequestModel::STATUS_CANCELLED,
                    'changed_by'      => $userId,
                    'remark'          => $reason ?: null,
                ]);
                $db->commit();
            } catch (\Throwable $inner) {
                $db->rollBack();
                throw $inner;
            }

            AuditLogService::log($userId, 'resident', AuditLog::ACTION_STATUS_CHANGED, 'requests',
                'requests', $reqId,
                ['status' => $request['status']],
                ['status' => RequestModel::STATUS_CANCELLED],
                "Request {$request['reference_number']} cancelled via API by resident ID {$userId}");

            NotificationService::cancelled($userId, $reqId, $request['reference_number']);

            // Notify Staff/Admin
            try {
                $dt = (new DocumentType())->find((int)($request['document_type_id'] ?? 0));
                $docTypeName = $dt['name'] ?? '';
            } catch (\Throwable $ignored) { $docTypeName = ''; }

            NotificationService::residentCancelledForStaffAdmin($reqId, $request['reference_number'],
                trim((AuthMiddleware::firstName() ?? '') . ' ' . (AuthMiddleware::lastName() ?? '')),
                $docTypeName);

            $updated = $requestModel->find($reqId);
            $this->json($updated, 'Request cancelled.');

        } catch (\Throwable $e) {
            Logger::error('RequestApiController::cancel: ' . $e->getMessage());
            $this->error('Failed to cancel request.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveStorageRoot(): string
    {
        $basePath   = dirname(__DIR__, 3);
        $uploadPath = $_ENV['UPLOAD_PATH'] ?? 'storage/uploads';
        $uploadPath = ltrim(str_replace(['../', '..\\'], '', $uploadPath), '/\\');
        return $basePath . DIRECTORY_SEPARATOR . $uploadPath;
    }
}
