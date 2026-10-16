<?php

namespace Emunicipal\Controllers\Resident;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Validators\Validator;
use Emunicipal\Models\Request as RequestModel;
use Emunicipal\Models\RequestDocument;
use Emunicipal\Models\RequestStatusHistory;
use Emunicipal\Models\DocumentType;
use Emunicipal\Models\DocumentRequirement;
use Emunicipal\Models\Payment;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Services\FileUploadService;
use Emunicipal\Services\EmailService;
use Emunicipal\Services\NotificationService;
use Emunicipal\Core\Logger;

/**
 * RequestController — Resident document request management.
 *
 * Implements:
 *  Req 6  — Request submission, file upload, reference number, PENDING status
 *  Req 8  — My requests list, request detail, request tracking, cancel
 *  Req 13 — Audit logging
 *
 * File upload security per:
 *  Req 6.4 — MIME allowlist: PDF, JPEG, PNG
 *  Req 6.5 — MIME validated via PHP finfo (server-side, not client declaration)
 *  Req 6.6 — Max 5 MB per file
 *  Req 6.7 — Secure UUID filename; stored outside web root
 *
 * Routes (defined in routes/web.php):
 *  GET  /resident/requests/new          newRequest()
 *  POST /resident/requests              store()
 *  GET  /resident/requests              index()        [Module H]
 *  GET  /resident/requests/{id}         show()         [Module H]
 *  POST /resident/requests/{id}/cancel  cancel()       [Module H]
 *  GET  /resident/track                 track()        [Module H]
 */
class RequestController extends BaseController
{
    // -------------------------------------------------------------------------
    // NEW REQUEST — Step 1: select document type + show requirements
    // -------------------------------------------------------------------------

    /**
     * GET /resident/requests/new
     * Display the new request form.
     * Pre-selects a document type when ?type_id=N is supplied.
     */
    public function newRequest(): void
    {
        AuthMiddleware::requireRole('resident');

        $documentTypes = [];
        $selectedType  = null;
        $requirements  = [];

        try {
            $documentTypes = (new DocumentType())->allActive();

            $typeId = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 0;
            if ($typeId > 0) {
                $dtModel = new DocumentType();
                $dt      = $dtModel->find($typeId);
                // Only allow active types to be pre-selected (Req 5.4)
                if ($dt && (bool)$dt['is_active']) {
                    $selectedType = $dt;
                    $requirements = (new DocumentRequirement())->activeForDocumentType($typeId);
                }
            }
        } catch (\Throwable $e) {
            Logger::error('RequestController newRequest error: ' . $e->getMessage());
        }

        $this->renderWithLayout('resident/requests/new', [
            'title'         => 'New Request — eMunicipal',
            'activePage'    => 'new-request',
            'documentTypes' => $documentTypes,
            'selectedType'  => $selectedType,
            'requirements'  => $requirements,
            'csrfToken'     => SecurityHelper::getCsrfToken(),
            'errors'        => SessionHelper::getFlash('req_errors', []),
            'old'           => SessionHelper::getFlash('req_old', []),
        ], 'layouts/resident');
    }

    // -------------------------------------------------------------------------
    // STORE — submit the request
    // -------------------------------------------------------------------------

    /**
     * POST /resident/requests
     * Validate, upload files, insert request + history row in a transaction.
     */
    public function store(): void
    {
        AuthMiddleware::requireRole('resident');

        // CSRF (Req 6.13)
        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('req_errors', ['form' => 'Invalid security token. Please try again.']);
            UrlHelper::redirect('/resident/requests/new');
        }

        // ---------- Validate form fields ----------
        $v = new Validator($_POST);
        $v->required(['document_type_id', 'purpose', 'copies_requested'])
          ->positiveInteger('copies_requested')
          ->maxLength('purpose', 1000);

        $old = [
            'document_type_id' => $v->get('document_type_id', ''),
            'purpose'          => $v->get('purpose', ''),
            'copies_requested' => $v->get('copies_requested', ''),
        ];

        if ($v->fails()) {
            SessionHelper::flash('req_errors', $v->errors());
            SessionHelper::flash('req_old', $old);
            $typeParam = $v->get('document_type_id') ? '?type_id=' . (int)$v->get('document_type_id') : '';
            UrlHelper::redirect('/resident/requests/new' . $typeParam);
        }

        $docTypeId = (int)$v->get('document_type_id');
        $copies    = (int)$v->get('copies_requested');

        // Validate document type exists and is active (Req 5.4, 6.1)
        try {
            $dtModel  = new DocumentType();
            $docType  = $dtModel->find($docTypeId);
        } catch (\Throwable $e) {
            Logger::error('RequestController store DB error (docType lookup): ' . $e->getMessage());
            SessionHelper::flash('req_errors', ['form' => 'A system error occurred. Please try again.']);
            SessionHelper::flash('req_old', $old);
            UrlHelper::redirect('/resident/requests/new');
        }

        if (!$docType || !(bool)$docType['is_active']) {
            SessionHelper::flash('req_errors', ['document_type_id' => 'The selected document type is not available.']);
            SessionHelper::flash('req_old', $old);
            UrlHelper::redirect('/resident/requests/new');
        }

        // ---------- Validate uploaded files ----------
        $uploadedFiles   = $_FILES['documents'] ?? [];
        $fileErrors      = [];
        $validFiles      = [];
        $storageRoot     = $this->resolveStorageRoot();
        $uploadService   = new FileUploadService();

        // Normalise the $_FILES array structure for multiple files
        // PHP gives: $_FILES['documents']['name'][0], [1], etc.
        if (!empty($uploadedFiles['name']) && is_array($uploadedFiles['name'])) {
            $count = count($uploadedFiles['name']);

            if ($count > FileUploadService::MAX_FILES) {
                SessionHelper::flash('req_errors', [
                    'documents' => 'You may upload a maximum of ' . FileUploadService::MAX_FILES . ' files.',
                ]);
                SessionHelper::flash('req_old', $old);
                UrlHelper::redirect('/resident/requests/new?type_id=' . $docTypeId);
            }

            for ($i = 0; $i < $count; $i++) {
                // Skip blank file inputs
                if (empty($uploadedFiles['name'][$i]) || $uploadedFiles['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $singleFile = [
                    'name'     => $uploadedFiles['name'][$i],
                    'tmp_name' => $uploadedFiles['tmp_name'][$i],
                    'size'     => $uploadedFiles['size'][$i],
                    'error'    => $uploadedFiles['error'][$i],
                ];
                try {
                    $validFiles[] = $uploadService->store($singleFile, $storageRoot);
                } catch (\InvalidArgumentException $e) {
                    $fileErrors[] = $e->getMessage();
                } catch (\RuntimeException $e) {
                    Logger::error('File storage error: ' . $e->getMessage());
                    $fileErrors[] = 'A file could not be stored. Please try again.';
                }
            }
        }

        if (!empty($fileErrors)) {
            // Clean up any files that were already stored before the error
            foreach ($validFiles as $vf) {
                $uploadService->delete($storageRoot, $vf['storage_path']);
            }
            SessionHelper::flash('req_errors', ['documents' => implode(' ', $fileErrors)]);
            SessionHelper::flash('req_old', $old);
            UrlHelper::redirect('/resident/requests/new?type_id=' . $docTypeId);
        }

        // ---------- Insert request + history in a transaction ----------
        $userId = AuthMiddleware::userId();

        try {
            $requestModel = new RequestModel();

            $result = $requestModel->insertWithReferenceNumber(
                [
                    'user_id'          => $userId,
                    'document_type_id' => $docTypeId,
                    'purpose'          => $v->get('purpose'),
                    'copies_requested' => $copies,
                ],
                [
                    'previous_status' => null,
                    'changed_by'      => null,   // system-generated initial entry
                    'remark'          => null,
                ]
            );

            $requestId       = $result['id'];
            $referenceNumber = $result['reference_number'];

            // Insert request_documents rows (Req 6 — file metadata only)
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

                    // Audit file upload (Req 13.1)
                    AuditLogService::log(
                        $userId, 'resident',
                        AuditLog::ACTION_FILE_UPLOADED,
                        'files',
                        'request_documents', null,
                        null,
                        ['request_id' => $requestId, 'filename' => $fileData['original_filename']],
                        "File '{$fileData['original_filename']}' uploaded for request {$referenceNumber}"
                    );
                }
            }

            // Audit request submission (Req 13.1)
            AuditLogService::log(
                $userId, 'resident',
                AuditLog::ACTION_REQUEST_SUBMITTED,
                'requests',
                'requests', $requestId,
                null,
                ['reference_number' => $referenceNumber, 'document_type_id' => $docTypeId],
                "Request {$referenceNumber} submitted by resident ID {$userId}"
            );

            // In-system notification (Req 16.1) — inside transaction commit scope
            NotificationService::requestSubmitted(
                $userId,
                $requestId,
                $referenceNumber,
                $docType['name'] ?? ''
            );

            // Staff/Admin operational notification — non-fatal (Req 25.1)
            NotificationService::newRequestForStaffAdmin(
                $requestId,
                $referenceNumber,
                $docType['name'] ?? '',
                trim((AuthMiddleware::firstName() ?? '') . ' ' . (AuthMiddleware::lastName() ?? ''))
            );

        } catch (\Throwable $e) {
            Logger::error('RequestController store transaction failed: ' . $e->getMessage());
            // Roll back uploaded files — transaction already rolled back inside insertWithReferenceNumber
            foreach ($validFiles as $vf) {
                $uploadService->delete($storageRoot, $vf['storage_path']);
            }
            SessionHelper::flash('req_errors', ['form' => 'Your request could not be submitted. Please try again.']);
            SessionHelper::flash('req_old', $old);
            UrlHelper::redirect('/resident/requests/new?type_id=' . $docTypeId);
        }

        // Send request submitted email (Req 10.1 — failure must not roll back, Req 10.4)
        try {
            $userModel      = new \Emunicipal\Models\User();
            $residentUser   = $userModel->findActiveById($userId);
            $requestForEmail = (new RequestModel())->find($requestId);
            if ($residentUser && $requestForEmail) {
                // Attach document type name for the email
                $requestForEmail['document_type_name'] = $docType['name'] ?? '';
                EmailService::sendRequestSubmitted($residentUser, $requestForEmail);
            }
        } catch (\Throwable $emailEx) {
            Logger::error('Request submitted email error: ' . $emailEx->getMessage());
            // Email failure is non-fatal (Req 10.4) — continue to redirect
        }

        // Redirect to confirmation/detail page (Req 6.12)
        SessionHelper::flash('success', "Your request has been submitted. Reference number: {$referenceNumber}");
        UrlHelper::redirect('/resident/requests/' . $requestId . '?submitted=1');
    }

    // -------------------------------------------------------------------------
    // INDEX — My Requests list (Module H — stub for now)
    // -------------------------------------------------------------------------

    /**
     * GET /resident/requests
     */
    public function index(): void
    {
        AuthMiddleware::requireRole('resident');
        $userId   = AuthMiddleware::userId();
        $requests = [];

        try {
            $requests = (new RequestModel())->findByUserId($userId);
        } catch (\Throwable $e) {
            Logger::error('RequestController index error: ' . $e->getMessage());
        }

        $this->renderWithLayout('resident/requests/index', [
            'title'      => 'My Requests — eMunicipal',
            'activePage' => 'requests',
            'requests'   => $requests,
        ], 'layouts/resident');
    }

    // -------------------------------------------------------------------------
    // SHOW — Request detail (Module H — stub for now)
    // -------------------------------------------------------------------------

    /**
     * GET /resident/requests/{id}
     */
    public function show(string $id): void
    {
        AuthMiddleware::requireRole('resident');
        $userId    = AuthMiddleware::userId();
        $requestId = (int)$id;

        $request  = null;
        $documents = [];
        $history   = [];
        $submitted = isset($_GET['submitted']);

        try {
            $requestModel = new RequestModel();
            $request      = $requestModel->find($requestId);

            // Ownership check — Resident may only see their own requests (Req 8.8)
            if (!$request || (int)$request['user_id'] !== $userId) {
                SessionHelper::flash('error', 'Request not found.');
                UrlHelper::redirect('/resident/requests');
            }

            $documents = (new RequestDocument())->forRequest($requestId);
            $history   = (new RequestStatusHistory())->forRequest($requestId);

        } catch (\Throwable $e) {
            Logger::error('RequestController show error: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load request details.');
            UrlHelper::redirect('/resident/requests');
        }

        // Fetch document type name and fee for display
        $docTypeName = '';
        $docTypeFee  = 0.00;
        try {
            $dt = (new DocumentType())->find((int)($request['document_type_id'] ?? 0));
            $docTypeName = $dt['name']           ?? '';
            $docTypeFee  = (float)($dt['processing_fee'] ?? 0.00);
        } catch (\Throwable $e) {}

        // Load payment record for this request (Phase 2 — Cash Payment)
        $payment = false;
        try {
            $payment = (new Payment())->findByRequestId($requestId);
        } catch (\Throwable $e) {
            Logger::error('RequestController show (payment load): ' . $e->getMessage());
            // Non-fatal — payment card simply won't render
        }

        $this->renderWithLayout('resident/requests/show', [
            'title'       => 'Request Details — eMunicipal',
            'activePage'  => 'requests',
            'request'     => $request,
            'docTypeName' => $docTypeName,
            'docTypeFee'  => $docTypeFee,
            'payment'     => $payment,
            'documents'   => $documents,
            'history'     => $history,
            'submitted'   => $submitted,
            'csrfToken'   => SecurityHelper::getCsrfToken(),
        ], 'layouts/resident');
    }

    // -------------------------------------------------------------------------
    // CANCEL (Module H)
    // -------------------------------------------------------------------------

    /**
     * POST /resident/requests/{id}/cancel
     */
    public function cancel(string $id): void
    {
        AuthMiddleware::requireRole('resident');
        $userId    = AuthMiddleware::userId();
        $requestId = (int)$id;

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('error', 'Invalid security token.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        try {
            $requestModel = new RequestModel();
            $request      = $requestModel->find($requestId);

            // Ownership + eligibility check
            if (!$request || (int)$request['user_id'] !== $userId) {
                SessionHelper::flash('error', 'Request not found.');
                UrlHelper::redirect('/resident/requests');
            }

            if (!in_array($request['status'], RequestModel::RESIDENT_CANCELLABLE_STATUSES, true)) {
                SessionHelper::flash('error', 'This request cannot be cancelled at its current status.');
                UrlHelper::redirect('/resident/requests/' . $requestId);
            }

            $reason = trim($_POST['cancellation_reason'] ?? '');

            // Update request status + record history (per database.md §16.3 transaction)
            $db = \Emunicipal\Core\Database::getInstance();
            $db->beginTransaction();

            try {
                $requestModel->update($requestId, [
                    'status'              => RequestModel::STATUS_CANCELLED,
                    'cancellation_reason' => $reason ?: null,
                    'cancelled_by'        => $userId,
                ]);

                // Append history row
                (new RequestStatusHistory())->insert([
                    'request_id'      => $requestId,
                    'previous_status' => $request['status'],
                    'new_status'      => RequestModel::STATUS_CANCELLED,
                    'changed_by'      => $userId,
                    'remark'          => $reason ?: null,
                ]);

                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e;
            }

            AuditLogService::log(
                $userId, 'resident',
                AuditLog::ACTION_STATUS_CHANGED,
                'requests',
                'requests', $requestId,
                ['status' => $request['status']],
                ['status' => RequestModel::STATUS_CANCELLED],
                "Request {$request['reference_number']} cancelled by resident ID {$userId}"
            );

            // In-system notification (Req 16.1)
            NotificationService::cancelled(
                $userId,
                $requestId,
                $request['reference_number']
            );

            // Staff/Admin operational notification — non-fatal (Req 25.2)
            $docTypeName = '';
            try {
                $dt = (new \Emunicipal\Models\DocumentType())->find((int)($request['document_type_id'] ?? 0));
                $docTypeName = $dt['name'] ?? '';
            } catch (\Throwable $ignored) {}

            NotificationService::residentCancelledForStaffAdmin(
                $requestId,
                $request['reference_number'],
                trim((AuthMiddleware::firstName() ?? '') . ' ' . (AuthMiddleware::lastName() ?? '')),
                $docTypeName
            );

            SessionHelper::flash('success', 'Your request has been cancelled.');

        } catch (\Throwable $e) {
            Logger::error('RequestController cancel error: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to cancel the request. Please try again.');
        }

        UrlHelper::redirect('/resident/requests/' . $requestId);
    }

    // -------------------------------------------------------------------------
    // UPLOAD ADDITIONAL DOCUMENTS (Req 8.6–8.7, Req 25.14)
    // -------------------------------------------------------------------------

    /**
     * POST /resident/requests/{id}/documents
     *
     * Allows a Resident to upload additional supporting documents when their
     * request is in ADDITIONAL_INFORMATION_REQUIRED status.
     *
     * Security:
     *  - requireRole('resident')
     *  - Request ownership enforced (Req 8.8)
     *  - Status guard: only allowed when status = additional_info_required
     *  - CSRF validated
     *  - Reuses FileUploadService for all file security (MIME, size, UUID filename)
     *  - Files stored outside web root (same as original upload)
     *  - Audit log written per file (Req 13.1)
     *
     * Status behaviour:
     *  - The request status MUST remain additional_info_required after upload (Req 7.1 table).
     *  - Only Staff can advance the status from this point.
     *
     * Notification:
     *  - Staff/Admin receive an in-system notification (Req 25.14) — non-fatal.
     */
    public function uploadAdditionalDocuments(string $id): void
    {
        AuthMiddleware::requireRole('resident');
        $userId    = AuthMiddleware::userId();
        $requestId = (int)$id;

        // CSRF (Req 14.3)
        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('error', 'Invalid security token. Please try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        // Load request and verify ownership (Req 8.8)
        try {
            $requestModel = new RequestModel();
            $request      = $requestModel->find($requestId);
        } catch (\Throwable $e) {
            Logger::error('RequestController uploadAdditionalDocuments (load): ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load request. Please try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        if (!$request || (int)$request['user_id'] !== $userId) {
            SessionHelper::flash('error', 'Request not found.');
            UrlHelper::redirect('/resident/requests');
        }

        // Status guard — upload only allowed when additional information is required (Req 8.6)
        if ($request['status'] !== RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED) {
            SessionHelper::flash('error', 'Additional documents can only be uploaded when the request requires additional information.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        // Validate that at least one file was submitted
        $uploadedFiles = $_FILES['additional_documents'] ?? [];
        $hasFiles = !empty($uploadedFiles['name']) && is_array($uploadedFiles['name'])
            && count(array_filter($uploadedFiles['name'])) > 0;

        if (!$hasFiles) {
            SessionHelper::flash('error', 'Please select at least one file to upload.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        $storageRoot   = $this->resolveStorageRoot();
        $uploadService = new FileUploadService();
        $fileErrors    = [];
        $validFiles    = [];

        // Normalise multi-file $_FILES structure (same as store() — Req 8.7)
        $count = count($uploadedFiles['name']);

        if ($count > FileUploadService::MAX_FILES) {
            SessionHelper::flash('error', 'You may upload a maximum of ' . FileUploadService::MAX_FILES . ' files at a time.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        for ($i = 0; $i < $count; $i++) {
            if (empty($uploadedFiles['name'][$i]) || $uploadedFiles['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $singleFile = [
                'name'     => $uploadedFiles['name'][$i],
                'tmp_name' => $uploadedFiles['tmp_name'][$i],
                'size'     => $uploadedFiles['size'][$i],
                'error'    => $uploadedFiles['error'][$i],
            ];
            try {
                $validFiles[] = $uploadService->store($singleFile, $storageRoot);
            } catch (\InvalidArgumentException $e) {
                $fileErrors[] = $e->getMessage();
            } catch (\RuntimeException $e) {
                Logger::error('RequestController uploadAdditionalDocuments file error: ' . $e->getMessage());
                $fileErrors[] = 'A file could not be stored. Please try again.';
            }
        }

        if (!empty($fileErrors)) {
            // Clean up any already-stored files before aborting
            foreach ($validFiles as $vf) {
                $uploadService->delete($storageRoot, $vf['storage_path']);
            }
            SessionHelper::flash('error', implode(' ', $fileErrors));
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        if (empty($validFiles)) {
            SessionHelper::flash('error', 'No valid files were uploaded. Please select PDF, JPEG, or PNG files.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        // Persist file metadata to request_documents (Req 18.7)
        // Status is NOT changed — it remains additional_info_required (Req 7.1)
        try {
            $rdModel         = new RequestDocument();
            $referenceNumber = $request['reference_number'];

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

                // Audit each uploaded file (Req 13.1)
                AuditLogService::log(
                    $userId, 'resident',
                    AuditLog::ACTION_FILE_UPLOADED,
                    'files',
                    'request_documents', null,
                    null,
                    ['request_id' => $requestId, 'filename' => $fileData['original_filename']],
                    "Additional document '{$fileData['original_filename']}' uploaded for request {$referenceNumber} by resident ID {$userId}"
                );
            }
        } catch (\Throwable $e) {
            Logger::error('RequestController uploadAdditionalDocuments (persist): ' . $e->getMessage());
            // Roll back stored files — DB insert failed
            foreach ($validFiles as $vf) {
                $uploadService->delete($storageRoot, $vf['storage_path']);
            }
            SessionHelper::flash('error', 'Your files could not be saved. Please try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        // Staff/Admin notification — non-fatal (Req 25.14)
        try {
            $docTypeName = '';
            $dt = (new DocumentType())->find((int)($request['document_type_id'] ?? 0));
            $docTypeName = $dt['name'] ?? '';
        } catch (\Throwable $ignored) {
            $docTypeName = '';
        }

        NotificationService::additionalInfoSubmittedForStaffAdmin(
            $requestId,
            $request['reference_number'],
            trim((AuthMiddleware::firstName() ?? '') . ' ' . (AuthMiddleware::lastName() ?? '')),
            $docTypeName
        );

        $fileCount = count($validFiles);
        $noun      = $fileCount === 1 ? 'document' : 'documents';
        SessionHelper::flash('success', "{$fileCount} additional {$noun} uploaded successfully. Our staff will review your submission.");
        UrlHelper::redirect('/resident/requests/' . $requestId);
    }

    // -------------------------------------------------------------------------
    // TRACK — search by reference number (Module H)
    // -------------------------------------------------------------------------

    /**
     * GET /resident/track
     */
    public function track(): void
    {
        AuthMiddleware::requireRole('resident');

        $referenceNumber = trim($_GET['ref'] ?? '');
        $request         = null;
        $history         = [];
        $notFound        = false;

        if ($referenceNumber !== '') {
            try {
                $request = (new RequestModel())->findByReferenceNumber($referenceNumber);
                if ($request) {
                    $history = (new RequestStatusHistory())->forRequest((int)$request['id']);
                } else {
                    $notFound = true;
                }
            } catch (\Throwable $e) {
                Logger::error('RequestController track error: ' . $e->getMessage());
            }
        }

        $this->renderWithLayout('resident/requests/track', [
            'title'           => 'Track Request — eMunicipal',
            'activePage'      => 'track',
            'referenceNumber' => $referenceNumber,
            'request'         => $request,
            'history'         => $history,
            'notFound'        => $notFound,
        ], 'layouts/resident');
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    /**
     * Resolve the absolute filesystem path to the uploads storage directory.
     * Constructed from UPLOAD_PATH env var (relative to project root) per config/app.php.
     * The resulting path must never be inside the web root.
     */
    private function resolveStorageRoot(): string
    {
        $basePath   = dirname(__DIR__, 3); // project root
        $uploadPath = $_ENV['UPLOAD_PATH'] ?? 'storage/uploads';

        // Always treat as relative to project root to prevent path traversal
        $uploadPath = ltrim(str_replace(['../', '..\\'], '', $uploadPath), '/\\');

        return $basePath . DIRECTORY_SEPARATOR . $uploadPath;
    }
}
