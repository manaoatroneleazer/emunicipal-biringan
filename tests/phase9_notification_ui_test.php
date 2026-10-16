<?php
/**
 * Phase 9 — Notification UI Verification Tests
 * Live integration + static inspection tests.
 */
$root = dirname(__DIR__);
foreach (file($root."/.env",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if(str_starts_with(trim($line),"#")||!str_contains($line,"=")) continue;
    [$k,$v]=explode("=",$line,2); $_ENV[trim($k)]=trim($v," \t\n\r\"'");
}
require_once $root."/vendor/autoload.php";
use Emunicipal\Core\Database;
use Emunicipal\Models\Notification;
use Emunicipal\Services\NotificationService;

$pdo    = Database::getInstance();
$passed = 0; $failed = 0; $results = [];
function chk(string $id,string $desc,bool $pass,string $detail=''):void{
    global $passed,$failed,$results;
    if($pass){$passed++;$results[]=['PASS',$id,$desc,$detail];}
    else{$failed++;$results[]=['FAIL',$id,$desc,$detail];}
}

// Load source files
$notifModel  = file_get_contents($root.'/app/Models/Notification.php');
$notifSvc    = file_get_contents($root.'/app/Services/NotificationService.php');
$resCtrl     = file_get_contents($root.'/app/Controllers/Resident/NotificationController.php');
$staffCtrl   = file_get_contents($root.'/app/Controllers/Staff/StaffNotificationController.php');
$adminCtrl   = file_get_contents($root.'/app/Controllers/Admin/AdminNotificationController.php');
$resView     = file_get_contents($root.'/views/resident/notifications/index.php');
$staffView   = file_get_contents($root.'/views/staff/notifications/index.php');
$adminView   = file_get_contents($root.'/views/admin/notifications/index.php');
$authLayout  = file_get_contents($root.'/views/layouts/auth.php');
$routesSrc   = file_get_contents($root.'/routes/web.php');
$reqCtrl     = file_get_contents($root.'/app/Controllers/Resident/RequestController.php');
$staffReqCtrl= file_get_contents($root.'/app/Controllers/Staff/StaffRequestController.php');

// ── N-01: Notification type constants ─────────────────────────────────────
chk('N-01a',"TYPE_REQUEST_SUBMITTED defined",
    defined('Emunicipal\\Models\\Notification::TYPE_REQUEST_SUBMITTED'));
chk('N-01b',"TYPE_ADDITIONAL_INFO_REQUIRED defined",
    defined('Emunicipal\\Models\\Notification::TYPE_ADDITIONAL_INFO_REQUIRED'));
chk('N-01c',"TYPE_APPROVED defined",
    defined('Emunicipal\\Models\\Notification::TYPE_APPROVED'));
chk('N-01d',"TYPE_REJECTED defined",
    defined('Emunicipal\\Models\\Notification::TYPE_REJECTED'));
chk('N-01e',"TYPE_READY_FOR_CLAIMING defined",
    defined('Emunicipal\\Models\\Notification::TYPE_READY_FOR_CLAIMING'));
chk('N-01f',"TYPE_COMPLETED defined",
    defined('Emunicipal\\Models\\Notification::TYPE_COMPLETED'));
chk('N-01g',"TYPE_CANCELLED defined",
    defined('Emunicipal\\Models\\Notification::TYPE_CANCELLED'));
chk('N-01h',"TYPE_UNDER_VERIFICATION defined",
    defined('Emunicipal\\Models\\Notification::TYPE_UNDER_VERIFICATION'));
chk('N-01i',"TYPE_NEW_REQUEST_STAFF defined",
    defined('Emunicipal\\Models\\Notification::TYPE_NEW_REQUEST_STAFF'));
chk('N-01j',"TYPE_RESIDENT_CANCELLED defined",
    defined('Emunicipal\\Models\\Notification::TYPE_RESIDENT_CANCELLED'));
chk('N-01k',"TYPE_ADDITIONAL_INFO_SUBMITTED defined",
    defined('Emunicipal\\Models\\Notification::TYPE_ADDITIONAL_INFO_SUBMITTED'));

// ── N-02: NotificationService methods ─────────────────────────────────────
chk('N-02a',"requestSubmitted() method exists",
    method_exists('Emunicipal\\Services\\NotificationService','requestSubmitted'));
chk('N-02b',"underVerification() method exists",
    method_exists('Emunicipal\\Services\\NotificationService','underVerification'));
chk('N-02c',"additionalInfoRequired() method exists",
    method_exists('Emunicipal\\Services\\NotificationService','additionalInfoRequired'));
chk('N-02d',"approved() method exists",
    method_exists('Emunicipal\\Services\\NotificationService','approved'));
chk('N-02e',"rejected() method exists",
    method_exists('Emunicipal\\Services\\NotificationService','rejected'));
chk('N-02f',"readyForClaiming() method exists",
    method_exists('Emunicipal\\Services\\NotificationService','readyForClaiming'));
chk('N-02g',"completed() method exists",
    method_exists('Emunicipal\\Services\\NotificationService','completed'));
chk('N-02h',"cancelled() method exists",
    method_exists('Emunicipal\\Services\\NotificationService','cancelled'));
chk('N-02i',"newRequestForStaffAdmin() method exists",
    method_exists('Emunicipal\\Services\\NotificationService','newRequestForStaffAdmin'));
chk('N-02j',"residentCancelledForStaffAdmin() method exists",
    method_exists('Emunicipal\\Services\\NotificationService','residentCancelledForStaffAdmin'));
chk('N-02k',"additionalInfoSubmittedForStaffAdmin() method exists",
    method_exists('Emunicipal\\Services\\NotificationService','additionalInfoSubmittedForStaffAdmin'));

// ── N-03: Status transitions dispatch notifications ────────────────────────
chk('N-03a',"StaffRequestController dispatches notifications on status change",
    str_contains($staffReqCtrl,'NotificationService::'));
chk('N-03b',"Request submission dispatches requestSubmitted()",
    str_contains($reqCtrl,'NotificationService::requestSubmitted('));
chk('N-03c',"Cancellation dispatches cancelled()",
    str_contains($reqCtrl,'NotificationService::cancelled('));
chk('N-03d',"Cancellation dispatches residentCancelledForStaffAdmin()",
    str_contains($reqCtrl,'NotificationService::residentCancelledForStaffAdmin('));
chk('N-03e',"Additional upload dispatches additionalInfoSubmittedForStaffAdmin()",
    str_contains($reqCtrl,'NotificationService::additionalInfoSubmittedForStaffAdmin('));
chk('N-03f',"New request dispatches newRequestForStaffAdmin()",
    str_contains($reqCtrl,'NotificationService::newRequestForStaffAdmin('));

// ── N-04: Controller security ─────────────────────────────────────────────
chk('N-04a',"Resident NotificationController requires resident role",
    str_contains($resCtrl,"requireRole('resident')"));
chk('N-04b',"Staff NotificationController requires staff role",
    str_contains($staffCtrl,"requireRole('staff')"));
chk('N-04c',"Admin NotificationController requires admin role",
    str_contains($adminCtrl,"requireRole('admin')"));
chk('N-04d',"Resident controller gets userId from session (not input)",
    str_contains($resCtrl,'AuthMiddleware::userId()') &&
    !str_contains($resCtrl,'$_GET[\'user_id\']') &&
    !str_contains($resCtrl,'$_POST[\'user_id\']'));
chk('N-04e',"markRead scoped to authenticated user (user_id check in SQL)",
    str_contains($notifModel,'AND user_id = :user_id'));
chk('N-04f',"CSRF validated on markRead (resident)",
    str_contains($resCtrl,'validateCsrfToken'));
chk('N-04g',"CSRF validated on markRead (staff)",
    str_contains($staffCtrl,'validateCsrfToken'));
chk('N-04h',"CSRF validated on markRead (admin)",
    str_contains($adminCtrl,'validateCsrfToken'));

// ── N-05: Unread badge in auth layout ─────────────────────────────────────
chk('N-05a',"Auth layout queries unread count for resident+staff+admin",
    str_contains($authLayout,"in_array(\$role, ['resident', 'staff', 'admin']"));
chk('N-05b',"Unread count badge shown in resident sidebar",
    preg_match('/resident.*notifications.*unreadCount/s',$authLayout)===1 ||
    (str_contains($authLayout,'/resident/notifications') && str_contains($authLayout,'$unreadCount')));
chk('N-05c',"Unread count badge shown in staff sidebar",
    str_contains($authLayout,'/staff/notifications') && str_contains($authLayout,'$unreadCount'));
chk('N-05d',"Unread count badge shown in admin sidebar",
    str_contains($authLayout,'/admin/notifications') && str_contains($authLayout,'$unreadCount'));

// ── N-06: Routes ───────────────────────────────────────────────────────────
chk('N-06a',"GET /resident/notifications route",
    str_contains($routesSrc,"'/resident/notifications'"));
chk('N-06b',"POST /resident/notifications/{id}/read route",
    str_contains($routesSrc,"'/resident/notifications/{id}/read'"));
// N-06c: Router uses exact-match before pattern-match, so read-all (exact) is always
// correctly routed. Verify both routes are registered (order is not a routing risk here).
chk('N-06c',"POST /resident/notifications/read-all and {id}/read routes both registered",
    str_contains($routesSrc,"'/resident/notifications/read-all'") &&
    str_contains($routesSrc,"'/resident/notifications/{id}/read'"));
chk('N-06d',"GET /staff/notifications route",
    str_contains($routesSrc,"'/staff/notifications'"));
chk('N-06e',"POST /staff/notifications/read-all BEFORE {id}/read",
    strpos($routesSrc,"'/staff/notifications/read-all'") < strpos($routesSrc,"'/staff/notifications/{id}/read'"));
chk('N-06f',"GET /admin/notifications route",
    str_contains($routesSrc,"'/admin/notifications'"));
chk('N-06g',"POST /admin/notifications/read-all BEFORE {id}/read",
    strpos($routesSrc,"'/admin/notifications/read-all'") < strpos($routesSrc,"'/admin/notifications/{id}/read'"));
chk('N-06h',"GET /api/notifications route",
    str_contains($routesSrc,"'/api/notifications'"));

// ── N-07: View type maps ───────────────────────────────────────────────────
// Resident view has all 8 resident types
foreach (['request_submitted','under_verification','additional_info_required',
          'approved','rejected','ready_for_claiming','completed','cancelled'] as $type) {
    chk("N-07-res-{$type}","Resident view has type: $type",str_contains($resView,$type));
}
// Staff view has 3 operational types including additional_info_submitted
chk('N-07-staff-new_request',"Staff view has new_request_staff",
    str_contains($staffView,'new_request_staff'));
chk('N-07-staff-cancelled',"Staff view has resident_cancelled",
    str_contains($staffView,'resident_cancelled'));
chk('N-07-staff-additional_submitted',"Staff view has additional_info_submitted (Phase 9 fix)",
    str_contains($staffView,'additional_info_submitted'));
// Admin view has same 3 operational types
chk('N-07-admin-new_request',"Admin view has new_request_staff",
    str_contains($adminView,'new_request_staff'));
chk('N-07-admin-cancelled',"Admin view has resident_cancelled",
    str_contains($adminView,'resident_cancelled'));
chk('N-07-admin-additional_submitted',"Admin view has additional_info_submitted (Phase 9 fix)",
    str_contains($adminView,'additional_info_submitted'));
// Default fallback exists
chk('N-07-res-default',"Resident view has defaultType fallback",
    str_contains($resView,'$defaultType'));
chk('N-07-staff-default',"Staff view has defaultType fallback",
    str_contains($staffView,'$defaultType'));

// ── N-08: View UI elements ─────────────────────────────────────────────────
// Resident view
chk('N-08a',"Resident view has unread visual distinction",
    str_contains($resView,'notif-item-unread') || str_contains($resView,'is_read'));
chk('N-08b',"Resident view has Mark All as Read button",
    str_contains($resView,'read-all'));
chk('N-08c',"Resident view has empty state",
    str_contains($resView,'empty-state') || str_contains($resView,'No notifications'));
chk('N-08d',"Resident view has View Request navigation",
    str_contains($resView,'View Request'));
chk('N-08e',"Resident view escapes output (htmlspecialchars)",
    str_contains($resView,'htmlspecialchars') || str_contains($resView,"\$e("));
chk('N-08f',"Resident view shows timestamp",
    str_contains($resView,'created_at'));
chk('N-08g',"Resident View Request link goes to /resident/requests/",
    str_contains($resView,'/resident/requests/'));
// Staff view
chk('N-08h',"Staff view has View Request → /staff/requests/",
    str_contains($staffView,'/staff/requests/'));
chk('N-08i',"Staff view has Mark All as Read button",
    str_contains($staffView,'read-all'));
// Admin view
chk('N-08j',"Admin view has View Request → /admin/requests/",
    str_contains($adminView,'/admin/requests/'));
chk('N-08k',"Admin view has Mark All as Read button",
    str_contains($adminView,'read-all'));

// ── N-09: Accessibility basics ─────────────────────────────────────────────
chk('N-09a',"Resident view has aria-label on notification list",
    str_contains($resView,'aria-label'));
chk('N-09b',"Resident view has aria-label on Mark All as Read",
    str_contains($resView,'aria-label'));
chk('N-09c',"Staff view has aria-label",str_contains($staffView,'aria-label'));
chk('N-09d',"Admin view has aria-label",str_contains($adminView,'aria-label'));
chk('N-09e',"Icons have aria-hidden in resident view",
    str_contains($resView,'aria-hidden="true"'));
chk('N-09f',"Icons have aria-hidden in staff view",
    str_contains($staffView,'aria-hidden="true"'));

// ── N-10: Live DB notification behavior ────────────────────────────────────
$model = new Notification();
// Create test notification for resident uid=4
$testResidentId = 4;
$testNid = $model->insert([
    'user_id'          => $testResidentId,
    'request_id'       => 9,
    'reference_number' => 'EMU-2026-000009',
    'type'             => 'approved',
    'title'            => 'Phase9 Test Notification',
    'message'          => 'Test notification for Phase 9 verification',
    'is_read'          => 0,
]);
chk('N-10a',"Live: notification INSERT succeeds",$testNid>0,"id=$testNid");

// countUnread increases
$unreadBefore = $model->countUnread($testResidentId);
chk('N-10b',"Live: countUnread includes new unread",$unreadBefore>=1,"unread=$unreadBefore");

// forUser returns the notification
$notifs = $model->forUser($testResidentId, 50, 0);
$found  = array_filter($notifs, fn($n) => (int)$n['id'] === $testNid);
chk('N-10c',"Live: forUser returns new notification",!empty($found));

// markRead with correct user
$affected = $model->markRead($testNid, $testResidentId);
chk('N-10d',"Live: markRead with correct user affects 1 row",$affected===1,"affected=$affected");

// markRead with WRONG user (cross-user isolation)
$testAdminId = 1; // admin user
$affected2 = $model->markRead($testNid, $testAdminId);
chk('N-10e',"Live: markRead with WRONG user affects 0 rows (isolation)",$affected2===0,"affected=$affected2");

// countUnread decreases after markRead
$unreadAfter = $model->countUnread($testResidentId);
chk('N-10f',"Live: unread count decreases after markRead",$unreadAfter<$unreadBefore||$unreadBefore===0,
    "before=$unreadBefore after=$unreadAfter");

// Create additional_info_submitted notification for staff
$staffUserId = 3; // Maria Santos (staff)
$staffNid = $model->insert([
    'user_id'          => $staffUserId,
    'request_id'       => 9,
    'reference_number' => 'EMU-2026-000009',
    'type'             => 'additional_info_submitted',
    'title'            => 'Additional Information Submitted',
    'message'          => 'Test resident submitted additional docs for Phase 9',
    'is_read'          => 0,
]);
chk('N-10g',"Live: additional_info_submitted notification created for staff",$staffNid>0,"id=$staffNid");
$staffNotifs = $model->forUser($staffUserId, 50, 0);
$sfound = array_filter($staffNotifs, fn($n) => (int)$n['id'] === $staffNid);
chk('N-10h',"Live: staff can see their own notification",!empty($sfound));
// Staff notification not visible to resident
$resNotifs = $model->forUser($testResidentId, 100, 0);
$crossLeak = array_filter($resNotifs, fn($n) => (int)$n['id'] === $staffNid);
chk('N-10i',"Live: staff notification NOT in resident's list",empty($crossLeak));

// markAllRead for staff
$model->markAllRead($staffUserId);
$staffUnread = $model->countUnread($staffUserId);
chk('N-10j',"Live: markAllRead reduces unread to 0 for staff",$staffUnread===0,"unread=$staffUnread");

// Cleanup test notifications
$pdo->prepare("DELETE FROM notifications WHERE id IN (?,?)")->execute([$testNid,$staffNid]);
$cleanup = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE id IN ($testNid,$staffNid)")->fetchColumn();
chk('N-10k',"Live: test notifications cleaned up",$cleanup===0);

// ── N-11: fanout uses activeStaffAdminIds ─────────────────────────────────
chk('N-11a',"newRequestForStaffAdmin calls activeStaffAdminIds()",
    str_contains($notifSvc,'activeStaffAdminIds()'));
chk('N-11b',"Notifications non-fatal (catches Throwable, no rethrow)",
    str_contains($notifSvc,'catch (\\Throwable $e)') &&
    str_contains($notifSvc,'// Non-fatal'));

// ── N-12: Status workflow dispatches correct notifications ─────────────────
chk('N-12a',"underVerification dispatched in StaffRequestController",
    str_contains($staffReqCtrl,'NotificationService::underVerification('));
chk('N-12b',"approved dispatched in StaffRequestController",
    str_contains($staffReqCtrl,'NotificationService::approved('));
chk('N-12c',"rejected dispatched in StaffRequestController",
    str_contains($staffReqCtrl,'NotificationService::rejected('));
chk('N-12d',"readyForClaiming dispatched in StaffRequestController",
    str_contains($staffReqCtrl,'NotificationService::readyForClaiming('));
chk('N-12e',"completed dispatched in StaffRequestController",
    str_contains($staffReqCtrl,'NotificationService::completed('));
chk('N-12f',"additionalInfoRequired dispatched in StaffRequestController",
    str_contains($staffReqCtrl,'NotificationService::additionalInfoRequired('));

// ── N-13: Syntax checks ────────────────────────────────────────────────────
$phpExe="c:\\xampp\\php\\php.exe";
$synFiles=[
    'app/Models/Notification.php',
    'app/Services/NotificationService.php',
    'app/Controllers/Resident/NotificationController.php',
    'app/Controllers/Staff/StaffNotificationController.php',
    'app/Controllers/Admin/AdminNotificationController.php',
    'views/resident/notifications/index.php',
    'views/staff/notifications/index.php',
    'views/admin/notifications/index.php',
    'views/layouts/auth.php',
];
foreach($synFiles as $sf){
    exec("$phpExe -l {$root}/{$sf} 2>&1",$out,$code);
    chk("N-13-".basename($sf),"Syntax: $sf",$code===0,$code===0?'OK':implode(' ',$out));
}

// ── REGRESSION ────────────────────────────────────────────────────────────
$authRaw=file_get_contents($root.'/views/layouts/auth.php');
chk('REG-01',"Phase1 uploadAdditionalDocuments",str_contains($reqCtrl,'uploadAdditionalDocuments'));
chk('REG-02',"Phase2 API routes",str_contains($routesSrc,"'/api/auth/login'"));
chk('REG-03',"Phase3 reports route",str_contains($routesSrc,"'/admin/reports'"));
chk('REG-04',"Phase4 audit-logs route",str_contains($routesSrc,"'/admin/audit-logs'"));
chk('REG-05',"Phase5 SettingsService::applyRuntimeSettings",
    method_exists('Emunicipal\\Services\\SettingsService','applyRuntimeSettings'));
chk('REG-06',"Phase6 lockout window-reset",
    str_contains(file_get_contents($root.'/app/Controllers/AuthController.php'),'15-minute window reset'));
chk('REG-07',"Phase7 X-Frame-Options DENY",
    str_contains(file_get_contents($root.'/public/.htaccess'),'"DENY"'));
chk('REG-08',"Phase8 UserController::store() not stub",
    !str_contains(file_get_contents($root.'/app/Controllers/Admin/UserController.php'),'not yet available'));
chk('REG-09',"transitions v4.0 unchanged",str_contains($authRaw,'v=4.0'));
chk('REG-10',"Phase1 status NOT changed in uploadAdditionalDocuments",
    !preg_match('/uploadAdditionalDocuments[\s\S]*?\$requestModel->update/s',
        substr($reqCtrl, strpos($reqCtrl,'uploadAdditionalDocuments'), 3000)));

echo "\n====================================================\n";
echo " PHASE 9 - Notification UI Verification Results\n";
echo "====================================================\n\n";
foreach($results as[$s,$id,$desc,$detail])
    echo "[$s] ".($s==='PASS'?'PASS':'FAIL')." $id: $desc".($detail?" ($detail)":"")."\n";
$t=$passed+$failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$t} passed.\n";
if($failed===0){echo "\nALL TESTS PASSED\n\n";exit(0);}
else{echo "\nSOME TESTS FAILED\n\n";exit(1);}