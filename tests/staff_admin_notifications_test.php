<?php
/**
 * eMunicipal — Staff/Admin Notifications + Search Integration Test
 * Run from project root: php staff_admin_test.php
 */

$root = __DIR__;
// Load .env
if (file_exists($root . '/.env')) {
    foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\n\r\"'");
    }
}
require_once $root . '/vendor/autoload.php';

use Emunicipal\Core\Database;
use Emunicipal\Models\User;
use Emunicipal\Models\Notification;
use Emunicipal\Models\Request as RequestModel;
use Emunicipal\Services\NotificationService;

$passed = 0; $failed = 0; $results = [];

function check(string $id, string $desc, bool $cond, string $detail = ''): void {
    global $passed, $failed, $results;
    if ($cond) { $passed++; $results[] = ['PASS', $id, $desc, $detail]; }
    else       { $failed++; $results[] = ['FAIL', $id, $desc, $detail]; }
}

// ----------------------------------------------------------------
// 1. DB Connection
// ----------------------------------------------------------------
try {
    $db = Database::getInstance();
    check('DB-01', 'Database connection established', true);
} catch (\Throwable $e) {
    check('DB-01', 'Database connection', false, $e->getMessage());
    echo "FATAL: Cannot connect to DB: " . $e->getMessage() . "\n"; exit(1);
}

// ----------------------------------------------------------------
// 2. notifications table columns
// ----------------------------------------------------------------
$stmt = $db->query("DESCRIBE notifications");
$cols = array_column($stmt->fetchAll(), 'Field');
check('DB-02', 'notifications table has expected columns',
    in_array('user_id', $cols) && in_array('type', $cols) && in_array('is_read', $cols) && in_array('reference_number', $cols),
    'Columns: ' . implode(', ', $cols));

// ----------------------------------------------------------------
// 3. User model: activeStaffAdminIds()
// ----------------------------------------------------------------
$staffAdminIds = (new User())->activeStaffAdminIds();
check('MODEL-01', 'User::activeStaffAdminIds() returns array', is_array($staffAdminIds),
    'Count: ' . count($staffAdminIds));
check('MODEL-02', 'At least one Staff or Admin user exists in DB', count($staffAdminIds) > 0,
    'IDs: ' . implode(', ', array_slice($staffAdminIds, 0, 5)));

// ----------------------------------------------------------------
// 4. Notification type constants
// ----------------------------------------------------------------
check('MODEL-03', 'TYPE_NEW_REQUEST_STAFF constant defined',
    defined('\Emunicipal\Models\Notification::TYPE_NEW_REQUEST_STAFF'),
    Notification::TYPE_NEW_REQUEST_STAFF);
check('MODEL-04', 'TYPE_RESIDENT_CANCELLED constant defined',
    defined('\Emunicipal\Models\Notification::TYPE_RESIDENT_CANCELLED'),
    Notification::TYPE_RESIDENT_CANCELLED);

// ----------------------------------------------------------------
// 5. Request search: email and contact
// ----------------------------------------------------------------
$requestModel = new RequestModel();

$all = $requestModel->findAllForStaff([]);
check('SEARCH-01', 'findAllForStaff() returns results array', is_array($all));
check('SEARCH-02', 'lastTotalCount property populated', $requestModel->lastTotalCount >= 0,
    'Total rows: ' . $requestModel->lastTotalCount);

// EMU reference search
$byRef = $requestModel->findAllForStaff(['search' => 'EMU-']);
check('SEARCH-03', 'EMU- search returns results', count($byRef) > 0, count($byRef) . ' results');

// Email search (search for @)
$byEmail = $requestModel->findAllForStaff(['search' => '@']);
check('SEARCH-04', 'Email search (contains @) executes without error', is_array($byEmail), count($byEmail) . ' results');

// Contact number search
$byContact = $requestModel->findAllForStaff(['search' => '09']);
check('SEARCH-05', 'Contact number search executes without error', is_array($byContact), count($byContact) . ' results');

// Status filter
$byPending = $requestModel->findAllForStaff(['status' => 'pending']);
$allPending = array_filter($byPending, fn($r) => $r['status'] === 'pending');
check('SEARCH-06', 'Status filter respects status', count($byPending) === count($allPending),
    'Returned: ' . count($byPending) . ', All pending: ' . count($allPending));

// Pagination
$page1 = $requestModel->findAllForStaff(['page' => 1, 'per_page' => 2]);
$total = $requestModel->lastTotalCount;
check('SEARCH-07', 'Pagination per_page=2 returns at most 2 results', count($page1) <= 2,
    'Got: ' . count($page1) . ', Total: ' . $total);

if ($total >= 3) {
    $page2 = $requestModel->findAllForStaff(['page' => 2, 'per_page' => 2]);
    $ids1 = array_column($page1, 'id');
    $ids2 = array_column($page2, 'id');
    check('SEARCH-08', 'Page 2 records differ from page 1 records',
        !empty($ids1) && !empty($ids2) && empty(array_intersect($ids1, $ids2)),
        'P1 IDs: ' . implode(',', $ids1) . ' | P2 IDs: ' . implode(',', $ids2));
} else {
    check('SEARCH-08', 'Pagination page 2 test: skipped (not enough records)', true, "Only {$total} records");
}

// Combined search + status
$combined = $requestModel->findAllForStaff(['search' => 'EMU-', 'status' => 'pending']);
$combinedOk = empty(array_filter($combined, fn($r) => $r['status'] !== 'pending'));
check('SEARCH-09', 'Combined search+status: all results respect status filter', $combinedOk,
    count($combined) . ' results');

// ----------------------------------------------------------------
// 6. NotificationService: newRequestForStaffAdmin fan-out
// ----------------------------------------------------------------
if (!empty($all)) {
    $testReq = $all[0];
    $countBefore = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE type = 'new_request_staff'")->fetchColumn();

    NotificationService::newRequestForStaffAdmin(
        (int)$testReq['id'],
        $testReq['reference_number'],
        $testReq['document_type_name'] ?? 'Test Doc',
        'Test Resident'
    );

    $countAfter = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE type = 'new_request_staff'")->fetchColumn();
    $created = $countAfter - $countBefore;

    check('NOTIF-01', 'newRequestForStaffAdmin creates 1 notification per staff/admin user',
        $created === count($staffAdminIds),
        "Expected: " . count($staffAdminIds) . ", Created: {$created}");

    // Check content
    $stmt2 = $db->prepare("SELECT * FROM notifications WHERE type='new_request_staff' AND request_id=:rid ORDER BY id DESC LIMIT 1");
    $stmt2->execute([':rid' => $testReq['id']]);
    $n = $stmt2->fetch();
    check('NOTIF-02', 'Notification has correct reference_number',
        $n && $n['reference_number'] === $testReq['reference_number'], $n ? $n['reference_number'] : 'null');
    check('NOTIF-03', 'Notification is_read defaults to 0', $n && (int)$n['is_read'] === 0);
    check('NOTIF-04', 'Notification has correct request_id', $n && (int)$n['request_id'] === (int)$testReq['id']);
} else {
    foreach (['NOTIF-01','NOTIF-02','NOTIF-03','NOTIF-04'] as $t) check($t, "Skipped: no requests", true, 'No data');
}

// ----------------------------------------------------------------
// 7. NotificationService: residentCancelledForStaffAdmin
// ----------------------------------------------------------------
if (!empty($all)) {
    $testReq = $all[0];
    $countBefore2 = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE type = 'resident_cancelled'")->fetchColumn();

    NotificationService::residentCancelledForStaffAdmin(
        (int)$testReq['id'],
        $testReq['reference_number'],
        'Test Resident',
        $testReq['document_type_name'] ?? 'Test Doc'
    );

    $countAfter2 = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE type = 'resident_cancelled'")->fetchColumn();
    $created2 = $countAfter2 - $countBefore2;
    check('NOTIF-05', 'residentCancelledForStaffAdmin creates 1 per staff/admin user',
        $created2 === count($staffAdminIds),
        "Expected: " . count($staffAdminIds) . ", Created: {$created2}");
} else {
    check('NOTIF-05', 'residentCancelledForStaffAdmin: skipped', true, 'No data');
}

// ----------------------------------------------------------------
// 8. Ownership isolation
// ----------------------------------------------------------------
if (count($staffAdminIds) >= 2) {
    $uid1 = (int)$staffAdminIds[0];
    $uid2 = (int)$staffAdminIds[1];
    $notifModel = new Notification();
    $nid = $notifModel->insert([
        'user_id' => $uid1, 'request_id' => null, 'reference_number' => 'EMU-ISOL-000001',
        'type' => 'new_request_staff', 'title' => 'Isolation Test', 'message' => 'test', 'is_read' => 0,
    ]);

    $affected = $notifModel->markRead($nid, $uid2);
    check('SEC-01', 'markRead by wrong user_id affects 0 rows', $affected === 0, "Affected: {$affected}");

    $affected2 = $notifModel->markRead($nid, $uid1);
    check('SEC-02', 'markRead by correct user_id affects 1 row', $affected2 === 1, "Affected: {$affected2}");

    $db->prepare("DELETE FROM notifications WHERE id=:id")->execute([':id' => $nid]);
} else {
    check('SEC-01', 'Isolation test: skipped (need 2+ staff/admin users)', true, 'Skipped');
    check('SEC-02', 'Isolation correct user: skipped', true, 'Skipped');
}

// ----------------------------------------------------------------
// 9. Unread count and markAllRead
// ----------------------------------------------------------------
if (!empty($staffAdminIds)) {
    $uid = (int)$staffAdminIds[0];
    $notifModel = new Notification();
    $nid2 = $notifModel->insert([
        'user_id' => $uid, 'request_id' => null, 'reference_number' => null,
        'type' => 'new_request_staff', 'title' => 'Unread Test', 'message' => 'test', 'is_read' => 0,
    ]);
    $countUnread = $notifModel->countUnread($uid);
    check('NOTIF-06', 'countUnread reflects unread notifications', $countUnread >= 1, "Unread: {$countUnread}");

    $notifModel->markAllRead($uid);
    $countAfterAll = $notifModel->countUnread($uid);
    check('NOTIF-07', 'markAllRead reduces unread to 0', $countAfterAll === 0, "After: {$countAfterAll}");

    $db->prepare("DELETE FROM notifications WHERE id=:id")->execute([':id' => $nid2]);
}

// ----------------------------------------------------------------
// Output
// ----------------------------------------------------------------
echo "\n===== eMunicipal Staff/Admin Notifications + Search Integration Test =====\n\n";
foreach ($results as [$status, $id, $desc, $detail]) {
    $icon = $status === 'PASS' ? '✓' : '✗';
    echo "[{$status}] {$icon} {$id}: {$desc}" . ($detail ? " ({$detail})" : '') . "\n";
}
$total2 = $passed + $failed;
echo "\n" . str_repeat('-', 72) . "\n";
echo "Results: {$passed}/{$total2} passed.\n";
if ($failed === 0) { echo "\nALL TESTS PASSED\n\n"; exit(0); }
else               { echo "\nSOME TESTS FAILED\n\n"; exit(1); }
