<?php

namespace Emunicipal\Controllers\Admin;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Models\Request as RequestModel;
use Emunicipal\Models\RequestDocument;
use Emunicipal\Models\RequestStatusHistory;
use Emunicipal\Models\DocumentRequirement;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Services\NotificationService;
use Emunicipal\Services\EmailService;
use Emunicipal\Core\Database;
use Emunicipal\Core\Logger;

/**
 * AdminRequestController — Administrator read-only request oversight
 * and status management.
 *
 * Per Req 3 permission matrix:
 *   Admin: View All Requests ✓ — same view access as Staff
 *   Admin: all status-change actions ✓ (via the same workflow as Staff)
 *
 * Reuses Request::findAllForStaff() and Request::findByIdForStaff()
 * since those queries are identical for both roles.
 */
class AdminRequestController extends BaseController
{
    // -------------------------------------------------------------------------
    // LIST — GET /admin/requests
    // -------------------------------------------------------------------------

    public function index(): void
    {
        AuthMiddleware::requireRole('admin');

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
        $requests = [];
        try {
            $requests = $requestModel->findAllForStaff($filters);
        } catch (\Throwable $e) {
            Logger::error('AdminRequestController index: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load requests. Please try again.');
        }

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

        $this->renderWithLayout('admin/requests/index', [
            'title'         => 'Requests — eMunicipal',
            'activePage'    => 'requests',
            'requests'      => $requests,
            'filters'       => $filters,
            'counts'        => $counts,
            'documentTypes' => $documentTypes,
            'totalRows'     => $requestModel->lastTotalCount,
            'currentPage'   => $filters['page'],
            'perPage'       => $filters['per_page'],
        ], 'layouts/auth');
    }

    // -------------------------------------------------------------------------
    // DETAIL — GET /admin/requests/{id}
    // -------------------------------------------------------------------------

    public function show(string $id): void
    {
        AuthMiddleware::requireRole('admin');

        $requestId = (int)$id;

        $request = null;
        try {
            $request = (new RequestModel())->findByIdForStaff($requestId);
        } catch (\Throwable $e) {
            Logger::error('AdminRequestController show (load): ' . $e->getMessage());
        }

        if (!$request) {
            SessionHelper::flash('error', 'Request not found.');
            UrlHelper::redirect('/admin/requests');
        }

        $documents = [];
        try {
            $documents = (new RequestDocument())->forRequest($requestId);
        } catch (\Throwable $e) { /* non-fatal */ }

        $history = [];
        try {
            $history = (new RequestStatusHistory())->forRequest($requestId);
        } catch (\Throwable $e) { /* non-fatal */ }

        $requirements = [];
        try {
            $requirements = (new DocumentRequirement())
                ->activeForDocumentType((int)$request['document_type_id']);
        } catch (\Throwable $e) { /* non-fatal */ }

        $allowedNext = RequestModel::allowedTransitions($request['status']);

        $this->renderWithLayout('admin/requests/show', [
            'title'        => 'Request — ' . htmlspecialchars($request['reference_number'], ENT_QUOTES, 'UTF-8'),
            'activePage'   => 'requests',
            'request'      => $request,
            'documents'    => $documents,
            'history'      => $history,
            'requirements' => $requirements,
            'allowedNext'  => $allowedNext,
            'csrfToken'    => SecurityHelper::getCsrfToken(),
            'errors'       => SessionHelper::getFlash('status_errors', []),
        ], 'layouts/auth');
    }

    // -------------------------------------------------------------------------
    // STATUS UPDATE — POST /admin/requests/{id}/status
    // -------------------------------------------------------------------------

    public function updateStatus(string $id): void
    {
        AuthMiddleware::requireRole('admin');

        $requestId = (int)$id;

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('status_errors', ['form' => 'Invalid security token.']);
            UrlHelper::redirect('/admin/requests/' . $requestId);
        }

        $newStatus = trim($_POST['new_status'] ?? '');
        $remark    = trim($_POST['remark']     ?? '');

        $request = null;
        try {
            $requestModel = new RequestModel();
            $request      = $requestModel->findByIdForStaff($requestId);
        } catch (\Throwable $e) {
            Logger::error('AdminRequestController updateStatus (load): ' . $e->getMessage());
            SessionHelper::flash('status_errors', ['form' => 'Unable to load request.']);
            UrlHelper::redirect('/admin/requests/' . $requestId);
        }

        if (!$request) {
            SessionHelper::flash('status_errors', ['form' => 'Request not found.']);
            UrlHelper::redirect('/admin/requests');
        }

        $currentStatus = $request['status'];
        $adminId       = AuthMiddleware::userId();

        // Validate transition
        $allowed = RequestModel::allowedTransitions($currentStatus);
        if (!in_array($newStatus, $allowed, true)) {
            SessionHelper::flash('status_errors', [
                'form' => "Cannot change status from '{$currentStatus}' to '{$newStatus}'.",
            ]);
            UrlHelper::redirect('/admin/requests/' . $requestId);
        }

        // Status-specific remark validation
        if ($newStatus === RequestModel::STATUS_REJECTED && $remark === '') {
            SessionHelper::flash('status_errors', ['remark' => 'A rejection reason is required.']);
            UrlHelper::redirect('/admin/requests/' . $requestId);
        }
        if ($newStatus === RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED && $remark === '') {
            SessionHelper::flash('status_errors', [
                'remark' => 'Please provide a remark explaining what additional information is needed.',
            ]);
            UrlHelper::redirect('/admin/requests/' . $requestId);
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            try {
                $updateFields = ['status' => $newStatus];
                if ($newStatus === RequestModel::STATUS_REJECTED) {
                    $updateFields['rejection_reason'] = $remark;
                }
                if ($newStatus === RequestModel::STATUS_CANCELLED) {
                    $updateFields['cancellation_reason'] = $remark ?: null;
                    $updateFields['cancelled_by']        = $adminId;
                }

                $requestModel->update($requestId, $updateFields);

                (new RequestStatusHistory())->insert([
                    'request_id'      => $requestId,
                    'previous_status' => $currentStatus,
                    'new_status'      => $newStatus,
                    'changed_by'      => $adminId,
                    'remark'          => $remark !== '' ? $remark : null,
                ]);

                $db->commit();

            } catch (\Throwable $inner) {
                $db->rollBack();
                throw $inner;
            }

            // Notifications — non-fatal
            $residentId = (int)$request['user_id'];
            $refNum     = $request['reference_number'];
            $this->dispatchNotification($newStatus, $residentId, $requestId, $refNum);

            // Email — non-fatal
            $this->dispatchEmail($newStatus, $request, $remark, $adminId);

            // Audit log
            AuditLogService::log(
                $adminId, 'admin',
                AuditLog::ACTION_STATUS_CHANGED,
                'requests',
                'requests', $requestId,
                ['status' => $currentStatus],
                ['status' => $newStatus],
                "Request {$refNum}: status changed from {$currentStatus} to {$newStatus} by admin ID {$adminId}"
            );

            $label = ucwords(str_replace('_', ' ', $newStatus));
            SessionHelper::flash('success', "Request status updated to: {$label}");

        } catch (\Throwable $e) {
            Logger::error('AdminRequestController updateStatus: ' . $e->getMessage());
            SessionHelper::flash('status_errors', ['form' => 'Status update failed. Please try again.']);
        }

        UrlHelper::redirect('/admin/requests/' . $requestId);
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS (mirrors StaffRequestController)
    // -------------------------------------------------------------------------

    private function dispatchNotification(
        string $newStatus,
        int    $residentId,
        int    $requestId,
        string $refNum
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
            Logger::error('AdminRequestController notification: ' . $e->getMessage());
        }
    }

    private function dispatchEmail(
        string $newStatus,
        array  $request,
        string $remark,
        int    $adminId
    ): void {
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT id, first_name, last_name, email FROM users WHERE id = :id"
            );
            $stmt->execute([':id' => $request['user_id']]);
            $user = $stmt->fetch();
            if (!$user) return;

            $cancelledBy = ($newStatus === RequestModel::STATUS_CANCELLED) ? 'Admin' : null;

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
            Logger::error('AdminRequestController email: ' . $e->getMessage());
        }
    }
}
