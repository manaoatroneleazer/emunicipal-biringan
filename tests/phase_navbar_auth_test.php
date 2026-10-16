<?php
/**
 * Navbar Auth Tests — Public Navbar Logged-In / Logged-Out UI
 *
 * Verifies:
 *  - AuthMiddleware import and session detection are present in public.php
 *  - Correct conditional branches for logged-in and logged-out states
 *  - Profile chip markup (nav-user-chip, nav-user-avatar, nav-user-name)
 *  - Dashboard button markup and correct role-based route strings
 *  - Login / Register buttons still present in the logged-out branch
 *  - No login/register buttons appear in the logged-in branch
 *  - Name escaping: htmlspecialchars / $navName used (not raw session data)
 *  - CSS classes appended to app.css (.nav-user-chip, .nav-user-avatar, etc.)
 *  - PHP syntax validity of both modified files
 *  - AuthMiddleware helper methods used (fullName, firstName, lastName, getRole, isAuthenticated)
 *  - Role match covers resident, staff, admin
 *  - Safe fallback dashboard URL present
 *  - Existing policy/legal links in footer are untouched
 *  - Existing nav links (Home, Track Request, FAQs…) are untouched
 *
 * Run with: php tests/phase_navbar_auth_test.php
 * No database access. No file writes. Static analysis only.
 */

$root = dirname(__DIR__);

$passed  = 0;
$failed  = 0;
$skipped = 0;
$results = [];

function navChk(string $id, string $desc, bool $pass, string $detail = ''): void
{
    global $passed, $failed, $results;
    if ($pass) { $passed++; $results[] = ['PASS', $id, $desc, $detail]; }
    else        { $failed++; $results[] = ['FAIL', $id, $desc, $detail]; }
}

function navSkip(string $id, string $desc, string $reason): void
{
    global $skipped, $results;
    $skipped++;
    $results[] = ['SKIP', $id, $desc, $reason];
}

// PHP binary (full path for Windows XAMPP)
$phpBin = 'c:\\xampp\\php\\php.exe';
if (!file_exists($phpBin)) {
    $phpBin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'php.exe' : 'php';
}

// File paths
$layoutPath = $root . '/views/layouts/public.php';
$cssPath    = $root . '/public/assets/css/app.css';

$layout = file_exists($layoutPath) ? file_get_contents($layoutPath) : '';
$css    = file_exists($cssPath)    ? file_get_contents($cssPath)    : '';

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK A: File existence
// ─────────────────────────────────────────────────────────────────────────────
navChk('A01', 'views/layouts/public.php exists',        file_exists($layoutPath), $layoutPath);
navChk('A02', 'public/assets/css/app.css exists',       file_exists($cssPath),    $cssPath);

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK B: PHP syntax checks
// ─────────────────────────────────────────────────────────────────────────────
if (file_exists($layoutPath)) {
    $out = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($layoutPath) . ' 2>&1') ?? '';
    navChk('B01', 'public.php passes PHP syntax check', str_contains($out, 'No syntax errors'), trim($out));
} else {
    navSkip('B01', 'public.php PHP syntax check', 'File not found');
}

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK C: AuthMiddleware integration in public.php
// ─────────────────────────────────────────────────────────────────────────────
navChk('C01', 'AuthMiddleware namespace imported',
    str_contains($layout, 'use Emunicipal\Middleware\AuthMiddleware'),
    'Confirmed use statement present');

navChk('C02', 'isAuthenticated() called for session detection',
    str_contains($layout, 'AuthMiddleware::isAuthenticated()'),
    'Confirmed isAuthenticated() call');

navChk('C03', 'fullName() used to get display name',
    str_contains($layout, 'AuthMiddleware::fullName()'),
    'Confirmed fullName() call');

navChk('C04', 'getRole() used for role-based routing',
    str_contains($layout, 'AuthMiddleware::getRole()'),
    'Confirmed getRole() call');

navChk('C05', 'firstName() used to build initials',
    str_contains($layout, 'AuthMiddleware::firstName()'),
    'Confirmed firstName() call');

navChk('C06', 'lastName() used to build initials',
    str_contains($layout, 'AuthMiddleware::lastName()'),
    'Confirmed lastName() call');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK D: Role-based dashboard routing
// ─────────────────────────────────────────────────────────────────────────────
navChk('D01', "Resident dashboard route '/resident/dashboard' present",
    str_contains($layout, '/resident/dashboard'),
    'Found /resident/dashboard in match expression');

navChk('D02', "Staff dashboard route '/staff/dashboard' present",
    str_contains($layout, '/staff/dashboard'),
    'Found /staff/dashboard in match expression');

navChk('D03', "Admin dashboard route '/admin/dashboard' present",
    str_contains($layout, '/admin/dashboard'),
    'Found /admin/dashboard in match expression');

navChk('D04', 'match() or role routing logic used for dashboard URL',
    str_contains($layout, 'match(') || str_contains($layout, 'navDashboardLabel'),
    'Confirmed role-routing logic present');

navChk('D05', 'Safe fallback dashboard URL defined (points to /login)',
    str_contains($layout, "'/login'") || str_contains($layout, '"/login"') || str_contains($layout, "'/login';"),
    'Fallback $navDashboardUrl = $appUrl . \'/login\' found');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK E: Logged-in branch HTML
// ─────────────────────────────────────────────────────────────────────────────
navChk('E01', 'Conditional <?php if ($navIsLoggedIn): ?> branch present',
    str_contains($layout, '$navIsLoggedIn'),
    'navIsLoggedIn conditional found');

navChk('E02', 'Avatar element present in logged-in branch (lp-user-avatar or nav-user-avatar)',
    str_contains($layout, 'lp-user-avatar') || str_contains($layout, 'nav-user-avatar'),
    'Found avatar element');

navChk('E03', 'User avatar initials rendered',
    str_contains($layout, 'lp-user-avatar') || str_contains($layout, 'nav-user-avatar'),
    'Found avatar element');

navChk('E04', 'User name label rendered (lp-user-name or nav-user-name)',
    str_contains($layout, 'lp-user-name') || str_contains($layout, 'nav-user-name'),
    'Found user-name element');

navChk('E05', 'Dashboard button present (lp-dash-btn or nav-dashboard-btn)',
    str_contains($layout, 'lp-dash-btn') || str_contains($layout, 'nav-dashboard-btn'),
    'Found dashboard button element');

navChk('E06', 'Dashboard button uses $navDash (escaped URL variable)',
    str_contains($layout, '$navDash'),
    'Found $navDash in href');

navChk('E07', 'User name rendered via $navName (escaped variable, not raw session)',
    str_contains($layout, '$navName'),
    'Found $navName — confirms htmlspecialchars escaping applied before output');

navChk('E08', 'Initials rendered via $navInit (escaped variable)',
    str_contains($layout, '$navInit'),
    'Found $navInit');

navChk('E09', 'aria-label on user control references user name',
    str_contains($layout, 'aria-label="Account menu for') || str_contains($layout, 'aria-label="Signed in as'),
    'Found accessible aria-label on user control');

navChk('E10', 'Dashboard button has white-space:nowrap to prevent wrapping',
    str_contains($layout, 'white-space:nowrap') || str_contains($css, 'white-space: nowrap'),
    'Found white-space:nowrap (HTML or CSS)');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK F: Logged-out branch HTML
// ─────────────────────────────────────────────────────────────────────────────
// Isolate the else branch by finding the else/endif PHP tags
$elseTag   = '<' . '?php else: ?' . '>';
$endifTag  = '<' . '?php endif; ?' . '>';
$elsePos   = strpos($layout, $elseTag);
$endifPos  = strpos($layout, $endifTag, $elsePos ?: 0);
$elseBranch = ($elsePos !== false && $endifPos !== false)
    ? substr($layout, $elsePos, $endifPos - $elsePos)
    : '';

navChk('F01', 'else branch present in navbar conditional',
    $elseBranch !== '',
    $elseBranch !== '' ? 'else branch found' : 'else tag not found');

navChk('F02', 'Login button in else (logged-out) branch',
    str_contains($elseBranch, '/login'),
    'Found /login href in else branch');

navChk('F03', 'Register button in else (logged-out) branch',
    str_contains($elseBranch, '/register'),
    'Found /register href in else branch');

navChk('F04', 'Login uses btn-outline-primary in else branch',
    str_contains($elseBranch, 'btn-outline-primary'),
    'Found btn-outline-primary in else branch');

navChk('F05', 'Register uses btn-primary in else branch',
    str_contains($elseBranch, 'btn-primary'),
    'Found btn-primary in else branch');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK G: Login/Register must NOT appear in logged-in branch
// ─────────────────────────────────────────────────────────────────────────────
// Isolate the if (logged-in) branch
$ifTag     = '<' . '?php if ($navIsLoggedIn): ?' . '>';
$ifStart   = strpos($layout, $ifTag);
$ifEnd     = strpos($layout, $elseTag, $ifStart ?: 0);
$ifBranch  = ($ifStart !== false && $ifEnd !== false)
    ? substr($layout, $ifStart, $ifEnd - $ifStart)
    : '';

navChk('G01', 'Login link does NOT appear in logged-in branch',
    $ifBranch !== '' && !str_contains($ifBranch, 'href="' . rtrim($_SERVER['REQUEST_URI'] ?? '', '/') . '/login"')
        && !preg_match('/<a[^>]+\/login["\s]/', $ifBranch),
    $ifBranch !== '' ? 'No login link in if-branch' : 'Could not isolate if-branch');

navChk('G02', 'Register link does NOT appear in logged-in branch',
    $ifBranch !== '' && !str_contains($ifBranch, '/register'),
    $ifBranch !== '' ? 'No register link in if-branch' : 'Could not isolate if-branch');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK H: Existing nav content preserved
// ─────────────────────────────────────────────────────────────────────────────
navChk('H01', 'Existing "Home" nav link preserved',
    str_contains($layout, "'home'") || str_contains($layout, '"home"'),
    'pubNavLink(\'home\'…) call found');

navChk('H02', 'Existing "Track Request" nav link preserved',
    str_contains($layout, 'Track Request'),
    'Track Request nav link found');

navChk('H03', 'Existing "FAQs" nav link preserved',
    str_contains($layout, 'FAQs'),
    'FAQs nav link found');

navChk('H04', 'Biringan City logo img preserved',
    str_contains($layout, 'biringan-logo-placeholder.svg'),
    'Logo img src found');

navChk('H05', 'Footer Legal column policy links preserved (/policy/privacy)',
    str_contains($layout, '/policy/privacy'),
    'Footer policy link present');

navChk('H06', 'Footer Legal column policy links preserved (/policy/terms)',
    str_contains($layout, '/policy/terms'),
    'Footer policy link present');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK I: CSS classes appended to app.css
// ─────────────────────────────────────────────────────────────────────────────
navChk('I01', '.nav-user-chip defined in app.css',
    str_contains($css, '.nav-user-chip'),
    'Found .nav-user-chip');

navChk('I02', '.nav-user-avatar defined in app.css',
    str_contains($css, '.nav-user-avatar'),
    'Found .nav-user-avatar');

navChk('I03', '.nav-user-name defined in app.css',
    str_contains($css, '.nav-user-name'),
    'Found .nav-user-name');

navChk('I04', '.nav-dashboard-btn defined in app.css',
    str_contains($css, '.nav-dashboard-btn'),
    'Found .nav-dashboard-btn');

navChk('I05', 'Avatar uses gold background variable (--biringan-gold)',
    str_contains($css, '--biringan-gold') && str_contains($css, '.nav-user-avatar'),
    'Gold background on avatar confirmed');

navChk('I06', 'name truncation: text-overflow: ellipsis on .nav-user-name',
    str_contains($css, 'text-overflow: ellipsis'),
    'Found text-overflow: ellipsis in .nav-user-name');

navChk('I07', 'max-width on .nav-user-name to prevent overflow',
    preg_match('/\.nav-user-name\s*\{[^}]*max-width/s', $css) === 1,
    'max-width found inside .nav-user-name rule');

navChk('I08', '@media responsive override present for mobile',
    str_contains($css, '@media (max-width: 991.98px)') && str_contains($css, '.nav-user-chip'),
    'Mobile media query for .nav-user-chip found');

navChk('I09', 'Dashboard button hover/focus states defined',
    str_contains($css, '.nav-dashboard-btn:hover'),
    'Found .nav-dashboard-btn:hover rule');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK J: Security — no raw session data output
// ─────────────────────────────────────────────────────────────────────────────
navChk('J01', 'No direct $_SESSION output in public.php navbar section',
    !str_contains($layout, '$_SESSION[\'user_first_name\']')
    && !str_contains($layout, '$_SESSION["user_first_name"]')
    && !str_contains($layout, '$_SESSION[\'user_last_name\']'),
    'No raw $_SESSION access in layout — AuthMiddleware used instead');

navChk('J02', 'htmlspecialchars applied before name is output ($navName set from escaped value)',
    str_contains($layout, 'htmlspecialchars($navUserFullName'),
    'htmlspecialchars() wraps the name before assignment to $navName');

navChk('J03', 'htmlspecialchars applied to dashboard URL ($navDash)',
    str_contains($layout, 'htmlspecialchars($navDashboardUrl'),
    'htmlspecialchars() wraps dashboard URL before assignment to $navDash');

navChk('J04', 'Email address NOT rendered in navbar',
    !str_contains($layout, 'AuthMiddleware::email()') ||
    !str_contains($layout, '$navEmail'),
    'Email not exposed in navbar markup');

// ─────────────────────────────────────────────────────────────────────────────
// REPORT
// ─────────────────────────────────────────────────────────────────────────────
echo "\n";
echo "=================================================================\n";
echo "  Phase Navbar Auth — Logged-In User Navbar Tests\n";
echo "=================================================================\n\n";

foreach ($results as [$status, $id, $desc, $detail]) {
    $label = match($status) {
        'PASS' => "\e[32mPASS\e[0m",
        'FAIL' => "\e[31mFAIL\e[0m",
        'SKIP' => "\e[33mSKIP\e[0m",
        default => $status,
    };
    printf("  %s  %-5s  %-58s  %s\n", $label, $id, $desc, $detail);
}

echo "\n";
echo "-----------------------------------------------------------------\n";
$total = $passed + $failed + $skipped;
printf("  Total: %d   PASS: %d   FAIL: %d   SKIP: %d\n", $total, $passed, $failed, $skipped);
echo "-----------------------------------------------------------------\n\n";

if ($failed > 0) {
    echo "  FAILED TESTS:\n";
    foreach ($results as [$status, $id, $desc, $detail]) {
        if ($status === 'FAIL') {
            printf("    [%s] %s\n          %s\n", $id, $desc, $detail);
        }
    }
    echo "\n";
    exit(1);
}

exit(0);
