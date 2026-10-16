<?php

namespace Emunicipal\Controllers\Staff;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Models\Request as RequestModel;
use Emunicipal\Models\Payment;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Services\NotificationService;
use Emunicipal\Core\Database;
use Emunicipal\Core\Logger;

/**
 * StaffPaymentController — Staff cash payment confirmation.
 *
 * Route:
 *  POST /staff/requests/{id}/payment/confirm  confirm()
 *
 * Security:
 *  - requireRole('staff') on every method.
 *  - Residents can never reach this controller.
 *  - CSRF validated on POST.
 *  - Request status gate: must be ready_for_claiming.
 *  - Payment existence gate: a pending payment row must exist.
 *  - Conditional UPDATE guards against double-confirmation (race-safe).
 *  - request.status is NOT changed by this controller.
 *  - Resident notification created only AFTER successful confirmation.
 */
class StaffPaymentController extends BaseController
{
    // -------------------------------------------------------------------------
    // POST /staff/requests/{id}/payment/confirm
    // -------------------------------------------------------------------------

    /**
     * Record that cash was physically received from the resident.
     *
     * Validation order (fail-fast):
     *  1. Authentication + role (staff)
     *  2. CSRF
     *  3. Request exists
     *  4. Request status = ready_for_claiming
     *  5. Pending payment row exists for this request
     *  6. Notes length (<= 500 chars, optional)
     *
     * On success: conditional UPDATE payment_status pending -> paid,
     *             audit log written, resident notification created.
     */
    public function confirm(string $id): void
    {
        AuthMiddleware::requireRole('staff');
        $staffId   = AuthMiddleware::userId();
        $requestId = (int)$id;

        // CSRF
        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('error', 'Invalid security token. Please try again.');
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        // Load request
        try {
            $requestModel = new RequestModel();
            $request      = $requestModel->findByIdForStaff($requestId);
        } catch (\Throwable $e) {
            Logger::error('StaffPaymentController confirm (load request): ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load request. Please try again.');
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        if (!$request) {
            SessionHelper::flash('error', 'Request not found.');
            UrlHelper::redirect('/staff/requests');
        }

        // Status gate
        if ($request['status'] !== RequestModel::STATUS_READY_FOR_CLAIMING) {
            SessionHelper::flash('error', 'Payment confirmation is only available when the request is ready for claiming.');
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        // Load pending payment
        try {
            $paymentModel = new Payment();
            $payment      = $paymentModel->findByRequestId($requestId);
        } catch (\Throwable $e) {
            Logger::error('StaffPaymentController confirm (load payment): ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load payment record. Please try again.');
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        if ($payment === false) {
            SessionHelper::flash('error', 'No payment record found for this request. The resident must select a payment method first.');
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        if ($payment['payment_status'] !== Payment::STATUS_PENDING) {
            SessionHelper::flash('error', 'This payment has already been confirmed.');
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        // Validate optional notes
        $notes = trim($_POST['notes'] ?? '');
        if (mb_strlen($notes) > 500) {
            SessionHelper::flash('error', 'Notes must not exceed 500 characters.');
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        $paymentId  = (int)$payment['id'];
        $amount     = (float)$payment['amount'];
        $residentId = (int)$request['user_id'];
        $refNum     = $request['reference_number'];

        // --- Conditional UPDATE in a transaction ---
        // WHERE payment_status = 'pending' ensures idempotency under concurrency.
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $rowsAffected = $paymentModel->confirm(
                $paymentId,
                $staffId,
                $notes !== '' ? $notes : null
            );

            if ($rowsAffected === 0) {
                // Another request already confirmed — rollback, show clean error
                $db->rollBack();
                SessionHelper::flash('error', 'This payment was already confirmed by another action.');
                UrlHelper::redirect('/staff/requests/' . $requestId);
            }

            $db->commit();

        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error('StaffPaymentController confirm DB error: ' . $e->getMessage());
            SessionHelper::flash('error', 'Payment confirmation failed. Please try again.');
            UrlHelper::redirect('/staff/requests/' . $requestId);
        }

        // Audit log — non-fatal
        AuditLogService::log(
            $staffId, 'staff',
            AuditLog::ACTION_PAYMENT_RECORDED,
            'payments',
            'payments', $paymentId,
            ['payment_status' => Payment::STATUS_PENDING],
            ['payment_status' => Payment::STATUS_PAID, 'confirmed_by' => $staffId],
            "Staff ID {$staffId} confirmed cash receipt (PHP {$amount}) for request {$refNum}"
        );

        // Resident notification — created only after successful confirmation, non-fatal
        NotificationService::paymentReceived(
            $residentId,
            $requestId,
            $refNum,
            number_format($amount, 2)
        );

        SessionHelper::flash('success', 'Cash payment of PHP ' . number_format($amount, 2) . ' confirmed for ' . htmlspecialchars($refNum, ENT_QUOTES, 'UTF-8') . '.');
        UrlHelper::redirect('/staff/requests/' . $requestId);
    }
}
