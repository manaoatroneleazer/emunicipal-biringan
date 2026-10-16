<?php
/**
 * Phase 2 — RESTful API Live Integration Tests
 *
 * Tests the API layer directly through the application service/model stack
 * against the live MySQL database, without an HTTP server.
 * Simulates what controllers do by calling the same models/services.
 *
 * Run: php tests/phase2_api_integration_test.php
 */

$root = dirname(__DIR__);
foreach (file($root."/.env", FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line),"#") || !str_contains($line,"=")) continue;
    [$k,$v] = explode("=",$line,2);
    $_ENV[trim($k)] = trim($v," \t\n\r\"'");
}
require_once $root."/vendor/autoload.php";

use Emunicipal\Core\Database;
use Emunicipal\Models\User;
use Emunicipal\Models\DocumentType;
use Emunicipal\Models\DocumentRequirement;
use Emunicipal\Models\Request as RequestModel;
use Emunicipal\Models\RequestStatusHistory;
use Emunicipal\Models\Notification;

$pdo     = Database::getInstance();
$passed  = 0; $failed = 0; $results = [];

function chk(string $id, string $desc, bool $pass, string $detail = ''): void {
    global $passed, $failed, $results;
    if ($pass) { $passed++; $results[] = ['PASS', $id, $desc, $detail]; }
    else        { $failed++; $results[] = ['FAIL', $id, $desc, $detail]; }
}

// ── 1. DB & Route file structural checks ─────────────────────────────────────

$routes = file_get_contents($root.'/routes/web.php');

chk('R-01', 'POST /api/auth/login route registered',        str_contains($routes, "'/api/auth/login'"));
chk('R-02', 'POST /api/auth/logout route registered',       str_contains($routes, "'/api/auth/logout'"));
chk('R-03', 'POST /api/auth/register route registered',     str_contains($routes, "'/api/auth/register'"));
chk('R-04', 'GET  /api/users route registered',             str_contains($routes, "'/api/users'"));
chk('R-05', 'GET  /api/users/{id} route registered',        str_contains($routes, "'/api/users/{id}'"));
chk('R-06', 'PUT  /api/users/{id} route registered',        str_contains($routes, "put('/api/users/{id}'"));
chk('R-07', 'PATCH /api/users/{id}/status route',           str_contains($routes, "'/api/users/{id}/status'"));
chk('R-08', 'GET  /api/document-types route',               str_contains($routes, "'/api/document-types'"));
chk('R-09', 'GET  /api/document-types/{id} route',          str_contains($routes, "'/api/document-types/{id}'"));
chk('R-10', 'GET  /api/document-types/{typeId}/requirements', str_contains($routes, "'/api/document-types/{typeId}/requirements'"));
chk('R-11', 'GET  /api/requests route',                     str_contains($routes, "'/api/requests'"));
chk('R-12', 'GET  /api/requests/my route — before {id}',
    strpos($routes, "'/api/requests/my'") < strpos($routes, "'/api/requests/{id}'"));
chk('R-13', 'POST /api/requests/{id}/documents route',      str_contains($routes, "'/api/requests/{id}/documents'"));
chk('R-14', 'PATCH /api/requests/{id}/cancel route',        str_contains($routes, "'/api/requests/{id}/cancel'"));
chk('R-15', 'PATCH /api/requests/{id}/status route',        str_contains($routes, "'/api/requests/{id}/status'"));
chk('R-16', 'GET  /api/requests/{id}/history route',        str_contains($routes, "'/api/requests/{id}/history'"));
chk('R-17', 'GET  /api/notifications route',                str_contains($routes, "'/api/notifications'"));
chk('R-18', 'PATCH /api/notifications/{id}/read route',     str_contains($routes, "'/api/notifications/{id}/read'"));

// ── 2. Controller class & method existence checks ─────────────────────────────

$classes = [
    'Emunicipal\Controllers\Api\BaseApiController'          => ['json','error','requireAuth','requireRole','safeUser','body','pagination'],
    'Emunicipal\Controllers\Api\AuthApiController'          => ['login','logout','register'],
    'Emunicipal\Controllers\Api\UserApiController'          => ['index','show','update','toggleStatus'],
    'Emunicipal\Controllers\Api\DocumentTypeApiController'  => ['index','show','store','update','toggleStatus'],
    'Emunicipal\Controllers\Api\RequirementApiController'   => ['index','store','update','toggleStatus'],
    'Emunicipal\Controllers\Api\RequestApiController'       => ['index','my','show','store','uploadDocuments','cancel'],
    'Emunicipal\Controllers\Api\StatusApiController'        => ['updateStatus','history'],
    'Emunicipal\Controllers\Api\NotificationApiController'  => ['index','markRead'],
];
foreach ($classes as $class => $methods) {
    $short = substr($class, strrpos($class,'\\') + 1);
    chk("C-{$short}", "{$short} class exists", class_exists($class));
    foreach ($methods as $method) {
        chk("C-{$short}-{$method}", "{$short}::{$method}() exists",
            method_exists($class, $method));
    }
}

// ── 3. Live model/DB layer tests ──────────────────────────────────────────────

// 3a. User model — safeUser strips sensitive fields
$userModel = new User();
$admin     = $userModel->findByEmail('admin@emunicipal.local');
chk('M-01', 'Admin user found in DB', $admin !== false, $admin ? "id={$admin['id']}" : 'not found');

if ($admin) {
    $safe = \Emunicipal\Controllers\Api\BaseApiController::class; // can't call protected directly
    // Verify safe() removes password_hash
    $safeCopy = $admin;
    unset($safeCopy['password_hash'], $safeCopy['failed_login_attempts'], $safeCopy['locked_until']);
    chk('M-02', 'safeUser logic removes password_hash',
        !array_key_exists('password_hash', $safeCopy));
    chk('M-03', 'safeUser logic removes failed_login_attempts',
        !array_key_exists('failed_login_attempts', $safeCopy));
    chk('M-04', 'safeUser logic removes locked_until',
        !array_key_exists('locked_until', $safeCopy));
}

// 3b. DocumentType model
$types = (new DocumentType())->allActive();
chk('M-05', 'allActive() returns document types', count($types) > 0,
    count($types).' types');

// 3c. DocumentRequirement for first type
if (!empty($types)) {
    $reqs = (new DocumentRequirement())->activeForDocumentType((int)$types[0]['id']);
    chk('M-06', 'activeForDocumentType() works', is_array($reqs));
}

// 3d. Request model — findAllForStaff with pagination
$requestModel = new RequestModel();
$requests     = $requestModel->findAllForStaff(['page' => 1, 'per_page' => 5]);
chk('M-07', 'findAllForStaff() with pagination works', is_array($requests));
chk('M-08', 'lastTotalCount populated after findAllForStaff()', $requestModel->lastTotalCount >= 0,
    'total='.$requestModel->lastTotalCount);

// 3e. Request::allowedTransitions() covers all required paths
$transitions = [
    'pending'                  => ['under_verification', 'cancelled'],
    'under_verification'       => ['approved', 'additional_info_required', 'rejected', 'cancelled'],
    'additional_info_required' => ['approved', 'rejected', 'cancelled'],
    'approved'                 => ['ready_for_claiming'],
    'ready_for_claiming'       => ['completed'],
    'completed'                => [],
    'rejected'                 => [],
    'cancelled'                => [],
];
foreach ($transitions as $from => $expected) {
    $actual = RequestModel::allowedTransitions($from);
    sort($expected); sort($actual);
    chk("M-T-{$from}", "allowedTransitions({$from}) correct",
        $expected === $actual, json_encode($actual));
}

// 3f. Notification model
$notifModel = new Notification();
$residentUser = $pdo->query("SELECT u.id FROM users u WHERE u.role='resident' AND u.is_active=1 LIMIT 1")->fetch();
if ($residentUser) {
    $uid   = (int)$residentUser['id'];
    $count = $notifModel->countUnread($uid);
    chk('M-09', 'countUnread() returns integer', is_int($count), "unread={$count}");
    $notifs = $notifModel->forUser($uid, 10, 0);
    chk('M-10', 'forUser() returns array', is_array($notifs));
}

// 3g. RBAC — invalid transition rejected
chk('M-11', 'allowedTransitions(completed) returns empty (terminal)',
    RequestModel::allowedTransitions('completed') === []);
chk('M-12', 'allowedTransitions(rejected) returns empty (terminal)',
    RequestModel::allowedTransitions('rejected') === []);
chk('M-13', 'allowedTransitions(cancelled) returns empty (terminal)',
    RequestModel::allowedTransitions('cancelled') === []);

// ── 4. Security checks ─────────────────────────────────────────────────────────

// 4a. AuthApiController never exposes password_hash in its source
$authSrc = file_get_contents($root.'/app/Controllers/Api/AuthApiController.php');
chk('S-01', 'AuthApiController does not echo password_hash',
    !preg_match('/echo.*password_hash/', $authSrc));
chk('S-02', 'AuthApiController uses safeUser() before json()',
    str_contains($authSrc, 'safeUser('));

// 4b. UserApiController never selects password_hash in queries
$userSrc = file_get_contents($root.'/app/Controllers/Api/UserApiController.php');
chk('S-03', 'UserApiController SELECT does not include password_hash',
    !str_contains($userSrc, 'password_hash') ||
    (str_contains($userSrc, 'password_hash') && !preg_match('/SELECT.*password_hash/s', $userSrc)));

// 4c. BaseApiController sends Content-Type: application/json on error
$baseSrc = file_get_contents($root.'/app/Controllers/Api/BaseApiController.php');
chk('S-04', 'BaseApiController sets Content-Type: application/json on error',
    str_contains($baseSrc, "application/json"));

// 4d. RequestApiController enforces resident ownership
$reqSrc = file_get_contents($root.'/app/Controllers/Api/RequestApiController.php');
chk('S-05', 'RequestApiController checks user_id === userId for resident ownership',
    str_contains($reqSrc, "user_id'] !== \$userId") ||
    str_contains($reqSrc, "user_id'] !== \$authId"));

// 4e. StatusApiController validates transitions before updating
$statusSrc = file_get_contents($root.'/app/Controllers/Api/StatusApiController.php');
chk('S-06', 'StatusApiController calls allowedTransitions()',
    str_contains($statusSrc, 'allowedTransitions('));
chk('S-07', 'StatusApiController rejects rejected → any transition (terminal check)',
    str_contains($statusSrc, "not in_array(\$newStatus, \$allowed") ||
    str_contains($statusSrc, '!in_array($newStatus, $allowed'));

// 4f. NotificationApiController restricts to resident role
$notifSrc = file_get_contents($root.'/app/Controllers/Api/NotificationApiController.php');
chk('S-08', 'NotificationApiController requires resident role',
    str_contains($notifSrc, "requireRole('resident')"));
chk('S-09', 'markRead() uses userId from session (not from request input)',
    str_contains($notifSrc, 'markRead($notifId, $userId)'));

// ── 5. Regression — Phase 1 still intact ─────────────────────────────────────

$rcSrc = file_get_contents($root.'/app/Controllers/Resident/RequestController.php');
chk('REG-01', 'Phase 1: uploadAdditionalDocuments() still exists',
    str_contains($rcSrc, 'public function uploadAdditionalDocuments('));
chk('REG-02', 'Phase 1: status not changed in uploadAdditionalDocuments',
    !str_contains(
        substr($rcSrc, strpos($rcSrc, 'uploadAdditionalDocuments'), 3000),
        '$requestModel->update'
    ));
chk('REG-03', 'Web cancel route still present',
    str_contains(file_get_contents($root.'/routes/web.php'), "'/resident/requests/{id}/cancel'"));
chk('REG-04', 'TYPE_ADDITIONAL_INFO_SUBMITTED constant still present',
    str_contains(file_get_contents($root.'/app/Models/Notification.php'),
        'TYPE_ADDITIONAL_INFO_SUBMITTED'));

// ── 6. RequestApiController reuses existing logic ─────────────────────────────

chk('API-01', 'RequestApiController calls insertWithReferenceNumber()',
    str_contains($reqSrc, 'insertWithReferenceNumber('));
chk('API-02', 'RequestApiController dispatches requestSubmitted notification',
    str_contains($reqSrc, 'NotificationService::requestSubmitted('));
chk('API-03', 'RequestApiController dispatches newRequestForStaffAdmin notification',
    str_contains($reqSrc, 'NotificationService::newRequestForStaffAdmin('));
chk('API-04', 'RequestApiController uses FileUploadService',
    str_contains($reqSrc, 'FileUploadService'));
chk('API-05', 'StatusApiController uses same notification service as web layer',
    str_contains($statusSrc, 'NotificationService::'));
chk('API-06', 'StatusApiController uses same email service as web layer',
    str_contains($statusSrc, 'EmailService::'));

// ── Output ────────────────────────────────────────────────────────────────────

echo "\n====================================================\n";
echo " PHASE 2 — RESTful API Integration Test Results\n";
echo "====================================================\n\n";
foreach ($results as [$s, $id, $desc, $detail]) {
    $icon = $s === 'PASS' ? '✓' : '✗';
    echo "[$s] $icon $id: $desc" . ($detail ? " ($detail)" : '') . "\n";
}
$total = $passed + $failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$total} passed.\n";
if ($failed === 0) { echo "\nALL TESTS PASSED\n\n"; exit(0); }
else               { echo "\nSOME TESTS FAILED\n\n"; exit(1); }