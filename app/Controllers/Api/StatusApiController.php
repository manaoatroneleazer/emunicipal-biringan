<?php

namespace Emunicipal\Controllers\Api;

use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Models\Request as RequestModel;
use Emunicipal\Models\RequestStatusHistory;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Services\NotificationService;
use Emunicipal\Services\EmailService;
use Emunicipal\Core\Database;
use Emunicipal\Core\Logger;
use Emunicipal\Models\User;

/**
 * StatusApiController — Request Status endpoints.
 *
 * PATCH /api/requests/{id}/status  — Staff: update request status
 * GET   /api/requests/{id}/history — Resident (own) / Staff / Admin: status history
 *
 * Per Req 15.1, Req 7 (workflow), Req 9.6 (Staff management).
 * Reuses Request::allowedTransitions() exactly — no second workflow.
 * Notifications and emails are dispatched using the same services as the web layer.
 */
class StatusApiController extends BaseApiController
{
    // ─────────────────────────────────────────────────────────────────────────
    // PATCH /api/requests/{id}/status  — Staff only
    // ─────────────────────────────────────────────────────────────────────────

    public function updateStatus(string $id): void
    {
        $staffId = $this->requireRole('staff');
        $reqId   = (int)$id;

        $body      = $this->body();
        $newStatus = trim((string)($body['new_status'] ?? ''));
        $remark    = trim((string)($body['remark']     ?? ''));

        if ($newStatus === '') {
            $this->error('Validation failed.', 422, ['new_status' => 'new_status is required.']);
        }

        try {
            $requestModel  = new RequestModel();
            $request       = $requestModel->findByIdForStaff($reqId);

            if (!$request) {
                $this->error('Request not found.', 404);
            }

            $currentStatus = $request['status'];

            // Validate transition (Req 7.2)
            $allowed = RequestModel::allowedTransitions($currentStatus);
            if (!in_array($newStatus, $allowed, true)) {
                $this->error(
                    "Cannot change status from '{$currentStatus}' to '{$newStatus}'.",
                    422,
                    ['new_status' => "Transition from '{$currentStatus}' to '{$newStatus}' is not permitted."]
                );
            }

            // Rejection requires reason (Req 7.3)
            if ($newStatus === RequestModel::STATUS_REJECTED && $remark === '') {
                $this->error('Validation failed.', 422,
                    ['remark' => 'A rejection reason is required.']);
            }
            // Additional info requires remark (Req 7.4)
            if ($newStatus === RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED && $remark === '') {
                $this->error('Validation failed.', 422,
                    ['remark' => 'A remark explaining what is needed is required.']);
            }

            $db = Database::getInstance();
            $db->beginTransaction();
            try {
                $updateFields = ['status' => $newStatus];
                if ($newStatus === RequestModel::STATUS_REJECTED) {
                    $updateFields['rejection_reason'] = $remark;
                }
                if ($newStatus === RequestModel::STATUS_CANCELLED) {
                    $updateFields['cancellation_reason'] = $remark ?: null;
                    $updateFields['cancelled_by']        = $staffId;
                }

                $requestModel->update($reqId, $updateFields);

                (new RequestStatusHistory())->insert([
                    'request_id'      => $reqId,
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

            // Notifications — non-fatal (Req 7.6)
            $residentId = (int)$request['user_id'];
            $refNum     = $request['reference_number'];
            $this->dispatchResidentNotification($newStatus, $residentId, $reqId, $refNum);

            // Email — non-fatal (Req 10.4)
            $this->dispatchEmail($newStatus, $request, $remark, $staffId);

            // Audit log (Req 9.8)
            AuditLogService::log($staffId, 'staff', AuditLog::ACTION_STATUS_CHANGED, 'requests',
                'requests', $reqId,
                ['status' => $currentStatus],
                ['status' => $newStatus],
                "Request {$refNum}: {$currentStatus} → {$newStatus} via API by staff ID {$staffId}");

            $updated = $requestModel->findByIdForStaff($reqId);
            $this->json($updated, 'Status updated.');

        } catch (\Throwable $e) {
            Logger::error('StatusApiController::updateStatus: ' . $e->getMessage());
            $this->error('Failed to update status.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/requests/{id}/history  — Resident (own) / Staff / Admin
    // ─────────────────────────────────────────────────────────────────────────

    public function history(string $id): void
    {
        $authId = $this->requireAuth();
        $reqId  = (int)$id;

        try {
            $request = (new RequestModel())->find($reqId);
            if (!$request) {
                $this->error('Request not found.', 404);
            }

            // Residents can only view their own history (Req 8.8)
            if (AuthMiddleware::getRole() === 'resident' && (int)$request['user_id'] !== $authId) {
                $this->error('Request not found.', 404);
            }

            $history = (new RequestStatusHistory())->forRequest($reqId);
            $this->json($history);

        } catch (\Throwable $e) {
            Logger::error('StatusApiController::history: ' . $e->getMessage());
            $this->error('Failed to load status history.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers — mirror web layer dispatchers exactly
    // ─────────────────────────────────────────────────────────────────────────

    private function dispatchResidentNotification(
        string $newStatus, int $residentId, int $requestId, string $refNum
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
            Logger::error('StatusApiController notification dispatch: ' . $e->getMessage());
        }
    }

    private function dispatchEmail(
        string $newStatus, array $request, string $remark, int $staffId
    ): void {
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = :id");
            $stmt->execute([':id' => $request['user_id']]);
            $user = $stmt->fetch();
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
            Logger::error('StatusApiController email dispatch: ' . $e->getMessage());
        }
    }
}
