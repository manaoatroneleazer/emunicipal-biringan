<?php

namespace Emunicipal\Services;

use Emunicipal\Models\Notification;
use Emunicipal\Core\Logger;

/**
 * NotificationService — creates in-system notification records.
 *
 * Requirements implemented:
 *  Req 16.1 — Create a notification record for each status change event
 *  Req 16.5 — Store: user_id, reference_number, type, title, message, read=0, created_at
 *  database.md §9.2 — Notification types match status transition events
 *  design.md §13.5 — Standard message text templates
 *
 * This service is separate from EmailService.
 * An email failure does not affect notification creation and vice versa.
 *
 * Registration does NOT produce an in-system notification (design.md §13.1 note —
 * the resident is not yet logged in at the point of registration).
 */
class NotificationService
{
    // -------------------------------------------------------------------------
    // Per-event factory methods
    // -------------------------------------------------------------------------

    /**
     * Notification: Request Submitted (→ PENDING).
     * Req 16.1, design.md §13.5
     */
    public static function requestSubmitted(
        int    $userId,
        int    $requestId,
        string $referenceNumber,
        string $documentTypeName
    ): void {
        self::create(
            $userId,
            $requestId,
            $referenceNumber,
            Notification::TYPE_REQUEST_SUBMITTED,
            'Request Submitted',
            "Your request for {$documentTypeName} has been submitted. Reference: {$referenceNumber}"
        );
    }

    /**
     * Notification: Status → Under Verification.
     */
    public static function underVerification(
        int    $userId,
        int    $requestId,
        string $referenceNumber
    ): void {
        self::create(
            $userId,
            $requestId,
            $referenceNumber,
            Notification::TYPE_UNDER_VERIFICATION,
            'Request Under Verification',
            "Your request {$referenceNumber} is now under verification by our staff."
        );
    }

    /**
     * Notification: Status → Additional Information Required.
     */
    public static function additionalInfoRequired(
        int    $userId,
        int    $requestId,
        string $referenceNumber
    ): void {
        self::create(
            $userId,
            $requestId,
            $referenceNumber,
            Notification::TYPE_ADDITIONAL_INFO_REQUIRED,
            'Additional Information Required',
            "Your request {$referenceNumber} requires additional information. Please check the details."
        );
    }

    /**
     * Notification: Status → Approved.
     */
    public static function approved(
        int    $userId,
        int    $requestId,
        string $referenceNumber
    ): void {
        self::create(
            $userId,
            $requestId,
            $referenceNumber,
            Notification::TYPE_APPROVED,
            'Request Approved',
            "Your request {$referenceNumber} has been approved and is being processed."
        );
    }

    /**
     * Notification: Status → Rejected.
     */
    public static function rejected(
        int    $userId,
        int    $requestId,
        string $referenceNumber
    ): void {
        self::create(
            $userId,
            $requestId,
            $referenceNumber,
            Notification::TYPE_REJECTED,
            'Request Rejected',
            "Your request {$referenceNumber} has been rejected. Please check the rejection reason."
        );
    }

    /**
     * Notification: Status → Ready for Claiming.
     */
    public static function readyForClaiming(
        int    $userId,
        int    $requestId,
        string $referenceNumber
    ): void {
        self::create(
            $userId,
            $requestId,
            $referenceNumber,
            Notification::TYPE_READY_FOR_CLAIMING,
            'Document Ready for Claiming',
            "Your document for {$referenceNumber} is ready for claiming at our office."
        );
    }

    /**
     * Notification: Status → Completed.
     */
    public static function completed(
        int    $userId,
        int    $requestId,
        string $referenceNumber
    ): void {
        self::create(
            $userId,
            $requestId,
            $referenceNumber,
            Notification::TYPE_COMPLETED,
            'Request Completed',
            "Your request {$referenceNumber} has been completed. Thank you."
        );
    }

    /**
     * Notification: Status → Cancelled.
     */
    public static function cancelled(
        int    $userId,
        int    $requestId,
        string $referenceNumber
    ): void {
        self::create(
            $userId,
            $requestId,
            $referenceNumber,
            Notification::TYPE_CANCELLED,
            'Request Cancelled',
            "Your request {$referenceNumber} has been cancelled."
        );
    }

    // -------------------------------------------------------------------------
    // Staff/Admin operational fan-out methods (Req 25.1, 25.2)
    // -------------------------------------------------------------------------

    /**
     * Fan-out: New Request Submitted — notify all active Staff/Admin users.
     * (Req 25.1)
     *
     * @param int    $requestId        The new request's ID
     * @param string $referenceNumber  EMU reference number
     * @param string $documentTypeName Document type name
     * @param string $residentName     Resident's full name
     */
    public static function newRequestForStaffAdmin(
        int    $requestId,
        string $referenceNumber,
        string $documentTypeName,
        string $residentName
    ): void {
        try {
            $recipientIds = (new \Emunicipal\Models\User())->activeStaffAdminIds();
            foreach ($recipientIds as $userId) {
                self::create(
                    (int)$userId,
                    $requestId,
                    $referenceNumber,
                    Notification::TYPE_NEW_REQUEST_STAFF,
                    'New Request Submitted',
                    "{$residentName} submitted a new request for {$documentTypeName}. Reference: {$referenceNumber}"
                );
            }
        } catch (\Throwable $e) {
            Logger::error('NotificationService::newRequestForStaffAdmin failed: ' . $e->getMessage());
            // Non-fatal — never rethrow (Req 25.13)
        }
    }

    /**
     * Fan-out: Resident Cancelled Request — notify all active Staff/Admin users.
     * (Req 25.2)
     *
     * @param int    $requestId        The request's ID
     * @param string $referenceNumber  EMU reference number
     * @param string $residentName     Resident's full name
     * @param string $documentTypeName Document type name
     */
    public static function residentCancelledForStaffAdmin(
        int    $requestId,
        string $referenceNumber,
        string $residentName,
        string $documentTypeName
    ): void {
        try {
            $recipientIds = (new \Emunicipal\Models\User())->activeStaffAdminIds();
            foreach ($recipientIds as $userId) {
                self::create(
                    (int)$userId,
                    $requestId,
                    $referenceNumber,
                    Notification::TYPE_RESIDENT_CANCELLED,
                    'Request Cancelled by Resident',
                    "{$residentName} cancelled their request for {$documentTypeName}. Reference: {$referenceNumber}"
                );
            }
        } catch (\Throwable $e) {
            Logger::error('NotificationService::residentCancelledForStaffAdmin failed: ' . $e->getMessage());
            // Non-fatal — never rethrow (Req 25.13)
        }
    }

    /**
     * Fan-out: Resident Submitted Additional Information — notify all active Staff/Admin users.
     * (Req 25.14)
     *
     * Called when a Resident uploads additional documents in response to
     * ADDITIONAL_INFORMATION_REQUIRED status. The request status does NOT change.
     *
     * @param int    $requestId       The request's ID
     * @param string $referenceNumber EMU reference number
     * @param string $residentName    Resident's full name
     * @param string $documentTypeName Document type name
     */
    public static function additionalInfoSubmittedForStaffAdmin(
        int    $requestId,
        string $referenceNumber,
        string $residentName,
        string $documentTypeName
    ): void {
        try {
            $recipientIds = (new \Emunicipal\Models\User())->activeStaffAdminIds();
            foreach ($recipientIds as $userId) {
                self::create(
                    (int)$userId,
                    $requestId,
                    $referenceNumber,
                    Notification::TYPE_ADDITIONAL_INFO_SUBMITTED,
                    'Additional Information Submitted',
                    "{$residentName} submitted additional documents for {$documentTypeName}. Reference: {$referenceNumber}"
                );
            }
        } catch (\Throwable $e) {
            Logger::error('NotificationService::additionalInfoSubmittedForStaffAdmin failed: ' . $e->getMessage());
            // Non-fatal — never rethrow (Req 25.13)
        }
    }
    /**
     * Notification: Cash payment confirmed by Staff.
     * Sent to the Resident after Staff records cash receipt.
     * Created only after a successful confirmation — never before.
     */
    public static function paymentReceived(
        int    $residentId,
        int    $requestId,
        string $referenceNumber,
        string $amount
    ): void {
        self::create(
            $residentId,
            $requestId,
            $referenceNumber,
            Notification::TYPE_PAYMENT_RECEIVED,
            'Payment Confirmed',
            "Cash payment of \u20b1{$amount} for request {$referenceNumber} has been received and confirmed by staff."
        );
    }

    /**
     * Notification: Simulated online payment completed (Phase 3 Demo).
     * Sent to the Resident after a successful demo payment simulation.
     * Created only after the database transaction commits — never before.
     * A failed simulation MUST NOT trigger this notification.
     */
    public static function onlinePaymentReceived(
        int    $residentId,
        int    $requestId,
        string $referenceNumber,
        string $amount,
        string $transactionRef
    ): void {
        self::create(
            $residentId,
            $requestId,
            $referenceNumber,
            Notification::TYPE_ONLINE_PAYMENT_INITIATED,
            'Online Payment Confirmed (Demo)',
            "Demo online payment of \u20b1{$amount} for request {$referenceNumber} was successful. "
                . "Demo transaction ref: {$transactionRef}. "
                . "DEMO ONLY \u2014 no real money was charged."
        );
    }


    // -------------------------------------------------------------------------
    // Core insert helper
    // -------------------------------------------------------------------------

    /**
     * Insert a notification record.
     *
     * Failures are caught and logged — never thrown to callers.
     * A notification failure must not break the triggering operation
     * (same principle as email failure non-fatality — Req 10.4 pattern).
     */
    public static function create(
        int    $userId,
        ?int   $requestId,
        ?string $referenceNumber,
        string  $type,
        string  $title,
        string  $message
    ): void {
        try {
            (new Notification())->insert([
                'user_id'          => $userId,
                'request_id'       => $requestId,
                'reference_number' => $referenceNumber,
                'type'             => $type,
                'title'            => $title,
                'message'          => $message,
                'is_read'          => 0,
            ]);
        } catch (\Throwable $e) {
            Logger::error('NotificationService: failed to create notification', [
                'type'    => $type,
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            // Non-fatal — never rethrow
        }
    }
}
