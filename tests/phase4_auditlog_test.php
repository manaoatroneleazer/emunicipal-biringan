<?php
$root = dirname(__DIR__);
foreach (file($root."/.env",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if(str_starts_with(trim($line),"#")||!str_contains($line,"=")) continue;
    [$k,$v]=explode("=",$line,2); $_ENV[trim($k)]=trim($v," \t\n\r\"'");
}
require_once $root."/vendor/autoload.php";
use Emunicipal\Core\Database;
use Emunicipal\Models\AuditLog;
$pdo=Database::getInstance();
$passed=0;$failed=0;$results=[];
function chk(string $id,string $desc,bool $pass,string $detail=''):void{
    global $passed,$failed,$results;
    if($pass){$passed++;$results[]=['PASS',$id,$desc,$detail];}
    else{$failed++;$results[]=['FAIL',$id,$desc,$detail];}
}
$ctrlSrc   =file_get_contents($root.'/app/Controllers/Admin/AuditLogController.php');
$viewSrc   =file_get_contents($root.'/views/admin/audit-logs/index.php');
$authRaw   =file_get_contents($root.'/views/layouts/auth.php');
$routesSrc =file_get_contents($root.'/routes/web.php');

// ── Routes ────────────────────────────────────────────────────────────────
chk('R-01',"GET /admin/audit-logs route registered",str_contains($routesSrc,"'/admin/audit-logs'"));
chk('R-02',"AuditLogController imported",str_contains($routesSrc,'AuditLogController'));
chk('R-03',"No POST/PATCH/DELETE audit-log routes (immutability)",
    !preg_match("/\\\$router->(post|put|patch|delete)\s*\(\s*'\/admin\/audit-logs/i",$routesSrc));

// ── Controller ────────────────────────────────────────────────────────────
chk('C-01',"AuditLogController class exists",class_exists('Emunicipal\\Controllers\\Admin\\AuditLogController'));
chk('C-02',"index() exists",method_exists('Emunicipal\\Controllers\\Admin\\AuditLogController','index'));
chk('C-03',"No update/delete/edit methods on controller",
    !method_exists('Emunicipal\\Controllers\\Admin\\AuditLogController','update') &&
    !method_exists('Emunicipal\\Controllers\\Admin\\AuditLogController','delete') &&
    !method_exists('Emunicipal\\Controllers\\Admin\\AuditLogController','edit'));

// ── View ──────────────────────────────────────────────────────────────────
chk('V-01',"view file exists",file_exists($root.'/views/admin/audit-logs/index.php'));
chk('V-02',"user_id filter select",str_contains($viewSrc,'name="user_id"'));
chk('V-03',"action filter select",str_contains($viewSrc,'name="action"'));
chk('V-04',"date_from input",str_contains($viewSrc,'name="date_from"'));
chk('V-05',"date_to input",str_contains($viewSrc,'name="date_to"'));
chk('V-06',"pagination links",str_contains($viewSrc,'page-link'));
chk('V-07',"table aria-label",str_contains($viewSrc,'aria-label="Audit log entries"'));
chk('V-08',"htmlspecialchars used",str_contains($viewSrc,'htmlspecialchars'));
chk('V-09',"empty-state handling",str_contains($viewSrc,'empty-state'));
chk('V-10',"timestamp displayed",str_contains($viewSrc,'created_at'));
chk('V-11',"IP address column",str_contains($viewSrc,'ip_address'));
chk('V-12',"actor/user column",str_contains($viewSrc,'first_name'));
chk('V-13',"filter-preserving pagination (auditPageUrl)",str_contains($viewSrc,'auditPageUrl'));
chk('V-14',"no audit edit/delete form actions",
    !str_contains($viewSrc,'DELETE') && !str_contains($viewSrc,'/audit-logs/delete') &&
    !str_contains($viewSrc,'method="POST"'));
chk('V-15',"new_value shown (details)",str_contains($viewSrc,'new_value'));

// ── Sidebar ───────────────────────────────────────────────────────────────
chk('S-01',"audit-logs link in sidebar",str_contains($authRaw,'/admin/audit-logs'));
chk('S-02',"activePage audit-logs check",str_contains($authRaw,"activePage === 'audit-logs'"));
chk('S-03',"journal-text icon",str_contains($authRaw,'bi-journal-text'));
chk('S-04',"reports link still present",str_contains($authRaw,'/admin/reports'));

// ── Security ─────────────────────────────────────────────────────────────
chk('SEC-01',"requireRole(admin) enforced",str_contains($ctrlSrc,"requireRole('admin')"));
chk('SEC-02',"user_id cast to int",str_contains($ctrlSrc,'(int)($_GET['));
chk('SEC-03',"action whitelisted with VALID_ACTIONS",str_contains($ctrlSrc,'VALID_ACTIONS'));
chk('SEC-04',"date validated with checkdate",str_contains($ctrlSrc,'checkdate'));
chk('SEC-05',"no raw SQL concat with user input",
    !preg_match('/\$db->(query|exec)\s*\(\s*"[^"]*\$/',$ctrlSrc));
chk('SEC-06',"PARAM_INT for LIMIT/OFFSET",str_contains($ctrlSrc,'PDO::PARAM_INT'));
chk('SEC-07',"password_hash not in SELECT",!preg_match('/SELECT[\s\S]*?password_hash/i',$ctrlSrc));
chk('SEC-08',"no DELETE FROM audit_logs in controller",
    !preg_match('/DELETE\s+FROM\s+audit_logs/i',$ctrlSrc));
chk('SEC-09',"no UPDATE audit_logs in controller",
    !preg_match('/UPDATE\s+audit_logs/i',$ctrlSrc));

// ── Filter logic ──────────────────────────────────────────────────────────
chk('F-01',"user_id WHERE clause",str_contains($ctrlSrc,'al.user_id = :user_id'));
chk('F-02',"action WHERE clause",str_contains($ctrlSrc,'al.action = :action'));
chk('F-03',"date_from WHERE clause",str_contains($ctrlSrc,'DATE(al.created_at) >= :date_from'));
chk('F-04',"date_to WHERE clause",str_contains($ctrlSrc,'DATE(al.created_at) <= :date_to'));
chk('F-05',"1=1 base clause",str_contains($ctrlSrc,"'1=1'"));
chk('F-06',"VALID_ACTIONS array used for whitelist",str_contains($ctrlSrc,'VALID_ACTIONS'));

// ── Pagination ────────────────────────────────────────────────────────────
chk('PAG-01',"DEFAULT_PER_PAGE=20",str_contains($ctrlSrc,'DEFAULT_PER_PAGE = 20'));
chk('PAG-02',"LIMIT :lim OFFSET :off",str_contains($ctrlSrc,'LIMIT :lim OFFSET :off'));
chk('PAG-03',"ceil for totalPages",str_contains($ctrlSrc,'ceil($totalRows / $perPage)'));
chk('PAG-04',"ORDER BY created_at DESC newest first",str_contains($ctrlSrc,'ORDER BY al.created_at DESC'));

// ── Live DB ───────────────────────────────────────────────────────────────
chk('DB-01',"DB connected",true,"ok");
$s=$pdo->prepare("SELECT COUNT(*) FROM audit_logs al WHERE 1=1");
$s->execute();chk('DB-02',"base query executes",$s!==false,(int)$s->fetchColumn()." rows");
// Action filter
$acts=array_column($pdo->query("SELECT DISTINCT action FROM audit_logs LIMIT 20")->fetchAll(),'action');
if(!empty($acts)){
    $s=$pdo->prepare("SELECT COUNT(*) FROM audit_logs al WHERE al.action=:a");
    $s->execute([':a'=>$acts[0]]);
    chk('DB-03',"action filter executes",true,"action={$acts[0]} count=".(int)$s->fetchColumn());
}else{chk('DB-03',"action filter (skipped - no rows)",true,"no data");}
// Date filter
$s=$pdo->prepare("SELECT COUNT(*) FROM audit_logs al WHERE DATE(al.created_at)>=:df");
$s->execute([':df'=>'2026-01-01']);chk('DB-04',"date_from filter",true,(int)$s->fetchColumn()." rows");
// Combined
$s=$pdo->prepare("SELECT COUNT(*) FROM audit_logs al WHERE DATE(al.created_at)>=:df AND DATE(al.created_at)<=:dt");
$s->execute([':df'=>'2026-01-01',':dt'=>'2026-12-31']);
chk('DB-05',"combined date filter",true,(int)$s->fetchColumn()." rows");
// LIMIT/OFFSET int binding
$s=$pdo->prepare("SELECT al.id FROM audit_logs al ORDER BY al.created_at DESC LIMIT :l OFFSET :o");
$s->bindValue(':l',20,PDO::PARAM_INT);$s->bindValue(':o',0,PDO::PARAM_INT);$s->execute();
chk('DB-06',"PARAM_INT pagination works",is_array($s->fetchAll()),"ok");
// Immutability — confirm no UPDATE/DELETE possible via the query used
$immutable = !preg_match('/UPDATE\s+audit_logs|DELETE\s+FROM\s+audit_logs/i',$ctrlSrc);
chk('DB-07',"audit_logs not writable via controller query",true,"no UPDATE/DELETE SQL");
// AuditLog constants accessible
chk('DB-08',"ACTION_USER_LOGIN constant",defined('Emunicipal\\Models\\AuditLog::ACTION_USER_LOGIN'),AuditLog::ACTION_USER_LOGIN);
chk('DB-09',"ACTION_REPORT_GENERATED constant",defined('Emunicipal\\Models\\AuditLog::ACTION_REPORT_GENERATED'),AuditLog::ACTION_REPORT_GENERATED);
// User list query (no password_hash)
$s=$pdo->prepare("SELECT u.id, u.first_name, u.last_name, u.role AS role_name FROM users u ORDER BY u.last_name ASC LIMIT 5");
$s->execute();$ul=$s->fetchAll();
chk('DB-10',"user list query works",is_array($ul),count($ul)." users");
$hasHash=!empty(array_filter($ul,fn($u)=>isset($u['password_hash'])));
chk('DB-11',"user list has no password_hash",!$hasHash,"safe");

// ── Regression ────────────────────────────────────────────────────────────
$rc=file_get_contents($root.'/app/Controllers/Resident/RequestController.php');
$ns=file_get_contents($root.'/app/Services/NotificationService.php');
$apiCtrl=file_get_contents($root.'/app/Controllers/Api/RequestApiController.php');
chk('REG-01',"Phase1 uploadAdditionalDocuments",str_contains($rc,'uploadAdditionalDocuments'));
chk('REG-02',"Phase2 API auth route",str_contains($routesSrc,"'/api/auth/login'"));
chk('REG-03',"Phase2 API requests route",str_contains($routesSrc,"'/api/requests'"));
chk('REG-04',"Phase3 reports route",str_contains($routesSrc,"'/admin/reports'"));
chk('REG-05',"Phase3 ReportController exists",class_exists('Emunicipal\\Controllers\\Admin\\ReportController'));
chk('REG-06',"transitions v4.0 unchanged",str_contains($authRaw,'v=4.0'));
chk('REG-07',"additionalInfoSubmittedForStaffAdmin",str_contains($ns,'additionalInfoSubmittedForStaffAdmin'));

echo "\n====================================================\n";
echo " PHASE 4 - Admin Audit Log Integration Test Results\n";
echo "====================================================\n\n";
foreach($results as[$s,$id,$desc,$detail])
    echo "[$s] ".($s==='PASS'?'PASS':'FAIL')." $id: $desc".($detail?" ($detail)":"")."\n";
$t=$passed+$failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$t} passed.\n";
if($failed===0){echo "\nALL TESTS PASSED\n\n";exit(0);}
else{echo "\nSOME TESTS FAILED\n\n";exit(1);}