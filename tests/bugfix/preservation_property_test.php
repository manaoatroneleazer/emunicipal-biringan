<?php
/**
 * Preservation Property Tests (Task 2)
 * Spec: emunicipal-nav-sidebar-faq-fixes
 *
 * These tests establish the behavioural BASELINE on UNFIXED code.
 * All assertions MUST PASS before any fix is applied.
 *
 * They document the exact structures that the fix must leave intact:
 *   - Sidebar active-link / aria-current mechanism  (Req 3.3)
 *   - CSRF token in sidebar logout form              (Req 3.13)
 *   - Resident notification badge                    (Req 3.14)
 *   - Admin remark modals (Reject / Add-Info / Cancel) (Req 3.7)
 *   - Staff remark modals                            (Req 3.7)
 *   - data-loading double-submit prevention          (Req 3.6)
 *   - CSS baseline offsets (the before-state values) (Req 2.2 — will change in fix)
 *   - All public landing-page section IDs present    (Req 3.8)
 *
 * Run:  php tests/bugfix/preservation_property_test.php
 * Exit: 0 = all PASS, 1 = one or more FAIL
 */

declare(strict_types=1);

// ──────────────────────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────────────────────

$pass  = 0;
$fail  = 0;
$results = [];

/**
 * Assert that $haystack contains $needle.
 * Records PASS/FAIL and prints evidence when found (or not).
 */
function assertContains(
    string $testName,
    string $haystack,
    string $needle,
    string $evidenceLabel = 'Found'
): void {
    global $pass, $fail, $results;

    $found = str_contains($haystack, $needle);
    $status = $found ? 'PASS' : 'FAIL';

    if ($found) {
        $pass++;
        // Show a trimmed excerpt around the match
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

/**
 * Assert that $haystack does NOT contain $needle.
 */
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

/**
 * Assert that the integer count equals the expected value.
 */
function assertCount(
    string $testName,
    int $actual,
    int $expected,
    string $evidenceLabel = 'Count'
): void {
    global $pass, $fail, $results;

    $ok     = ($actual === $expected);
    $status = $ok ? 'PASS' : 'FAIL';

    if ($ok) {
        $pass++;
        $evidence = $evidenceLabel . ': ' . $actual . ' (expected ' . $expected . ')';
    } else {
        $fail++;
        $evidence = $evidenceLabel . ': ' . $actual . ' (expected ' . $expected . ')';
    }

    $results[] = compact('status', 'testName', 'evidence');
}

/**
 * Assert that $actual >= $min.
 */
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

/** Load a file and abort if missing. */
function loadFile(string $path): string {
    if (!file_exists($path)) {
        echo "FATAL: Cannot load file: {$path}\n";
        exit(2);
    }
    return file_get_contents($path);
}

// ──────────────────────────────────────────────────────────────────────────────
// Resolve paths relative to the project root (two levels up from this script)
// ──────────────────────────────────────────────────────────────────────────────

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    echo "FATAL: Cannot resolve project root from " . __DIR__ . "\n";
    exit(2);
}

$authLayout       = loadFile($root . '/views/layouts/auth.php');
$adminRequestShow = loadFile($root . '/views/admin/requests/show.php');
$staffRequestShow = loadFile($root . '/views/staff/requests/show.php');
$residentReqShow  = loadFile($root . '/views/resident/requests/show.php');
$appCss           = loadFile($root . '/public/assets/css/app.css');
$appJs            = loadFile($root . '/public/assets/js/app.js');
$homeView         = loadFile($root . '/views/home/index.php');

echo str_repeat('=', 72) . "\n";
echo "eMunicipal  —  Preservation Property Tests (Task 2)\n";
echo "Baseline confirmation on UNFIXED code.\n";
echo str_repeat('=', 72) . "\n\n";

// ══════════════════════════════════════════════════════════════════════════════
// GROUP 1: Sidebar active-link mechanism in auth.php (Req 3.3)
// ══════════════════════════════════════════════════════════════════════════════

echo "── Group 1: Sidebar active-link mechanism (auth.php) ──────────────────\n";

// The nav-link class conditional must be present for each portal block
assertContains(
    '1.1  Resident sidebar uses $activePage === "dashboard" for active class',
    $authLayout,
    '$activePage === \'dashboard\' ? \'active\' : \'\'',
    'Found active-class conditional'
);

assertContains(
    '1.2  Sidebar uses aria-current="page" with $activePage check',
    $authLayout,
    'aria-current="page"',
    'Found aria-current attribute'
);

// The aria-current is gated on activePage match, not always emitted
assertContains(
    '1.3  aria-current conditional is tied to $activePage comparison',
    $authLayout,
    '$activePage === \'dashboard\' ? \'aria-current="page"\' : \'\'',
    'Found aria-current conditional'
);

// Verify the pattern covers resident "requests" link too (representative sample)
assertContains(
    '1.4  Resident "My Requests" link uses $activePage === "requests" active class',
    $authLayout,
    '$activePage === \'requests\' ? \'active\' : \'\'',
    'Found requests active-class conditional'
);

// Verify Staff sidebar has the same active-link pattern
assertContains(
    '1.5  Staff sidebar contains active-link conditional for "requests"',
    $authLayout,
    '/staff/requests',
    'Found staff requests link href'
);

// Verify Admin sidebar has dashboard active-link
assertContains(
    '1.6  Admin sidebar contains active-link conditional for "users"',
    $authLayout,
    '$activePage === \'users\' ? \'active\' : \'\'',
    'Found admin users active-class conditional'
);

// ══════════════════════════════════════════════════════════════════════════════
// GROUP 2: CSRF token in sidebar logout form (Req 3.13)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── Group 2: CSRF token in sidebar logout form (auth.php) ──────────────\n";

assertContains(
    '2.1  Sidebar logout form POSTs to /logout',
    $authLayout,
    'action="<?= $appUrl ?>/logout"',
    'Found logout form action'
);

assertContains(
    '2.2  Sidebar logout form has method="POST"',
    $authLayout,
    'method="POST"',
    'Found POST method in logout form'
);

assertContains(
    '2.3  Sidebar logout form carries _csrf_token hidden input',
    $authLayout,
    'name="_csrf_token"',
    'Found _csrf_token hidden input'
);

assertContains(
    '2.4  Sidebar logout _csrf_token is generated by SecurityHelper::getCsrfToken()',
    $authLayout,
    'SecurityHelper::getCsrfToken()',
    'Found getCsrfToken() call'
);

// The sidebar must have its OWN logout form (the one in the nav, not just in the header dropdown)
// Confirm the sidebar nav section has the logout form
assertContains(
    '2.5  Sidebar logout button has type="submit"',
    $authLayout,
    'type="submit"',
    'Found submit button in sidebar logout form'
);

// ══════════════════════════════════════════════════════════════════════════════
// GROUP 3: Resident notification badge in sidebar (Req 3.14)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── Group 3: Resident notification badge in sidebar (auth.php) ─────────\n";

assertContains(
    '3.1  Resident sidebar renders notification badge element',
    $authLayout,
    '$unreadCount',
    'Found $unreadCount variable reference'
);

assertContains(
    '3.2  Notification badge uses $unreadCount > 0 condition',
    $authLayout,
    '$unreadCount > 0',
    'Found unread count conditional'
);

assertContains(
    '3.3  Sidebar notifications link points to /resident/notifications',
    $authLayout,
    '/resident/notifications',
    'Found notifications href'
);

// Badge is inside the Resident sidebar block (role check)
assertContains(
    '3.4  Notification badge is inside the resident role block',
    $authLayout,
    '$role === \'resident\'',
    'Found resident role conditional'
);

// The badge element uses rounded-pill styling or a badge class
assertContains(
    '3.5  Notification badge uses rounded-pill or badge class',
    $authLayout,
    'rounded-pill',
    'Found rounded-pill badge styling'
);

// ══════════════════════════════════════════════════════════════════════════════
// GROUP 4: Admin remark modals (Reject / Additional Info / Cancel) (Req 3.7)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── Group 4: Admin remark modals in admin/requests/show.php ────────────\n";

// Modal IDs are generated dynamically in both views via:
//   $modalId = 'modal-' . $next;
//   id="<?= $e($modalId) ?>"
// The literal strings "modal-rejected" etc. are never present in the PHP source.
// Instead we verify the dynamic ID pattern and the $needsRemark keys that drive it.

assertContains(
    '4.1  Admin: Modal IDs are built dynamically via $modalId = "modal-" . $next',
    $adminRequestShow,
    '$modalId     = \'modal-\' . $next;',
    'Found dynamic modal-ID assignment'
);

assertContains(
    '4.2  Admin: Reject modal form has _csrf_token hidden input',
    $adminRequestShow,
    'name="_csrf_token"',
    'Found _csrf_token in admin view'
);

assertContains(
    '4.3  Admin: Reject modal form has new_status hidden input',
    $adminRequestShow,
    'name="new_status"',
    'Found new_status hidden input in admin view'
);

// $needsRemark drives which statuses get modals — verify all three keys are present
assertContains(
    '4.4  Admin: $needsRemark includes STATUS_REJECTED (drives Reject modal)',
    $adminRequestShow,
    'RequestModel::STATUS_REJECTED',
    'Found STATUS_REJECTED key in $needsRemark'
);

assertContains(
    '4.5  Admin: $needsRemark includes STATUS_ADDITIONAL_INFO_REQUIRED (drives Add-Info modal)',
    $adminRequestShow,
    'RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED',
    'Found STATUS_ADDITIONAL_INFO_REQUIRED key in $needsRemark'
);

// STATUS_CANCELLED is the other modal trigger (checked separately in $hasModal condition)
assertContains(
    '4.5b Admin: STATUS_CANCELLED is an additional modal trigger in $hasModal condition',
    $adminRequestShow,
    'RequestModel::STATUS_CANCELLED',
    'Found STATUS_CANCELLED in $hasModal condition'
);

// Modals POST to the correct admin endpoint
assertContains(
    '4.6  Admin: Status-update modal form POSTs to /admin/requests/{id}/status',
    $adminRequestShow,
    '/admin/requests/',
    'Found admin requests action URL'
);

// Modal uses data-bs-backdrop="static" (consistent style)
assertContains(
    '4.7  Admin: Modals use data-bs-backdrop="static"',
    $adminRequestShow,
    'data-bs-backdrop="static"',
    'Found static backdrop on admin modal'
);

// Reject remark is required
assertContains(
    '4.8  Admin: Reject remark textarea is required',
    $adminRequestShow,
    'required',
    'Found required attribute on admin modal textarea'
);

// Verify the $needsRemark array controls required state
assertContains(
    '4.9  Admin: $needsRemark array defines required field for rejected',
    $adminRequestShow,
    '$needsRemark',
    'Found $needsRemark array reference in admin view'
);

// ══════════════════════════════════════════════════════════════════════════════
// GROUP 5: Staff remark modals (Req 3.7)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── Group 5: Staff remark modals in staff/requests/show.php ────────────\n";

// Modal IDs are also dynamic in the staff view via $modal = "modal-{$next}"
assertContains(
    '5.1  Staff: Modal IDs are built dynamically via $modal = "modal-{$next}"',
    $staffRequestShow,
    '"modal-{$next}"',
    'Found dynamic modal-ID interpolation in staff view'
);

assertContains(
    '5.2  Staff: Reject modal form has _csrf_token hidden input',
    $staffRequestShow,
    'name="_csrf_token"',
    'Found _csrf_token in staff view'
);

assertContains(
    '5.3  Staff: Reject modal form has new_status hidden input',
    $staffRequestShow,
    'name="new_status"',
    'Found new_status in staff view'
);

assertContains(
    '5.4  Staff: $needsRemark includes STATUS_REJECTED (drives Reject modal)',
    $staffRequestShow,
    'RequestModel::STATUS_REJECTED',
    'Found STATUS_REJECTED key in $needsRemark in staff view'
);

assertContains(
    '5.5  Staff: $needsRemark includes STATUS_ADDITIONAL_INFO_REQUIRED (drives Add-Info modal)',
    $staffRequestShow,
    'RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED',
    'Found STATUS_ADDITIONAL_INFO_REQUIRED key in staff view'
);

assertContains(
    '5.6  Staff: Status-update modal form POSTs to /staff/requests/{id}/status',
    $staffRequestShow,
    '/staff/requests/',
    'Found staff requests action URL'
);

assertContains(
    '5.7  Staff: Modals use data-bs-backdrop="static"',
    $staffRequestShow,
    'data-bs-backdrop="static"',
    'Found static backdrop on staff modal'
);

assertContains(
    '5.8  Staff: $needsRemark array controls required remark',
    $staffRequestShow,
    '$needsRemark',
    'Found $needsRemark array reference in staff view'
);

// ══════════════════════════════════════════════════════════════════════════════
// GROUP 6: data-loading double-submit prevention (Req 3.6 / Req 16.1)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── Group 6: data-loading double-submit prevention ──────────────────────\n";

// app.js must contain the data-loading handler
assertContains(
    '6.1  app.js: data-loading handler targets form[data-loading]',
    $appJs,
    'form[data-loading]',
    'Found data-loading selector in app.js'
);

assertContains(
    '6.2  app.js: data-loading handler disables the submit button',
    $appJs,
    'btn.disabled = true',
    'Found disabled assignment in app.js'
);

assertContains(
    '6.3  app.js: data-loading handler shows spinner markup',
    $appJs,
    'spinner-border',
    'Found spinner-border in app.js'
);

// Admin view: some forms carry data-loading
assertContains(
    '6.4  Admin view: modal forms carry data-loading attribute',
    $adminRequestShow,
    'data-loading=',
    'Found data-loading in admin request show view'
);

// Staff view: modal forms carry data-loading
assertContains(
    '6.5  Staff view: modal forms carry data-loading attribute',
    $staffRequestShow,
    'data-loading=',
    'Found data-loading in staff request show view'
);

// ══════════════════════════════════════════════════════════════════════════════
// GROUP 7: CSS baseline — before-state values (will change in the fix) (Req 2.2)
// These assertions verify the CURRENT (buggy) values, establishing the before-state.
// After the fix, these specific values will be absent (that's when the fix tests confirm).
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── Group 7: CSS baseline — before-state offsets (app.css) ─────────────\n";

// .sidebar rule: top: var(--header-height) — this is the UNFIXED value
assertContains(
    '7.1  CSS: .sidebar currently has "top: var(--header-height)" (pre-fix baseline)',
    $appCss,
    'top: var(--header-height)',
    'Found top: var(--header-height) in app.css'
);

// .auth-content rule: margin-top: var(--header-height) — UNFIXED value
assertContains(
    '7.2  CSS: .auth-content currently has "margin-top: var(--header-height)" (pre-fix baseline)',
    $appCss,
    'margin-top: var(--header-height)',
    'Found margin-top: var(--header-height) in app.css'
);

// .auth-content rule: min-height calc subtracts header height — UNFIXED value
assertContains(
    '7.3  CSS: .auth-content min-height uses calc(100vh - var(--header-height)) (pre-fix baseline)',
    $appCss,
    'calc(100vh - var(--header-height))',
    'Found calc(100vh - var(--header-height)) in app.css'
);

// Confirm --header-height is defined so the above references are valid
assertContains(
    '7.4  CSS: --header-height custom property is defined',
    $appCss,
    '--header-height: 60px',
    'Found --header-height: 60px definition'
);

// Confirm .sidebar rule also has "bottom: 0" (alternative height mechanism pre-fix)
assertContains(
    '7.5  CSS: .sidebar currently has "bottom: 0" (implicit height before fix)',
    $appCss,
    'bottom: 0',
    'Found bottom: 0 in .sidebar rule'
);

// ══════════════════════════════════════════════════════════════════════════════
// GROUP 8: All public landing-page section IDs present (Req 3.8)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── Group 8: Public landing-page sections present (views/home/index.php) \n";

assertContains(
    '8.1  Home page: #home / lp-hero section is present',
    $homeView,
    'id="home"',
    'Found id="home" section'
);

assertContains(
    '8.2  Home page: #about section is present',
    $homeView,
    'id="about"',
    'Found id="about" section'
);

assertContains(
    '8.3  Home page: #services section is present',
    $homeView,
    'id="services"',
    'Found id="services" section'
);

assertContains(
    '8.4  Home page: #how-it-works section is present',
    $homeView,
    'id="how-it-works"',
    'Found id="how-it-works" section'
);

assertContains(
    '8.5  Home page: #faqs section is present',
    $homeView,
    'id="faqs"',
    'Found id="faqs" section'
);

assertContains(
    '8.6  Home page: #contact section is present',
    $homeView,
    'id="contact"',
    'Found id="contact" section'
);

// FAQ accordion uses Bootstrap 5 accordion component (must survive the content expansion)
assertContains(
    '8.7  Home page: Bootstrap accordion markup is present (accordion-item)',
    $homeView,
    'class="accordion-item',
    'Found accordion-item class'
);

assertContains(
    '8.8  Home page: Bootstrap accordion uses aria-expanded for keyboard accessibility',
    $homeView,
    'aria-expanded=',
    'Found aria-expanded attribute in FAQ accordion'
);

assertContains(
    '8.9  Home page: Bootstrap accordion uses data-bs-toggle="collapse"',
    $homeView,
    'data-bs-toggle="collapse"',
    'Found data-bs-toggle collapse in FAQ accordion'
);

// Current FAQ array has 6 items (the pre-fix count — will become 15 after fix).
// We count by extracting the $faqs array definition from the source.
// The source has exactly 6 paired entries: ['Question', 'Answer'],
// Each entry is a PHP array literal starting with ['
// Count by the number of FAQ question strings in the array block.
// The most reliable proxy: count the array brackets that open a FAQ entry.
// Each entry starts with "['<Question text>",
// which in the source appears as: "['" at the start of an indented line
// inside $faqs = [ ... ] — i.e., we count "],\n" closing entries + final "]," or "];"
// Simpler: count the separator pattern "=> '" which appears once per entry (the answer string key).
// Actually the simplest: count occurrences of the literal pattern used in the loop:
//   foreach ($faqs as $i => $faq):
// exists once; the array has 6 two-element sub-arrays.
// Most robust: count occurrences of "],\n" within the $faqs block.

// Locate the $faqs array in the source
$faqsStart = strpos($homeView, '$faqs = [');
$faqsEnd   = strpos($homeView, '];', $faqsStart);
$faqsBlock = substr($homeView, $faqsStart, $faqsEnd - $faqsStart + 2);
// Each FAQ entry is a two-element array: ['Q', 'A']
// Count by number of opening "    ['" patterns (4-space indent + array open)
// The entries look like:   ['How do I...', 'Answer...'],
$faqEntryCount = preg_match_all("/\[\s*'/", $faqsBlock, $faqMatches);
assertCount(
    '8.10 Home page: FAQ $faqs array currently has exactly 6 entries (pre-fix baseline)',
    $faqEntryCount,
    6,
    'FAQ entry count in $faqs array'
);

// Announcement bar is present
assertContains(
    '8.11 Home page: Announcement bar is present',
    $homeView,
    'lp-announcement',
    'Found lp-announcement section'
);

// Hero CTAs intact
assertContains(
    '8.12 Home page: Hero "Request a Document" CTA is present',
    $homeView,
    'Request a Document',
    'Found Request a Document CTA'
);

// ══════════════════════════════════════════════════════════════════════════════
// GROUP 9: Additional preservation — data-confirm handler in app.js (Req 3.6)
// This handler must remain in app.js; it will simply no longer be triggered
// for Begin Verification after the fix (the form's data-confirm attr is removed).
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── Group 9: data-confirm handler preserved in app.js ──────────────────\n";

assertContains(
    '9.1  app.js: data-confirm handler is present (form[data-confirm])',
    $appJs,
    'form[data-confirm]',
    'Found data-confirm selector in app.js'
);

assertContains(
    '9.2  app.js: data-confirm handler calls window.confirm()',
    $appJs,
    'confirm(',
    'Found confirm( call in app.js'
);

assertContains(
    '9.3  app.js: data-confirm handler prevents default on cancel',
    $appJs,
    'e.preventDefault()',
    'Found preventDefault in data-confirm handler'
);

// ══════════════════════════════════════════════════════════════════════════════
// GROUP 10: Property-based coverage — sidebar active state for all roles
// Verify the active-link pattern is present for EVERY sidebar nav link
// across all three roles (resident, staff, admin) — exhaustive check.
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── Group 10: Exhaustive sidebar active-link coverage (all roles) ───────\n";

// Resident links
$residentLinks = [
    'dashboard'    => '/resident/dashboard',
    'new-request'  => '/resident/requests/new',
    'requests'     => '/resident/requests',
    'notifications'=> '/resident/notifications',
    'profile'      => '/resident/profile',
];
foreach ($residentLinks as $pageId => $href) {
    assertContains(
        '10.R Resident sidebar: "' . $pageId . '" link href present',
        $authLayout,
        $href,
        'Found href ' . $href
    );
}

// Staff links
$staffLinks = [
    'dashboard' => '/staff/dashboard',
    'requests'  => '/staff/requests',
    'profile'   => '/staff/profile',
];
foreach ($staffLinks as $pageId => $href) {
    assertContains(
        '10.S Staff sidebar: "' . $pageId . '" link href present',
        $authLayout,
        $href,
        'Found href ' . $href
    );
}

// Admin links
$adminLinks = [
    'dashboard'      => '/admin/dashboard',
    'users'          => '/admin/users',
    'requests'       => '/admin/requests',
    'document-types' => '/admin/document-types',
    'profile'        => '/admin/profile',
];
foreach ($adminLinks as $pageId => $href) {
    assertContains(
        '10.A Admin sidebar: "' . $pageId . '" link href present',
        $authLayout,
        $href,
        'Found href ' . $href
    );
}

// ══════════════════════════════════════════════════════════════════════════════
// GROUP 11: Auth layout structural landmarks (survive the header removal)
// ══════════════════════════════════════════════════════════════════════════════

echo "\n── Group 11: Auth layout structural landmarks ──────────────────────────\n";

assertContains(
    '11.1  Auth layout: sidebar-overlay div is present (needed for mobile)',
    $authLayout,
    'class="sidebar-overlay"',
    'Found sidebar-overlay div'
);

assertContains(
    '11.2  Auth layout: sidebar nav element is present',
    $authLayout,
    'class="sidebar"',
    'Found sidebar nav element'
);

assertContains(
    '11.3  Auth layout: main.auth-content is present',
    $authLayout,
    'class="auth-content"',
    'Found auth-content main element'
);

assertContains(
    '11.4  Auth layout: sidebar brand block is present',
    $authLayout,
    'sidebar-brand',
    'Found sidebar-brand block'
);

assertContains(
    '11.5  Auth layout: sidebar-nav ul is present',
    $authLayout,
    'sidebar-nav',
    'Found sidebar-nav ul'
);

assertContains(
    '11.6  Auth layout: sidebar section labels are present',
    $authLayout,
    'sidebar-section-label',
    'Found sidebar-section-label elements'
);

assertContains(
    '11.7  Auth layout: Bootstrap JS bundle script tag is present',
    $authLayout,
    'bootstrap.bundle.min.js',
    'Found Bootstrap bundle script'
);

assertContains(
    '11.8  Auth layout: app.js script tag is present',
    $authLayout,
    '/public/assets/js/app.js',
    'Found app.js script tag'
);

assertContains(
    '11.9  Auth layout: flash success alert structure is present',
    $authLayout,
    'alert-success',
    'Found flash success alert'
);

assertContains(
    '11.10 Auth layout: flash error alert structure is present',
    $authLayout,
    'alert-danger',
    'Found flash error alert'
);

assertContains(
    '11.11 Auth layout: breadcrumb rendering block is present',
    $authLayout,
    'breadcrumb-item',
    'Found breadcrumb-item element'
);

// ══════════════════════════════════════════════════════════════════════════════
// RESULTS SUMMARY
// ══════════════════════════════════════════════════════════════════════════════

echo "\n" . str_repeat('=', 72) . "\n";
echo "RESULTS\n";
echo str_repeat('=', 72) . "\n\n";

foreach ($results as $r) {
    $icon = $r['status'] === 'PASS' ? '✓' : '✗';
    printf("  [%s] %s\n      %s\n\n", $r['status'], $r['testName'], $r['evidence']);
}

echo str_repeat('-', 72) . "\n";
$total = $pass + $fail;
printf("  Total: %d   PASS: %d   FAIL: %d\n", $total, $pass, $fail);
echo str_repeat('-', 72) . "\n\n";

if ($fail === 0) {
    echo "  ✓  ALL PRESERVATION TESTS PASS — baseline confirmed.\n";
    echo "     Safe to begin fix implementation (Tasks 3–8).\n\n";
    exit(0);
} else {
    echo "  ✗  {$fail} PRESERVATION TEST(S) FAILED.\n";
    echo "     The baseline could not be established on unfixed code.\n";
    echo "     Review the failures above before applying any fix.\n\n";
    exit(1);
}
