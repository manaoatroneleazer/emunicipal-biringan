<?php
/**
 * Phase 7 — Security Headers & Hardening Tests
 */
$root = dirname(__DIR__);
foreach (file($root."/.env",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if(str_starts_with(trim($line),"#")||!str_contains($line,"=")) continue;
    [$k,$v]=explode("=",$line,2); $_ENV[trim($k)]=trim($v," \t\n\r\"'");
}
require_once $root."/vendor/autoload.php";
use Emunicipal\Core\Database;

$pdo    = Database::getInstance();
$passed = 0; $failed = 0; $results = [];
function chk(string $id,string $desc,bool $pass,string $detail=''):void{
    global $passed,$failed,$results;
    if($pass){$passed++;$results[]=['PASS',$id,$desc,$detail];}
    else{$failed++;$results[]=['FAIL',$id,$desc,$detail];}
}

$htaccess  = file_get_contents($root.'/public/.htaccess');
$appSrc    = file_get_contents($root.'/app/Core/Application.php');
$authSrc   = file_get_contents($root.'/app/Middleware/AuthMiddleware.php');
$sessSrc   = file_get_contents($root.'/app/Helpers/SessionHelper.php');
$apiBase   = file_get_contents($root.'/app/Controllers/Api/BaseApiController.php');
$filectrl  = file_get_contents($root.'/app/Controllers/FileController.php');
$routesSrc = file_get_contents($root.'/routes/web.php');
$authRaw   = file_get_contents($root.'/views/layouts/auth.php');

// ── SEC-01: X-Content-Type-Options: nosniff ──────────────────────────────
chk('SEC-01',"X-Content-Type-Options: nosniff in .htaccess",
    str_contains($htaccess,'X-Content-Type-Options') &&
    str_contains($htaccess,'"nosniff"'),
    'present');

// ── SEC-02: X-Frame-Options: DENY (NOT SAMEORIGIN) ───────────────────────
chk('SEC-02a',"X-Frame-Options: DENY in .htaccess",
    preg_match('/Header always set X-Frame-Options\s+"DENY"/',$htaccess)===1,
    'DENY set');
chk('SEC-02b',"X-Frame-Options NOT SAMEORIGIN (old value removed)",
    !str_contains($htaccess,'SAMEORIGIN'),
    'SAMEORIGIN gone');

// ── SEC-03: CSP header exists ────────────────────────────────────────────
chk('SEC-03',"Content-Security-Policy header present in .htaccess",
    str_contains($htaccess,'Content-Security-Policy'),
    'CSP present');

// ── SEC-04: CSP does not use broad wildcards ─────────────────────────────
$cspLine = '';
foreach(explode("\n",$htaccess) as $line)
    if(str_contains($line,'Content-Security-Policy')) $cspLine .= $line;
chk('SEC-04a',"CSP does not allow script-src *",
    !str_contains($cspLine,"script-src *") && !str_contains($cspLine,"script-src '*'"));
chk('SEC-04b',"CSP has frame-ancestors 'none'",
    str_contains($cspLine,"frame-ancestors 'none'"),
    "frame-ancestors=none");
chk('SEC-04c',"CSP has form-action 'self'",
    str_contains($cspLine,"form-action 'self'"),
    "form-action=self");
chk('SEC-04d',"CSP has base-uri 'self'",
    str_contains($cspLine,"base-uri 'self'"),
    "base-uri=self");
chk('SEC-04e',"CSP 'unsafe-inline' in script-src (needed for logout inline script)",
    str_contains($cspLine,"'unsafe-inline'"),
    "required for auth.php logout script");
chk('SEC-04f',"CSP allows Bootstrap CDN",
    str_contains($cspLine,'cdn.jsdelivr.net'),
    "cdn.jsdelivr.net in CSP");
chk('SEC-04g',"CSP allows Google Fonts",
    str_contains($cspLine,'fonts.googleapis.com') && str_contains($cspLine,'fonts.gstatic.com'),
    "Google Fonts allowed");

// ── SEC-05: X-XSS-Protection removed (deprecated) ───────────────────────
chk('SEC-05',"Deprecated X-XSS-Protection header removed from .htaccess",
    !preg_match('/^\s*Header always set X-XSS-Protection/m',$htaccess),
    'directive removed (comment is OK)');

// ── SEC-06: Security headers present in Application exception handler ────
chk('SEC-06a',"Application exception handler sets X-Content-Type-Options",
    str_contains($appSrc,"X-Content-Type-Options: nosniff"));
chk('SEC-06b',"Application exception handler sets X-Frame-Options: DENY",
    str_contains($appSrc,"X-Frame-Options: DENY"));
chk('SEC-06c',"headers_sent() check before setting headers (safe)",
    str_contains($appSrc,'headers_sent()'));

// ── SEC-07: Session HttpOnly ─────────────────────────────────────────────
chk('SEC-07',"Session cookie HttpOnly=true",
    str_contains($sessSrc,"'httponly' => true"));

// ── SEC-08: Session SameSite configured ─────────────────────────────────
chk('SEC-08',"Session SameSite=Lax configured",
    str_contains($sessSrc,"'samesite' => 'Lax'"));

// ── SEC-09: Secure cookie only on HTTPS (not forced on HTTP) ────────────
chk('SEC-09',"Secure cookie only on HTTPS (conditional, preserves XAMPP HTTP dev)",
    str_contains($sessSrc,'$isHttps') && preg_match("/['\"]secure['\"]/", $sessSrc) && str_contains($sessSrc,'$isHttps'));

// ── SEC-10: .env not in public/ directory ───────────────────────────────
chk('SEC-10a',".env NOT in public/ (not web-accessible by filesystem)",
    !file_exists($root.'/public/.env'),
    'not in public/');
chk('SEC-10b',".env exists at project root (outside public/)",
    file_exists($root.'/.env'),
    'at root');
chk('SEC-10c',".env listed in .gitignore",
    str_contains(file_get_contents($root.'/.gitignore'),'.env'),
    'in .gitignore');
// storage/ is also denied via .htaccess
chk('SEC-10d',"storage/ access denied in .htaccess",
    str_contains($htaccess,'RewriteRule ^storage/') && str_contains($htaccess,'[F,L]'),
    'storage blocked');

// ── SEC-11: Sensitive credentials not exposed in normal responses ────────
// Check that no controller or view directly echoes password_hash
$controllerFiles = glob($root.'/app/Controllers/**/*.php');
$credLeak = false;
foreach($controllerFiles as $cf){
    $csrc = file_get_contents($cf);
    // Allow "safeUser" (which strips it) and comments; flag actual echo/print of hash
    if(preg_match('/echo.*password_hash|print.*password_hash/i',$csrc)){
        $credLeak = true; break;
    }
}
chk('SEC-11a',"No controller echoes password_hash directly",!$credLeak,"no leaks found");
// Check .env credentials not hardcoded in public files
$pubIndexSrc = file_get_contents($root.'/public/index.php');
chk('SEC-11b',".env credentials not hardcoded in public/index.php",
    !str_contains($pubIndexSrc,'DB_PASSWORD') && !str_contains($pubIndexSrc,'MAIL_PASSWORD'),
    'clean');
// APP_DEBUG mode: production should hide error details
$debugMode = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
chk('SEC-11c',"APP_DEBUG respects configureErrorHandling() (display_errors controlled)",
    str_contains($appSrc,'display_errors') && str_contains($appSrc,'APP_DEBUG'),
    "APP_DEBUG=$".($debugMode?'true':'false'));

// ── SEC-12: CSRF protection intact ──────────────────────────────────────
chk('SEC-12',"CSRF token validation still in AuthController",
    str_contains(file_get_contents($root.'/app/Controllers/AuthController.php'),'validateCsrfToken'));

// ── SEC-13: Account lockout intact ──────────────────────────────────────
chk('SEC-13',"Account lockout still in AuthController (>= 5 attempts)",
    str_contains(file_get_contents($root.'/app/Controllers/AuthController.php'),'$attempts >= 5'));

// ── SEC-14: API authentication intact ───────────────────────────────────
chk('SEC-14',"API requireAuth() method intact",
    method_exists('Emunicipal\\Controllers\\Api\\BaseApiController','requireAuth'));

// ── SEC-15: File upload security intact ─────────────────────────────────
chk('SEC-15a',"FileUploadService MIME validation via finfo intact",
    str_contains(file_get_contents($root.'/app/Services/FileUploadService.php'),'finfo'));
chk('SEC-15b',"FileUploadService stores files outside web root (UPLOAD_PATH)",
    str_contains(file_get_contents($root.'/app/Services/FileUploadService.php'),'$storageRoot'));

// ── SEC-16: RBAC intact ──────────────────────────────────────────────────
chk('SEC-16',"requireRole() still in AuthMiddleware",
    method_exists('Emunicipal\\Middleware\\AuthMiddleware','requireRole'));

// ── SEC-17: PHP syntax on modified files ─────────────────────────────────
$phpExe = "c:\\xampp\\php\\php.exe";
$synFiles = [
    'app/Core/Application.php',
    'app/Helpers/SessionHelper.php',
    'app/Middleware/AuthMiddleware.php',
    'app/Controllers/Api/BaseApiController.php',
];
foreach($synFiles as $sf){
    exec("$phpExe -l {$root}/{$sf} 2>&1",$out,$code);
    chk("SYN-".basename($sf),"Syntax: $sf",$code===0,$code===0?'OK':implode(' ',$out));
}

// ── Live DB/runtime checks ────────────────────────────────────────────────
chk('LIVE-01',"DB connection available",$pdo!==null,"ok");
$tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
chk('LIVE-02',"All expected tables present",
    in_array('users',$tables)&&in_array('audit_logs',$tables)&&in_array('system_settings',$tables),
    count($tables)." tables");

// Phase 5 SettingsService still loads correctly
\Emunicipal\Services\SettingsService::clearCache();
\Emunicipal\Services\SettingsService::applyRuntimeSettings();
chk('LIVE-03',"SettingsService::applyRuntimeSettings() still works after Phase 7 changes",
    is_string(\Emunicipal\Services\SettingsService::get('office_name')),
    \Emunicipal\Services\SettingsService::get('office_name'));

// ── Phase 1-6 Regression ────────────────────────────────────────────────
$rc=file_get_contents($root.'/app/Controllers/Resident/RequestController.php');
$ns=file_get_contents($root.'/app/Services/NotificationService.php');
chk('REG-01',"Phase1 uploadAdditionalDocuments",str_contains($rc,'uploadAdditionalDocuments'));
chk('REG-02',"Phase2 API routes",str_contains($routesSrc,"'/api/auth/login'"));
chk('REG-03',"Phase3 reports route",str_contains($routesSrc,"'/admin/reports'"));
chk('REG-04',"Phase4 audit-logs route",str_contains($routesSrc,"'/admin/audit-logs'"));
chk('REG-05',"Phase5 settings routes",str_contains($routesSrc,"'/admin/settings'"));
chk('REG-06',"Phase5 SettingsService exists",class_exists('Emunicipal\\Services\\SettingsService'));
chk('REG-07',"Phase6 lockout window-reset",str_contains(file_get_contents($root.'/app/Controllers/AuthController.php'),'15-minute window reset'));
chk('REG-08',"transitions v4.0 unchanged",str_contains($authRaw,'v=4.0'));
chk('REG-09',"Phase1 TYPE_ADDITIONAL_INFO_SUBMITTED",defined('Emunicipal\\Models\\Notification::TYPE_ADDITIONAL_INFO_SUBMITTED'));

echo "\n====================================================\n";
echo " PHASE 7 - Security Headers & Hardening Test Results\n";
echo "====================================================\n\n";
foreach($results as[$s,$id,$desc,$detail])
    echo "[$s] ".($s==='PASS'?'PASS':'FAIL')." $id: $desc".($detail?" ($detail)":"")."\n";
$t=$passed+$failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$t} passed.\n";
if($failed===0){echo "\nALL TESTS PASSED\n\n";exit(0);}
else{echo "\nSOME TESTS FAILED\n\n";exit(1);}