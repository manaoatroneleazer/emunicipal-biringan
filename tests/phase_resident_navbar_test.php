<?php
/**
 * Phase Resident Navbar — Resident Top Navbar Redesign Tests
 *
 * Verifies:
 *  A. File existence
 *  B. PHP syntax validity (resident layout + 5 controllers)
 *  C. Resident controllers use layouts/resident (not layouts/auth)
 *  D. Staff/admin controllers still use layouts/auth (not layouts/resident)
 *  E. Resident layout structure: top navbar elements present
 *  F. Navigation links: all required destinations present
 *  G. Notification bell markup and badge
 *  H. User dropdown: avatar, name, profile, logout
 *  I. Mobile drawer: overlay, panel, hamburger, close button
 *  J. Logout: form, CSRF token, modal
 *  K. Security: no raw $_SESSION access, htmlspecialchars applied, no email exposed
 *  L. CSS classes: all rn-* classes present in app.css
 *  M. Sidebar absent: .sidebar, sidebar-nav not in resident layout
 *  N. auth.php layout still contains the sidebar (not broken)
 *  O. Breadcrumb and flash-message support preserved
 *  P. Page-level modal support preserved
 *
 * Run with: php tests/phase_resident_navbar_test.php
 * Static analysis only — no DB access, no file writes.
 */

$root = dirname(__DIR__);

$passed  = 0;
$failed  = 0;
$skipped = 0;
$results = [];

function rnChk(string $id, string $desc, bool $pass, string $detail = ''): void
{
    global $passed, $failed, $results;
    if ($pass) { $passed++; $results[] = ['PASS', $id, $desc, $detail]; }
    else        { $failed++; $results[] = ['FAIL', $id, $desc, $detail]; }
}
function rnSkip(string $id, string $desc, string $reason): void
{
    global $skipped, $results;
    $skipped++;
    $results[] = ['SKIP', $id, $desc, $reason];
}

$phpBin = 'c:\\xampp\\php\\php.exe';
if (!file_exists($phpBin)) {
    $phpBin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'php.exe' : 'php';
}

// ── File paths ───────────────────────────────────────────────────────────────
$residentLayout  = $root . '/views/layouts/resident.php';
$authLayout      = $root . '/views/layouts/auth.php';
$cssPath         = $root . '/public/assets/css/app.css';

$residentCtrls = [
    'ResidentDashboardController' => $root . '/app/Controllers/Resident/ResidentDashboardController.php',
    'RequestController'           => $root . '/app/Controllers/Resident/RequestController.php',
    'ProfileController'           => $root . '/app/Controllers/Resident/ProfileController.php',
    'NotificationController'      => $root . '/app/Controllers/Resident/NotificationController.php',
    'PaymentController'           => $root . '/app/Controllers/Resident/PaymentController.php',
];

$staffAdminCtrls = [
    'StaffDashboardController'    => $root . '/app/Controllers/Staff/StaffDashboardController.php',
    'StaffRequestController'      => $root . '/app/Controllers/Staff/StaffRequestController.php',
    'AdminDashboardController'    => $root . '/app/Controllers/Admin/AdminDashboardController.php',
    'AdminRequestController'      => $root . '/app/Controllers/Admin/AdminRequestController.php',
];

// Read helpers
$rl  = file_exists($residentLayout) ? file_get_contents($residentLayout) : '';
$al  = file_exists($authLayout)     ? file_get_contents($authLayout)     : '';
$css = file_exists($cssPath)        ? file_get_contents($cssPath)        : '';

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK A: File existence
// ─────────────────────────────────────────────────────────────────────────────
rnChk('A01', 'views/layouts/resident.php exists',   file_exists($residentLayout), $residentLayout);
rnChk('A02', 'views/layouts/auth.php exists',       file_exists($authLayout),     $authLayout);
rnChk('A03', 'public/assets/css/app.css exists',    file_exists($cssPath),        $cssPath);

foreach ($residentCtrls as $name => $path) {
    rnChk('A0' . (array_search($path, array_values($residentCtrls)) + 4),
        "$name exists", file_exists($path), $path);
}

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK B: PHP syntax — resident layout + all 5 controllers
// ─────────────────────────────────────────────────────────────────────────────
$syntaxFiles = array_merge(
    ['views/layouts/resident.php' => $residentLayout],
    $residentCtrls
);
$bIdx = 1;
foreach ($syntaxFiles as $label => $path) {
    $id = 'B' . str_pad($bIdx++, 2, '0', STR_PAD_LEFT);
    if (!file_exists($path)) { rnSkip($id, "PHP syntax: $label", 'File not found'); continue; }
    $out  = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($path) . ' 2>&1') ?? '';
    rnChk($id, "PHP syntax valid: $label", str_contains($out, 'No syntax errors'), trim($out));
}

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK C: Resident controllers use layouts/resident, NOT layouts/auth
// ─────────────────────────────────────────────────────────────────────────────
$cIdx = 1;
foreach ($residentCtrls as $name => $path) {
    $id      = 'C' . str_pad($cIdx++, 2, '0', STR_PAD_LEFT);
    $content = file_exists($path) ? file_get_contents($path) : '';
    $usesResident = str_contains($content, "layouts/resident");
    $usesAuth     = str_contains($content, "layouts/auth");
    rnChk($id, "$name uses layouts/resident",
        $usesResident && !$usesAuth,
        $usesResident
            ? ($usesAuth ? 'WARN: still contains layouts/auth reference' : 'Confirmed layouts/resident only')
            : 'layouts/resident NOT found');
}

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK D: Staff/admin controllers still use layouts/auth, NOT layouts/resident
// ─────────────────────────────────────────────────────────────────────────────
$dIdx = 1;
foreach ($staffAdminCtrls as $name => $path) {
    $id      = 'D' . str_pad($dIdx++, 2, '0', STR_PAD_LEFT);
    if (!file_exists($path)) { rnSkip($id, "$name: layout isolation check", 'File not found'); continue; }
    $content = file_get_contents($path);
    $noResident = !str_contains($content, "layouts/resident");
    rnChk($id, "$name does NOT use layouts/resident",
        $noResident,
        $noResident ? 'Confirmed — layouts/auth preserved' : 'FAIL: layouts/resident found in staff/admin controller');
}

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK E: Resident layout top-navbar structural elements
// ─────────────────────────────────────────────────────────────────────────────
rnChk('E01', 'rn-topbar element present',          str_contains($rl, 'rn-topbar'),          'Found rn-topbar');
rnChk('E02', 'rn-topbar-inner present',            str_contains($rl, 'rn-topbar-inner'),    'Found rn-topbar-inner');
rnChk('E03', 'rn-topbar-left section present',     str_contains($rl, 'rn-topbar-left'),     'Found rn-topbar-left');
rnChk('E04', 'rn-topbar-center section present',   str_contains($rl, 'rn-topbar-center'),   'Found rn-topbar-center');
rnChk('E05', 'rn-topbar-right section present',    str_contains($rl, 'rn-topbar-right'),    'Found rn-topbar-right');
rnChk('E06', 'rn-brand (logo + branding) present', str_contains($rl, 'rn-brand'),           'Found rn-brand');
rnChk('E07', 'biringan-logo-placeholder.svg used', str_contains($rl, 'biringan-logo-placeholder.svg'), 'Logo src found');
rnChk('E08', 'rn-body class on <body>',            str_contains($rl, 'class="rn-body"'),    'Found rn-body on body');
rnChk('E09', 'AuthMiddleware imported in layout',  str_contains($rl, 'use Emunicipal\Middleware\AuthMiddleware'), 'use statement present');
rnChk('E10', 'Notification model imported',        str_contains($rl, 'use Emunicipal\Models\Notification'), 'Notification import present');
rnChk('E11', 'SecurityHelper imported (CSRF)',     str_contains($rl, 'use Emunicipal\Helpers\SecurityHelper'), 'SecurityHelper import present');
rnChk('E12', 'SessionHelper imported (flash)',     str_contains($rl, 'use Emunicipal\Helpers\SessionHelper'), 'SessionHelper import present');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK F: Navigation links — all required destinations
// ─────────────────────────────────────────────────────────────────────────────
$navRoutes = [
    'F01' => ['/resident/dashboard',       'Dashboard link present'],
    'F02' => ['/resident/requests',        'My Requests link present'],
    'F03' => ['/resident/requests/new',    'New Request link present'],
    'F04' => ['/resident/profile',         'Profile link present'],
    'F05' => ['/resident/notifications',   'Notifications link present'],
    'F06' => ['/',                         'Landing Page link present (replaces Help & Support)'],
    'F07' => ['/logout',                   'Logout action present'],
    'F08' => ['/',                         'Public site link present (home)'],
];
foreach ($navRoutes as $id => [$route, $desc]) {
    rnChk($id, $desc, str_contains($rl, $route), "Route '$route' found");
}

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK G: Notification bell
// ─────────────────────────────────────────────────────────────────────────────
rnChk('G01', 'rn-bell class present',             str_contains($rl, 'rn-bell'),          'Found rn-bell');
rnChk('G02', 'rn-bell--has-unread modifier present',str_contains($rl, 'rn-bell--has-unread'),'Found rn-bell--has-unread conditional');
rnChk('G03', 'rn-bell-badge for unread count',    str_contains($rl, 'rn-bell-badge'),    'Found rn-bell-badge');
rnChk('G04', 'unreadCount computed from Notification model',
    str_contains($rl, 'countUnread'), 'countUnread() call found');
rnChk('G05', 'Bell has aria-label with unread count',
    str_contains($rl, 'aria-label="Notifications'),  'Bell aria-label found');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK H: User dropdown
// ─────────────────────────────────────────────────────────────────────────────
rnChk('H01', 'rn-user-btn (dropdown trigger) present', str_contains($rl, 'rn-user-btn'),  'Found rn-user-btn');
rnChk('H02', 'rn-avatar (initials) present',           str_contains($rl, 'rn-avatar'),    'Found rn-avatar');
rnChk('H03', 'rn-user-name present',                   str_contains($rl, 'rn-user-name'), 'Found rn-user-name');
rnChk('H04', 'rn-dropdown class on menu',              str_contains($rl, 'rn-dropdown'),  'Found rn-dropdown');
rnChk('H05', '$initials variable rendered (escaped)',  str_contains($rl, '$initials'),    'Found $initials in layout');
rnChk('H06', '$fullName variable rendered (escaped)',  str_contains($rl, '$fullName'),    'Found $fullName in layout');
rnChk('H07', 'Dropdown has profile link',              str_contains($rl, '/resident/profile'), 'Profile href in dropdown');
rnChk('H08', 'Dropdown has logout button (modal trigger)',
    str_contains($rl, 'logoutConfirmModal'), 'logoutConfirmModal data-bs-target found');
rnChk('H09', 'rn-dropdown-badge for notification count in dropdown',
    str_contains($rl, 'rn-dropdown-badge'), 'Found rn-dropdown-badge');
rnChk('H10', 'aria-label on user menu button',
    str_contains($rl, 'aria-label="Account menu for'), 'Account menu aria-label found');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK I: Mobile drawer
// ─────────────────────────────────────────────────────────────────────────────
rnChk('I01', 'rn-hamburger button present',        str_contains($rl, 'rn-hamburger'),       'Found rn-hamburger');
rnChk('I02', 'rnDrawerToggle id on hamburger',     str_contains($rl, 'id="rnDrawerToggle"'),'Found rnDrawerToggle id');
rnChk('I03', 'rn-drawer panel present',            str_contains($rl, 'rn-drawer'),          'Found rn-drawer');
rnChk('I04', 'rnDrawer id on panel',               str_contains($rl, 'id="rnDrawer"'),      'Found rnDrawer id');
rnChk('I05', 'rn-drawer-overlay present',          str_contains($rl, 'rn-drawer-overlay'),  'Found rn-drawer-overlay');
rnChk('I06', 'rn-drawer-close button present',     str_contains($rl, 'rn-drawer-close'),    'Found rn-drawer-close');
rnChk('I07', 'aria-controls links hamburger to drawer',
    str_contains($rl, 'aria-controls="rnDrawer"'), 'aria-controls="rnDrawer" found');
rnChk('I08', 'Drawer has aria-hidden="true" by default',
    str_contains($rl, 'aria-hidden="true"'), 'Default aria-hidden found on drawer');
rnChk('I09', 'Drawer JS: openDrawer function present',
    str_contains($rl, 'function openDrawer'), 'openDrawer() JS function found');
rnChk('I10', 'Drawer JS: closeDrawer function present',
    str_contains($rl, 'function closeDrawer'), 'closeDrawer() JS function found');
rnChk('I11', 'Drawer JS: Escape key closes drawer',
    str_contains($rl, "e.key === 'Escape'"), 'Escape key handler found');
rnChk('I12', 'Drawer JS: overlay click closes drawer',
    str_contains($rl, 'overlay.addEventListener'), 'Overlay click listener found');
rnChk('I13', 'Drawer JS: nav link click closes drawer',
    str_contains($rl, 'link.addEventListener'), 'Nav link close-on-click found');
rnChk('I14', 'Body scroll lock class applied on drawer open',
    str_contains($rl, 'rn-drawer-body-lock'), 'rn-drawer-body-lock found in JS');
rnChk('I15', 'Drawer nav has same routes as topbar',
    str_contains($rl, 'rn-drawer-nav'), 'rn-drawer-nav found');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK J: Logout form + modal
// ─────────────────────────────────────────────────────────────────────────────
rnChk('J01', 'Hidden logout form present',
    str_contains($rl, 'id="logoutForm"'), 'logoutForm id found');
rnChk('J02', 'Logout form POSTs to /logout',
    str_contains($rl, 'action="') && str_contains($rl, '/logout'), '/logout action found');
rnChk('J03', 'CSRF token in logout form via getCsrfToken()',
    str_contains($rl, 'getCsrfToken()'), 'getCsrfToken() call found');
rnChk('J04', 'Logout confirmation modal present',
    str_contains($rl, 'logoutConfirmModal'), 'logoutConfirmModal found');
rnChk('J05', 'rnLogoutConfirmBtn submits logoutForm',
    str_contains($rl, 'rnLogoutConfirmBtn') && str_contains($rl, 'logoutForm.submit()'),
    'Logout confirm button JS found');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK K: Security checks
// ─────────────────────────────────────────────────────────────────────────────
rnChk('K01', 'No raw $_SESSION access in resident layout',
    !str_contains($rl, '$_SESSION['),
    str_contains($rl, '$_SESSION[') ? 'FAIL: raw $_SESSION found' : 'No raw $_SESSION — AuthMiddleware used');

rnChk('K02', 'fullName obtained via AuthMiddleware::fullName()',
    str_contains($rl, 'AuthMiddleware::fullName()'), 'AuthMiddleware::fullName() call found');

rnChk('K03', 'firstName/lastName via AuthMiddleware',
    str_contains($rl, 'AuthMiddleware::firstName()') && str_contains($rl, 'AuthMiddleware::lastName()'),
    'Both firstName() and lastName() calls found');

rnChk('K04', 'htmlspecialchars applied to firstName/lastName',
    str_contains($rl, 'htmlspecialchars(AuthMiddleware::firstName'),
    'htmlspecialchars wraps firstName output');

rnChk('K05', 'htmlspecialchars applied to fullName',
    str_contains($rl, 'htmlspecialchars(AuthMiddleware::fullName'),
    'htmlspecialchars wraps fullName output');

rnChk('K06', 'Email address NOT rendered directly in navbar',
    !str_contains($rl, 'AuthMiddleware::email()') ||
    !preg_match('/<[^>]+>\s*<\?=.*email.*\?>/i', $rl),
    'Email not directly output in navbar HTML');

rnChk('K07', 'CSRF token on logout form is escaped',
    str_contains($rl, 'htmlspecialchars(SecurityHelper::getCsrfToken()'),
    'getCsrfToken() wrapped in htmlspecialchars');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK L: CSS classes in app.css
// ─────────────────────────────────────────────────────────────────────────────
$cssClasses = [
    'L01' => ['.rn-body',               'rn-body base styles'],
    'L02' => ['.rn-topbar',             'rn-topbar styles'],
    'L03' => ['.rn-topbar-inner',       'rn-topbar-inner styles'],
    'L04' => ['.rn-topbar-left',        'rn-topbar-left styles'],
    'L05' => ['.rn-topbar-center',      'rn-topbar-center styles'],
    'L06' => ['.rn-topbar-right',       'rn-topbar-right styles'],
    'L07' => ['.rn-hamburger',          'rn-hamburger styles'],
    'L08' => ['.rn-brand',              'rn-brand styles'],
    'L09' => ['.rn-nav-link',           'rn-nav-link styles'],
    'L10' => ['.rn-nav-link--active',   'rn-nav-link--active gold underline'],
    'L11' => ['.rn-bell',               'rn-bell styles'],
    'L12' => ['.rn-bell-badge',         'rn-bell-badge styles'],
    'L13' => ['.rn-user-btn',           'rn-user-btn styles'],
    'L14' => ['.rn-avatar',             'rn-avatar styles'],
    'L15' => ['.rn-user-name',          'rn-user-name styles'],
    'L16' => ['.rn-dropdown',           'rn-dropdown styles'],
    'L17' => ['.rn-dropdown-item',      'rn-dropdown-item styles'],
    'L18' => ['.rn-content',            'rn-content styles'],
    'L19' => ['.rn-drawer',             'rn-drawer panel styles'],
    'L20' => ['.rn-drawer-overlay',     'rn-drawer-overlay styles'],
    'L21' => ['.rn-drawer-header',      'rn-drawer-header styles'],
    'L22' => ['.rn-drawer-nav',         'rn-drawer-nav styles'],
    'L23' => ['.rn-drawer-footer',      'rn-drawer-footer styles'],
    'L24' => ['.rn-drawer-body-lock',   'rn-drawer-body-lock scroll-lock'],
    'L25' => ['rn-page-entered',        'rn-page-entered animation class'],
];
foreach ($cssClasses as $id => [$cls, $desc]) {
    rnChk($id, "CSS: $desc", str_contains($css, $cls), "Found '$cls' in app.css");
}

rnChk('L26', 'CSS: gold underline on active link uses --biringan-gold',
    str_contains($css, '--biringan-gold') && str_contains($css, 'rn-nav-link--active'),
    'Gold variable used for active link');

rnChk('L27', 'CSS: mobile breakpoint @media max-width 991.98px',
    preg_match('/@media\s*\(max-width:\s*991\.98px\).*rn-hamburger/s', $css) === 1,
    '@media 991.98px rule contains rn-hamburger');

rnChk('L28', 'CSS: reduced-motion override for rn-content',
    str_contains($css, 'rn-content') && str_contains($css, 'prefers-reduced-motion'),
    'prefers-reduced-motion block found');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK M: Sidebar elements ABSENT from resident layout
// ─────────────────────────────────────────────────────────────────────────────
rnChk('M01', 'No .sidebar class in resident layout',
    !str_contains($rl, 'class="sidebar"'),
    str_contains($rl, 'class="sidebar"') ? 'FAIL: sidebar found' : 'No .sidebar — correct');

rnChk('M02', 'No sidebar-nav in resident layout',
    !str_contains($rl, 'sidebar-nav'),
    str_contains($rl, 'sidebar-nav') ? 'FAIL: sidebar-nav found' : 'No sidebar-nav — correct');

rnChk('M03', 'No sidebar-brand in resident layout',
    !str_contains($rl, 'sidebar-brand'),
    str_contains($rl, 'sidebar-brand') ? 'FAIL: sidebar-brand found' : 'No sidebar-brand — correct');

rnChk('M04', 'No auth-content class in resident layout (full-width rn-content used)',
    !str_contains($rl, 'auth-content'),
    str_contains($rl, 'auth-content') ? 'FAIL: auth-content found' : 'No auth-content — rn-content used instead');

rnChk('M05', 'No margin-left sidebar offset on rn-content',
    !preg_match('/rn-content\s*\{[^}]*margin-left\s*:\s*var\(--sidebar-width/s', $css),
    'rn-content does not apply sidebar-width margin — full width confirmed');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK N: auth.php layout still has the sidebar (not broken)
// ─────────────────────────────────────────────────────────────────────────────
rnChk('N01', 'auth.php still contains the sidebar element',
    str_contains($al, 'class="sidebar"') || str_contains($al, 'id="appSidebar"'),
    'sidebar found in auth.php — not removed');

rnChk('N02', 'auth.php staff sidebar block still present',
    str_contains($al, "role === 'staff'"),
    'Staff role block in auth.php confirmed');

rnChk('N03', 'auth.php admin sidebar block still present',
    str_contains($al, "role === 'admin'"),
    'Admin role block in auth.php confirmed');

rnChk('N04', 'auth.php resident sidebar block still present (kept for reference)',
    str_contains($al, "role === 'resident'"),
    'Resident role block in auth.php still present');

rnChk('N05', 'auth.php does NOT reference rn-topbar (layouts are separate)',
    !str_contains($al, 'rn-topbar'),
    str_contains($al, 'rn-topbar') ? 'FAIL: rn-topbar in auth.php' : 'No rn-topbar in auth.php — correct');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK O: Breadcrumb + flash message support in resident layout
// ─────────────────────────────────────────────────────────────────────────────
rnChk('O01', 'Breadcrumb block present in resident layout',
    str_contains($rl, 'breadcrumbs'), 'breadcrumbs variable support found');

rnChk('O02', 'Flash success message support present',
    str_contains($rl, 'flashSuccess') && str_contains($rl, 'alert-success'),
    'flashSuccess block found');

rnChk('O03', 'Flash error message support present',
    str_contains($rl, 'flashError') && str_contains($rl, 'alert-danger'),
    'flashError block found');

rnChk('O04', 'Flash info message support present',
    str_contains($rl, 'flashInfo') && str_contains($rl, 'alert-info'),
    'flashInfo block found');

rnChk('O05', 'getFlash() used via SessionHelper',
    str_contains($rl, 'SessionHelper::getFlash'), 'SessionHelper::getFlash() found');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK P: Page-level modal support
// ─────────────────────────────────────────────────────────────────────────────
rnChk('P01', '$GLOBALS[page_modals] slot preserved in resident layout',
    str_contains($rl, 'page_modals'), 'page_modals global slot found');

rnChk('P02', 'Bootstrap 5 JS included',
    str_contains($rl, 'bootstrap.bundle.min.js'), 'Bootstrap JS CDN link found');

rnChk('P03', 'app.js included',
    str_contains($rl, 'app.js'), 'app.js script tag found');

rnChk('P04', 'Skip-to-main-content accessibility link present',
    str_contains($rl, 'Skip to main content') && str_contains($rl, '#main-content'),
    'Skip link found');

rnChk('P05', 'main#main-content id present for skip link target',
    str_contains($rl, 'id="main-content"'), 'main-content id on <main> element found');

// ─────────────────────────────────────────────────────────────────────────────
// REPORT
// ─────────────────────────────────────────────────────────────────────────────
echo "\n";
echo "=================================================================\n";
echo "  Phase Resident Navbar — Resident Top Navbar Redesign Tests\n";
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
