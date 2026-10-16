<?php

namespace Emunicipal\Controllers\Resident;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Models\Request as RequestModel;
use Emunicipal\Models\DocumentType;
use Emunicipal\Models\Payment;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Services\NotificationService;
use Emunicipal\Services\QrService;
use Emunicipal\Core\Database;
use Emunicipal\Core\Logger;

/**
 * PaymentController — Resident payment selection and simulated online payment.
 *
 * Routes:
 *  GET  /resident/requests/{id}/payment               show()           Redirect to detail.
 *  POST /resident/requests/{id}/payment               selectCash()     Cash selection (Phase 2).
 *  GET  /resident/requests/{id}/payment/online        simulateOnline() Show demo payment page.
 *  POST /resident/requests/{id}/payment/online        processOnline()  Run simulation server-side.
 *
 * Security (all methods):
 *  - requireRole('resident')
 *  - Ownership: resident may only pay for their own request.
 *  - CSRF on every POST.
 *  - Status gate: request must be ready_for_claiming.
 *  - Fee gate: processing_fee > 0 (free documents need no payment).
 *  - Duplicate gate: no payment row may already exist.
 *  - Amount read from DB — never from client input.
 *  - transaction_ref generated server-side — never from client input.
 *  - request.status is NOT changed by this controller.
 *
 * Phase 3 online payment is SIMULATED / DEMO ONLY.
 * No real payment gateway is contacted. No real money is charged.
 */
class PaymentController extends BaseController
{
    // -------------------------------------------------------------------------
    // GET /resident/requests/{id}/payment
    // -------------------------------------------------------------------------

    /**
     * The payment card is embedded in the request detail page.
     * This route simply redirects there.
     */
    public function show(string $id): void
    {
        UrlHelper::redirect('/resident/requests/' . (int)$id);
    }

    // -------------------------------------------------------------------------
    // POST /resident/requests/{id}/payment  — Cash selection (Phase 2, unchanged)
    // -------------------------------------------------------------------------

    /**
     * Resident selects cash as payment method.
     */
    public function selectCash(string $id): void
    {
        AuthMiddleware::requireRole('resident');
        $userId    = AuthMiddleware::userId();
        $requestId = (int)$id;

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('error', 'Invalid security token. Please try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        [$request, $fee] = $this->loadAndValidateRequest($requestId, $userId);

        $method = trim($_POST['payment_method'] ?? '');
        if ($method !== Payment::METHOD_CASH) {
            SessionHelper::flash('error', 'Only cash payment is supported at this time.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        $this->assertNoPriorPayment($requestId);

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $paymentModel = new Payment();
            $paymentId    = $paymentModel->insertPending($requestId, $fee, $userId);
            $db->commit();
        } catch (\PDOException $e) {
            $db->rollBack();
            if (str_starts_with((string)$e->getCode(), '23')) {
                SessionHelper::flash('error', 'A payment method has already been selected for this request.');
            } else {
                Logger::error('PaymentController selectCash DB error: ' . $e->getMessage());
                SessionHelper::flash('error', 'Payment could not be saved. Please try again.');
            }
            UrlHelper::redirect('/resident/requests/' . $requestId);
        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error('PaymentController selectCash error: ' . $e->getMessage());
            SessionHelper::flash('error', 'Payment could not be saved. Please try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        AuditLogService::log(
            $userId, 'resident',
            AuditLog::ACTION_PAYMENT_RECORDED,
            'payments', 'payments', $paymentId,
            null,
            ['method' => Payment::METHOD_CASH, 'amount' => $fee, 'status' => Payment::STATUS_PENDING],
            "Resident ID {$userId} selected cash payment (PHP {$fee}) for request ID {$requestId}"
        );

        SessionHelper::flash('success', 'Cash payment method selected. Please bring ₱' . number_format($fee, 2) . ' when you collect your document.');
        UrlHelper::redirect('/resident/requests/' . $requestId);
    }

    // -------------------------------------------------------------------------
    // GET /resident/requests/{id}/payment/online — Demo payment page (Phase 3)
    // -------------------------------------------------------------------------

    /**
     * Display the simulated online payment selection page.
     *
     * Validates all eligibility gates before rendering.
     * If already paid, redirects back to request detail.
     */
    public function simulateOnline(string $id): void
    {
        AuthMiddleware::requireRole('resident');
        $userId    = AuthMiddleware::userId();
        $requestId = (int)$id;

        // Full request load + all gates
        [$request, $fee] = $this->loadAndValidateRequest($requestId, $userId);

        // Check for existing payment
        try {
            $existing = (new Payment())->findByRequestId($requestId);
        } catch (\Throwable $e) {
            Logger::error('PaymentController simulateOnline (check existing): ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to verify payment status. Please try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        if ($existing !== false) {
            // Already selected or paid — go back to detail
            SessionHelper::flash('error', 'A payment method has already been selected for this request.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        // Load document type name for display
        $docTypeName = '';
        try {
            $dt = (new \Emunicipal\Models\DocumentType())->find((int)$request['document_type_id']);
            $docTypeName = $dt['name'] ?? '';
        } catch (\Throwable $e) {}

        // Generate demo QR code (SVG data URI — no GD required, no sensitive data)
        $qrDataUri  = '';
        $qrPayload  = '';
        try {
            $qrDataUri = QrService::generateDemoQr($request['reference_number'] ?? '', $fee);
            $qrPayload = QrService::demoPayload($request['reference_number'] ?? '', $fee);
        } catch (\Throwable $e) {
            // QR generation is non-fatal — page renders without QR on failure
            Logger::error('PaymentController simulateOnline (QR generation): ' . $e->getMessage());
        }

        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');

        $this->renderWithLayout('resident/payments/online_select', [
            'title'       => 'Online Payment (Demo) — eMunicipal',
            'activePage'  => 'requests',
            'request'     => $request,
            'docTypeName' => $docTypeName,
            'fee'         => $fee,
            'providers'   => Payment::ONLINE_PROVIDERS,
            'csrfToken'   => SecurityHelper::getCsrfToken(),
            'qrDataUri'   => $qrDataUri,
            'qrPayload'   => $qrPayload,
        ], 'layouts/resident');
    }

    // -------------------------------------------------------------------------
    // POST /resident/requests/{id}/payment/online — Run simulation (Phase 3)
    // -------------------------------------------------------------------------

    /**
     * Process the simulated online payment.
     *
     * Security:
     *  All gates re-checked server-side (CSRF, ownership, status, fee, duplicate).
     *  Amount read from DB — never from client.
     *  transaction_ref generated server-side via random_bytes().
     *  'simulate_result' accepted from client ONLY to allow failure simulation;
     *    it can only produce a failed result — never directly sets payment_status=paid.
     *    A successful payment is only created by the server's internal logic.
     *
     * On success: INSERT online_pending row, immediately confirmOnline() → paid,
     *             audit log, notification, redirect to receipt page.
     * On failure: no DB write, redirect back with failure flash.
     */
    public function processOnline(string $id): void
    {
        AuthMiddleware::requireRole('resident');
        $userId    = AuthMiddleware::userId();
        $requestId = (int)$id;

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('error', 'Invalid security token. Please try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        [$request, $fee] = $this->loadAndValidateRequest($requestId, $userId);

        // Validate chosen provider
        $provider = trim($_POST['provider'] ?? '');
        if (!array_key_exists($provider, Payment::ONLINE_PROVIDERS)) {
            SessionHelper::flash('error', 'Invalid payment provider selected.');
            UrlHelper::redirect('/resident/requests/' . $requestId . '/payment/online');
        }

        $this->assertNoPriorPayment($requestId);

        // ── Simulate success or failure ────────────────────────────────────────
        // The client may submit simulate_result=fail to test the failure path.
        // The server decides the outcome — no client value can force a paid record.
        $simulateResult = trim($_POST['simulate_result'] ?? 'success');
        $isSuccess      = ($simulateResult !== 'fail');

        if (!$isSuccess) {
            // Failed simulation: no DB write, no notification, no audit
            SessionHelper::flash('error', 'Demo payment simulation failed. No payment was recorded. You may try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId . '/payment/online');
        }

        // ── Successful simulation ──────────────────────────────────────────────
        // Generate server-side transaction reference
        $transactionRef  = Payment::generateTransactionRef();
        $providerLabel   = Payment::ONLINE_PROVIDERS[$provider] ?? $provider;

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $paymentModel = new Payment();

            // Step 1: INSERT with online_pending status
            $paymentId = $paymentModel->insertOnlinePending($requestId, $fee, $userId);

            // Step 2: Immediately confirm (auto-confirm on successful simulation)
            // The conditional UPDATE (WHERE payment_status='online_pending') is
            // the server-side gate — client can never bypass this.
            $rows = $paymentModel->confirmOnline($paymentId, $userId, $transactionRef);

            if ($rows !== 1) {
                // Should never happen within the same transaction, but guard anyway
                $db->rollBack();
                Logger::error("PaymentController processOnline: confirmOnline returned {$rows} for payment {$paymentId}");
                SessionHelper::flash('error', 'Payment simulation could not be completed. Please try again.');
                UrlHelper::redirect('/resident/requests/' . $requestId . '/payment/online');
            }

            $db->commit();

        } catch (\PDOException $e) {
            $db->rollBack();
            if (str_starts_with((string)$e->getCode(), '23')) {
                SessionHelper::flash('error', 'A payment method has already been selected for this request.');
            } else {
                Logger::error('PaymentController processOnline DB error: ' . $e->getMessage());
                SessionHelper::flash('error', 'Payment could not be saved. Please try again.');
            }
            UrlHelper::redirect('/resident/requests/' . $requestId . '/payment/online');
        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error('PaymentController processOnline error: ' . $e->getMessage());
            SessionHelper::flash('error', 'Payment could not be saved. Please try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId . '/payment/online');
        }

        // ── Post-commit: audit + notification (both non-fatal) ─────────────────
        AuditLogService::log(
            $userId, 'resident',
            AuditLog::ACTION_PAYMENT_RECORDED,
            'payments', 'payments', $paymentId,
            null,
            [
                'method'          => Payment::METHOD_ONLINE,
                'provider'        => $provider,
                'amount'          => $fee,
                'status'          => Payment::STATUS_PAID,
                'transaction_ref' => $transactionRef,
                'demo'            => true,
            ],
            "Resident ID {$userId} completed demo online payment (₱{$fee}) via {$providerLabel} for request ID {$requestId}. Ref: {$transactionRef}"
        );

        NotificationService::onlinePaymentReceived(
            $userId,
            $requestId,
            $request['reference_number'],
            number_format($fee, 2),
            $transactionRef
        );

        // ── Store receipt data in session for the receipt view ─────────────────
        SessionHelper::flash('online_receipt', [
            'reference_number' => $request['reference_number'],
            'doc_type_name'    => '', // will be looked up in receipt view if needed
            'amount'           => $fee,
            'provider'         => $provider,
            'provider_label'   => $providerLabel,
            'transaction_ref'  => $transactionRef,
            'paid_at'          => date('Y-m-d H:i:s'),
        ]);

        UrlHelper::redirect('/resident/requests/' . $requestId . '/payment/receipt');
    }

    // -------------------------------------------------------------------------
    // GET /resident/requests/{id}/payment/receipt — Demo receipt page (Phase 3)
    // -------------------------------------------------------------------------

    /**
     * Display the demo payment receipt after a successful simulation.
     * Data is read from the session flash set by processOnline().
     * If the flash is missing (e.g. direct navigation), redirect to request detail.
     */
    public function receipt(string $id): void
    {
        AuthMiddleware::requireRole('resident');
        $userId    = AuthMiddleware::userId();
        $requestId = (int)$id;

        $receiptData = SessionHelper::getFlash('online_receipt');
        if (!$receiptData) {
            // No receipt data — redirect to request detail
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        // Enrich with current request info (ownership check)
        try {
            $request = (new RequestModel())->find($requestId);
        } catch (\Throwable $e) {
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }
        if (!$request || (int)$request['user_id'] !== $userId) {
            UrlHelper::redirect('/resident/requests');
        }

        // Fetch document type name
        $docTypeName = '';
        try {
            $dt = (new \Emunicipal\Models\DocumentType())->find((int)$request['document_type_id']);
            $docTypeName = $dt['name'] ?? '';
        } catch (\Throwable $e) {}

        $receiptData['doc_type_name'] = $docTypeName;

        $this->renderWithLayout('resident/payments/online_receipt', [
            'title'      => 'Payment Receipt (Demo) — eMunicipal',
            'activePage' => 'requests',
            'request'    => $request,
            'receipt'    => $receiptData,
        ], 'layouts/resident');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Load request, verify ownership and eligibility.
     * Redirects on any failure. Returns [$request, $fee] on success.
     */
    private function loadAndValidateRequest(int $requestId, int $userId): array
    {
        // Load request
        try {
            $request = (new RequestModel())->find($requestId);
        } catch (\Throwable $e) {
            Logger::error('PaymentController loadAndValidateRequest (load request): ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load request. Please try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        // Existence + ownership
        if (!$request || (int)$request['user_id'] !== $userId) {
            SessionHelper::flash('error', 'Request not found.');
            UrlHelper::redirect('/resident/requests');
        }

        // Status gate
        if ($request['status'] !== RequestModel::STATUS_READY_FOR_CLAIMING) {
            SessionHelper::flash('error', 'Payment is only available when the request is ready for claiming.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        // Fee gate — read from DB, never from client
        try {
            $docType = (new DocumentType())->find((int)$request['document_type_id']);
        } catch (\Throwable $e) {
            Logger::error('PaymentController loadAndValidateRequest (load docType): ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load document details. Please try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        $fee = (float)($docType['processing_fee'] ?? 0.00);
        if ($fee <= 0.00) {
            SessionHelper::flash('error', 'This document has no processing fee; no payment is required.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        return [$request, $fee];
    }

    /**
     * Check that no payment row exists for this request.
     * Redirects with a flash error if one already exists.
     */
    private function assertNoPriorPayment(int $requestId): void
    {
        try {
            $existing = (new Payment())->findByRequestId($requestId);
        } catch (\Throwable $e) {
            Logger::error('PaymentController assertNoPriorPayment: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to verify payment status. Please try again.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }

        if ($existing !== false) {
            SessionHelper::flash('error', 'A payment method has already been selected for this request.');
            UrlHelper::redirect('/resident/requests/' . $requestId);
        }
    }
}
