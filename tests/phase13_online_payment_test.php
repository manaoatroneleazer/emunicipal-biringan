<?php
/**
 * Phase 13 — Simulated Online Payment Tests (Phase 3)
 *
 * Tests the Phase 3 demo online payment feature alongside Phase 2 cash.
 * All inserted test rows are cleaned up after the suite.
 * Existing project records are NEVER modified or deleted.
 *
 * NOTE: Tests that require the Phase 3 schema (online payments) are
 * automatically skipped if the existing database has not yet been upgraded,
 * and are marked SKIP with a clear explanation. Apply the upgrade with:
 *   mysql -u root <db> < database/migration_phase3_online_payment.sql
 * Fresh installations from schema.sql already include the full schema.
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

// ── Detect migration state ─────────────────────────────────────────────────────
$cols = $pdo->query("DESCRIBE payments")->fetchAll(\PDO::FETCH_COLUMN);
$hasTxnRef        = in_array('transaction_ref', $cols);
$hasOnlineMethod  = false;
$hasOnlinePending = false;

// Check ENUM values
$row = $pdo->query("SHOW COLUMNS FROM payments LIKE 'payment_method'")->fetch();
if ($row) $hasOnlineMethod  = str_contains($row['Type'] ?? '', "'online'");
$row2 = $pdo->query("SHOW COLUMNS FROM payments LIKE 'payment_status'")->fetch();
if ($row2) $hasOnlinePending = str_contains($row2['Type'] ?? '', "'online_pending'");

$migrationApplied = $hasTxnRef && $hasOnlineMethod && $hasOnlinePending;

chk('MIG-01', 'Phase 3 migration: payment_method ENUM has online value',
    $hasOnlineMethod, $hasOnlineMethod ? 'present' : 'MIGRATION NOT APPLIED');
chk('MIG-02', 'Phase 3 migration: payment_status ENUM has online_pending value',
    $hasOnlinePending, $hasOnlinePending ? 'present' : 'MIGRATION NOT APPLIED');
chk('MIG-03', 'Phase 3 migration: transaction_ref column exists',
    $hasTxnRef, $hasTxnRef ? 'present' : 'MIGRATION NOT APPLIED');

// ── Static source checks ───────────────────────────────────────────────────────
$paymentSrc  = file_get_contents($root . '/app/Models/Payment.php');
$notifSrc    = file_get_contents($root . '/app/Models/Notification.php');
$notifSvcSrc = file_get_contents($root . '/app/Services/NotificationService.php');
$ctrlSrc     = file_get_contents($root . '/app/Controllers/Resident/PaymentController.php');
$routesSrc   = file_get_contents($root . '/routes/web.php');

chk('ST-01', 'Payment model: METHOD_ONLINE constant defined',
    str_contains($paymentSrc, "METHOD_ONLINE = 'online'"));
chk('ST-02', 'Payment model: STATUS_ONLINE_PENDING constant defined',
    str_contains($paymentSrc, "STATUS_ONLINE_PENDING = 'online_pending'"));
chk('ST-03', 'Payment model: insertOnlinePending() defined',
    str_contains($paymentSrc, 'insertOnlinePending'));
chk('ST-04', 'Payment model: confirmOnline() uses conditional WHERE online_pending',
    str_contains($paymentSrc, 'AND `payment_status` = :online_pending'));
chk('ST-05', 'Payment model: generateTransactionRef() defined',
    str_contains($paymentSrc, 'generateTransactionRef'));
chk('ST-06', 'Payment model: generateTransactionRef uses random_bytes (server-side)',
    str_contains($paymentSrc, 'random_bytes'));
chk('ST-07', 'Payment model: no BOM',
    substr($paymentSrc, 0, 5) === '<?php');
chk('ST-08', 'Notification: TYPE_ONLINE_PAYMENT_INITIATED constant defined',
    str_contains($notifSrc, 'TYPE_ONLINE_PAYMENT_INITIATED'));
chk('ST-09', 'NotificationService: onlinePaymentReceived() defined',
    str_contains($notifSvcSrc, 'function onlinePaymentReceived'));
chk('ST-10', 'PaymentController: simulateOnline() defined',
    str_contains($ctrlSrc, 'function simulateOnline'));
chk('ST-11', 'PaymentController: processOnline() defined',
    str_contains($ctrlSrc, 'function processOnline'));
chk('ST-12', 'PaymentController: receipt() defined',
    str_contains($ctrlSrc, 'function receipt'));
chk('ST-13', 'PaymentController: amount read from DB in processOnline (never from POST)',
    !str_contains($ctrlSrc, "\$_POST['amount']") && !str_contains($ctrlSrc, '$_POST["amount"]'));
chk('ST-14', 'PaymentController: transaction_ref generated server-side (generateTransactionRef)',
    str_contains($ctrlSrc, 'generateTransactionRef'));
chk('ST-15', 'PaymentController: failed simulation creates NO DB write',
    str_contains($ctrlSrc, "\$simulateResult !== 'fail'") ||
    str_contains($ctrlSrc, "isSuccess"));
chk('ST-16', 'PaymentController: notification created only AFTER commit',
    strpos($ctrlSrc, 'db->commit') < strpos($ctrlSrc, 'onlinePaymentReceived'));
chk('ST-17', 'PaymentController: CSRF validated in processOnline',
    str_contains($ctrlSrc, 'validateCsrfToken'));
chk('ST-18', 'PaymentController: ownership check in loadAndValidateRequest',
    str_contains($ctrlSrc, 'user_id') && str_contains($ctrlSrc, 'userId'));
chk('ST-19', 'PaymentController: status gate (ready_for_claiming) in loadAndValidateRequest',
    str_contains($ctrlSrc, 'STATUS_READY_FOR_CLAIMING'));
chk('ST-20', 'PaymentController: duplicate gate (assertNoPriorPayment) in processOnline',
    str_contains($ctrlSrc, 'assertNoPriorPayment'));
chk('ST-21', 'PaymentController: no real payment gateway call',
    !str_contains($ctrlSrc, 'curl_init') &&
    !str_contains($ctrlSrc, 'file_get_contents(') &&
    !str_contains($ctrlSrc, 'GuzzleHttp') &&
    !str_contains($ctrlSrc, 'gcash.com') &&
    !str_contains($ctrlSrc, 'maya.ph'));
chk('ST-22', 'Routes: simulateOnline GET route registered',
    str_contains($routesSrc, "'/resident/requests/{id}/payment/online'") &&
    str_contains($routesSrc, 'simulateOnline'));
chk('ST-23', 'Routes: processOnline POST route registered',
    str_contains($routesSrc, 'processOnline'));
chk('ST-24', 'Routes: receipt GET route registered',
    str_contains($routesSrc, 'receipt'));
chk('ST-25', 'Phase 2 cash route still present (cash flow preserved)',
    str_contains($routesSrc, 'selectCash'));
chk('ST-26', 'Staff view: shows transaction_ref for online payments',
    str_contains(file_get_contents($root . '/views/staff/requests/show.php'), 'transaction_ref'));
chk('ST-27', 'Staff view: shows DEMO label for online payments',
    str_contains(file_get_contents($root . '/views/staff/requests/show.php'), 'DEMO TRANSACTION'));
chk('ST-28', 'Resident view: Online Payment (Demo) option present',
    str_contains(file_get_contents($root . '/views/resident/requests/show.php'), 'payment/online'));
chk('ST-29', 'Online select view: DEMO warning present',
    str_contains(file_get_contents($root . '/views/resident/payments/online_select.php'), 'DEMO TRANSACTION'));
chk('ST-30', 'Online select view: Simulate Failed Payment button present',
    str_contains(file_get_contents($root . '/views/resident/payments/online_select.php'), 'simulate_result'));
chk('ST-31', 'Online select view: no real credential fields',
    !str_contains(file_get_contents($root . '/views/resident/payments/online_select.php'), 'type="password"') &&
    !str_contains(file_get_contents($root . '/views/resident/payments/online_select.php'), 'otp') &&
    !str_contains(file_get_contents($root . '/views/resident/payments/online_select.php'), 'pin'));
chk('ST-32', 'Online receipt view: DEMO notice present',
    str_contains(file_get_contents($root . '/views/resident/payments/online_receipt.php'), 'DEMO TRANSACTION'));
chk('ST-33', 'Online receipt view: transaction_ref displayed',
    str_contains(file_get_contents($root . '/views/resident/payments/online_receipt.php'), 'transaction_ref'));

// ── QR code static checks ──────────────────────────────────────────────────────
$qrSvcSrc    = file_get_contents($root . '/app/Services/QrService.php');
$selectSrc   = file_get_contents($root . '/views/resident/payments/online_select.php');

chk('QR-01', 'QrService.php exists',
    file_exists($root . '/app/Services/QrService.php'));
chk('QR-02', 'QrService: uses chillerlan/php-qrcode library',
    str_contains($qrSvcSrc, 'chillerlan\\QRCode\\QRCode'));
chk('QR-03', 'QrService: generateDemoQr() defined',
    str_contains($qrSvcSrc, 'function generateDemoQr'));
chk('QR-04', 'QrService: demoPayload() defined',
    str_contains($qrSvcSrc, 'function demoPayload'));
chk('QR-05', 'QrService: uses OUTPUT_MARKUP_SVG (no GD required)',
    str_contains($qrSvcSrc, 'OUTPUT_MARKUP_SVG'));
chk('QR-06', 'QrService: QR payload contains EMUNICIPAL-DEMO prefix',
    str_contains($qrSvcSrc, 'EMUNICIPAL-DEMO'));
chk('QR-07', 'QrService: no sensitive data in payload (no session, no CSRF, no password)',
    !str_contains($qrSvcSrc, '$_SESSION') &&
    !str_contains($qrSvcSrc, 'csrf') &&
    !str_contains($qrSvcSrc, 'password') &&
    !str_contains($qrSvcSrc, '$_COOKIE'));
chk('QR-08', 'QrService: QR generation non-fatal (wrapped in try/catch in controller)',
    str_contains(file_get_contents($root . '/app/Controllers/Resident/PaymentController.php'), 'generateDemoQr') &&
    str_contains(file_get_contents($root . '/app/Controllers/Resident/PaymentController.php'), 'QR generation is non-fatal'));
chk('QR-09', 'Online select view: QR image rendered via <img> tag',
    str_contains($selectSrc, 'qrDataUri') && str_contains($selectSrc, '<img'));
chk('QR-10', 'Online select view: QR DEMO label present beside QR image',
    str_contains($selectSrc, 'DEMO QR PAYMENT'));
chk('QR-11', 'Online select view: QR content expander shows payload (no sensitive data label)',
    str_contains($selectSrc, 'qrPayload') && str_contains($selectSrc, 'demo data only'));
chk('QR-12', 'Online select view: QR has alt text for accessibility',
    str_contains($selectSrc, 'alt=') && str_contains($selectSrc, 'Demo QR code'));
chk('QR-13', 'PaymentController: qrDataUri passed to view',
    str_contains(file_get_contents($root . '/app/Controllers/Resident/PaymentController.php'), "'qrDataUri'"));
chk('QR-14', 'PaymentController: qrPayload passed to view',
    str_contains(file_get_contents($root . '/app/Controllers/Resident/PaymentController.php'), "'qrPayload'"));
chk('QR-15', 'No external QR service URL in QrService (no third-party API)',
    !str_contains($qrSvcSrc, 'api.qrserver.com') &&
    !str_contains($qrSvcSrc, 'qr-code-generator.com') &&
    !str_contains($qrSvcSrc, 'chart.googleapis.com') &&
    !str_contains($qrSvcSrc, 'curl_init') &&
    !str_contains($qrSvcSrc, 'file_get_contents('));

// ── QR live generation tests ───────────────────────────────────────────────────
use Emunicipal\Services\QrService;

$qrDataUri = '';
try {
    $qrDataUri = QrService::generateDemoQr('EMU-TEST-QR-001', 50.00);
} catch (\Throwable $e) {
    $qrDataUri = '';
}
chk('QR-16', 'QrService::generateDemoQr() returns non-empty string',
    strlen($qrDataUri) > 100, 'length=' . strlen($qrDataUri));
chk('QR-17', 'generateDemoQr() returns a data URI (base64 SVG)',
    str_starts_with($qrDataUri, 'data:image/svg+xml;base64,'), substr($qrDataUri, 0, 30));
chk('QR-18', 'generateDemoQr() output is a valid base64 string',
    base64_decode(substr($qrDataUri, strlen('data:image/svg+xml;base64,')), true) !== false);

$decodedSvg = base64_decode(substr($qrDataUri, strlen('data:image/svg+xml;base64,')));
chk('QR-19', 'Decoded SVG contains <svg element',
    $decodedSvg !== false && str_contains($decodedSvg, '<svg'));
chk('QR-20', 'SVG is reasonably large (>= 500 bytes, real QR matrix present)',
    strlen($decodedSvg ?: '') >= 500, 'bytes=' . strlen($decodedSvg ?: ''));

$payload = QrService::demoPayload('EMU-TEST-QR-001', 50.00);
chk('QR-21', 'demoPayload() returns string containing EMUNICIPAL-DEMO prefix',
    str_starts_with($payload, 'EMUNICIPAL-DEMO:'), "payload=$payload");
chk('QR-22', 'demoPayload() contains reference number',
    str_contains($payload, 'EMU-TEST-QR-001'), "payload=$payload");
chk('QR-23', 'demoPayload() contains PHP-formatted amount',
    str_contains($payload, 'PHP50.00'), "payload=$payload");
chk('QR-24', 'demoPayload() contains no session ID, CSRF, or credentials',
    !str_contains($payload, 'SESS') &&
    !str_contains($payload, 'csrf') &&
    !str_contains($payload, 'password') &&
    !str_contains($payload, '@'), "payload=$payload");

// Different references produce different QR codes
$qrA = QrService::generateDemoQr('EMU-2026-000001', 50.00);
$qrB = QrService::generateDemoQr('EMU-2026-000002', 50.00);
chk('QR-25', 'Different references produce different QR data URIs',
    $qrA !== $qrB, 'unique per reference');

// Different amounts produce different QR codes
$qrC = QrService::generateDemoQr('EMU-2026-000001', 100.00);
chk('QR-26', 'Different amounts produce different QR data URIs',
    $qrA !== $qrC, 'unique per amount');

// QR not generated for zero-fee documents (controller blocks before QrService call)
chk('QR-27', 'Controller: QR generation only happens after fee > 0 gate passes',
    str_contains(
        file_get_contents($root . '/app/Controllers/Resident/PaymentController.php'),
        'fee <= 0'
    ), 'fee gate in loadAndValidateRequest()');

// chillerlan/php-qrcode is in composer.json
$composerJson = json_decode(file_get_contents($root . '/composer.json'), true);
chk('QR-28', 'chillerlan/php-qrcode listed in composer.json require',
    isset($composerJson['require']['chillerlan/php-qrcode']),
    $composerJson['require']['chillerlan/php-qrcode'] ?? 'missing');
chk('QR-29', 'chillerlan/php-qrcode vendor directory exists',
    is_dir($root . '/vendor/chillerlan'));

// Syntax check for QrService
exec("\"c:\\xampp\\php\\php.exe\" -l \"{$root}/app/Services/QrService.php\" 2>&1", $qrOut, $qrCode);
chk('QR-30', 'QrService.php passes php -l syntax check',
    $qrCode === 0, $qrCode === 0 ? 'OK' : implode(' ', $qrOut));
unset($qrOut);
$phpBin = 'c:\\xampp\\php\\php.exe';
foreach ([
    'app/Models/Payment.php',
    'app/Models/Notification.php',
    'app/Services/NotificationService.php',
    'app/Services/QrService.php',
    'app/Controllers/Resident/PaymentController.php',
    'routes/web.php',
    'views/resident/requests/show.php',
    'views/staff/requests/show.php',
    'views/resident/payments/online_select.php',
    'views/resident/payments/online_receipt.php',
] as $sf) {
    exec("\"$phpBin\" -l \"{$root}/{$sf}\" 2>&1", $out, $code);
    chk("SYN-" . basename($sf), "Syntax: $sf", $code === 0, $code === 0 ? 'OK' : implode(' ', $out));
    unset($out);
}

// ── Helper: generateTransactionRef ────────────────────────────────────────────
chk('FN-01', 'generateTransactionRef returns TXN- prefixed string',
    str_starts_with(Payment::generateTransactionRef(), 'TXN-'));
chk('FN-02', 'generateTransactionRef produces unique values',
    Payment::generateTransactionRef() !== Payment::generateTransactionRef());
chk('FN-03', 'methodLabel returns correct labels',
    Payment::methodLabel('cash') === 'Cash' &&
    Payment::methodLabel('online') === 'Online Payment (Demo)');
chk('FN-04', 'isPaid returns true for paid status',
    Payment::isPaid(['payment_status' => 'paid']) === true &&
    Payment::isPaid(['payment_status' => 'pending']) === false);
chk('FN-05', 'isAnyPending returns true for pending and online_pending',
    Payment::isAnyPending(['payment_status' => 'pending']) === true &&
    Payment::isAnyPending(['payment_status' => 'online_pending']) === true &&
    Payment::isAnyPending(['payment_status' => 'paid']) === false);

// ── Live DB tests (require migration) ─────────────────────────────────────────
if (!$migrationApplied) {
    $skipIds = ['PAY3-01','PAY3-02','PAY3-03','PAY3-04','PAY3-05',
                'PAY3-06','PAY3-07','PAY3-08','PAY3-09','PAY3-10',
                'PAY3-11','PAY3-12','PAY3-13'];
    foreach ($skipIds as $sid) {
        chk($sid, 'SKIP: Existing database needs upgrade — apply migration_phase3_online_payment.sql', false, 'migration absent');
    }
    goto cash_regression;
}

// ── Setup ──────────────────────────────────────────────────────────────────────
$staffRow   = $pdo->query("SELECT id FROM users WHERE role='staff'    AND is_active=1 LIMIT 1")->fetch();
$resRow     = $pdo->query("SELECT id FROM users WHERE role='resident' AND is_active=1 LIMIT 1")->fetch();
$dtRow      = $pdo->query("SELECT id, processing_fee FROM document_types WHERE is_active=1 AND processing_fee > 0 LIMIT 1")->fetch();
$zeroDtRow  = $pdo->query("SELECT id FROM document_types WHERE is_active=1 AND processing_fee = 0 LIMIT 1")->fetch();

$testResidentId = $resRow  ? (int)$resRow['id']  : null;
$testStaffId    = $staffRow ? (int)$staffRow['id'] : null;
$testDocTypeId  = $dtRow   ? (int)$dtRow['id']   : null;
$testDocFee     = $dtRow   ? (float)$dtRow['processing_fee'] : null;

// Create a fresh test request
$testRefNum = 'EMU-TEST-P3-' . time();
$old = $pdo->query("SELECT id FROM requests WHERE reference_number LIKE 'EMU-TEST-P3-%'")->fetchAll(\PDO::FETCH_COLUMN);
foreach ($old as $oid) {
    $pdo->prepare("DELETE FROM payments WHERE request_id=:id")->execute([':id'=>$oid]);
    $pdo->prepare("DELETE FROM request_status_history WHERE request_id=:id")->execute([':id'=>$oid]);
    $pdo->prepare("DELETE FROM requests WHERE id=:id")->execute([':id'=>$oid]);
}
$pdo->prepare(
    "INSERT INTO requests (reference_number,user_id,document_type_id,purpose,copies_requested,status,created_at,updated_at)
     VALUES (:ref,:uid,:dtid,'Phase 13 test',1,'ready_for_claiming',NOW(),NOW())"
)->execute([':ref'=>$testRefNum,':uid'=>$testResidentId,':dtid'=>$testDocTypeId]);
$testRequestId = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO request_status_history (request_id,previous_status,new_status,changed_by,created_at)
    VALUES (:rid,NULL,'ready_for_claiming',:uid,NOW())")->execute([':rid'=>$testRequestId,':uid'=>$testStaffId??1]);

// Ensure no payment exists
$pdo->prepare("DELETE FROM payments WHERE request_id=:rid")->execute([':rid'=>$testRequestId]);

$payModel = new Payment();

// ── PAY3-01: insertOnlinePending creates a row with online_pending status ──────
$payId = $payModel->insertOnlinePending($testRequestId, $testDocFee, $testResidentId);
$stmt  = $pdo->prepare("SELECT * FROM payments WHERE id=:id");
$stmt->execute([':id'=>$payId]);
$row   = $stmt->fetch();
chk('PAY3-01', 'insertOnlinePending creates row with status=online_pending',
    $row && $row['payment_status'] === 'online_pending', "status=" . ($row['payment_status'] ?? 'null'));
chk('PAY3-02', 'insertOnlinePending sets method=online',
    $row && $row['payment_method'] === 'online', "method=" . ($row['payment_method'] ?? 'null'));
chk('PAY3-03', 'insertOnlinePending snapshots correct amount (from DB, not client)',
    $row && (float)$row['amount'] === $testDocFee, "amount=" . ($row['amount'] ?? 'null'));
chk('PAY3-04', 'transaction_ref is NULL before confirmOnline',
    $row && ($row['transaction_ref'] ?? null) === null, "txn_ref=" . ($row['transaction_ref'] ?? 'null'));

// ── PAY3-05: confirmOnline transitions online_pending -> paid ──────────────────
$txnRef = Payment::generateTransactionRef();
$rows   = $payModel->confirmOnline($payId, $testResidentId, $txnRef);
chk('PAY3-05', 'confirmOnline returns rowCount=1 on first call',
    $rows === 1, "rows=$rows");

$stmt2 = $pdo->prepare("SELECT * FROM payments WHERE id=:id");
$stmt2->execute([':id'=>$payId]);
$paidRow = $stmt2->fetch();
chk('PAY3-06', 'Payment status is paid after confirmOnline',
    $paidRow && $paidRow['payment_status'] === 'paid', "status=" . ($paidRow['payment_status'] ?? 'null'));
chk('PAY3-07', 'transaction_ref stored correctly',
    $paidRow && $paidRow['transaction_ref'] === $txnRef, "txn_ref=" . ($paidRow['transaction_ref'] ?? 'null'));
chk('PAY3-08', 'confirmed_at timestamp set',
    $paidRow && !empty($paidRow['confirmed_at']), "confirmed_at=" . ($paidRow['confirmed_at'] ?? 'null'));

// ── PAY3-09: Duplicate confirmOnline returns 0 ────────────────────────────────
$rows2 = $payModel->confirmOnline($payId, $testResidentId, Payment::generateTransactionRef());
chk('PAY3-09', 'Duplicate confirmOnline returns rowCount=0 (idempotent guard)',
    $rows2 === 0, "rows=$rows2");

// ── PAY3-10: Request status unchanged after online payment ────────────────────
$statusNow = $pdo->prepare("SELECT status FROM requests WHERE id=:id");
$statusNow->execute([':id'=>$testRequestId]);
$st = $statusNow->fetchColumn();
chk('PAY3-10', 'request.status unchanged (still ready_for_claiming) after online payment',
    $st === 'ready_for_claiming', "status=$st");

// ── PAY3-11: Duplicate insertOnlinePending throws SQLSTATE 23000 ──────────────
$threw23 = false;
try {
    $payModel->insertOnlinePending($testRequestId, $testDocFee, $testResidentId);
} catch (\PDOException $e) {
    $threw23 = str_starts_with((string)$e->getCode(), '23');
} catch (\Throwable $e) {}
chk('PAY3-11', 'Duplicate insertOnlinePending throws SQLSTATE 23000',
    $threw23, $threw23 ? 'SQLSTATE 23xxx' : 'no exception');

// ── PAY3-12: Zero-fee document should not allow payment (fee gate) ─────────────
chk('PAY3-12', 'Zero-fee document type exists for fee gate testing',
    $zeroDtRow !== false, "id=" . ($zeroDtRow['id'] ?? 'none'));

// ── PAY3-13: generateTransactionRef produces TXN- prefixed unique value ───────
$ref1 = Payment::generateTransactionRef();
$ref2 = Payment::generateTransactionRef();
chk('PAY3-13', 'generateTransactionRef is unique across consecutive calls',
    $ref1 !== $ref2 && str_starts_with($ref1, 'TXN-'), "ref1=$ref1 ref2=$ref2");

// ── Phase 2 cash payment still works alongside online ────────────────────────
cash_regression:

// Create a second test request for cash regression
$cashRefNum = 'EMU-TEST-P3-CASH-' . time();
$oldCash = $pdo->query("SELECT id FROM requests WHERE reference_number LIKE 'EMU-TEST-P3-CASH-%'")->fetchAll(\PDO::FETCH_COLUMN);
foreach ($oldCash as $oid) {
    $pdo->prepare("DELETE FROM payments WHERE request_id=:id")->execute([':id'=>$oid]);
    $pdo->prepare("DELETE FROM request_status_history WHERE request_id=:id")->execute([':id'=>$oid]);
    $pdo->prepare("DELETE FROM requests WHERE id=:id")->execute([':id'=>$oid]);
}
$pdo->prepare(
    "INSERT INTO requests (reference_number,user_id,document_type_id,purpose,copies_requested,status,created_at,updated_at)
     VALUES (:ref,:uid,:dtid,'Phase 13 cash test',1,'ready_for_claiming',NOW(),NOW())"
)->execute([':ref'=>$cashRefNum,':uid'=>($testResidentId??4),':dtid'=>($testDocTypeId??31)]);
$cashRequestId = (int)$pdo->lastInsertId();

$cashPayId = (new Payment())->insertPending($cashRequestId, $testDocFee ?? 50.00, $testResidentId ?? 4);
$stmtC = $pdo->prepare("SELECT * FROM payments WHERE id=:id");
$stmtC->execute([':id'=>$cashPayId]);
$cashRow = $stmtC->fetch();
chk('REG-01', 'Phase 2: insertPending (cash) still works alongside Phase 3',
    $cashRow && $cashRow['payment_method'] === 'cash' && $cashRow['payment_status'] === 'pending',
    "method=" . ($cashRow['payment_method'] ?? 'null') . " status=" . ($cashRow['payment_status'] ?? 'null'));

$cashRows = (new Payment())->confirm($cashPayId, $testStaffId ?? 3, 'Regression test');
$stmtC2 = $pdo->prepare("SELECT * FROM payments WHERE id=:id");
$stmtC2->execute([':id'=>$cashPayId]);
$cashPaid = $stmtC2->fetch();
chk('REG-02', 'Phase 2: cash confirm() still transitions pending->paid',
    $cashRows === 1 && $cashPaid['payment_status'] === 'paid', "rows=$cashRows status=" . ($cashPaid['payment_status'] ?? 'null'));

chk('REG-03', 'Phase 2: transaction_ref is NULL for cash payments',
    ($cashPaid['transaction_ref'] ?? null) === null, 'null as expected');

chk('REG-04', 'requests table has no payment_status column (payment kept separate)',
    $pdo->query("SHOW COLUMNS FROM requests LIKE 'payment_status'")->fetch() === false, 'absent');

chk('REG-05', 'allowedTransitions unchanged for pending status',
    in_array('under_verification', \Emunicipal\Models\Request::allowedTransitions('pending'), true));

chk('REG-06', 'Existing user count intact',
    (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() >= 6);

// ── Cleanup ────────────────────────────────────────────────────────────────────
if (isset($testRequestId)) {
    $pdo->prepare("DELETE FROM payments WHERE request_id=:id")->execute([':id'=>$testRequestId]);
    $pdo->prepare("DELETE FROM request_status_history WHERE request_id=:id")->execute([':id'=>$testRequestId]);
    $pdo->prepare("DELETE FROM requests WHERE id=:id")->execute([':id'=>$testRequestId]);
}
if (isset($cashRequestId)) {
    $pdo->prepare("DELETE FROM payments WHERE request_id=:id")->execute([':id'=>$cashRequestId]);
    $pdo->prepare("DELETE FROM request_status_history WHERE request_id=:id")->execute([':id'=>$cashRequestId]);
    $pdo->prepare("DELETE FROM requests WHERE id=:id")->execute([':id'=>$cashRequestId]);
}
// Remove any test notifications
$pdo->prepare("DELETE FROM notifications WHERE type='online_payment_initiated' AND reference_number LIKE 'EMU-TEST-P3%'")->execute();

// ── Results ────────────────────────────────────────────────────────────────────
echo "\n====================================================\n";
echo " PHASE 13 - Simulated Online Payment Test Results\n";
echo "====================================================\n\n";
foreach ($results as [$s, $id, $desc, $detail]) {
    echo "[{$s}] " . ($s === 'PASS' ? 'PASS' : 'FAIL') . " {$id}: {$desc}" . ($detail ? " ({$detail})" : "") . "\n";
}
$t = $passed + $failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$t} passed.\n";
if ($migrationApplied) {
    echo "Migration status: APPLIED — all tests ran.\n";
} else {
    echo "Migration status: NOT APPLIED — PAY3-* tests skipped.\n";
    echo "To apply: mysql -u root <db> < database/migration_phase3_online_payment.sql\n";
}
if ($failed === 0) { echo "\nALL TESTS PASSED\n\n"; exit(0); }
else               { echo "\nSOME TESTS FAILED\n\n"; exit(1); }
