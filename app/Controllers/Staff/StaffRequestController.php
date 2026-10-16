<?php

namespace Emunicipal\Controllers\Staff;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Models\Request as RequestModel;
use Emunicipal\Models\RequestDocument;
use Emunicipal\Models\RequestStatusHistory;
use Emunicipal\Models\DocumentRequirement;
use Emunicipal\Models\Payment;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Services\NotificationService;
use Emunicipal\Services\EmailService;
use Emunicipal\Core\Database;
use Emunicipal\Core\Logger;

/**
 * StaffRequestController — Staff request list, detail, and status management.
 *
 * Implements Req 9 (Staff Request Management) and Req 7 (Status Workflow).
 *  Req 9.1  — List all requests with filters
 *  Req 9.3  — Full request detail including uploads and resident contact info
 *  Req 9.4  — File download (via FileController)
 *  Req 9.6  — Status transitions enforced by Request::allowedTransitions()
 *  Req 9.8  — Every status change recorded in audit_logs
 *  Req 7.3  — Rejection requires non-empty rejection reason
 *  Req 7.4  — Additional info required → non-empty remark
 *  Req 7.5  — Status change written to request_status_history
 *  Req 7.6  — Notification sent to resident on each status change
 */
class StaffRequestController extends BaseController
{
    // -------------------------------------------------------------------------
    // LIST  —  GET /staff/requests
    // -------------------------------------------------------------------------

    public function index(): void
    {
        AuthMiddleware::requireRole('staff');

        // Simple filter support: ?status=pending&search=EMU-...
        $filters = [];
        if (!empty($_GET['status']))          $filters['status']          = $_GET['status'];
        if (!empty($_GET['search']))          $filters['search']          = trim($_GET['search']);
        if (!empty($_GET['document_type_id'])) $filters['document_type_id'] = (int)$_GET['document_type_id'];
        if (!empty($_GET['date_from']))       $filters['date_from']       = $_GET['date_from'];
        if (!empty($_GET['date_to']))         $filters['date_to']         = $_GET['date_to'];
        // Pagination — honour URL params; default from system_settings (Req 20)
        $filters['page']     = max(1, (int)($_GET['page'] ?? 1));
        $filters['per_page'] = \Emunicipal\Services\SettingsService::recordsPerPage();

        $requestModel = new RequestModel();
        try {
            $requests = $requestModel->findAllForStaff($filters);
        } catch (\Throwable $e) {
            Logger::error('StaffRequestController index: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load requests. Please try again.');
        }

        // Status counts for dashboard badge (Req 9.7)
        $counts = [];
        try {
            $db   = Database::getInstance();
            $stmt = $db->query(
                "SELECT status, COUNT(*) AS cnt FROM requests GROUP BY status"
            );
            foreach ($stmt->fetchAll() as $row) {
                $counts[$row['status']] = (int)$row['cnt'];
            }
        } catch (\Throwable $e) { /* non-fatal */ }

        // Document types for filter dropdown (Req 24.4)
        $documentTypes = [];
        try {
            $documentTypes = (new \Emunicipal\Models\DocumentType())->allActive();
        } catch (\Throwable $e) { /* non-fatal */ }

        $this->renderWithLayout('staff/requests/index', [
            'title'         => 'Requests — eMunicipal',
            'activePage'    => 'requests',
            'requests'      => $requests,
            'filters'       => $filters,
            'counts'        => $counts,
            'documentTypes' => $documentTypes,
            'totalRows'     => $requestModel->lastTotalCount,
            'currentPage'   => $filters['page'],
            'perPage'       => $filters['per_page'],
            'csrfToken'     => SecurityHelper::getCsrfToken(),
        ], 'layouts/auth');
    }

    // -------------------------------------------------------------------------
    // DETAIL  —  GET /staff/requests/{id}
    // -------------------------------------------------------------------------

    public function show(string $id): void
    {
        AuthMiddleware::requireRole('staff');

        $requestId = (int)$id;

        // Load request with resident + document-type joins
        $request = null;
        try {
            $request = (new RequestModel())->findByIdForStaff($requestId);
        } catch (\Throwable $e) {
            Logger::error('StaffRequestController show (load): ' . $e->getMessage());
        }

        if (!$request) {
            SessionHelper::flash('error', 'Request not found.');
            UrlHelper::redirect('/staff/requests');
        }

        // Uploaded documents
        $documents = [];
        try {
            $documents = (new RequestDocument())->forRequest($requestId);
        } catch (\Throwable $e) { /* non-fatal */ }

        // Status history (with role join for display per Req 8.3)
        $history = [];
        try {
            $history = (new RequestStatusHistory())->forRequest($requestId);
        } catch (\Throwable $e) { /* non-fatal */ }

        // Active requirements for this document type
        $requirements = [];
        try {
            $requirements = (new DocumentRequirement())
                ->activeForDocumentType((int)$request['document_type_id']);
        } catch (\Throwable $e) { /* non-fatal */ }

        // Compute which transitions are available from the current status
        $allowedNext = RequestModel::allowedTransitions($request['status']);

        // Load payment record (Phase 2 — Cash Payment)
        $payment    = false;
        $docTypeFee = 0.00;
        try {
            // Get the processing fee for the payment panel
            $dt         = (new \Emunicipal\Models\DocumentType())->find((int)($request['document_type_id'] ?? 0));
            $docTypeFee = (float)($dt['processing_fee'] ?? 0.00);
            $payment    = (new Payment())->findByRequestId($requestId);
        } catch (\Throwable $e) { /* non-fatal */ }

        $this->renderWithLayout('staff/requests/show', [
            'title'        => 'Request — ' . htmlspecialchars($request['reference_number'], ENT_QUOTES, 'UTF-8'),
            'activePage'   => 'requests',
            'request'      => $request,
            'documents'    => $documents,
            'history'      => $history,
            'requirements' => $requirements,
            'allowedNext'  => $allowedNext,
            'payment'      => $payment,
            'docTypeFee'   => $docTypeFee,
            'csrfToken'    => SecurityHelper::getCsrfToken(),
            'errors'       => SessionHelper::getFlash('status_errors', []),
        ], 'layouts/auth');
    }

    // -------------------------------------------------------------------------
    // STATUS UPDATE  —  POST /staff/requests/{id}/status
    // -------------------------------------------------------------------------

    public function updateStatus(string $id): void
    {
        AuthMiddleware::requireRole('staff');

        $requestId = (int)$id;

        // CSRF
        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('status_errors', ['form' => 'Invalid security token.']);
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        $newStatus = trim($_POST['new_status'] ?? '');
        $remark    = trim($_POST['remark']     ?? '');

        // Load current request (staff join for context)
        try {
            $requestModel = new RequestModel();
            $request      = $requestModel->findByIdForStaff($requestId);
        } catch (\Throwable $e) {
            Logger::error('StaffRequestController updateStatus (load): ' . $e->getMessage());
            SessionHelper::flash('status_errors', ['form' => 'Unable to load request.']);
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        if (!$request) {
            SessionHelper::flash('status_errors', ['form' => 'Request not found.']);
            UrlHelper::redirect('/staff/requests');
        }

        $currentStatus = $request['status'];
        $staffId       = AuthMiddleware::userId();

        // --- Validate: is the target status an allowed transition? (Req 7.2) ---
        $allowed = RequestModel::allowedTransitions($currentStatus);
        if (!in_array($newStatus, $allowed, true)) {
            SessionHelper::flash('status_errors', [
                'form' => "Cannot change status from '{$currentStatus}' to '{$newStatus}'.",
            ]);
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        // --- Status-specific validation ---
        // Rejection requires a reason (Req 7.3)
        if ($newStatus === RequestModel::STATUS_REJECTED && $remark === '') {
            SessionHelper::flash('status_errors', [
                'remark' => 'A rejection reason is required.',
            ]);
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }
        // Additional info required needs a remark (Req 7.4)
        if ($newStatus === RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED && $remark === '') {
            SessionHelper::flash('status_errors', [
                'remark' => 'Please provide a remark explaining what additional information is needed.',
            ]);
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        // --- Execute transition inside a transaction (database.md §16.2) ---
        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            try {
                // Build the update fields
                $updateFields = ['status' => $newStatus];
                if ($newStatus === RequestModel::STATUS_REJECTED) {
                    $updateFields['rejection_reason'] = $remark;
                }
                if ($newStatus === RequestModel::STATUS_CANCELLED) {
                    $updateFields['cancellation_reason'] = $remark ?: null;
                    $updateFields['cancelled_by']        = $staffId;
                }

                $requestModel->update($requestId, $updateFields);

                // Append history row (Req 7.5)
                (new RequestStatusHistory())->insert([
                    'request_id'      => $requestId,
                    'previous_status' => $currentStatus,
                    'new_status'      => $newStatus,
                    'changed_by'      => $staffId,
                    'remark'          => $remark !== '' ? $remark : null,
                ]);

                $db->commit();

            } catch (\Throwable $inner) {
                $db->rollBack();
                throw $inner;
            }

            // --- Notifications (Req 7.6) — non-fatal, outside transaction ---
            $residentId  = (int)$request['user_id'];
            $refNum      = $request['reference_number'];

            $this->dispatchNotification(
                $newStatus, $residentId, $requestId, $refNum, $remark
            );

            // --- Send email — non-fatal (Req 10.4) ---
            $this->dispatchEmail($newStatus, $request, $remark, $staffId);

            // --- Audit log (Req 9.8) ---
            AuditLogService::log(
                $staffId, 'staff',
                AuditLog::ACTION_STATUS_CHANGED,
                'requests',
                'requests', $requestId,
                ['status' => $currentStatus],
                ['status' => $newStatus],
                "Request {$refNum}: status changed from {$currentStatus} to {$newStatus} by staff ID {$staffId}"
            );

            $label = ucwords(str_replace('_', ' ', $newStatus));
            SessionHelper::flash('success', "Request status updated to: {$label}");

        } catch (\Throwable $e) {
            Logger::error('StaffRequestController updateStatus: ' . $e->getMessage());
            SessionHelper::flash('status_errors', [
                'form' => 'Status update failed. Please try again.',
            ]);
        }

        UrlHelper::redirect('/staff/requests/' . $requestId);
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    private function dispatchNotification(
        string $newStatus,
        int    $residentId,
        int    $requestId,
        string $refNum,
        string $remark
    ): void {
        try {
            match ($newStatus) {
                RequestModel::STATUS_UNDER_VERIFICATION
                    => NotificationService::underVerification($residentId, $requestId, $refNum),
                RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED
                    => NotificationService::additionalInfoRequired($residentId, $requestId, $refNum),
                RequestModel::STATUS_APPROVED
                    => NotificationService::approved($residentId, $requestId, $refNum),
                RequestModel::STATUS_REJECTED
                    => NotificationService::rejected($residentId, $requestId, $refNum),
                RequestModel::STATUS_READY_FOR_CLAIMING
                    => NotificationService::readyForClaiming($residentId, $requestId, $refNum),
                RequestModel::STATUS_COMPLETED
                    => NotificationService::completed($residentId, $requestId, $refNum),
                RequestModel::STATUS_CANCELLED
                    => NotificationService::cancelled($residentId, $requestId, $refNum),
                default => null,
            };
        } catch (\Throwable $e) {
            Logger::error('StaffRequestController notification dispatch: ' . $e->getMessage());
        }
    }

    private function dispatchEmail(
        string $newStatus,
        array  $request,
        string $remark,
        int    $staffId
    ): void {
        try {
            $db       = Database::getInstance();
            $resident = $db->prepare(
                "SELECT id, first_name, last_name, email FROM users WHERE id = :id"
            );
            $resident->execute([':id' => $request['user_id']]);
            $user = $resident->fetch();
            if (!$user) return;

            $cancelledBy = ($newStatus === RequestModel::STATUS_CANCELLED) ? 'Staff' : null;

            match ($newStatus) {
                RequestModel::STATUS_UNDER_VERIFICATION
                    => EmailService::sendStatusUpdate($user, $request, EmailService::TYPE_UNDER_VERIFICATION),
                RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED
                    => EmailService::sendStatusUpdate($user, $request, EmailService::TYPE_ADDITIONAL_INFO_REQUIRED, $remark),
                RequestModel::STATUS_APPROVED
                    => EmailService::sendStatusUpdate($user, $request, EmailService::TYPE_APPROVED),
                RequestModel::STATUS_REJECTED
                    => EmailService::sendStatusUpdate($user, $request, EmailService::TYPE_REJECTED, $remark),
                RequestModel::STATUS_READY_FOR_CLAIMING
                    => EmailService::sendStatusUpdate($user, $request, EmailService::TYPE_READY_FOR_CLAIMING),
                RequestModel::STATUS_COMPLETED
                    => EmailService::sendStatusUpdate($user, $request, EmailService::TYPE_COMPLETED),
                RequestModel::STATUS_CANCELLED
                    => EmailService::sendStatusUpdate($user, $request, EmailService::TYPE_CANCELLED, $remark, $cancelledBy),
                default => null,
            };
        } catch (\Throwable $e) {
            Logger::error('StaffRequestController email dispatch: ' . $e->getMessage());
        }
    }
}
