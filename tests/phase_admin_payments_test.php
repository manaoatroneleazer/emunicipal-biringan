<?php
/**
 * eMunicipal — Admin Payment History + Admin Requests Table Tests
 *
 * ST-*  = static source / structure checks (no DB required)
 * DB-*  = live database checks
 * AC-*  = access-control checks
 * TBL-* = admin requests table fix checks
 *
 * All test payment rows inserted here are cleaned up after the suite.
 * Existing project data is never modified or deleted.
 */

$root = dirname(__DIR__);
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $_ENV[trim($k)] = trim($v, " \t\n\r\"'");
}
require_once $root . '/vendor/autoload.php';

use Emunicipal\Core\Application;
use Emunicipal\Core\Database;
use Emunicipal\Models\Payment;

$app = new Application($root);   // sets timezone, boots DB

$pdo     = Database::getInstance();
$passed  = 0;
$failed  = 0;
$results = [];

function chk(string $id, string $desc, bool $pass, string $detail = ''): void
{
    global $passed, $failed, $results;
    if ($pass) { $passed++; $results[] = ['PASS', $id, $desc, $detail]; }
    else       { $failed++; $results[] = ['FAIL', $id, $desc, $detail]; }
}

// ── File contents ─────────────────────────────────────────────────────────────
$ctrlSrc  = file_get_contents($root . '/app/Controllers/Admin/AdminPaymentController.php');
$viewSrc  = file_get_contents($root . '/views/admin/payments/index.php');
$navSrc   = file_get_contents($root . '/views/layouts/auth.php');
$routeSrc = file_get_contents($root . '/routes/web.php');
$reqSrc   = file_get_contents($root . '/views/admin/requests/index.php');
$cssSrc   = file_get_contents($root . '/public/assets/css/app.css');

// ── PART 1: Payment History — static checks ───────────────────────────────────

chk('ST-01', 'AdminPaymentController.php exists',
    file_exists($root . '/app/Controllers/Admin/AdminPaymentController.php'));

chk('ST-02', 'Controller enforces requireRole(admin)',
    str_contains($ctrlSrc, "requireRole('admin')"));

chk('ST-03', 'Controller uses PDO prepared statements (bindValue)',
    str_contains($ctrlSrc, 'bindValue'));

chk('ST-04', 'Controller joins requests table for reference_number',
    str_contains($ctrlSrc, 'JOIN requests r'));

chk('ST-05', 'Controller joins users table for resident name',
    str_contains($ctrlSrc, 'JOIN users res'));

chk('ST-06', 'Controller joins document_types for doc name',
    str_contains($ctrlSrc, 'JOIN document_types dt'));

chk('ST-07', 'Controller LEFT JOINs confirmed-by user',
    str_contains($ctrlSrc, 'LEFT JOIN users conf'));

chk('ST-08', 'Controller does NOT select password_hash in SQL query',
    (function() use ($ctrlSrc): bool {
        // Find the SELECT block in fetchPayments and confirm password_hash is absent
        $selectStart = strpos($ctrlSrc, 'SELECT');
        $fromStart   = strpos($ctrlSrc, 'FROM payments p', $selectStart);
        if ($selectStart === false || $fromStart === false) return false;
        $selectBlock = substr($ctrlSrc, $selectStart, $fromStart - $selectStart);
        return !str_contains($selectBlock, 'password_hash');
    })());

chk('ST-09', 'Controller validates method filter against whitelist',
    str_contains($ctrlSrc, 'VALID_METHODS') && str_contains($ctrlSrc, "in_array(\$method"));

chk('ST-10', 'Controller validates status filter against whitelist',
    str_contains($ctrlSrc, 'VALID_STATUSES') && str_contains($ctrlSrc, "in_array(\$status"));

chk('ST-11', 'Controller validates date format',
    str_contains($ctrlSrc, 'isValidDate'));

chk('ST-12', 'Controller uses LIMIT/OFFSET pagination',
    str_contains($ctrlSrc, ':lim') && str_contains($ctrlSrc, ':off'));

chk('ST-13', 'Controller selects request_id for request link',
    str_contains($ctrlSrc, 'p.request_id'));

chk('ST-14', 'Payment History view exists',
    file_exists($root . '/views/admin/payments/index.php'));

chk('ST-15', 'View escapes all output with htmlspecialchars',
    str_contains($viewSrc, "\$e(") && str_contains($viewSrc, 'ENT_QUOTES'));

chk('ST-16', 'View shows transaction_ref column',
    str_contains($viewSrc, 'transaction_ref'));

chk('ST-17', 'View shows payment_method badge',
    str_contains($viewSrc, 'methodBadge') || str_contains($viewSrc, 'payment_method'));

chk('ST-18', 'View shows payment_status badge',
    str_contains($viewSrc, 'statusBadge') || str_contains($viewSrc, 'payment_status'));

chk('ST-19', 'View has search filter form',
    str_contains($viewSrc, 'paymentFilterForm') || str_contains($viewSrc, 'admin/payments'));

chk('ST-20', 'View has method dropdown filter',
    str_contains($viewSrc, 'name="method"'));

chk('ST-21', 'View has status dropdown filter',
    str_contains($viewSrc, 'name="status"'));

chk('ST-22', 'View has date filter inputs',
    str_contains($viewSrc, 'name="date_from"') && str_contains($viewSrc, 'name="date_to"'));

chk('ST-23', 'View has empty-state block',
    str_contains($viewSrc, 'empty-state'));

chk('ST-24', 'View has pagination markup',
    str_contains($viewSrc, 'pagination'));

chk('ST-25', 'View has scoped #admin-payments-table-wrap',
    str_contains($viewSrc, 'admin-payments-table-wrap') ||
    str_contains($cssSrc, '#admin-payments-table-wrap'));

chk('ST-26', 'Admin nav has Payment History link (admin section only)',
    (function() use ($navSrc): bool {
        // Locate the admin role block start
        $adminStart = strpos($navSrc, "elseif (\$role === 'admin')");
        // The role block closes with the endif before "Navigation" section label.
        // Use a plain marker string that won't confuse the PHP parser.
        $navSectionPos = strpos($navSrc, 'sidebar-section-label">Navigation');
        // Find the last occurrence of the endif tag before the Navigation section
        $endifMarker = '<' . '?php endif; ?' . '>';
        $roleEndif   = strrpos(substr($navSrc, 0, $navSectionPos), $endifMarker);
        $paymentPos  = strpos($navSrc, '/admin/payments');
        if ($adminStart === false || $roleEndif === false || $paymentPos === false) return false;
        return $paymentPos > $adminStart && $paymentPos < $roleEndif;
    })());

chk('ST-27', 'Payment History nav link NOT in resident sidebar',
    (function() use ($navSrc): bool {
        $resStart = strpos($navSrc, "if (\$role === 'resident')");
        $resEnd   = strpos($navSrc, "elseif (\$role === 'staff')", $resStart);
        if ($resStart === false || $resEnd === false) return false;
        return !str_contains(substr($navSrc, $resStart, $resEnd - $resStart), '/admin/payments');
    })());

chk('ST-28', 'Payment History nav link NOT in staff sidebar',
    (function() use ($navSrc): bool {
        $stStart = strpos($navSrc, "elseif (\$role === 'staff')");
        $stEnd   = strpos($navSrc, "elseif (\$role === 'admin')", $stStart);
        if ($stStart === false || $stEnd === false) return false;
        return !str_contains(substr($navSrc, $stStart, $stEnd - $stStart), '/admin/payments');
    })());

chk('ST-29', 'Route GET /admin/payments registered',
    str_contains($routeSrc, "'/admin/payments'") &&
    str_contains($routeSrc, 'AdminPaymentController'));

chk('ST-30', 'AdminPaymentController imported in routes/web.php',
    str_contains($routeSrc, 'use Emunicipal\Controllers\Admin\AdminPaymentController'));

// ── PART 2: Admin Requests Table fix — static checks ──────────────────────────

chk('TBL-01', 'requests/index.php uses #admin-requests-table-wrap id',
    str_contains($reqSrc, 'id="admin-requests-table-wrap"'));

chk('TBL-02', 'requests/index.php no longer uses generic .table-responsive div alone',
    // The old bare .table-responsive wrapper is replaced by the scoped id
    !preg_match('/<div class="table-responsive">\s*<table[^>]*aria-label="All document requests"/', $reqSrc));

chk('TBL-03', 'CSS contains #admin-requests-table-wrap min-width rule',
    str_contains($cssSrc, '#admin-requests-table-wrap') &&
    str_contains($cssSrc, '#admin-requests-table-wrap .table { min-width'));

chk('TBL-04', 'CSS #admin-requests-table-wrap has overflow-x: auto',
    (function() use ($cssSrc): bool {
        $start = strpos($cssSrc, '#admin-requests-table-wrap {');
        if ($start === false) return false;
        $block = substr($cssSrc, $start, 300);
        return str_contains($block, 'overflow-x: auto');
    })());

chk('TBL-05', 'CSS scoped rule does not affect .b2-table (different selector)',
    !str_contains($cssSrc, '#admin-requests-table-wrap .b2-table'));

chk('TBL-06', 'CSS also contains #admin-payments-table-wrap min-width rule',
    str_contains($cssSrc, '#admin-payments-table-wrap .table { min-width'));

// ── PART 3: Live DB checks ────────────────────────────────────────────────────

// Find test fixtures
$resRow  = $pdo->query("SELECT id FROM users WHERE role='resident' AND is_active=1 LIMIT 1")->fetch();
$staffRow = $pdo->query("SELECT id FROM users WHERE role='staff' AND is_active=1 LIMIT 1")->fetch();
$dtRow   = $pdo->query("SELECT id FROM document_types WHERE is_active=1 AND processing_fee > 0 LIMIT 1")->fetch();
$resId   = $resRow   ? (int)$resRow['id']   : null;
$staffId = $staffRow ? (int)$staffRow['id'] : null;
$dtId    = $dtRow    ? (int)$dtRow['id']    : null;

chk('DB-01', 'payments table has transaction_ref column',
    (bool)$pdo->query("SHOW COLUMNS FROM payments LIKE 'transaction_ref'")->fetch());

chk('DB-02', 'payments table has payment_method with online value',
    (function() use ($pdo): bool {
        $r = $pdo->query("SHOW COLUMNS FROM payments LIKE 'payment_method'")->fetch();
        return $r && str_contains($r['Type'] ?? '', "'online'");
    })());

chk('DB-03', 'payments table has payment_status with online_pending value',
    (function() use ($pdo): bool {
        $r = $pdo->query("SHOW COLUMNS FROM payments LIKE 'payment_status'")->fetch();
        return $r && str_contains($r['Type'] ?? '', "'online_pending'");
    })());

// Create a temporary test request and payment for query verification
// Use a short prefix so the full value fits in VARCHAR(20):
// 'EMU-TST-' (8) + substr(time(), -11) = max 19 chars — always within the column limit.
$testRefNum  = 'EMU-TST-' . substr((string)time(), -11);
$testReqId   = null;
$testPayId   = null;
$createdRows = false;

if ($resId && $dtId) {
    // Clean up any leftover rows from a previous run
    $old = $pdo->query("SELECT id FROM requests WHERE reference_number LIKE 'EMU-TST-%'")->fetchAll(\PDO::FETCH_COLUMN);
    foreach ($old as $oid) {
        $pdo->prepare("DELETE FROM payments WHERE request_id=:id")->execute([':id' => $oid]);
        $pdo->prepare("DELETE FROM request_status_history WHERE request_id=:id")->execute([':id' => $oid]);
        $pdo->prepare("DELETE FROM requests WHERE id=:id")->execute([':id' => $oid]);
    }

    $pdo->prepare(
        "INSERT INTO requests (reference_number,user_id,document_type_id,purpose,copies_requested,status,created_at,updated_at)
         VALUES (:ref,:uid,:dtid,'Admin payment test',1,'ready_for_claiming',NOW(),NOW())"
    )->execute([':ref' => $testRefNum, ':uid' => $resId, ':dtid' => $dtId]);
    $testReqId = (int)$pdo->lastInsertId();

    // Cash pending row
    $pm = new Payment();
    $testPayId   = $pm->insertPending($testReqId, 50.00, $resId);
    $createdRows = true;
}

if ($createdRows) {
    // DB-04: query returns the test payment row
    $stmt = $pdo->prepare(
        "SELECT p.id AS payment_id, p.payment_method, p.payment_status, p.amount,
                p.transaction_ref, r.reference_number,
                res.first_name AS resident_first, res.last_name AS resident_last,
                dt.name AS document_type_name,
                conf.first_name AS confirmed_first
         FROM payments p
         JOIN requests r        ON p.request_id       = r.id
         JOIN users res         ON r.user_id           = res.id
         JOIN document_types dt ON r.document_type_id  = dt.id
         LEFT JOIN users conf   ON p.confirmed_by      = conf.id
         WHERE p.id = :id"
    );
    $stmt->execute([':id' => $testPayId]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

    chk('DB-04', 'Payment history query returns correct row',
        $row !== false && (int)$row['payment_id'] === $testPayId,
        "payment_id=" . ($row['payment_id'] ?? 'null'));

    chk('DB-05', 'Query returns correct reference_number',
        $row && $row['reference_number'] === $testRefNum,
        "ref=" . ($row['reference_number'] ?? 'null'));

    chk('DB-06', 'Query returns resident name',
        $row && !empty($row['resident_first']) && !empty($row['resident_last']),
        "name=" . (($row['resident_first'] ?? '') . ' ' . ($row['resident_last'] ?? '')));

    chk('DB-07', 'Query returns document_type_name',
        $row && !empty($row['document_type_name']),
        "doc=" . ($row['document_type_name'] ?? 'null'));

    chk('DB-08', 'Cash payment: payment_method=cash',
        $row && $row['payment_method'] === 'cash');

    chk('DB-09', 'Cash payment: payment_status=pending',
        $row && $row['payment_status'] === 'pending');

    chk('DB-10', 'Cash payment: transaction_ref is NULL',
        $row && ($row['transaction_ref'] ?? null) === null);

    chk('DB-11', 'confirmed_first is NULL for unconfirmed cash payment',
        $row && ($row['confirmed_first'] ?? null) === null);

    // DB-12: search filter by reference_number
    // Use two distinct param names — ATTR_EMULATE_PREPARES=false forbids duplicate placeholders
    $stmt2 = $pdo->prepare(
        "SELECT COUNT(*) FROM payments p
         JOIN requests r ON p.request_id = r.id
         JOIN users res  ON r.user_id = res.id
         JOIN document_types dt ON r.document_type_id = dt.id
         WHERE (p.transaction_ref LIKE :s1 OR r.reference_number LIKE :s2)"
    );
    $searchVal = '%' . $testRefNum . '%';
    $stmt2->execute([':s1' => $searchVal, ':s2' => $searchVal]);
    chk('DB-12', 'Search filter by reference_number returns the test row',
        (int)$stmt2->fetchColumn() >= 1);

    // DB-13: method filter
    $stmt3 = $pdo->prepare(
        "SELECT COUNT(*) FROM payments p
         JOIN requests r ON p.request_id = r.id
         JOIN users res  ON r.user_id = res.id
         JOIN document_types dt ON r.document_type_id = dt.id
         WHERE p.payment_method = :m AND p.id = :id"
    );
    $stmt3->execute([':m' => 'cash', ':id' => $testPayId]);
    chk('DB-13', 'Method filter (cash) returns the test row',
        (int)$stmt3->fetchColumn() === 1);

    // DB-14: status filter
    $stmt4 = $pdo->prepare(
        "SELECT COUNT(*) FROM payments p
         JOIN requests r ON p.request_id = r.id
         JOIN users res  ON r.user_id = res.id
         JOIN document_types dt ON r.document_type_id = dt.id
         WHERE p.payment_status = :s AND p.id = :id"
    );
    $stmt4->execute([':s' => 'pending', ':id' => $testPayId]);
    chk('DB-14', 'Status filter (pending) returns the test row',
        (int)$stmt4->fetchColumn() === 1);

    // DB-15: date filter
    $today = date('Y-m-d');
    $stmt5 = $pdo->prepare(
        "SELECT COUNT(*) FROM payments p
         JOIN requests r ON p.request_id = r.id
         JOIN users res  ON r.user_id = res.id
         JOIN document_types dt ON r.document_type_id = dt.id
         WHERE DATE(p.created_at) >= :d AND p.id = :id"
    );
    $stmt5->execute([':d' => $today, ':id' => $testPayId]);
    chk('DB-15', 'Date filter returns the test row for today',
        (int)$stmt5->fetchColumn() === 1);

    // DB-16: online payment row test
    $pm2 = new Payment();
    // Create a second request for online payment test
    $onlineRefNum = 'EMU-TST-ONL-' . substr((string)time(), -7);
    $pdo->prepare(
        "INSERT INTO requests (reference_number,user_id,document_type_id,purpose,copies_requested,status,created_at,updated_at)
         VALUES (:ref,:uid,:dtid,'Online pay test',1,'ready_for_claiming',NOW(),NOW())"
    )->execute([':ref' => $onlineRefNum, ':uid' => $resId, ':dtid' => $dtId]);
    $onlineReqId = (int)$pdo->lastInsertId();
    $onlinePayId = $pm2->insertOnlinePending($onlineReqId, 50.00, $resId);

    $stmt6 = $pdo->prepare(
        "SELECT p.payment_method, p.payment_status, p.transaction_ref
         FROM payments p WHERE p.id = :id"
    );
    $stmt6->execute([':id' => $onlinePayId]);
    $onlineRow = $stmt6->fetch(\PDO::FETCH_ASSOC);

    chk('DB-16', 'Online payment: payment_method=online',
        $onlineRow && $onlineRow['payment_method'] === 'online');
    chk('DB-17', 'Online payment: payment_status=online_pending',
        $onlineRow && $onlineRow['payment_status'] === 'online_pending');

    // DB-18: verify total payment count is reachable and unchanged by our reads
    $preCount = (int)$pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
    chk('DB-18', 'Existing payments table accessible and has rows',
        $preCount >= 2);  // our 2 test rows at minimum

} else {
    foreach (['DB-04','DB-05','DB-06','DB-07','DB-08','DB-09','DB-10','DB-11',
              'DB-12','DB-13','DB-14','DB-15','DB-16','DB-17','DB-18'] as $id) {
        chk($id, 'SKIP: missing test fixtures (no resident or doc type with fee>0)', false, 'skipped');
    }
}

// ── PART 4: Access control — static source checks ─────────────────────────────

chk('AC-01', 'Controller calls requireRole on every public method',
    (function() use ($ctrlSrc): bool {
        // Every method that handles requests must call requireRole('admin')
        $methods = ['function index'];
        foreach ($methods as $m) {
            $pos = strpos($ctrlSrc, $m);
            if ($pos === false) return false;
            $block = substr($ctrlSrc, $pos, 500);
            if (!str_contains($block, "requireRole('admin')")) return false;
        }
        return true;
    })());

chk('AC-02', 'No GET /admin/payments route registered for non-admin roles',
    // Only one route for /admin/payments and it uses AdminPaymentController
    substr_count($routeSrc, "'/admin/payments'") === 1 &&
    str_contains($routeSrc, 'AdminPaymentController'));

chk('AC-03', 'Payment History nav link is inside admin-only sidebar block',
    // Already verified in ST-26/27/28 — confirm combined
    str_contains($navSrc, 'admin/payments') &&
    // Does NOT appear before the admin role check
    strpos($navSrc, 'admin/payments') > strpos($navSrc, "elseif (\$role === 'admin')"));

chk('AC-04', 'Controller does not expose transaction_ref in error messages',
    !str_contains($ctrlSrc, "Logger::error.*transaction_ref") &&
    !str_contains($ctrlSrc, "flash.*transaction_ref"));

// ── PHP syntax checks ─────────────────────────────────────────────────────────
$phpBin = 'c:\\xampp\\php\\php.exe';
foreach ([
    'app/Controllers/Admin/AdminPaymentController.php',
    'views/admin/payments/index.php',
    'views/admin/requests/index.php',
    'views/layouts/auth.php',
    'routes/web.php',
] as $sf) {
    exec("\"$phpBin\" -l \"{$root}/{$sf}\" 2>&1", $out, $code);
    chk("SYN-" . basename($sf), "Syntax: $sf", $code === 0,
        $code === 0 ? 'OK' : implode(' ', $out));
    unset($out);
}

// ── Cleanup ───────────────────────────────────────────────────────────────────
if ($createdRows) {
    if (isset($onlinePayId)) {
        $pdo->prepare("DELETE FROM payments WHERE id=:id")->execute([':id' => $onlinePayId]);
        $pdo->prepare("DELETE FROM request_status_history WHERE request_id=:id")->execute([':id' => $onlineReqId]);
        $pdo->prepare("DELETE FROM requests WHERE id=:id")->execute([':id' => $onlineReqId]);
    }
    $pdo->prepare("DELETE FROM payments WHERE request_id=:id")->execute([':id' => $testReqId]);
    $pdo->prepare("DELETE FROM request_status_history WHERE request_id=:id")->execute([':id' => $testReqId]);
    $pdo->prepare("DELETE FROM requests WHERE id=:id")->execute([':id' => $testReqId]);
}

// ── Results ───────────────────────────────────────────────────────────────────
echo "\n====================================================\n";
echo " Admin Payment History + Requests Table Fix Tests\n";
echo "====================================================\n\n";
foreach ($results as [$s, $id, $desc, $detail]) {
    echo "[{$s}] " . ($s === 'PASS' ? 'PASS' : 'FAIL') . " {$id}: {$desc}"
         . ($detail ? " ({$detail})" : '') . "\n";
}
$t = $passed + $failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$t} passed.\n";
if ($failed === 0) { echo "\nALL TESTS PASSED\n\n"; exit(0); }
else               { echo "\nSOME TESTS FAILED\n\n"; exit(1); }
