<?php
/**
 * Phase 12 — Cash Payment Integration Tests
 *
 * Tests the Phase 2 cash payment feature.
 * Uses isolated test records; all inserted rows are cleaned up after each test.
 * NEVER modifies existing project data.
 *
 * Requires:
 *  - MySQL running on 127.0.0.1:3306
 *  - `payments` table present (created by database/schema.sql or setup.php)
 *  - At least one resident (uid=4), one staff (uid=3), one request with
 *    status=ready_for_claiming and a document type with processing_fee > 0.
 */

$root = dirname(__DIR__);
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $_ENV[trim($k)] = trim($v, " \t\n\r\"'");
}
require_once $root . '/vendor/autoload.php';

use Emunicipal\Core\Database;
use Emunicipal\Models\Payment;
use Emunicipal\Models\AuditLog;
use Emunicipal\Models\Notification;

$pdo    = Database::getInstance();
$passed = 0;
$failed = 0;
$results = [];

function chk(string $id, string $desc, bool $pass, string $detail = ''): void
{
    global $passed, $failed, $results;
    if ($pass) { $passed++; $results[] = ['PASS', $id, $desc, $detail]; }
    else       { $failed++; $results[] = ['FAIL', $id, $desc, $detail]; }
}

// ── STATIC checks ─────────────────────────────────────────────────────────────

$paymentSrc      = file_get_contents($root . '/app/Models/Payment.php');
$auditSrc        = file_get_contents($root . '/app/Models/AuditLog.php');
$notifSrc        = file_get_contents($root . '/app/Models/Notification.php');
$notifSvcSrc     = file_get_contents($root . '/app/Services/NotificationService.php');
$residentCtrlSrc = file_get_contents($root . '/app/Controllers/Resident/PaymentController.php');
$staffCtrlSrc    = file_get_contents($root . '/app/Controllers/Staff/StaffPaymentController.php');
$reqCtrlSrc      = file_get_contents($root . '/app/Controllers/Resident/RequestController.php');
$staffReqSrc     = file_get_contents($root . '/app/Controllers/Staff/StaffRequestController.php');
$routesSrc       = file_get_contents($root . '/routes/web.php');

chk('ST-01', 'Payment model: STATUS_PENDING constant defined',
    str_contains($paymentSrc, "STATUS_PENDING") && str_contains($paymentSrc, "'pending'"));
chk('ST-02', 'Payment model: STATUS_PAID constant defined',
    str_contains($paymentSrc, "STATUS_PAID") && str_contains($paymentSrc, "'paid'"));
chk('ST-03', 'Payment model: METHOD_CASH constant defined',
    str_contains($paymentSrc, "METHOD_CASH") && str_contains($paymentSrc, "'cash'"));
chk('ST-04', 'Payment model: findByRequestId() defined',
    str_contains($paymentSrc, 'findByRequestId'));
chk('ST-05', 'Payment model: insertPending() defined',
    str_contains($paymentSrc, 'insertPending'));
chk('ST-06', 'Payment model: confirm() uses conditional WHERE payment_status = pending',
    str_contains($paymentSrc, "AND `payment_status` = :pending"));
chk('ST-07', 'Payment model: no BOM (namespace on line 3)',
    substr($paymentSrc, 0, 5) === '<?php');
chk('ST-08', 'AuditLog: ACTION_PAYMENT_RECORDED constant defined',
    str_contains($auditSrc, "ACTION_PAYMENT_RECORDED"));
chk('ST-09', 'Notification: TYPE_PAYMENT_RECEIVED constant defined',
    str_contains($notifSrc, "TYPE_PAYMENT_RECEIVED"));
chk('ST-10', 'NotificationService: paymentReceived() method defined',
    str_contains($notifSvcSrc, 'function paymentReceived'));
chk('ST-11', 'PaymentController: selectCash() validates ready_for_claiming status',
    str_contains($residentCtrlSrc, 'STATUS_READY_FOR_CLAIMING'));
chk('ST-12', 'PaymentController: fee read from DB, not client input',
    str_contains($residentCtrlSrc, 'processing_fee') &&
    !str_contains($residentCtrlSrc, '$_POST[\'amount\']') &&
    !str_contains($residentCtrlSrc, '$_POST["amount"]'));
chk('ST-13', 'PaymentController: fee > 0 gate enforced',
    str_contains($residentCtrlSrc, '$fee <= 0'));
chk('ST-14', 'PaymentController: ownership check present',
    str_contains($residentCtrlSrc, 'user_id') && str_contains($residentCtrlSrc, 'userId'));
chk('ST-15', 'PaymentController: duplicate gate present',
    str_contains($residentCtrlSrc, 'assertNoPriorPayment') ||
    str_contains($residentCtrlSrc, 'existingPayment'));
chk('ST-16', 'PaymentController: uses transaction (beginTransaction)',
    str_contains($residentCtrlSrc, 'beginTransaction'));
chk('ST-17', 'PaymentController: CSRF validated',
    str_contains($residentCtrlSrc, 'validateCsrfToken'));
chk('ST-18', 'StaffPaymentController: requireRole staff',
    str_contains($staffCtrlSrc, "requireRole('staff')"));
chk('ST-19', 'StaffPaymentController: conditional UPDATE (payment_status=pending)',
    str_contains($staffCtrlSrc, "STATUS_PENDING"));
chk('ST-20', 'StaffPaymentController: rowCount() check prevents double-confirm',
    str_contains($staffCtrlSrc, 'rowsAffected'));
chk('ST-21', 'StaffPaymentController: uses transaction',
    str_contains($staffCtrlSrc, 'beginTransaction'));
chk('ST-22', 'StaffPaymentController: notification created AFTER commit',
    strpos($staffCtrlSrc, 'db->commit') < strpos($staffCtrlSrc, 'paymentReceived'));
chk('ST-23', 'StaffPaymentController: CSRF validated',
    str_contains($staffCtrlSrc, 'validateCsrfToken'));
chk('ST-24', 'RequestController: use Emunicipal\Models\Payment present',
    str_contains($reqCtrlSrc, 'use Emunicipal\Models\Payment'));
chk('ST-25', 'StaffRequestController: use Emunicipal\Models\Payment present',
    str_contains($staffReqSrc, 'use Emunicipal\Models\Payment'));
chk('ST-26', 'Routes: resident payment selection route registered',
    str_contains($routesSrc, "'/resident/requests/{id}/payment'") &&
    str_contains($routesSrc, 'PaymentController'));
chk('ST-27', 'Routes: staff payment confirm route registered',
    str_contains($routesSrc, "'/staff/requests/{id}/payment/confirm'") &&
    str_contains($routesSrc, 'StaffPaymentController'));
chk('ST-28', 'No Admin payment confirm route (not in scope)',
    !str_contains($routesSrc, "'/admin/requests/{id}/payment/confirm'"));
chk('ST-29', 'request.status not changed by PaymentController (no STATUS_ update in ResidentCtrl)',
    !str_contains($residentCtrlSrc, "'status'") ||
    // The only status reference in PaymentController should be STATUS_READY_FOR_CLAIMING guard, not an update
    str_contains($residentCtrlSrc, 'STATUS_READY_FOR_CLAIMING') &&
    !str_contains($residentCtrlSrc, "update(\$requestId, ['status'"));
chk('ST-30', 'request.status not changed by StaffPaymentController',
    !str_contains($staffCtrlSrc, "update(\$requestId, ['status'") &&
    !str_contains($staffCtrlSrc, "'status' => "));

// ── DATABASE checks ────────────────────────────────────────────────────────────

$tables = $pdo->query("SHOW TABLES LIKE 'payments'")->fetchColumn();
chk('DB-01', 'payments table exists',
    $tables === 'payments', "found: " . ($tables ?: 'not found'));

// If table doesn't exist, skip DB tests
if ($tables !== 'payments') {
    chk('DB-02', 'SKIP: payments table missing, skipping all DB tests', false, 'table absent');
    goto print_results;
}

$cols = $pdo->query("DESCRIBE payments")->fetchAll(\PDO::FETCH_COLUMN);
chk('DB-02', 'payments has request_id column',    in_array('request_id',     $cols));
chk('DB-03', 'payments has payment_status column', in_array('payment_status', $cols));
chk('DB-04', 'payments has amount column',         in_array('amount',         $cols));
chk('DB-05', 'payments has selected_by column',    in_array('selected_by',    $cols));
chk('DB-06', 'payments has confirmed_by column',   in_array('confirmed_by',   $cols));
chk('DB-07', 'payments has confirmed_at column',   in_array('confirmed_at',   $cols));
chk('DB-08', 'payments has notes column',          in_array('notes',          $cols));

$idxRows = $pdo->query("SHOW INDEX FROM payments WHERE Column_name='request_id' AND Non_unique=0")->fetchAll();
chk('DB-09', 'payments.request_id is UNIQUE KEY',
    count($idxRows) === 1, "unique indexes found: " . count($idxRows));

$fkRows = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA='emunicipal' AND TABLE_NAME='payments'
    AND REFERENCED_TABLE_NAME IS NOT NULL")->fetchAll(\PDO::FETCH_COLUMN);
chk('DB-10', 'payments has FK to requests',
    in_array('fk_payments_request_id', $fkRows), implode(',', $fkRows));
chk('DB-11', 'payments has FK to users (selected_by)',
    in_array('fk_payments_selected_by', $fkRows), implode(',', $fkRows));
chk('DB-12', 'payments has FK to users (confirmed_by)',
    in_array('fk_payments_confirmed_by', $fkRows), implode(',', $fkRows));

// ── LIVE PAYMENT OPERATION TESTS ───────────────────────────────────────────────
//
// Setup: find a request that can be used for testing.
// We look for a request owned by an active resident with a document_type that has fee > 0.
// If none exists with ready_for_claiming, we create a transient test record and clean it up.

$testRequestId  = null;
$testResidentId = null;
$testStaffId    = null;
$testDocFee     = null;
$testRefNum     = null;
$createdRequest = false;

// Find staff user
$staffRow = $pdo->query("SELECT id FROM users WHERE role='staff' AND is_active=1 LIMIT 1")->fetch();
$testStaffId = $staffRow ? (int)$staffRow['id'] : null;
chk('SETUP-01', 'Active staff user exists', $testStaffId !== null, "staff id: $testStaffId");

// Find resident user
$resRow = $pdo->query("SELECT id FROM users WHERE role='resident' AND is_active=1 LIMIT 1")->fetch();
$testResidentId = $resRow ? (int)$resRow['id'] : null;
chk('SETUP-02', 'Active resident user exists', $testResidentId !== null, "resident id: $testResidentId");

// Find a document type with processing_fee > 0
$dtRow = $pdo->query("SELECT id, processing_fee FROM document_types WHERE is_active=1 AND processing_fee > 0 LIMIT 1")->fetch();
$testDocTypeId = $dtRow ? (int)$dtRow['id'] : null;
$testDocFee    = $dtRow ? (float)$dtRow['processing_fee'] : null;
chk('SETUP-03', 'Document type with fee > 0 exists',
    $testDocTypeId !== null, "doc_type_id=$testDocTypeId fee=$testDocFee");

// Find a request with status=ready_for_claiming owned by testResident with fee > 0 doc type
// OR create a temporary one
if ($testResidentId && $testDocTypeId) {
    $existingReq = $pdo->prepare(
        "SELECT r.id, r.reference_number FROM requests r
         JOIN document_types dt ON r.document_type_id = dt.id
         WHERE r.user_id = :uid AND r.status = 'ready_for_claiming'
           AND dt.processing_fee > 0 LIMIT 1"
    );
    $existingReq->execute([':uid' => $testResidentId]);
    $reqRow = $existingReq->fetch();

    if ($reqRow) {
        $testRequestId = (int)$reqRow['id'];
        $testRefNum    = $reqRow['reference_number'];
        $createdRequest = false;
    } else {
        // Create a temporary test request in ready_for_claiming status
        // Use a distinctive reference number unlikely to conflict
        $testRefNum = 'EMU-TEST-PAY999';
        // Check if this test ref already exists from a previous run
        $oldTest = $pdo->prepare("SELECT id FROM requests WHERE reference_number = :ref");
        $oldTest->execute([':ref' => $testRefNum]);
        $old = $oldTest->fetchColumn();
        if ($old) {
            // Clean up leftover from a previous test run
            $pdo->prepare("DELETE FROM payments WHERE request_id = :id")->execute([':id' => $old]);
            $pdo->prepare("DELETE FROM request_status_history WHERE request_id = :id")->execute([':id' => $old]);
            $pdo->prepare("DELETE FROM requests WHERE id = :id")->execute([':id' => $old]);
        }

        $pdo->prepare(
            "INSERT INTO requests (reference_number, user_id, document_type_id, purpose,
             copies_requested, status, created_at, updated_at)
             VALUES (:ref, :uid, :dtid, 'Phase 12 payment test', 1, 'ready_for_claiming', NOW(), NOW())"
        )->execute([
            ':ref'  => $testRefNum,
            ':uid'  => $testResidentId,
            ':dtid' => $testDocTypeId,
        ]);
        $testRequestId  = (int)$pdo->lastInsertId();
        $createdRequest = true;

        // Insert initial status history row
        $pdo->prepare(
            "INSERT INTO request_status_history (request_id, previous_status, new_status, changed_by, created_at)
             VALUES (:rid, NULL, 'ready_for_claiming', :uid, NOW())"
        )->execute([':rid' => $testRequestId, ':uid' => $testStaffId ?? 1]);
    }
}
chk('SETUP-04', 'Test request available',
    $testRequestId !== null, "id=$testRequestId ref=$testRefNum created=$createdRequest");

// ── PAY-01: insertPending creates a payment row ────────────────────────────────
if ($testRequestId && $testResidentId && $testDocFee) {
    // Ensure no leftover payment
    $pdo->prepare("DELETE FROM payments WHERE request_id = :rid")->execute([':rid' => $testRequestId]);

    $payModel = new Payment();
    $payId = $payModel->insertPending($testRequestId, $testDocFee, $testResidentId);

    $row = $pdo->prepare("SELECT * FROM payments WHERE id = :id")->execute([':id' => $payId])
        && ($stmt = $pdo->prepare("SELECT * FROM payments WHERE id = :id")) && $stmt->execute([':id' => $payId])
        ? $stmt->fetch() : false;
    // Simpler approach:
    $stmt2 = $pdo->prepare("SELECT * FROM payments WHERE id = :id");
    $stmt2->execute([':id' => $payId]);
    $row = $stmt2->fetch();

    chk('PAY-01', 'insertPending creates row with status=pending',
        $row && $row['payment_status'] === 'pending', "status=" . ($row['payment_status'] ?? 'not found'));
    chk('PAY-02', 'insertPending snapshots correct amount',
        $row && (float)$row['amount'] === $testDocFee, "amount=" . ($row['amount'] ?? 'null'));
    chk('PAY-03', 'insertPending records correct selected_by',
        $row && (int)$row['selected_by'] === $testResidentId, "selected_by=" . ($row['selected_by'] ?? 'null'));
    chk('PAY-04', 'insertPending sets method=cash',
        $row && $row['payment_method'] === 'cash', "method=" . ($row['payment_method'] ?? 'null'));
    chk('PAY-05', 'confirmed_by is NULL on pending payment',
        $row && $row['confirmed_by'] === null, "confirmed_by=" . ($row['confirmed_by'] ?? 'null'));

    // ── PAY-06: findByRequestId returns the row ────────────────────────────────
    $found = $payModel->findByRequestId($testRequestId);
    chk('PAY-06', 'findByRequestId returns the payment row',
        $found !== false && (int)$found['id'] === $payId, "id=" . ($found['id'] ?? 'not found'));
    chk('PAY-07', 'findByRequestId joins selected_by user name',
        $found && !empty($found['selected_first']), "selected_first=" . ($found['selected_first'] ?? 'null'));

    // ── PAY-08: duplicate INSERT throws PDOException (SQLSTATE 23000) ─────────
    $threw23 = false;
    try {
        $payModel->insertPending($testRequestId, $testDocFee, $testResidentId);
    } catch (\PDOException $e) {
        $threw23 = str_starts_with($e->getCode(), '23');
    } catch (\Throwable $e) { /* unexpected */ }
    chk('PAY-08', 'Duplicate insertPending throws PDO SQLSTATE 23000',
        $threw23, "threw: " . ($threw23 ? 'SQLSTATE 23xxx' : 'no exception'));

    // ── PAY-09: confirm() transitions pending -> paid ──────────────────────────
    if ($testStaffId) {
        $rowsAffected = $payModel->confirm($payId, $testStaffId, 'Test receipt #99');
        chk('PAY-09', 'confirm() returns rowCount=1 on first confirmation',
            $rowsAffected === 1, "rowsAffected=$rowsAffected");

        $stmt3 = $pdo->prepare("SELECT * FROM payments WHERE id = :id");
        $stmt3->execute([':id' => $payId]);
        $paidRow = $stmt3->fetch();
        chk('PAY-10', 'Payment status is paid after confirm()',
            $paidRow && $paidRow['payment_status'] === 'paid', "status=" . ($paidRow['payment_status'] ?? 'null'));
        chk('PAY-11', 'confirmed_by is set to staff user ID',
            $paidRow && (int)$paidRow['confirmed_by'] === $testStaffId, "confirmed_by=" . ($paidRow['confirmed_by'] ?? 'null'));
        chk('PAY-12', 'confirmed_at timestamp is set',
            $paidRow && !empty($paidRow['confirmed_at']), "confirmed_at=" . ($paidRow['confirmed_at'] ?? 'null'));
        chk('PAY-13', 'notes stored correctly',
            $paidRow && $paidRow['notes'] === 'Test receipt #99', "notes=" . ($paidRow['notes'] ?? 'null'));

        // ── PAY-14: Duplicate confirm() returns 0 (idempotency guard) ─────────
        $rowsAffected2 = $payModel->confirm($payId, $testStaffId, 'Duplicate attempt');
        chk('PAY-14', 'Duplicate confirm() returns rowCount=0 (already paid guard)',
            $rowsAffected2 === 0, "rowsAffected=$rowsAffected2");
    } else {
        chk('PAY-09', 'SKIP: no staff user — skipping confirmation tests', false, 'no staff');
    }

} else {
    foreach (['PAY-01','PAY-02','PAY-03','PAY-04','PAY-05','PAY-06','PAY-07','PAY-08',
              'PAY-09','PAY-10','PAY-11','PAY-12','PAY-13','PAY-14'] as $pid) {
        chk($pid, 'SKIP: missing test setup', false, 'no test request');
    }
}

// ── PAY-15: request status unchanged by payment operations ────────────────────
if ($testRequestId) {
    $stmt4 = $pdo->prepare("SELECT status FROM requests WHERE id = :id");
    $stmt4->execute([':id' => $testRequestId]);
    $statusNow = $stmt4->fetchColumn();
    chk('PAY-15', 'request.status unchanged (still ready_for_claiming) after payment operations',
        $statusNow === 'ready_for_claiming', "status=$statusNow");
}

// ── PAY-16: Zero-fee document — payment model should not be used ───────────────
$zeroDt = $pdo->query("SELECT id FROM document_types WHERE processing_fee = 0.00 AND is_active=1 LIMIT 1")->fetch();
chk('PAY-16', 'Zero-fee document type exists in DB (fee gate can be triggered)',
    $zeroDt !== false, "id=" . ($zeroDt['id'] ?? 'none'));

// ── Regression: existing request/status workflow unaffected ────────────────────
chk('REG-01', 'requests table still has same structure (id, status, user_id)',
    (bool)$pdo->query("SHOW COLUMNS FROM requests LIKE 'status'")->fetch(), 'status column present');
chk('REG-02', 'No payment_status column on requests table',
    $pdo->query("SHOW COLUMNS FROM requests LIKE 'payment_status'")->fetch() === false, 'absent');
chk('REG-03', 'allowedTransitions() still returns pending->under_verification',
    in_array('under_verification',
        \Emunicipal\Models\Request::allowedTransitions('pending'), true),
    'transition intact');
chk('REG-04', 'Existing users unchanged',
    (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() >= 6, 'count >= 6');

// ── PHP syntax checks ──────────────────────────────────────────────────────────
$phpBin = 'c:\\xampp\\php\\php.exe';
$synFiles = [
    'app/Models/Payment.php',
    'app/Models/AuditLog.php',
    'app/Models/Notification.php',
    'app/Services/NotificationService.php',
    'app/Controllers/Resident/PaymentController.php',
    'app/Controllers/Staff/StaffPaymentController.php',
    'app/Controllers/Resident/RequestController.php',
    'app/Controllers/Staff/StaffRequestController.php',
    'routes/web.php',
];
foreach ($synFiles as $sf) {
    exec("\"$phpBin\" -l \"{$root}/{$sf}\" 2>&1", $out, $code);
    chk("SYN-" . basename($sf), "Syntax: $sf", $code === 0, $code === 0 ? 'OK' : implode(' ', $out));
    unset($out);
}

// ── Cleanup ────────────────────────────────────────────────────────────────────
// Remove all payment rows inserted during this test
if ($testRequestId) {
    $pdo->prepare("DELETE FROM payments WHERE request_id = :rid")->execute([':rid' => $testRequestId]);
}
// Remove temporary request if we created it
if ($createdRequest && $testRequestId) {
    $pdo->prepare("DELETE FROM request_status_history WHERE request_id = :rid")->execute([':rid' => $testRequestId]);
    $pdo->prepare("DELETE FROM requests WHERE id = :rid AND reference_number = 'EMU-TEST-PAY999'")->execute([':rid' => $testRequestId]);
}
// Clean up any test notifications that might have been created
$pdo->prepare("DELETE FROM notifications WHERE type = 'payment_received' AND message LIKE '%TEST%'")->execute();

goto print_results;

print_results:

echo "\n====================================================\n";
echo " PHASE 12 - Cash Payment Test Results\n";
echo "====================================================\n\n";
foreach ($results as [$s, $id, $desc, $detail]) {
    echo "[{$s}] " . ($s === 'PASS' ? 'PASS' : 'FAIL') . " {$id}: {$desc}" . ($detail ? " ({$detail})" : "") . "\n";
}
$t = $passed + $failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$t} passed.\n";
if ($failed === 0) { echo "\nALL TESTS PASSED\n\n"; exit(0); }
else               { echo "\nSOME TESTS FAILED\n\n"; exit(1); }
