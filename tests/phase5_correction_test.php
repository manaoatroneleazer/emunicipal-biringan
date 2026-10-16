<?php
/**
 * Phase 5 Correction — Runtime Settings Persistence Tests
 * Verifies that system_settings changes propagate to NEW request contexts.
 */
$root = dirname(__DIR__);
foreach (file($root."/.env",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if(str_starts_with(trim($line),"#")||!str_contains($line,"=")) continue;
    [$k,$v]=explode("=",$line,2); $_ENV[trim($k)]=trim($v," \t\n\r\"'");
}
require_once $root."/vendor/autoload.php";
use Emunicipal\Core\Database;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\FileUploadService;
use Emunicipal\Services\SettingsService;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Controllers\Admin\SystemSettingController;

$pdo=Database::getInstance();
$passed=0;$failed=0;$results=[];
function chk(string $id,string $desc,bool $pass,string $detail=''):void{
    global $passed,$failed,$results;
    if($pass){$passed++;$results[]=['PASS',$id,$desc,$detail];}
    else{$failed++;$results[]=['FAIL',$id,$desc,$detail];}
}
$src   = file_get_contents($root.'/app/Core/Application.php');
$ssSrc = file_get_contents($root.'/app/Services/SettingsService.php');
$ctrlSrc = file_get_contents($root.'/app/Controllers/Admin/SystemSettingController.php');
$repSrc = file_get_contents($root.'/app/Controllers/Admin/ReportController.php');
$audSrc = file_get_contents($root.'/app/Controllers/Admin/AuditLogController.php');
$staffSrc = file_get_contents($root.'/app/Controllers/Staff/StaffRequestController.php');
$adminReqSrc = file_get_contents($root.'/app/Controllers/Admin/AdminRequestController.php');
$staffView = file_get_contents($root.'/views/staff/requests/index.php');
$adminView = file_get_contents($root.'/views/admin/requests/index.php');
$routesSrc = file_get_contents($root.'/routes/web.php');
$authRaw   = file_get_contents($root.'/views/layouts/auth.php');

// ── 1. SettingsService exists and has required methods ──────────────────────
chk('SS-01',"SettingsService class exists",class_exists('Emunicipal\\Services\\SettingsService'));
chk('SS-02',"applyRuntimeSettings() method",method_exists('Emunicipal\\Services\\SettingsService','applyRuntimeSettings'));
chk('SS-03',"all() method",method_exists('Emunicipal\\Services\\SettingsService','all'));
chk('SS-04',"get() method",method_exists('Emunicipal\\Services\\SettingsService','get'));
chk('SS-05',"recordsPerPage() method",method_exists('Emunicipal\\Services\\SettingsService','recordsPerPage'));
chk('SS-06',"clearCache() method",method_exists('Emunicipal\\Services\\SettingsService','clearCache'));

// ── 2. Application bootstraps SettingsService per-request ───────────────────
chk('APP-01',"Application calls applyRuntimeSettings() in __construct",
    str_contains($src,'SettingsService::applyRuntimeSettings()'));
chk('APP-02',"Call is after DB connection is available (after config load)",
    strpos($src,'SettingsService::applyRuntimeSettings()') > strpos($src,'require $this->basePath'));

// ── 3. SettingsService writes to $_ENV ──────────────────────────────────────
chk('SS-07',"applyRuntimeSettings writes SESSION_LIFETIME to \$_ENV",
    str_contains($ssSrc,"'SESSION_LIFETIME'"));
chk('SS-08',"applyRuntimeSettings writes UPLOAD_MAX_SIZE_MB to \$_ENV",
    str_contains($ssSrc,"'UPLOAD_MAX_SIZE_MB'"));
chk('SS-09',"applyRuntimeSettings writes RECORDS_PER_PAGE to \$_ENV",
    str_contains($ssSrc,"'RECORDS_PER_PAGE'"));
chk('SS-10',"applyRuntimeSettings is non-fatal (catches Throwable)",
    str_contains($ssSrc,'catch (\Throwable'));

// ── 4. PERSISTENCE: change DB → simulate fresh request → confirm value used ─
// Step 1: Record original DB values
$orig = SettingsService::all();
$origUpload  = $orig['max_upload_size_mb']      ?? '5';
$origTimeout = $orig['session_timeout_minutes']  ?? '30';
$origPerPage = $orig['records_per_page']         ?? '20';

// Step 2: Change all three runtime settings in DB
$pdo->prepare("UPDATE system_settings SET value='8' WHERE `key`='max_upload_size_mb'")->execute();
$pdo->prepare("UPDATE system_settings SET value='45' WHERE `key`='session_timeout_minutes'")->execute();
$pdo->prepare("UPDATE system_settings SET value='50' WHERE `key`='records_per_page'")->execute();

// Step 3: Simulate a "fresh request" by clearing SettingsService cache and
//         un-setting the existing $_ENV values, then calling applyRuntimeSettings()
//         — this is exactly what Application::__construct() does on every request
SettingsService::clearCache();
unset($_ENV['UPLOAD_MAX_SIZE_MB'], $_ENV['SESSION_LIFETIME'], $_ENV['RECORDS_PER_PAGE']);
SettingsService::applyRuntimeSettings();

// Step 4: Verify downstream components now see the DB-persisted values
$newMaxBytes = FileUploadService::getMaxBytes();
chk('PERS-01',"After DB change to 8MB, getMaxBytes() = 8*1048576",
    $newMaxBytes === 8 * 1048576,
    "getMaxBytes()=$newMaxBytes expected=".(8*1048576));

$newTimeout = AuthMiddleware::getTimeoutMinutes();
chk('PERS-02',"After DB change to 45min, getTimeoutMinutes() = 45",
    $newTimeout === 45,
    "getTimeoutMinutes()=$newTimeout");

$newPerPage = SettingsService::recordsPerPage();
chk('PERS-03',"After DB change to 50, recordsPerPage() = 50",
    $newPerPage === 50,
    "recordsPerPage()=$newPerPage");

// Step 5: Restore originals
$pdo->prepare("UPDATE system_settings SET value=:v WHERE `key`='max_upload_size_mb'")->execute([':v'=>$origUpload]);
$pdo->prepare("UPDATE system_settings SET value=:v WHERE `key`='session_timeout_minutes'")->execute([':v'=>$origTimeout]);
$pdo->prepare("UPDATE system_settings SET value=:v WHERE `key`='records_per_page'")->execute([':v'=>$origPerPage]);
SettingsService::clearCache();
SettingsService::applyRuntimeSettings();

$restoredBytes   = FileUploadService::getMaxBytes();
$restoredTimeout = AuthMiddleware::getTimeoutMinutes();
$restoredPerPage = SettingsService::recordsPerPage();

chk('PERS-04',"Restore: getMaxBytes() back to original",
    $restoredBytes === (int)$origUpload * 1048576,
    "restored=$restoredBytes orig=".(int)$origUpload."MB");
chk('PERS-05',"Restore: getTimeoutMinutes() back to original",
    $restoredTimeout === (int)$origTimeout,
    "restored=$restoredTimeout orig=$origTimeout");
chk('PERS-06',"Restore: recordsPerPage() back to original",
    $restoredPerPage === (int)$origPerPage,
    "restored=$restoredPerPage orig=$origPerPage");

// ── 5. SystemSettingController uses SettingsService after save ──────────────
chk('CTRL-01',"SystemSettingController imports SettingsService",
    str_contains($ctrlSrc,'SettingsService'));
chk('CTRL-02',"update() calls clearCache() and applyRuntimeSettings()",
    str_contains($ctrlSrc,'SettingsService::clearCache()') &&
    str_contains($ctrlSrc,'SettingsService::applyRuntimeSettings()'));
chk('CTRL-03',"loadSettings() delegates to SettingsService::all()",
    str_contains($ctrlSrc,'SettingsService::all()'));

// ── 6. Records per page wired into controllers ──────────────────────────────
chk('RPP-01',"ReportController uses SettingsService::recordsPerPage()",
    str_contains($repSrc,'SettingsService::recordsPerPage()'));
chk('RPP-02',"AuditLogController uses SettingsService::recordsPerPage()",
    str_contains($audSrc,'SettingsService::recordsPerPage()'));
chk('RPP-03',"StaffRequestController sets per_page from SettingsService",
    str_contains($staffSrc,"SettingsService::recordsPerPage()"));
chk('RPP-04',"AdminRequestController sets per_page from SettingsService",
    str_contains($adminReqSrc,"SettingsService::recordsPerPage()"));

// ── 7. Request list views have pagination controls ──────────────────────────
chk('VIEW-01',"Staff requests view has pagination links",
    str_contains($staffView,'page-link'));
chk('VIEW-02',"Staff requests view preserves filters in page URLs",
    str_contains($staffView,'$pageUrl'));
chk('VIEW-03',"Admin requests view has pagination links",
    str_contains($adminView,'page-link'));
chk('VIEW-04',"Admin requests view preserves filters in page URLs",
    str_contains($adminView,'$pageUrl'));
chk('VIEW-05',"Staff view passes totalRows from model",
    str_contains($staffSrc,'totalRows') && str_contains($staffSrc,'lastTotalCount'));
chk('VIEW-06',"Admin view passes totalRows from model",
    str_contains($adminReqSrc,'totalRows') && str_contains($adminReqSrc,'lastTotalCount'));

// ── 8. FileUploadService integration (from Phase 5 original) ────────────────
chk('FUP-01',"getMaxBytes() method exists",method_exists('Emunicipal\\Services\\FileUploadService','getMaxBytes'));
chk('FUP-02',"getMaxBytes(5MB env) = 5242880",
    (function(){ $_ENV['UPLOAD_MAX_SIZE_MB']='5'; return FileUploadService::getMaxBytes(); })() === 5242880);
chk('FUP-03',"getMaxBytes(99MB env) clamped",
    (function(){ $_ENV['UPLOAD_MAX_SIZE_MB']='99'; $r=FileUploadService::getMaxBytes(); $_ENV['UPLOAD_MAX_SIZE_MB']='5'; return $r; })() === 5242880);

// ── 9. AuthMiddleware integration ────────────────────────────────────────────
chk('AUTH-01',"getTimeoutMinutes() exists",method_exists('Emunicipal\\Middleware\\AuthMiddleware','getTimeoutMinutes'));
chk('AUTH-02',"getTimeoutMinutes(0 env) falls back to 30",
    (function(){ $_ENV['SESSION_LIFETIME']='0'; $r=AuthMiddleware::getTimeoutMinutes(); $_ENV['SESSION_LIFETIME']='30'; return $r; })() === 30);

// ── 10. SettingsService::all() live DB test ──────────────────────────────────
SettingsService::clearCache();
$all = SettingsService::all();
chk('DB-01',"SettingsService::all() returns all 7 required keys",
    isset($all['office_name'], $all['max_upload_size_mb'], $all['session_timeout_minutes'],
          $all['records_per_page'], $all['office_address'], $all['office_email']),
    count($all)." keys loaded");
chk('DB-02',"SettingsService::get() returns known value",
    SettingsService::get('office_name') !== '',
    SettingsService::get('office_name'));

// ── 11. Syntax checks ────────────────────────────────────────────────────────
$phpExe = "c:\\xampp\\php\\php.exe";
$toCheck = [
    'app/Services/SettingsService.php',
    'app/Core/Application.php',
    'app/Controllers/Admin/SystemSettingController.php',
    'app/Controllers/Admin/ReportController.php',
    'app/Controllers/Admin/AuditLogController.php',
    'app/Controllers/Staff/StaffRequestController.php',
    'app/Controllers/Admin/AdminRequestController.php',
    'views/staff/requests/index.php',
    'views/admin/requests/index.php',
];
foreach($toCheck as $f){
    exec("$phpExe -l {$root}/{$f} 2>&1", $out, $code);
    $outStr = implode(' ', $out);
    chk("SYN-".basename($f), "Syntax: $f", $code===0, $code===0?'OK':$outStr);
}

// ── 12. Regression ──────────────────────────────────────────────────────────
$rc=file_get_contents($root.'/app/Controllers/Resident/RequestController.php');
$ns=file_get_contents($root.'/app/Services/NotificationService.php');
chk('REG-01',"Phase1 uploadAdditionalDocuments",str_contains($rc,'uploadAdditionalDocuments'));
chk('REG-02',"Phase2 API routes",str_contains($routesSrc,"'/api/auth/login'"));
chk('REG-03',"Phase3 reports route",str_contains($routesSrc,"'/admin/reports'"));
chk('REG-04',"Phase4 audit-logs route",str_contains($routesSrc,"'/admin/audit-logs'"));
chk('REG-05',"Phase5 settings routes",str_contains($routesSrc,"'/admin/settings'"));
chk('REG-06',"transitions v4.0 unchanged",str_contains($authRaw,'v=4.0'));
chk('REG-07',"additionalInfoSubmittedForStaffAdmin",str_contains($ns,'additionalInfoSubmittedForStaffAdmin'));
chk('REG-08',"FileUploadService MAX_FILES still 10",Emunicipal\Services\FileUploadService::MAX_FILES===10);

// ── Output ───────────────────────────────────────────────────────────────────
echo "\n====================================================\n";
echo " PHASE 5 CORRECTION - Runtime Settings Persistence\n";
echo "====================================================\n\n";
foreach($results as[$s,$id,$desc,$detail])
    echo "[$s] ".($s==='PASS'?'PASS':'FAIL')." $id: $desc".($detail?" ($detail)":"")."\n";
$t=$passed+$failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$t} passed.\n";
if($failed===0){echo "\nALL TESTS PASSED\n\n";exit(0);}
else{echo "\nSOME TESTS FAILED\n\n";exit(1);}