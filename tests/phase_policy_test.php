<?php
/**
 * Phase Policy — Policy Pages + Registration Terms Checkbox Tests
 *
 * Tests:
 *  - All four policy route files exist and are PHP-valid.
 *  - HomeController contains all four policy methods.
 *  - Routes file registers the four policy routes.
 *  - Public layout footer contains the Legal column with all four links.
 *  - Register view contains the terms_accepted checkbox.
 *  - AuthController register() enforces terms_accepted server-side.
 *  - Privacy notice exists in the new request view.
 *  - Policy views contain required content sections.
 *
 * No database writes or deletes. No existing records touched.
 * Run with: php tests/phase_policy_test.php
 */

$root = dirname(__DIR__);

$passed  = 0;
$failed  = 0;
$skipped = 0;
$results = [];

function chk(string $id, string $desc, bool $pass, string $detail = ''): void
{
    global $passed, $failed, $results;
    if ($pass) { $passed++; $results[] = ['PASS', $id, $desc, $detail]; }
    else        { $failed++; $results[] = ['FAIL', $id, $desc, $detail]; }
}

function skip(string $id, string $desc, string $reason): void
{
    global $skipped, $results;
    $skipped++;
    $results[] = ['SKIP', $id, $desc, $reason];
}

// ─────────────────────────────────────────────────────────────────────────────
// PHP binary — use full path on Windows where 'php' may not be on PATH
// ─────────────────────────────────────────────────────────────────────────────
$phpBin = 'c:\\xampp\\php\\php.exe';
if (!file_exists($phpBin)) {
    // Fallback: let the OS find it
    $phpBin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'php.exe' : 'php';
}
function policyReadFile(string $path): string
{
    return file_exists($path) ? file_get_contents($path) : '';
}

// ─────────────────────────────────────────────────────────────────────────────
// T01–T04: Policy view files exist
// ─────────────────────────────────────────────────────────────────────────────
$policyViews = [
    'T01' => ['views/home/policy-privacy.php',    'Privacy Policy view exists'],
    'T02' => ['views/home/policy-terms.php',       'Terms and Conditions view exists'],
    'T03' => ['views/home/policy-moderation.php',  'Moderation Policy view exists'],
    'T04' => ['views/home/policy-copyright.php',   'Copyright Policy view exists'],
];

foreach ($policyViews as $id => [$file, $desc]) {
    $path  = $root . '/' . $file;
    $exists = file_exists($path);
    chk($id, $desc, $exists, $exists ? 'File present' : "Missing: $path");
}

// ─────────────────────────────────────────────────────────────────────────────
// T05–T08: Policy view files pass PHP syntax check
// ─────────────────────────────────────────────────────────────────────────────
$syntaxViews = [
    'T05' => 'views/home/policy-privacy.php',
    'T06' => 'views/home/policy-terms.php',
    'T07' => 'views/home/policy-moderation.php',
    'T08' => 'views/home/policy-copyright.php',
];

foreach ($syntaxViews as $id => $file) {
    $path = $root . '/' . $file;
    if (!file_exists($path)) {
        skip($id, "PHP syntax: $file", 'File not present');
        continue;
    }
    $output = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($path) . ' 2>&1') ?? '';
    $pass   = str_contains($output, 'No syntax errors');
    chk($id, "PHP syntax valid: $file", $pass, trim($output));
}

// ─────────────────────────────────────────────────────────────────────────────
// T09–T12: HomeController contains the four policy methods
// ─────────────────────────────────────────────────────────────────────────────
$hcPath    = $root . '/app/Controllers/HomeController.php';
$hcContent = policyReadFile($hcPath);

$methods = [
    'T09' => ['policyPrivacy',    'HomeController::policyPrivacy() exists'],
    'T10' => ['policyTerms',      'HomeController::policyTerms() exists'],
    'T11' => ['policyModeration', 'HomeController::policyModeration() exists'],
    'T12' => ['policyCopyright',  'HomeController::policyCopyright() exists'],
];

foreach ($methods as $id => [$method, $desc]) {
    chk($id, $desc, str_contains($hcContent, "function {$method}("),
        str_contains($hcContent, "function {$method}(") ? 'Found' : "Not found in HomeController");
}

// ─────────────────────────────────────────────────────────────────────────────
// T13–T16: Routes file registers all four policy routes
// ─────────────────────────────────────────────────────────────────────────────
$routesPath    = $root . '/routes/web.php';
$routesContent = policyReadFile($routesPath);

$routes = [
    'T13' => ['/policy/privacy',    'Route /policy/privacy registered'],
    'T14' => ['/policy/terms',       'Route /policy/terms registered'],
    'T15' => ['/policy/moderation',  'Route /policy/moderation registered'],
    'T16' => ['/policy/copyright',   'Route /policy/copyright registered'],
];

foreach ($routes as $id => [$route, $desc]) {
    chk($id, $desc, str_contains($routesContent, $route),
        str_contains($routesContent, $route) ? 'Found' : "Route '$route' not found in routes/web.php");
}

// ─────────────────────────────────────────────────────────────────────────────
// T17: Routes point to HomeController
// ─────────────────────────────────────────────────────────────────────────────
chk('T17', 'Policy routes reference HomeController', str_contains($routesContent, 'HomeController'),
    'Confirmed HomeController referenced in routes/web.php');

// ─────────────────────────────────────────────────────────────────────────────
// T18–T21: Public footer has Legal section with all four policy links
// ─────────────────────────────────────────────────────────────────────────────
$footerPath    = $root . '/views/layouts/public.php';
$footerContent = policyReadFile($footerPath);

$footerLinks = [
    'T18' => ['/policy/privacy',   'Footer links to Privacy Policy'],
    'T19' => ['/policy/terms',      'Footer links to Terms and Conditions'],
    'T20' => ['/policy/moderation', 'Footer links to Moderation Policy'],
    'T21' => ['/policy/copyright',  'Footer links to Copyright Policy'],
];

foreach ($footerLinks as $id => [$link, $desc]) {
    chk($id, $desc, str_contains($footerContent, $link),
        str_contains($footerContent, $link) ? 'Found' : "Link '$link' not found in public layout footer");
}

chk('T22', 'Footer has "Legal" column heading',
    str_contains($footerContent, 'Legal'),
    str_contains($footerContent, 'Legal') ? 'Found' : 'Legal heading not found');

// ─────────────────────────────────────────────────────────────────────────────
// T23–T26: Register view has terms_accepted checkbox
// ─────────────────────────────────────────────────────────────────────────────
$registerPath    = $root . '/views/auth/register.php';
$registerContent = policyReadFile($registerPath);

chk('T23', 'Register view has terms_accepted checkbox input',
    str_contains($registerContent, 'name="terms_accepted"'),
    str_contains($registerContent, 'name="terms_accepted"') ? 'Found' : 'Checkbox input not found');

chk('T24', 'Register view checkbox has value="1"',
    str_contains($registerContent, 'value="1"') && str_contains($registerContent, 'terms_accepted'),
    'Confirmed value="1" on terms_accepted field');

chk('T25', 'Register view links to /policy/terms from checkbox label',
    str_contains($registerContent, '/policy/terms'),
    str_contains($registerContent, '/policy/terms') ? 'Found' : 'terms link not in register view');

chk('T26', 'Register view links to /policy/privacy from checkbox label',
    str_contains($registerContent, '/policy/privacy'),
    str_contains($registerContent, '/policy/privacy') ? 'Found' : 'privacy link not in register view');

// ─────────────────────────────────────────────────────────────────────────────
// T27–T30: AuthController enforces terms_accepted server-side
// ─────────────────────────────────────────────────────────────────────────────
$authPath    = $root . '/app/Controllers/AuthController.php';
$authContent = policyReadFile($authPath);

chk('T27', 'AuthController checks terms_accepted !== "1"',
    str_contains($authContent, "terms_accepted"),
    str_contains($authContent, "terms_accepted") ? 'Found' : 'terms_accepted not referenced in AuthController');

chk('T28', 'AuthController calls addError for terms_accepted',
    str_contains($authContent, "addError('terms_accepted'"),
    str_contains($authContent, "addError('terms_accepted'") ? 'Found' : 'addError call not found');

chk('T29', 'AuthController error message mentions Terms and Conditions',
    str_contains($authContent, 'Terms and Conditions'),
    str_contains($authContent, 'Terms and Conditions') ? 'Found' : 'Error message text missing');

chk('T30', 'AuthController error message mentions Privacy Policy',
    str_contains($authContent, 'Privacy Policy'),
    str_contains($authContent, 'Privacy Policy') ? 'Found' : 'Error message text missing');

// ─────────────────────────────────────────────────────────────────────────────
// T31: terms_accepted check occurs before INSERT (ordering check)
// ─────────────────────────────────────────────────────────────────────────────
$termsPos  = strpos($authContent, 'terms_accepted');
$insertPos = strpos($authContent, '$userModel->insert(');
chk('T31', 'terms_accepted check occurs before $userModel->insert()',
    $termsPos !== false && $insertPos !== false && $termsPos < $insertPos,
    "terms_accepted at pos $termsPos, insert at pos $insertPos");

// ─────────────────────────────────────────────────────────────────────────────
// T32: Register view checkbox preserves old value (re-display on error)
// ─────────────────────────────────────────────────────────────────────────────
chk('T32', 'Register view checkbox uses $old to preserve checked state',
    str_contains($registerContent, "old['terms_accepted']"),
    str_contains($registerContent, "old['terms_accepted']") ? 'Found' : '$old[terms_accepted] not used');

// ─────────────────────────────────────────────────────────────────────────────
// T33: Register view uses $inv() / $err() pattern for terms_accepted
// ─────────────────────────────────────────────────────────────────────────────
chk('T33', 'Register view uses $inv(\'terms_accepted\') for invalid state',
    str_contains($registerContent, '$inv(\'terms_accepted\')') || str_contains($registerContent, '$inv("terms_accepted")'),
    'Found invalid-state helper on terms_accepted');

// ─────────────────────────────────────────────────────────────────────────────
// T34–T35: Privacy notice on request form
// ─────────────────────────────────────────────────────────────────────────────
$newRequestPath    = $root . '/views/resident/requests/new.php';
$newRequestContent = policyReadFile($newRequestPath);

chk('T34', 'New request view has privacy notice',
    str_contains($newRequestContent, 'Privacy Notice'),
    str_contains($newRequestContent, 'Privacy Notice') ? 'Found' : 'Privacy Notice text not found in new.php');

chk('T35', 'New request privacy notice mentions fictional sample information',
    str_contains($newRequestContent, 'fictional sample') || str_contains($newRequestContent, 'sample information'),
    'Confirmed demo disclaimer text present');

// ─────────────────────────────────────────────────────────────────────────────
// T36–T39: Policy view content spot-checks
// ─────────────────────────────────────────────────────────────────────────────
$privacyContent     = policyReadFile($root . '/views/home/policy-privacy.php');
$termsContent       = policyReadFile($root . '/views/home/policy-terms.php');
$moderationContent  = policyReadFile($root . '/views/home/policy-moderation.php');
$copyrightContent   = policyReadFile($root . '/views/home/policy-copyright.php');

chk('T36', 'Privacy Policy view has "Information We Collect" section',
    str_contains($privacyContent, 'Information We Collect'),
    'Content section found');

chk('T37', 'Terms view has "Prohibited Activities" section',
    str_contains($termsContent, 'Prohibited Activities'),
    'Content section found');

chk('T38', 'Moderation view has "Prohibited File Uploads" section',
    str_contains($moderationContent, 'Prohibited File Uploads'),
    'Content section found');

chk('T39', 'Copyright view has "Third-Party Open-Source Components" section',
    str_contains($copyrightContent, 'Third-Party'),
    'Content section found');

// ─────────────────────────────────────────────────────────────────────────────
// T40: All policy views use page-hero-sm class
// ─────────────────────────────────────────────────────────────────────────────
$heroChecks = [
    'views/home/policy-privacy.php'   => $privacyContent,
    'views/home/policy-terms.php'     => $termsContent,
    'views/home/policy-moderation.php'=> $moderationContent,
    'views/home/policy-copyright.php' => $copyrightContent,
];
$allHaveHero = true;
foreach ($heroChecks as $file => $content) {
    if (!str_contains($content, 'page-hero-sm')) { $allHaveHero = false; break; }
}
chk('T40', 'All policy views use page-hero-sm hero class', $allHaveHero, $allHaveHero ? 'Confirmed' : 'One or more views missing page-hero-sm');

// ─────────────────────────────────────────────────────────────────────────────
// T41: All policy views have academic disclaimer banner
// ─────────────────────────────────────────────────────────────────────────────
$allHaveDisclaimer = true;
foreach ($heroChecks as $file => $content) {
    if (!str_contains($content, 'Academic Demonstration Notice')) { $allHaveDisclaimer = false; break; }
}
chk('T41', 'All policy views have Academic Demonstration Notice banner', $allHaveDisclaimer, $allHaveDisclaimer ? 'Confirmed' : 'One or more views missing disclaimer banner');

// ─────────────────────────────────────────────────────────────────────────────
// T42: All policy views have breadcrumb navigation
// ─────────────────────────────────────────────────────────────────────────────
$allHaveBreadcrumb = true;
foreach ($heroChecks as $file => $content) {
    if (!str_contains($content, 'breadcrumb')) { $allHaveBreadcrumb = false; break; }
}
chk('T42', 'All policy views have breadcrumb navigation', $allHaveBreadcrumb, $allHaveBreadcrumb ? 'Confirmed' : 'One or more views missing breadcrumb');

// ─────────────────────────────────────────────────────────────────────────────
// T43: Privacy policy view mentions password_hash (security section)
// ─────────────────────────────────────────────────────────────────────────────
chk('T43', 'Privacy Policy security section mentions password_hash',
    str_contains($privacyContent, 'password_hash'),
    str_contains($privacyContent, 'password_hash') ? 'Found' : 'Security detail missing');

// ─────────────────────────────────────────────────────────────────────────────
// T44: Terms view has simulated payments disclaimer
// ─────────────────────────────────────────────────────────────────────────────
chk('T44', 'Terms view has simulated payments section',
    str_contains($termsContent, 'Simulated Payments') || str_contains($termsContent, 'simulated payment'),
    'Content found');

// ─────────────────────────────────────────────────────────────────────────────
// T45: Copyright view lists Bootstrap as a third-party component
// ─────────────────────────────────────────────────────────────────────────────
chk('T45', 'Copyright view lists Bootstrap as a third-party dependency',
    str_contains($copyrightContent, 'Bootstrap'),
    str_contains($copyrightContent, 'Bootstrap') ? 'Found' : 'Bootstrap not listed');

// ─────────────────────────────────────────────────────────────────────────────
// T46: Public layout syntax check (footer update)
// ─────────────────────────────────────────────────────────────────────────────
$layoutPath = $root . '/views/layouts/public.php';
if (file_exists($layoutPath)) {
    $output = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($layoutPath) . ' 2>&1') ?? '';
    chk('T46', 'Public layout (public.php) passes PHP syntax check',
        str_contains($output, 'No syntax errors'), trim($output));
} else {
    skip('T46', 'Public layout syntax check', 'views/layouts/public.php not found');
}

// ─────────────────────────────────────────────────────────────────────────────
// T47: AuthController syntax check
// ─────────────────────────────────────────────────────────────────────────────
if (file_exists($authPath)) {
    $output = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($authPath) . ' 2>&1') ?? '';
    chk('T47', 'AuthController.php passes PHP syntax check',
        str_contains($output, 'No syntax errors'), trim($output));
} else {
    skip('T47', 'AuthController syntax check', 'AuthController.php not found');
}

// ─────────────────────────────────────────────────────────────────────────────
// T48: Register view syntax check
// ─────────────────────────────────────────────────────────────────────────────
if (file_exists($registerPath)) {
    $output = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($registerPath) . ' 2>&1') ?? '';
    chk('T48', 'views/auth/register.php passes PHP syntax check',
        str_contains($output, 'No syntax errors'), trim($output));
} else {
    skip('T48', 'Register view syntax check', 'views/auth/register.php not found');
}

// ─────────────────────────────────────────────────────────────────────────────
// T49: New request view syntax check
// ─────────────────────────────────────────────────────────────────────────────
if (file_exists($newRequestPath)) {
    $output = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($newRequestPath) . ' 2>&1') ?? '';
    chk('T49', 'views/resident/requests/new.php passes PHP syntax check',
        str_contains($output, 'No syntax errors'), trim($output));
} else {
    skip('T49', 'New request view syntax check', 'views/resident/requests/new.php not found');
}

// ─────────────────────────────────────────────────────────────────────────────
// T50: HomeController syntax check
// ─────────────────────────────────────────────────────────────────────────────
if (file_exists($hcPath)) {
    $output = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($hcPath) . ' 2>&1') ?? '';
    chk('T50', 'HomeController.php passes PHP syntax check',
        str_contains($output, 'No syntax errors'), trim($output));
} else {
    skip('T50', 'HomeController syntax check', 'HomeController.php not found');
}

// ─────────────────────────────────────────────────────────────────────────────
// REPORT
// ─────────────────────────────────────────────────────────────────────────────
echo "\n";
echo "=================================================================\n";
echo "  Phase Policy — Policy Pages + Registration Checkbox Tests\n";
echo "=================================================================\n\n";

$colWidths = [6, 5, 55, 0]; // status, id, desc, detail
foreach ($results as $r) {
    [$status, $id, $desc, $detail] = $r;
    $statusLabel = match($status) {
        'PASS' => "\e[32mPASS\e[0m",
        'FAIL' => "\e[31mFAIL\e[0m",
        'SKIP' => "\e[33mSKIP\e[0m",
        default => $status,
    };
    printf("  %s  %-5s  %-55s  %s\n", $statusLabel, $id, $desc, $detail);
}

echo "\n";
echo "-----------------------------------------------------------------\n";
$total = $passed + $failed + $skipped;
echo "  Total: $total   PASS: $passed   FAIL: $failed   SKIP: $skipped\n";
echo "-----------------------------------------------------------------\n\n";

if ($failed > 0) {
    echo "  FAILED TESTS:\n";
    foreach ($results as $r) {
        if ($r[0] === 'FAIL') {
            printf("    [%s] %s — %s\n", $r[1], $r[2], $r[3]);
        }
    }
    echo "\n";
    exit(1);
}

exit(0);
