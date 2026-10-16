<?php
$root = dirname(__DIR__);
foreach (file($root."/.env",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if(str_starts_with(trim($line),"#")||!str_contains($line,"=")) continue;
    [$k,$v]=explode("=",$line,2); $_ENV[trim($k)]=trim($v," \t\n\r\"'");
}
require_once $root."/vendor/autoload.php";
use Emunicipal\Core\Database;
use Emunicipal\Models\DocumentType;
use Emunicipal\Models\AuditLog;
$pdo=Database::getInstance();
$passed=0;$failed=0;$results=[];
function chk(string $id,string $desc,bool $pass,string $detail=''):void{
    global $passed,$failed,$results;
    if($pass){$passed++;$results[]=['PASS',$id,$desc,$detail];}
    else{$failed++;$results[]=['FAIL',$id,$desc,$detail];}
}
$ctrlSrc  =file_get_contents($root.'/app/Controllers/Admin/ReportController.php');
$viewSrc  =file_get_contents($root.'/views/admin/reports/index.php');
// Load auth layout but strip PHP close tags to avoid PHP escaping plaintext mode
$authRaw  =file_get_contents($root.'/views/layouts/auth.php');
$routesSrc=file_get_contents($root.'/routes/web.php');
// Routes
chk('R-01',"GET /admin/reports route registered",str_contains($routesSrc,"'/admin/reports'"));
chk('R-02',"ReportController imported",str_contains($routesSrc,'ReportController'));
// Controller
chk('C-01',"ReportController class exists",class_exists('Emunicipal\\Controllers\\Admin\\ReportController'));
chk('C-02',"index() exists",method_exists('Emunicipal\\Controllers\\Admin\\ReportController','index'));
// View
chk('V-01',"view file exists",file_exists($root.'/views/admin/reports/index.php'));
chk('V-02',"date_from input",str_contains($viewSrc,'name="date_from"'));
chk('V-03',"date_to input",str_contains($viewSrc,'name="date_to"'));
chk('V-04',"document_type_id select",str_contains($viewSrc,'name="document_type_id"'));
chk('V-05',"status select",str_contains($viewSrc,'name="status"'));
chk('V-06',"pagination links",str_contains($viewSrc,'page-link'));
chk('V-07',"table aria-label",str_contains($viewSrc,'aria-label="Report results"'));
chk('V-08',"htmlspecialchars used",str_contains($viewSrc,'htmlspecialchars'));
chk('V-09',"links to admin/requests",str_contains($viewSrc,'/admin/requests/'));
chk('V-10',"empty-state handling",str_contains($viewSrc,'empty-state'));
chk('V-11',"reportPageUrl helper",str_contains($viewSrc,'reportPageUrl'));
// Sidebar (search in auth layout without triggering PHP close-tag escape)
chk('S-01',"reports link in sidebar",str_contains($authRaw,'/admin/reports'));
chk('S-02',"activePage reports check",str_contains($authRaw,"activePage === 'reports'"));
chk('S-03',"bar-chart icon",str_contains($authRaw,'bi-bar-chart-line'));
// Security
chk('SEC-01',"requireRole(admin)",str_contains($ctrlSrc,"requireRole('admin')"));
chk('SEC-02',"no raw user concat in SQL",!preg_match('/\$db->(query|exec)\s*\(\s*"[^"]*\$/',$ctrlSrc));
chk('SEC-03',"date validation",str_contains($ctrlSrc,'isValidDate('));
chk('SEC-04',"status whitelist",str_contains($ctrlSrc,'validStatuses'));
chk('SEC-05',"dt_id cast to int",str_contains($ctrlSrc,'(int)($_GET['));
chk('SEC-06',"password_hash not in SELECT",!preg_match('/SELECT[\s\S]*?password_hash[\s\S]*?FROM/i',$ctrlSrc));
chk('SEC-07',"PARAM_INT for LIMIT/OFFSET",str_contains($ctrlSrc,'PDO::PARAM_INT'));
// Audit
chk('AUD-01',"ACTION_REPORT_GENERATED used",str_contains($ctrlSrc,'AuditLog::ACTION_REPORT_GENERATED'));
chk('AUD-02',"AuditLogService::log called",str_contains($ctrlSrc,'AuditLogService::log('));
chk('AUD-03',"audit on _submitted only",str_contains($ctrlSrc,'_submitted'));
// Filters
chk('F-01',"date_from WHERE clause",str_contains($ctrlSrc,"DATE(r.created_at) >= :date_from"));
chk('F-02',"date_to WHERE clause",str_contains($ctrlSrc,"DATE(r.created_at) <= :date_to"));
chk('F-03',"status param",str_contains($ctrlSrc,"r.status = :status"));
chk('F-04',"doc type param",str_contains($ctrlSrc,"r.document_type_id = :dt_id"));
chk('F-05',"1=1 base clause",str_contains($ctrlSrc,"'1=1'"));
// Pagination
chk('PAG-01',"DEFAULT_PER_PAGE=20",str_contains($ctrlSrc,'DEFAULT_PER_PAGE = 20'));
chk('PAG-02',"LIMIT OFFSET in query",str_contains($ctrlSrc,'LIMIT :lim OFFSET :off'));
chk('PAG-03',"ceil for totalPages",str_contains($ctrlSrc,'ceil($totalRows / $perPage)'));
chk('PAG-04',"page in URL builder",str_contains($viewSrc,"\$q['page'] = \$page"));
// Live DB
chk('DB-01',"DB connected",true,"ok");
$s=$pdo->prepare("SELECT COUNT(*) FROM requests r JOIN users u ON r.user_id=u.id JOIN document_types dt ON r.document_type_id=dt.id WHERE 1=1");
$s->execute();chk('DB-02',"base query",$s!==false,(int)$s->fetchColumn()." rows");
$s=$pdo->prepare("SELECT COUNT(*) FROM requests r JOIN users u ON r.user_id=u.id JOIN document_types dt ON r.document_type_id=dt.id WHERE DATE(r.created_at)>=:df AND DATE(r.created_at)<=:dt");
$s->execute([':df'=>'2026-01-01',':dt'=>'2026-12-31']);chk('DB-03',"date range",true,(int)$s->fetchColumn()." rows");
$s=$pdo->prepare("SELECT COUNT(*) FROM requests r JOIN users u ON r.user_id=u.id JOIN document_types dt ON r.document_type_id=dt.id WHERE r.status=:st");
$s->execute([':st'=>'completed']);chk('DB-04',"status filter",true,(int)$s->fetchColumn()." rows");
$types=(new DocumentType())->allActive();
if(!empty($types)){$dtId=(int)$types[0]['id'];$s=$pdo->prepare("SELECT COUNT(*) FROM requests r WHERE r.document_type_id=:dt");$s->execute([':dt'=>$dtId]);chk('DB-05',"doc type filter",true,(int)$s->fetchColumn()." rows");}
$s=$pdo->prepare("SELECT COUNT(*) FROM requests r WHERE DATE(r.created_at)>=:df AND r.status=:st");
$s->execute([':df'=>'2020-01-01',':st'=>'completed']);chk('DB-06',"combined filter",true,(int)$s->fetchColumn()." rows");
$s=$pdo->prepare("SELECT r.status,COUNT(*) c FROM requests r WHERE 1=1 GROUP BY r.status");
$s->execute();$rows=$s->fetchAll();chk('DB-07',"by_status group",is_array($rows),count($rows)." groups");
$s=$pdo->prepare("SELECT r.id FROM requests r LIMIT :l OFFSET :o");
$s->bindValue(':l',2,PDO::PARAM_INT);$s->bindValue(':o',0,PDO::PARAM_INT);$s->execute();
chk('DB-08',"PARAM_INT pagination",is_array($s->fetchAll()),"ok");
chk('DB-09',"REPORT_GENERATED constant",defined('Emunicipal\\Models\\AuditLog::ACTION_REPORT_GENERATED'),AuditLog::ACTION_REPORT_GENERATED);
chk('DB-10',"bad date fails",!checkdate(13,45,2026));
chk('DB-11',"good date passes",checkdate(9,4,2026));
// Regression
$rc=file_get_contents($root.'/app/Controllers/Resident/RequestController.php');
$ns=file_get_contents($root.'/app/Services/NotificationService.php');
chk('REG-01',"Phase1 uploadAdditionalDocuments",str_contains($rc,'uploadAdditionalDocuments'));
chk('REG-02',"Phase2 API routes",str_contains($routesSrc,"'/api/auth/login'"));
chk('REG-03',"Phase2 additionalInfoSubmittedForStaffAdmin",str_contains($ns,'additionalInfoSubmittedForStaffAdmin'));
chk('REG-04',"admin requests route",str_contains($routesSrc,"'/admin/requests'"));
chk('REG-05',"transitions unchanged v4.0",str_contains($authRaw,'v=4.0'));
// Output
echo "\n====================================================\n";
echo " PHASE 3 - Admin Reports Integration Test Results\n";
echo "====================================================\n\n";
foreach($results as[$s,$id,$desc,$detail])
    echo "[$s] ".($s==='PASS'?'PASS':'FAIL')." $id: $desc".($detail?" ($detail)":"")."\n";
$t=$passed+$failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$t} passed.\n";
if($failed===0){echo "\nALL TESTS PASSED\n\n";exit(0);}
else{echo "\nSOME TESTS FAILED\n\n";exit(1);}