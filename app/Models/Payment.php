<?php

namespace Emunicipal\Models;

/**
 * Payment model — maps to the `payments` table.
 *
 * Phase 2 columns: id, request_id, payment_method, amount, payment_status,
 *                  selected_by, confirmed_by, confirmed_at, notes,
 *                  created_at, updated_at
 * Phase 3 adds:    transaction_ref
 *
 * Design rules:
 *  - One payment row per request (UNIQUE KEY on request_id).
 *  - Amount snapshotted from document_types.processing_fee — never from client.
 *  - transaction_ref generated server-side — never accepted from client input.
 *  - payment_status transitions:
 *      Cash:   pending → paid (Staff confirms)
 *      Online: online_pending → paid (auto-confirmed on successful simulation)
 *  - Confirmation uses conditional UPDATE (WHERE payment_status IN (...))
 *    so race conditions and repeated calls are safely handled via rowCount().
 *  - request.status is NOT changed by any method in this model.
 */
class Payment extends BaseModel
{
    protected string $table = 'payments';

    // ── Payment method constants ──────────────────────────────────────────────
    /** Phase 2: in-office cash collection, confirmed by Staff. */
    public const METHOD_CASH   = 'cash';
    /** Phase 3: simulated demo online payment, auto-confirmed on success. */
    public const METHOD_ONLINE = 'online';

    // ── Payment status constants ──────────────────────────────────────────────
    /** Cash selected by resident; awaiting physical receipt by Staff. */
    public const STATUS_PENDING        = 'pending';
    /** Fully paid and confirmed (both cash and online). */
    public const STATUS_PAID           = 'paid';
    /**
     * Online demo payment initiated by resident; server auto-confirms on
     * successful simulation before this row becomes STATUS_PAID.
     */
    public const STATUS_ONLINE_PENDING = 'online_pending';

    // ── Demo provider labels (display only, not stored in DB) ────────────────
    public const ONLINE_PROVIDERS = [
        'gcash'          => 'GCash (Demo)',
        'maya'           => 'Maya (Demo)',
        'online_banking' => 'Online Banking (Demo)',
    ];

    // ── Transaction reference prefix ─────────────────────────────────────────
    private const TXN_PREFIX = 'TXN-';

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    /**
     * Find the payment record for a given request, if one exists.
     * Returns false when no payment has been selected yet.
     */
    public function findByRequestId(int $requestId): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT p.*,
                    su.first_name AS selected_first, su.last_name AS selected_last,
                    cu.first_name AS confirmed_first, cu.last_name AS confirmed_last
             FROM payments p
             JOIN users su ON p.selected_by  = su.id
             LEFT JOIN users cu ON p.confirmed_by = cu.id
             WHERE p.request_id = :request_id
             LIMIT 1"
        );
        $stmt->execute([':request_id' => $requestId]);
        return $stmt->fetch();
    }

    // -------------------------------------------------------------------------
    // Write operations
    // -------------------------------------------------------------------------

    /**
     * Insert a pending cash payment record (Phase 2 — unchanged).
     *
     * The amount MUST be read from document_types.processing_fee by the
     * controller — never accepted from client input.
     *
     * @throws \PDOException SQLSTATE 23000 on duplicate request_id.
     */
    public function insertPending(
        int   $requestId,
        float $amount,
        int   $selectedBy
    ): int {
        return $this->insert([
            'request_id'     => $requestId,
            'payment_method' => self::METHOD_CASH,
            'amount'         => $amount,
            'payment_status' => self::STATUS_PENDING,
            'selected_by'    => $selectedBy,
        ]);
    }

    /**
     * Insert an online_pending payment record (Phase 3).
     *
     * Called when the resident initiates the demo online payment flow.
     * transaction_ref is NOT set here — it is set by confirmOnline() after
     * the simulated payment succeeds.
     *
     * @throws \PDOException SQLSTATE 23000 on duplicate request_id.
     */
    public function insertOnlinePending(
        int   $requestId,
        float $amount,
        int   $selectedBy
    ): int {
        return $this->insert([
            'request_id'     => $requestId,
            'payment_method' => self::METHOD_ONLINE,
            'amount'         => $amount,
            'payment_status' => self::STATUS_ONLINE_PENDING,
            'selected_by'    => $selectedBy,
        ]);
    }

    /**
     * Auto-confirm a successful simulated online payment (Phase 3).
     *
     * Uses a conditional UPDATE (WHERE payment_status = 'online_pending') so
     * that race conditions and repeated confirmations return rowCount = 0.
     *
     * Returns:
     *   1 = success (online_pending → paid, transaction_ref recorded)
     *   0 = already paid or not found (idempotent guard)
     */
    public function confirmOnline(
        int    $paymentId,
        string $transactionRef,
        int    $confirmedBy
    ): int {
        $stmt = $this->db->prepare(
            "UPDATE `payments`
             SET `payment_status`  = :paid,
                 `confirmed_by`    = :confirmed_by,
                 `confirmed_at`    = NOW(),
                 `transaction_ref` = :txn_ref
             WHERE `id` = :id
               AND `payment_status` = :online_pending"
        );
        $stmt->execute([
            ':paid'           => self::STATUS_PAID,
            ':confirmed_by'   => $confirmedBy,
            ':txn_ref'        => $transactionRef,
            ':id'             => $paymentId,
            ':online_pending' => self::STATUS_ONLINE_PENDING,
        ]);
        return $stmt->rowCount();
    }

    /**
     * Confirm cash receipt by Staff (Phase 2 — unchanged).
     *
     * Returns:
     *   1 = success (pending → paid)
     *   0 = already confirmed or not found
     */
    public function confirm(
        int    $paymentId,
        int    $confirmedBy,
        ?string $notes = null
    ): int {
        $stmt = $this->db->prepare(
            "UPDATE `payments`
             SET `payment_status` = :paid,
                 `confirmed_by`   = :confirmed_by,
                 `confirmed_at`   = NOW(),
                 `notes`          = :notes
             WHERE `id` = :id
               AND `payment_status` = :pending"
        );
        $stmt->execute([
            ':paid'         => self::STATUS_PAID,
            ':confirmed_by' => $confirmedBy,
            ':notes'        => $notes,
            ':id'           => $paymentId,
            ':pending'      => self::STATUS_PENDING,
        ]);
        return $stmt->rowCount();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a server-side demo transaction reference.
     * Format: TXN-XXXXXXXXXXXXXXXX (16 uppercase hex characters).
     * Never derived from client input.
     */
    public static function generateTransactionRef(): string
    {
        return self::TXN_PREFIX . strtoupper(bin2hex(random_bytes(8)));
    }

    /**
     * Return the display label for a payment method value.
     */
    public static function methodLabel(string $method): string
    {
        return match($method) {
            self::METHOD_CASH   => 'Cash',
            self::METHOD_ONLINE => 'Online Payment (Demo)',
            default             => ucfirst($method),
        };
    }

    /**
     * Return true if the payment is in a terminal paid state.
     */
    public static function isPaid(array $payment): bool
    {
        return $payment['payment_status'] === self::STATUS_PAID;
    }

    /**
     * Return true if the payment is still in a pending state
     * (either cash pending or online pending).
     */
    public static function isAnyPending(array $payment): bool
    {
        return in_array($payment['payment_status'], [
            self::STATUS_PENDING,
            self::STATUS_ONLINE_PENDING,
        ], true);
    }
}
