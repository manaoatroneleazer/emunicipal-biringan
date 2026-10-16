<?php
/**
 * Preservation Fixed Tests (Task 10)
 * Spec: emunicipal-nav-sidebar-faq-fixes
 *
 * Verifies that all baseline preserved behaviours are STILL intact after the fixes
 * have been applied. All 11 assertions must PASS.
 *
 * Run:  php tests/bugfix/preservation_fixed_test.php
 * Exit: 0 = all PASS, 1 = one or more FAIL
 */

declare(strict_types=1);

// ──────────────────────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────────────────────

$pass    = 0;
$fail    = 0;
$results = [];

function assertContains(
    string $testName,
    string $haystack,
    string $needle,
    string $evidenceLabel = 'Found'
): void {
    global $pass, $fail, $results;

    $found  = str_contains($haystack, $needle);
    $status = $found ? 'PASS' : 'FAIL';

    if ($found) {
        $pass++;
        $pos     = strpos($haystack, $needle);
        $start   = max(0, $pos - 60);
        $excerpt = '…' . substr($haystack, $start, 160) . '…';
        $evidence = $evidenceLabel . ': ' . trim(preg_replace('/\s+/', ' ', $excerpt));
    } else {
        $fail++;
        $evidence = 'NOT FOUND: ' . $needle;
    }

    $results[] = compact('status', 'testName', 'evidence');
}

function assertNotContains(
    string $testName,
    string $haystack,
    string $needle,
    string $evidenceLabel = 'Correctly absent'
): void {
    global $pass, $fail, $results;

    $found  = str_contains($haystack, $needle);
    $status = $found ? 'FAIL' : 'PASS';

    if (!$found) {
        $pass++;
        $evidence = $evidenceLabel . ': "' . $needle . '" is not present (correct)';
    } else {
        $fail++;
        $pos     = strpos($haystack, $needle);
        $start   = max(0, $pos - 60);
        $excerpt = '…' . substr($haystack, $start, 160) . '…';
        $evidence = 'UNEXPECTEDLY FOUND: ' . trim(preg_replace('/\s+/', ' ', $excerpt));
    }

    $results[] = compact('status', 'testName', 'evidence');
}

function assertAtLeast(
    string $testName,
    int $actual,
    int $min,
    string $evidenceLabel = 'Count'
): void {
    global $pass, $fail, $results;

    $ok     = ($actual >= $min);
    $status = $ok ? 'PASS' : 'FAIL';

    if ($ok) {
        $pass++;
        $evidence = $evidenceLabel . ': ' . $actual . ' (min required: ' . $min . ')';
    } else {
        $fail++;
        $evidence = $evidenceLabel . ': ' . $actual . ' (min required: ' . $min . ')';
    }

    $results[] = compact('status', 'testName', 'evidence');
}

function loadFile(string $path): string {
    if (!file_exists($path)) {
        echo "FATAL: Cannot load file: {$path}\n";
        exit(2);
    }
    return file_get_contents($path);
}

// ──────────────────────────────────────────────────────────────────────────────
// Resolve project root
// ──────────────────────────────────────────────────────────────────────────────

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    echo "FATAL: Cannot resolve project root from " . __DIR__ . "\n";
    exit(2);
}

$authLayout       = loadFile($root . '/views/layouts/auth.php');
$adminRequestShow = loadFile($root . '/views/admin/requests/show.php');
$staffRequestShow = loadFile($root . '/views/staff/requests/show.php');
$appCss           = loadFile($root . '/public/assets/css/app.css');
$homeView         = loadFile($root . '/views/home/index.php');

echo str_repeat('=', 72) . "\n";
echo "eMunicipal  —  Preservation FIXED Tests (Task 10)\n";
echo "Confirming all preserved behaviours still intact after fixes.\n";
echo str_repeat('=', 72) . "\n\n";

// ══════════════════════════════════════════════════════════════════════════════
// ASSERTION 1: Sidebar active-link state preserved (auth.php)
// views/layouts/auth.php still uses $activePage === 'X' ? 'active' : '' pattern
// ══════════════════════════════════════════════════════════════════════════════

echo "── 1: Sidebar active-link state preserved (auth.php) ───────────────────\n";

assertContains(
    '1.1  auth.php: $activePage === "dashboard" ? "active" : "" pattern present',
    $authLayout,
    '$activePage === \'dashboard\' ? \'active\' : \'\'',
    'Found active-class conditional'
);

assertContains(
    '1.2  auth.php: $activePage === "requests" ? "active" : "" pattern present',
    $authLayout,
    '$activePage === \'requests\' ? \'active\' : \'\'',
    'Found requests active-class conditional'
);

assertContains(
    '1.3  auth.php: aria-current="page" attribute still present',
    $authLayout,
    'aria-current="page"',
    'Found aria-current attribute'
);

// ══════════════════════════════════════════════════════════════════════════════
// ASSERTION 2: CSRF in logout form preserved (auth.php)
// Sidebar logout form still contains _csrf_token hidden input and POSTs to /logout
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── 2: CSRF in logout form preserved (auth.php) ─────────────────────────\n";

assertContains(
    '2.1  auth.php: logout form action POSTs to /logout',
    $authLayout,
    'action="<?= $appUrl ?>/logout"',
    'Found logout form action'
);

assertContains(
    '2.2  auth.php: logout form carries _csrf_token hidden input',
    $authLayout,
    'name="_csrf_token"',
    'Found _csrf_token hidden input'
);

assertContains(
    '2.3  auth.php: logout form method is POST',
    $authLayout,
    'method="POST"',
    'Found POST method on logout form'
);

// ══════════════════════════════════════════════════════════════════════════════
// ASSERTION 3: Resident notification badge preserved (auth.php)
// Still renders unread count badge in Resident sidebar
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── 3: Resident notification badge preserved (auth.php) ─────────────────\n";

assertContains(
    '3.1  auth.php: $unreadCount variable reference present',
    $authLayout,
    '$unreadCount',
    'Found $unreadCount variable reference'
);

assertContains(
    '3.2  auth.php: $unreadCount > 0 conditional for badge present',
    $authLayout,
    '$unreadCount > 0',
    'Found unread count conditional'
);

assertContains(
    '3.3  auth.php: notifications link points to /resident/notifications',
    $authLayout,
    '/resident/notifications',
    'Found notifications href'
);

// ══════════════════════════════════════════════════════════════════════════════
// ASSERTION 4: Admin remark modals preserved (admin/requests/show.php)
// Reject, Additional Info, Cancel modals with _csrf_token and new_status
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── 4: Admin remark modals preserved (admin/requests/show.php) ──────────\n";

assertContains(
    '4.1  Admin view: _csrf_token hidden input present in modals',
    $adminRequestShow,
    'name="_csrf_token"',
    'Found _csrf_token in admin view'
);

assertContains(
    '4.2  Admin view: new_status hidden input present in modals',
    $adminRequestShow,
    'name="new_status"',
    'Found new_status hidden input in admin view'
);

assertContains(
    '4.3  Admin view: STATUS_REJECTED used in modal logic',
    $adminRequestShow,
    'RequestModel::STATUS_REJECTED',
    'Found STATUS_REJECTED key in $needsRemark'
);

assertContains(
    '4.4  Admin view: STATUS_ADDITIONAL_INFO_REQUIRED used in modal logic',
    $adminRequestShow,
    'RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED',
    'Found STATUS_ADDITIONAL_INFO_REQUIRED key'
);

assertContains(
    '4.5  Admin view: STATUS_CANCELLED used in modal logic',
    $adminRequestShow,
    'RequestModel::STATUS_CANCELLED',
    'Found STATUS_CANCELLED in modal condition'
);

assertContains(
    '4.6  Admin view: modal uses data-bs-backdrop="static"',
    $adminRequestShow,
    'data-bs-backdrop="static"',
    'Found static backdrop on admin modal'
);

// ══════════════════════════════════════════════════════════════════════════════
// ASSERTION 5: Staff remark modals preserved (staff/requests/show.php)
// Same modal structure with _csrf_token and new_status
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── 5: Staff remark modals preserved (staff/requests/show.php) ──────────\n";

assertContains(
    '5.1  Staff view: _csrf_token hidden input present in modals',
    $staffRequestShow,
    'name="_csrf_token"',
    'Found _csrf_token in staff view'
);

assertContains(
    '5.2  Staff view: new_status hidden input present in modals',
    $staffRequestShow,
    'name="new_status"',
    'Found new_status in staff view'
);

assertContains(
    '5.3  Staff view: STATUS_REJECTED used in modal logic',
    $staffRequestShow,
    'RequestModel::STATUS_REJECTED',
    'Found STATUS_REJECTED key in staff view'
);

assertContains(
    '5.4  Staff view: STATUS_ADDITIONAL_INFO_REQUIRED used in modal logic',
    $staffRequestShow,
    'RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED',
    'Found STATUS_ADDITIONAL_INFO_REQUIRED key in staff view'
);

assertContains(
    '5.5  Staff view: modal uses data-bs-backdrop="static"',
    $staffRequestShow,
    'data-bs-backdrop="static"',
    'Found static backdrop on staff modal'
);

// ══════════════════════════════════════════════════════════════════════════════
// ASSERTION 6: data-loading preserved (admin/requests/show.php)
// admin view still has data-loading attribute on forms
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── 6: data-loading preserved (admin/requests/show.php) ─────────────────\n";

assertContains(
    '6.1  Admin view: data-loading attribute present on forms',
    $adminRequestShow,
    'data-loading=',
    'Found data-loading attribute in admin request show view'
);

// ══════════════════════════════════════════════════════════════════════════════
// ASSERTION 7: CSS sidebar top: 0 (FIXED value)
// .sidebar now has top: 0 (not var(--header-height))
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── 7: CSS sidebar top: 0 (fixed value) ─────────────────────────────────\n";

// .sidebar rule has top: 0
assertContains(
    '7.1  CSS: .sidebar rule has "top: 0" (fixed value)',
    $appCss,
    '.sidebar {',
    'Found .sidebar rule block'
);

// The sidebar block specifically contains top: 0 — verify no var(--header-height) on .sidebar
// We extract the .sidebar block to check its specific top value
$sidebarBlockStart = strpos($appCss, '.sidebar {');
$sidebarBlockEnd   = strpos($appCss, '}', $sidebarBlockStart);
$sidebarBlock      = ($sidebarBlockStart !== false && $sidebarBlockEnd !== false)
    ? substr($appCss, $sidebarBlockStart, $sidebarBlockEnd - $sidebarBlockStart + 1)
    : '';

assertContains(
    '7.2  CSS: .sidebar block contains "top: 0" (fixed, header-independent)',
    $sidebarBlock,
    'top: 0',
    'Found top: 0 in .sidebar block'
);

assertNotContains(
    '7.3  CSS: .sidebar block no longer uses top: var(--header-height) (removed)',
    $sidebarBlock,
    'top: var(--header-height)',
    'Correctly absent: var(--header-height) offset on .sidebar'
);

// ══════════════════════════════════════════════════════════════════════════════
// ASSERTION 8: CSS auth-content margin-top: 0 (FIXED value)
// .auth-content now has margin-top: 0
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── 8: CSS auth-content margin-top: 0 (fixed value) ─────────────────────\n";

$authContentStart = strpos($appCss, '.auth-content {');
$authContentEnd   = strpos($appCss, '}', $authContentStart);
$authContentBlock = ($authContentStart !== false && $authContentEnd !== false)
    ? substr($appCss, $authContentStart, $authContentEnd - $authContentStart + 1)
    : '';

assertContains(
    '8.1  CSS: .auth-content block contains "margin-top: 0" (fixed value)',
    $authContentBlock,
    'margin-top: 0',
    'Found margin-top: 0 in .auth-content block'
);

assertNotContains(
    '8.2  CSS: .auth-content block no longer uses margin-top: var(--header-height) (removed)',
    $authContentBlock,
    'margin-top: var(--header-height)',
    'Correctly absent: var(--header-height) offset on .auth-content'
);

// ══════════════════════════════════════════════════════════════════════════════
// ASSERTION 9: Landing page sections preserved (views/home/index.php)
// All major section IDs present: hero/home, about, services, how-it-works, faqs, contact
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── 9: Landing page sections preserved (views/home/index.php) ───────────\n";

// Accept either id="home" or id="hero" for the top section
$hasHome = str_contains($homeView, 'id="home"') || str_contains($homeView, 'id="hero"');
if ($hasHome) {
    global $pass, $results;
    $pass++;
    $results[] = [
        'status'   => 'PASS',
        'testName' => '9.1  Home view: #home or #hero section present',
        'evidence' => 'Found id="home" or id="hero" section',
    ];
} else {
    global $fail, $results;
    $fail++;
    $results[] = [
        'status'   => 'FAIL',
        'testName' => '9.1  Home view: #home or #hero section present',
        'evidence' => 'NOT FOUND: id="home" or id="hero"',
    ];
}

assertContains(
    '9.2  Home view: #about section present',
    $homeView,
    'id="about"',
    'Found id="about"'
);

assertContains(
    '9.3  Home view: #services section present',
    $homeView,
    'id="services"',
    'Found id="services"'
);

assertContains(
    '9.4  Home view: #how-it-works section present',
    $homeView,
    'id="how-it-works"',
    'Found id="how-it-works"'
);

assertContains(
    '9.5  Home view: #faqs section present',
    $homeView,
    'id="faqs"',
    'Found id="faqs"'
);

assertContains(
    '9.6  Home view: #contact section present',
    $homeView,
    'id="contact"',
    'Found id="contact"'
);

// Verify FAQ count is now 15 (post-fix)
$faqsStart     = strpos($homeView, '$faqs = [');
$faqsEnd       = strpos($homeView, '];', $faqsStart);
$faqsBlock     = ($faqsStart !== false && $faqsEnd !== false)
    ? substr($homeView, $faqsStart, $faqsEnd - $faqsStart + 2)
    : '';
$faqEntryCount = preg_match_all("/\[\s*'/", $faqsBlock, $faqMatches);

assertAtLeast(
    '9.7  Home view: FAQ $faqs array has at least 15 entries (post-fix)',
    $faqEntryCount,
    15,
    'FAQ entry count in $faqs array'
);

// ══════════════════════════════════════════════════════════════════════════════
// ASSERTION 10: Mobile toggle present inside <main (auth.php)
// auth.php contains id="sidebarToggle" inside <main
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── 10: Mobile toggle inside <main (auth.php) ───────────────────────────\n";

// Locate the <main class="auth-content"> block and check sidebarToggle is after it
$mainPos   = strpos($authLayout, '<main class="auth-content"');
$togglePos = strpos($authLayout, 'id="sidebarToggle"');

if ($mainPos !== false && $togglePos !== false && $togglePos > $mainPos) {
    global $pass, $results;
    $pass++;
    $results[] = [
        'status'   => 'PASS',
        'testName' => '10.1 auth.php: id="sidebarToggle" is present inside <main class="auth-content">',
        'evidence' => 'Found sidebarToggle at position ' . $togglePos . ' after <main> at ' . $mainPos,
    ];
} else {
    global $fail, $results;
    $fail++;
    $reason = $mainPos === false ? '<main class="auth-content"> not found'
        : ($togglePos === false ? 'id="sidebarToggle" not found'
            : 'sidebarToggle appears before <main> (expected inside <main>)');
    $results[] = [
        'status'   => 'FAIL',
        'testName' => '10.1 auth.php: id="sidebarToggle" is present inside <main class="auth-content">',
        'evidence' => $reason,
    ];
}

// ══════════════════════════════════════════════════════════════════════════════
// ASSERTION 11: User identity strip present (auth.php)
// Contains sidebar-user div with $firstName and $lastName
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── 11: User identity strip present (auth.php) ───────────────────────────\n";

assertContains(
    '11.1 auth.php: sidebar-user div present',
    $authLayout,
    'sidebar-user',
    'Found sidebar-user div'
);

assertContains(
    '11.2 auth.php: $firstName variable present in sidebar-user block',
    $authLayout,
    '$firstName',
    'Found $firstName in auth layout'
);

assertContains(
    '11.3 auth.php: $lastName variable present in sidebar-user block',
    $authLayout,
    '$lastName',
    'Found $lastName in auth layout'
);

// ══════════════════════════════════════════════════════════════════════════════
// RESULTS SUMMARY
// ══════════════════════════════════════════════════════════════════════════════

echo "\n" . str_repeat('=', 72) . "\n";
echo "RESULTS\n";
echo str_repeat('=', 72) . "\n\n";

foreach ($results as $r) {
    printf("  [%s] %s\n      %s\n\n", $r['status'], $r['testName'], $r['evidence']);
}

echo str_repeat('-', 72) . "\n";
$total = $pass + $fail;
printf("  Total: %d   PASS: %d   FAIL: %d\n", $total, $pass, $fail);
echo str_repeat('-', 72) . "\n\n";

if ($fail === 0) {
    echo "  ✓  ALL PRESERVATION TESTS PASS — no regressions detected.\n";
    echo "     The fixes are confirmed correct and all baselines are intact.\n\n";
    exit(0);
} else {
    echo "  ✗  {$fail} PRESERVATION TEST(S) FAILED — regression(s) detected!\n";
    echo "     Review the failures above before marking this task complete.\n\n";
    exit(1);
}
