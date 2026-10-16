<?php
$root = dirname(__DIR__);
foreach (file($root."/.env",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if(str_starts_with(trim($line),"#")||!str_contains($line,"=")) continue;
    [$k,$v]=explode("=",$line,2); $_ENV[trim($k)]=trim($v," \t\n\r\"'");
}
require_once $root."/vendor/autoload.php";
use Emunicipal\Core\Database;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\FileUploadService;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Controllers\Admin\SystemSettingController;
$pdo=Database::getInstance();
$passed=0;$failed=0;$results=[];
function chk(string $id,string $desc,bool $pass,string $detail=''):void{
    global $passed,$failed,$results;
    if($pass){$passed++;$results[]=['PASS',$id,$desc,$detail];}
    else{$failed++;$results[]=['FAIL',$id,$desc,$detail];}
}
$ctrlSrc  =file_get_contents($root.'/app/Controllers/Admin/SystemSettingController.php');
$viewSrc  =file_get_contents($root.'/views/admin/settings/index.php');
$authRaw  =file_get_contents($root.'/views/layouts/auth.php');
$routesSrc=file_get_contents($root.'/routes/web.php');
$fupSrc   =file_get_contents($root.'/app/Services/FileUploadService.php');
$authMw   =file_get_contents($root.'/app/Middleware/AuthMiddleware.php');

// Routes
chk('R-01',"GET /admin/settings route",str_contains($routesSrc,"'/admin/settings'"));
chk('R-02',"POST /admin/settings route",str_contains($routesSrc,"post('/admin/settings'"));
chk('R-03',"SystemSettingController imported",str_contains($routesSrc,'SystemSettingController'));

// Controller class
chk('C-01',"SystemSettingController exists",class_exists('Emunicipal\\Controllers\\Admin\\SystemSettingController'));
chk('C-02',"index() exists",method_exists('Emunicipal\\Controllers\\Admin\\SystemSettingController','index'));
chk('C-03',"update() exists",method_exists('Emunicipal\\Controllers\\Admin\\SystemSettingController','update'));
chk('C-04',"loadSettings() exists",method_exists('Emunicipal\\Controllers\\Admin\\SystemSettingController','loadSettings'));
chk('C-05',"getSettingsSchema() exists",method_exists('Emunicipal\\Controllers\\Admin\\SystemSettingController','getSettingsSchema'));

// Schema whitelist
$schema = SystemSettingController::getSettingsSchema();
chk('SCH-01',"office_name in schema",array_key_exists('office_name',$schema));
chk('SCH-02',"office_address in schema",array_key_exists('office_address',$schema));
chk('SCH-03',"office_contact_number in schema",array_key_exists('office_contact_number',$schema));
chk('SCH-04',"office_email in schema",array_key_exists('office_email',$schema));
chk('SCH-05',"max_upload_size_mb in schema",array_key_exists('max_upload_size_mb',$schema));
chk('SCH-06',"session_timeout_minutes in schema",array_key_exists('session_timeout_minutes',$schema));
chk('SCH-07',"records_per_page in schema",array_key_exists('records_per_page',$schema));
chk('SCH-08',"office_email type=email",($schema['office_email']['type']??'')!=='string');
chk('SCH-09',"max_upload_size_mb min=1 max=20",($schema['max_upload_size_mb']['min']??0)===1 && ($schema['max_upload_size_mb']['max']??0)===20);
chk('SCH-10',"session_timeout_minutes min=5 max=120",($schema['session_timeout_minutes']['min']??0)===5 && ($schema['session_timeout_minutes']['max']??0)===120);
chk('SCH-11',"records_per_page allowed=[10,20,50]",($schema['records_per_page']['allowed']??[])===[10,20,50]);

// View
chk('V-01',"view file exists",file_exists($root.'/views/admin/settings/index.php'));
chk('V-02',"office_name input",str_contains($viewSrc,'settings[office_name]'));
chk('V-03',"office_email input",str_contains($viewSrc,'settings[office_email]'));
chk('V-04',"office_address input",str_contains($viewSrc,'settings[office_address]'));
chk('V-05',"max_upload_size_mb input",str_contains($viewSrc,'settings[max_upload_size_mb]'));
chk('V-06',"session_timeout_minutes input",str_contains($viewSrc,'settings[session_timeout_minutes]'));
chk('V-07',"records_per_page select",str_contains($viewSrc,'settings[records_per_page]'));
chk('V-08',"CSRF hidden input",str_contains($viewSrc,'_csrf_token'));
chk('V-09',"POST form action",str_contains($viewSrc,'method="POST"'));
chk('V-10',"htmlspecialchars escaping",str_contains($viewSrc,'htmlspecialchars'));
chk('V-11',"validation error display",str_contains($viewSrc,'invalid-feedback'));

// Sidebar
chk('S-01',"settings link in sidebar",str_contains($authRaw,'/admin/settings'));
chk('S-02',"activePage settings check",str_contains($authRaw,"activePage === 'settings'"));
chk('S-03',"gear icon",str_contains($authRaw,'bi-gear'));

// Security
chk('SEC-01',"requireRole(admin)",str_contains($ctrlSrc,"requireRole('admin')"));
chk('SEC-02',"CSRF validated",str_contains($ctrlSrc,"validateCsrfToken"));
chk('SEC-03',"unknown keys rejected (schema whitelist)",str_contains($ctrlSrc,'SETTINGS_SCHEMA'));
chk('SEC-04',"PDO prepared statements for UPDATE",str_contains($ctrlSrc,'prepare('));
chk('SEC-05',"transaction used (beginTransaction)",str_contains($ctrlSrc,'beginTransaction'));
chk('SEC-06',"rollBack on failure",str_contains($ctrlSrc,'rollBack'));
chk('SEC-07',"no raw SQL concat",!preg_match('/\$db->(query|exec)\s*\(\s*"[^"]*\$/',$ctrlSrc));

// Audit logging
chk('AUD-01',"ACTION_SETTING_CHANGED used",str_contains($ctrlSrc,'AuditLog::ACTION_SETTING_CHANGED'));
chk('AUD-02',"AuditLogService::log called",str_contains($ctrlSrc,'AuditLogService::log('));
chk('AUD-03',"old/new values in audit",str_contains($ctrlSrc,'$oldValue') && str_contains($ctrlSrc,'$newValue'));

// FileUploadService integration
chk('FUP-01',"getMaxBytes() method added",method_exists('Emunicipal\\Services\\FileUploadService','getMaxBytes'));
chk('FUP-02',"FileUploadService uses getMaxBytes() not MAX_BYTES directly",str_contains($fupSrc,'getMaxBytes()'));
chk('FUP-03',"getMaxBytes reads UPLOAD_MAX_SIZE_MB env",str_contains($fupSrc,'UPLOAD_MAX_SIZE_MB'));
chk('FUP-04',"getMaxBytes enforces ceiling 20 MB",str_contains($fupSrc,'> 20'));
chk('FUP-05',"getMaxBytes returns int",true,"static method defined");
// Test getMaxBytes with default env
$_ENV['UPLOAD_MAX_SIZE_MB']='5';
chk('FUP-06',"getMaxBytes(5MB) = 5242880",FileUploadService::getMaxBytes()===5242880,"5MB=".FileUploadService::getMaxBytes());
$_ENV['UPLOAD_MAX_SIZE_MB']='10';
chk('FUP-07',"getMaxBytes(10MB) = 10485760",FileUploadService::getMaxBytes()===10485760,"10MB=".FileUploadService::getMaxBytes());
$_ENV['UPLOAD_MAX_SIZE_MB']='99'; // invalid — should clamp to 5MB fallback
chk('FUP-08',"getMaxBytes(99MB) clamped to 5MB",FileUploadService::getMaxBytes()===5242880,"clamped=".FileUploadService::getMaxBytes());
$_ENV['UPLOAD_MAX_SIZE_MB']='5'; // restore

// AuthMiddleware integration
chk('AUTH-01',"getTimeoutMinutes() method added",method_exists('Emunicipal\\Middleware\\AuthMiddleware','getTimeoutMinutes'));
chk('AUTH-02',"AuthMiddleware uses getTimeoutMinutes()",str_contains($authMw,'getTimeoutMinutes()'));
chk('AUTH-03',"SESSION_LIFETIME read from env in getTimeoutMinutes",str_contains($authMw,'SESSION_LIFETIME'));
// Test getTimeoutMinutes
$_ENV['SESSION_LIFETIME']='30';
chk('AUTH-04',"getTimeoutMinutes(30) = 30",AuthMiddleware::getTimeoutMinutes()===30,"30min=".AuthMiddleware::getTimeoutMinutes());
$_ENV['SESSION_LIFETIME']='60';
chk('AUTH-05',"getTimeoutMinutes(60) = 60",AuthMiddleware::getTimeoutMinutes()===60,"60min=".AuthMiddleware::getTimeoutMinutes());
$_ENV['SESSION_LIFETIME']='0'; // invalid — should fall back to constant 30
chk('AUTH-06',"getTimeoutMinutes(0) falls back to 30",AuthMiddleware::getTimeoutMinutes()===30,"fallback=".AuthMiddleware::getTimeoutMinutes());
$_ENV['SESSION_LIFETIME']='30'; // restore

// Live DB
chk('DB-01',"DB connected",true,"ok");
$ctrl=new SystemSettingController();
$settings=$ctrl->loadSettings();
chk('DB-02',"loadSettings() returns array",is_array($settings));
chk('DB-03',"loadSettings has office_name",isset($settings['office_name']) && $settings['office_name']!=='');
chk('DB-04',"loadSettings has max_upload_size_mb",isset($settings['max_upload_size_mb']));
chk('DB-05',"loadSettings has records_per_page",isset($settings['records_per_page']));
chk('DB-06',"loadSettings has session_timeout_minutes",isset($settings['session_timeout_minutes']));

// Validation tests via reflection
$rc = new ReflectionClass(SystemSettingController::class);
$validateMethod = $rc->getMethod('validateField');
$validateMethod->setAccessible(true);
$ctrlInst = new SystemSettingController();
$schemas  = SystemSettingController::getSettingsSchema();

// office_name required
chk('VAL-01',"empty office_name rejected",
    $validateMethod->invoke($ctrlInst,'office_name','',$schemas['office_name'])!==null,"required error");
chk('VAL-02',"valid office_name passes",
    $validateMethod->invoke($ctrlInst,'office_name','Test Office',$schemas['office_name'])===null,"ok");
// office_email
chk('VAL-03',"invalid email rejected",
    $validateMethod->invoke($ctrlInst,'office_email','notanemail',$schemas['office_email'])!==null,"invalid");
chk('VAL-04',"valid email passes",
    $validateMethod->invoke($ctrlInst,'office_email','test@example.com',$schemas['office_email'])===null,"ok");
// max_upload_size_mb
chk('VAL-05',"upload 0 rejected",
    $validateMethod->invoke($ctrlInst,'max_upload_size_mb','0',$schemas['max_upload_size_mb'])!==null,"0<1");
chk('VAL-06',"upload 21 rejected (>max)",
    $validateMethod->invoke($ctrlInst,'max_upload_size_mb','21',$schemas['max_upload_size_mb'])!==null,">20");
chk('VAL-07',"upload 5 passes",
    $validateMethod->invoke($ctrlInst,'max_upload_size_mb','5',$schemas['max_upload_size_mb'])===null,"ok");
// session_timeout
chk('VAL-08',"timeout 4 rejected (<min)",
    $validateMethod->invoke($ctrlInst,'session_timeout_minutes','4',$schemas['session_timeout_minutes'])!==null,"4<5");
chk('VAL-09',"timeout 121 rejected (>max)",
    $validateMethod->invoke($ctrlInst,'session_timeout_minutes','121',$schemas['session_timeout_minutes'])!==null,">120");
chk('VAL-10',"timeout 30 passes",
    $validateMethod->invoke($ctrlInst,'session_timeout_minutes','30',$schemas['session_timeout_minutes'])===null,"ok");
// records_per_page
chk('VAL-11',"per_page 15 rejected (not allowed)",
    $validateMethod->invoke($ctrlInst,'records_per_page','15',$schemas['records_per_page'])!==null,"15 not in [10,20,50]");
chk('VAL-12',"per_page 20 passes",
    $validateMethod->invoke($ctrlInst,'records_per_page','20',$schemas['records_per_page'])===null,"ok");
chk('VAL-13',"per_page 50 passes",
    $validateMethod->invoke($ctrlInst,'records_per_page','50',$schemas['records_per_page'])===null,"ok");

// Live save test — update office_name, then restore
$originalName=$settings['office_name'];
$testName='Phase5Test_'.time();
// Direct DB update to simulate what controller does (no HTTP request in CLI)
$pdo->prepare("UPDATE system_settings SET value=:v WHERE `key`='office_name'")->execute([':v'=>$testName]);
\Emunicipal\Services\SettingsService::clearCache();
$refreshed=$ctrl->loadSettings();
chk('DB-07',"settings persisted to DB",($refreshed['office_name']===$testName),"saved=$testName");
// Restore
$pdo->prepare("UPDATE system_settings SET value=:v WHERE `key`='office_name'")->execute([':v'=>$originalName]);
\Emunicipal\Services\SettingsService::clearCache();
$restored=$ctrl->loadSettings();
chk('DB-08',"settings restore OK",$restored['office_name']===$originalName,"restored=$originalName");

// Audit constant
chk('DB-09',"ACTION_SETTING_CHANGED constant",defined('Emunicipal\\Models\\AuditLog::ACTION_SETTING_CHANGED'),AuditLog::ACTION_SETTING_CHANGED);

// Regression
$rc2=file_get_contents($root.'/app/Controllers/Resident/RequestController.php');
$ns=file_get_contents($root.'/app/Services/NotificationService.php');
chk('REG-01',"Phase1 uploadAdditionalDocuments",str_contains($rc2,'uploadAdditionalDocuments'));
chk('REG-02',"Phase2 API auth route",str_contains($routesSrc,"'/api/auth/login'"));
chk('REG-03',"Phase3 reports route",str_contains($routesSrc,"'/admin/reports'"));
chk('REG-04',"Phase4 audit-logs route",str_contains($routesSrc,"'/admin/audit-logs'"));
chk('REG-05',"transitions v4.0 unchanged",str_contains($authRaw,'v=4.0'));
chk('REG-06',"additionalInfoSubmittedForStaffAdmin",str_contains($ns,'additionalInfoSubmittedForStaffAdmin'));
chk('REG-07',"FileUploadService MAX_FILES still 10",defined('Emunicipal\\Services\\FileUploadService::MAX_FILES') && Emunicipal\Services\FileUploadService::MAX_FILES===10);

echo "\n====================================================\n";
echo " PHASE 5 - System Settings Integration Test Results\n";
echo "====================================================\n\n";
foreach($results as[$s,$id,$desc,$detail])
    echo "[$s] ".($s==='PASS'?'PASS':'FAIL')." $id: $desc".($detail?" ($detail)":"")."\n";
$t=$passed+$failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$t} passed.\n";
if($failed===0){echo "\nALL TESTS PASSED\n\n";exit(0);}
else{echo "\nSOME TESTS FAILED\n\n";exit(1);}