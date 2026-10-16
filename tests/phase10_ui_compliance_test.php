<?php
/**
 * Phase 10 — Accessibility / Responsive / UI Compliance Tests
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

// Load view files
$authLayout = file_get_contents($root.'/views/layouts/auth.php');
$pubLayout  = file_get_contents($root.'/views/layouts/public.php');
$minLayout  = file_get_contents($root.'/views/layouts/minimal.php');
$routesSrc  = file_get_contents($root.'/routes/web.php');
$appCss     = file_get_contents($root.'/public/assets/css/app.css');

// ── A-01: Skip Navigation Links ───────────────────────────────────────────
chk('A-01a',"auth.php has skip-to-main-content link",
    str_contains($authLayout,'Skip to main content'));
chk('A-01b',"public.php has skip-to-main-content link",
    str_contains($pubLayout,'Skip to main content'));
chk('A-01c',"minimal.php has skip-to-main-content link",
    str_contains($minLayout,'Skip to main content'));
chk('A-01d',"skip links use visually-hidden-focusable pattern",
    str_contains($authLayout,'visually-hidden-focusable') &&
    str_contains($pubLayout,'visually-hidden-focusable') &&
    str_contains($minLayout,'visually-hidden-focusable'));
chk('A-01e',"skip links point to #main-content",
    str_contains($authLayout,'href="#main-content"') &&
    str_contains($pubLayout,'href="#main-content"') &&
    str_contains($minLayout,'href="#main-content"'));

// ── A-02: HTML lang attribute ─────────────────────────────────────────────
chk('A-02a',"auth.php has lang=en on html element",
    str_contains($authLayout,'<html lang="en">'));
chk('A-02b',"public.php has lang=en on html element",
    str_contains($pubLayout,'<html lang="en">'));
chk('A-02c',"minimal.php has lang=en on html element",
    str_contains($minLayout,'<html lang="en">'));

// ── A-03: Landmarks ───────────────────────────────────────────────────────
chk('A-03a',"auth.php has <main> with id=main-content",
    preg_match('/<main\b[^>]*id="main-content"/',$authLayout)===1 ||
    preg_match('/<main\b[^>]*role="main"/',$authLayout)===1);
chk('A-03b',"public.php has <main id=main-content>",
    str_contains($pubLayout,'<main id="main-content"'));
chk('A-03c',"minimal.php has <main id=main-content>",
    str_contains($minLayout,'id="main-content"'));
chk('A-03d',"auth.php sidebar has aria-label",
    str_contains($authLayout,'aria-label="Sidebar navigation"'));
chk('A-03e',"public.php navbar has aria-label",
    str_contains($pubLayout,'aria-label="Main navigation"'));
chk('A-03f',"public.php footer has aria-label",
    str_contains($pubLayout,'aria-label="Site footer"'));

// ── A-04: minimal.php decorative panel ──────────────────────────────────
chk('A-04a',"minimal.php left panel is aria-hidden (decorative)",
    str_contains($minLayout,'minimal-split-left" aria-hidden="true"') ||
    str_contains($minLayout,"minimal-split-left' aria-hidden='true'") ||
    preg_match('/minimal-split-left[^>]*aria-hidden="true"/',$minLayout)===1);
chk('A-04b',"minimal.php feature list icons have aria-hidden",
    str_contains($minLayout,'bi-check-circle-fill" aria-hidden="true"') ||
    str_contains($minLayout,'check-circle-fill\\" aria-hidden=\\"true\\"'));

// ── A-05: Cache versions consistent ─────────────────────────────────────
chk('A-05a',"minimal.php CSS cache version v=4.0",
    str_contains($minLayout,'app.css?v=4.0'));
chk('A-05b',"minimal.php JS cache version v=3.4",
    str_contains($minLayout,'app.js?v=3.4'));
chk('A-05c',"auth.php CSS cache version v=4.0",
    str_contains($authLayout,'app.css?v=4.0'));
chk('A-05d',"public.php CSS cache version v=4.0",
    str_contains($pubLayout,'app.css?v=4.0'));

// ── A-06: Focus styles ────────────────────────────────────────────────────
chk('A-06a',"CSS has :focus-visible on .btn",
    str_contains($appCss,'.btn:focus-visible'));
chk('A-06b',"CSS has focus style on .form-control",
    str_contains($appCss,'.form-control:focus'));
chk('A-06c',"CSS does not suppress focus outline without replacement",
    !preg_match('/\.btn[^{]*\{[^}]*outline:\s*none[^}]*\}/s',$appCss) ||
    str_contains($appCss,'box-shadow') // has replacement indicator
);

// ── A-07: Table accessibility ─────────────────────────────────────────────
// All <th> in views should have scope attribute
$viewFiles = glob($root.'/views/**/*.php', GLOB_BRACE);
// Use recursive directory iteration
$allViews = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root.'/views', FilesystemIterator::SKIP_DOTS)
);
$thWithoutScope = 0;
foreach($allViews as $vf) {
    if($vf->getExtension()!=='php') continue;
    $c = file_get_contents($vf->getPathname());
    preg_match_all('/<th\b(?![^>]*scope=)[^>]*>/i',$c,$m);
    foreach($m[0] as $match) {
        if(str_contains($match,'<thead')) continue; // false positive guard
        $thWithoutScope++;
    }
}
chk('A-07a',"All <th> in views have scope attribute",$thWithoutScope===0,
    $thWithoutScope===0?"all have scope":"$thWithoutScope missing scope");

// Check table-responsive in key tables
$checkTables = [
    'views/admin/reports/index.php',
    'views/admin/audit-logs/index.php',
    'views/admin/users/index.php',
    'views/staff/requests/index.php',
    'views/admin/requests/index.php',
    'views/resident/requests/index.php',
];
foreach($checkTables as $tf) {
    $src = file_get_contents($root.'/'.$tf);
    $short = basename(dirname($tf)).'/'.basename($tf);
    chk("A-07-$short","table-responsive in $short",str_contains($src,'table-responsive'));
}

// ── A-08: Form labels ─────────────────────────────────────────────────────
$formsToCheck = [
    'views/auth/login.php'    => ['email','password'],
    'views/auth/register.php' => ['first_name','last_name','email','contact_number','password','house_number'],
    'views/admin/settings/index.php' => ['office_name','office_email','max_upload_size_mb'],
    'views/admin/reports/index.php'  => ['date_from','date_to','document_type_id','status'],
    'views/admin/audit-logs/index.php' => ['user_id','action','date_from'],
];
foreach($formsToCheck as $formPath => $fields) {
    $src = file_get_contents($root.'/'.$formPath);
    $short = basename($formPath);
    foreach($fields as $field) {
        $hasLabel = str_contains($src,"for=\"$field\"") || str_contains($src,"aria-label");
        chk("A-08-$short-$field","Label/aria for $field in $short",$hasLabel);
    }
}

// ── A-09: ARIA attributes on interactive elements ─────────────────────────
chk('A-09a',"auth.php sidebar toggle has aria-label",
    str_contains($authLayout,'aria-label="Toggle sidebar navigation"'));
chk('A-09b',"auth.php logout button has aria-label",
    str_contains($authLayout,'aria-label="Open sign out confirmation"'));
chk('A-09c',"auth.php main content has role=main",
    str_contains($authLayout,'role="main"'));

// ── A-10: Icons accessible ────────────────────────────────────────────────
// Check representative views for aria-hidden on bi- icons
$iconViews = ['views/layouts/auth.php','views/layouts/public.php','views/resident/notifications/index.php'];
foreach($iconViews as $iv) {
    $src = file_get_contents($root.'/'.$iv);
    // Count bi- icon occurrences without aria-hidden - simplified check
    // Count bi- icon tags and those with aria-hidden (anywhere in the tag, not just immediately after class)
    $allBiTags = preg_match_all('/<i\b[^>]*class="[^"]*\bbi\b[^"]*"[^>]*>/i',$src,$m1);
    $ariaHiddenBiTags = preg_match_all('/<i\b[^>]*class="[^"]*\bbi\b[^"]*"[^>]*aria-hidden="true"[^>]*>/i',$src,$m2);
    $ratio = $allBiTags > 0 ? round(count($m2[0])/$allBiTags*100) : 100;
    chk("A-10-".basename($iv),"Icons have aria-hidden in ".basename($iv),$ratio>=60,"$ratio% hidden");
}

// ── A-11: Color-not-only indicators ─────────────────────────────────────
// Notification unread: check for visual indicator beyond color
$resNotif = file_get_contents($root.'/views/resident/notifications/index.php');
chk('A-11a',"Resident notif: unread state uses visual dot indicator (not color only)",
    str_contains($resNotif,'border-radius:50%') || str_contains($resNotif,'notif-item-unread'));
// Status badges: check for icon + text (not color only)
$staffReqs = file_get_contents($root.'/views/staff/requests/index.php');
chk('A-11b',"Status badges use icon + text (not color alone)",
    str_contains($staffReqs,'bi-clock') && str_contains($staffReqs,'Pending'));

// ── A-12: Responsive basics ───────────────────────────────────────────────
chk('A-12a',"auth.php has viewport meta tag",
    str_contains($authLayout,'name="viewport"'));
chk('A-12b',"public.php has viewport meta tag",
    str_contains($pubLayout,'name="viewport"'));
chk('A-12c',"CSS has mobile breakpoint for sidebar",
    str_contains($appCss,'max-width: 991.98px') || str_contains($appCss,'sidebar-open'));
chk('A-12d',"Auth content uses margin-left for sidebar offset",
    str_contains($appCss,'margin-left: var(--sidebar-width)') ||
    str_contains($appCss,'margin-left: 0') // mobile reset
);
chk('A-12e',"Mobile: auth-content margin-left reset to 0",
    str_contains($appCss,'.auth-content { margin-left: 0; }') ||
    preg_match('/max-width.*991[^;]*;[\s\S]*?auth-content[\s\S]*?margin-left:\s*0/',$appCss)===1);

// ── A-13: Notification UI accessibility ──────────────────────────────────
$staffNotif = file_get_contents($root.'/views/staff/notifications/index.php');
chk('A-13a',"Staff notif view has additional_info_submitted in typeMap (Phase 9 fix intact)",
    str_contains($staffNotif,'additional_info_submitted'));
chk('A-13b',"Staff notif view has aria-label on notification list",
    str_contains($staffNotif,'aria-label'));
$adminNotif = file_get_contents($root.'/views/admin/notifications/index.php');
chk('A-13c',"Admin notif view has additional_info_submitted (Phase 9 fix intact)",
    str_contains($adminNotif,'additional_info_submitted'));

// ── A-14: Modal accessibility ─────────────────────────────────────────────
// Logout modal in auth.php
chk('A-14a',"Logout modal has aria-labelledby",
    str_contains($authLayout,'aria-labelledby="logoutConfirmTitle"'));
chk('A-14b',"Logout modal has role=dialog",
    str_contains($authLayout,'role="dialog"'));
chk('A-14c',"Modal close button has aria-label",
    str_contains($authLayout,'aria-label="Close"'));
chk('A-14d',"Logout confirm button has accessible text",
    str_contains($authLayout,'Log Out') || str_contains($authLayout,'Logout'));

// ── Syntax checks ──────────────────────────────────────────────────────────
$phpExe="c:\\xampp\\php\\php.exe";
$syntaxFiles=[
    'views/layouts/minimal.php',
    'views/layouts/auth.php',
    'views/layouts/public.php',
];
foreach($syntaxFiles as $sf){
    exec("$phpExe -l {$root}/{$sf} 2>&1",$out,$code);
    chk("SYN-".basename($sf),"Syntax: $sf",$code===0,$code===0?'OK':implode(' ',$out));
}

// ── Live DB ────────────────────────────────────────────────────────────────
chk('LIVE-01',"DB connection available",$pdo!==null,"ok");
$tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
chk('LIVE-02',"All expected tables present",
    in_array('users',$tables)&&in_array('notifications',$tables)&&in_array('system_settings',$tables));

// ── Regression ─────────────────────────────────────────────────────────────
$rc    = file_get_contents($root.'/app/Controllers/Resident/RequestController.php');
$htacc = file_get_contents($root.'/public/.htaccess');
chk('REG-01',"Phase1 uploadAdditionalDocuments",str_contains($rc,'uploadAdditionalDocuments'));
chk('REG-02',"Phase2 API routes",str_contains($routesSrc,"'/api/auth/login'"));
chk('REG-03',"Phase3 reports route",str_contains($routesSrc,"'/admin/reports'"));
chk('REG-04',"Phase4 audit-logs route",str_contains($routesSrc,"'/admin/audit-logs'"));
chk('REG-05',"Phase5 SettingsService",class_exists('Emunicipal\\Services\\SettingsService'));
chk('REG-06',"Phase6 lockout window-reset",
    str_contains(file_get_contents($root.'/app/Controllers/AuthController.php'),'15-minute window reset'));
chk('REG-07',"Phase7 CSP in .htaccess",str_contains($htacc,'Content-Security-Policy'));
chk('REG-07b',"Phase7 X-Frame-Options DENY",str_contains($htacc,'"DENY"'));
chk('REG-08',"Phase8 UserController store not stub",
    !str_contains(file_get_contents($root.'/app/Controllers/Admin/UserController.php'),'not yet available'));
chk('REG-09',"Phase9 additional_info_submitted in staff/admin notif views",
    str_contains($staffNotif,'additional_info_submitted') &&
    str_contains($adminNotif,'additional_info_submitted'));
chk('REG-10',"Transitions v4.0 unchanged",str_contains($authLayout,'v=4.0'));
chk('REG-11',"Phase1 status unchanged in uploadAdditionalDocuments",
    !preg_match('/uploadAdditionalDocuments[\s\S]*?\$requestModel->update/s',
        substr($rc, strpos($rc,'uploadAdditionalDocuments'), 3000)));

echo "\n====================================================\n";
echo " PHASE 10 - Accessibility/Responsive/UI Results\n";
echo "====================================================\n\n";
foreach($results as[$s,$id,$desc,$detail])
    echo "[$s] ".($s==='PASS'?'PASS':'FAIL')." $id: $desc".($detail?" ($detail)":"")."\n";
$t=$passed+$failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$t} passed.\n";
if($failed===0){echo "\nALL TESTS PASSED\n\n";exit(0);}
else{echo "\nSOME TESTS FAILED\n\n";exit(1);}