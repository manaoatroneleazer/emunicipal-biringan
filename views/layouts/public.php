<?php
/**
 * Public Layout
 * Used by: all public (unauthenticated) pages.
 * Variables: $title, $description, $content
 */

use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Middleware\AuthMiddleware;

$appName   = htmlspecialchars($_ENV['APP_NAME'] ?? 'eMunicipal', ENT_QUOTES, 'UTF-8');
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$pageTitle = isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : $appName;
$pageDesc  = isset($description)
    ? htmlspecialchars($description, ENT_QUOTES, 'UTF-8')
    : 'Request community documents online from the Biringan City Administration — eMunicipal Academic Demonstration System.';

// ---------------------------------------------------------------------------
// Logged-in user data — read from session via AuthMiddleware (server-side only)
// ---------------------------------------------------------------------------
$navIsLoggedIn      = AuthMiddleware::isAuthenticated();
$navUserFullName    = '';
$navUserInitials    = '';
$navDashboardUrl    = $appUrl . '/login'; // safe fallback
$navDashboardLabel  = 'Dashboard';
$navProfileUrl      = $appUrl . '/login';
$navRole            = '';

if ($navIsLoggedIn) {
    $navUserFullName = AuthMiddleware::fullName();
    $navRole         = AuthMiddleware::getRole() ?? '';

    // Initials: up to 2 chars from first + last name
    $navFirst        = AuthMiddleware::firstName() ?? '';
    $navLast         = AuthMiddleware::lastName()  ?? '';
    $navUserInitials = mb_strtoupper(
        mb_substr($navFirst, 0, 1) . mb_substr($navLast, 0, 1)
    );
    if ($navUserInitials === '') {
        $navUserInitials = '?';
    }

    // Role-based routes and labels — matches AuthMiddleware::redirectToDashboard()
    match ($navRole) {
        'staff' => [
            $navDashboardUrl   = $appUrl . '/staff/dashboard',
            $navDashboardLabel = 'Staff Dashboard',
            $navProfileUrl     = $appUrl . '/staff/profile',
        ],
        'admin' => [
            $navDashboardUrl   = $appUrl . '/admin/dashboard',
            $navDashboardLabel = 'Admin Dashboard',
            $navProfileUrl     = $appUrl . '/admin/profile',
        ],
        default => [
            $navDashboardUrl   = $appUrl . '/resident/dashboard',
            $navDashboardLabel = 'Resident Dashboard',
            $navProfileUrl     = $appUrl . '/resident/profile',
        ],
    };
}

// Escape helpers for navbar output
$navName      = htmlspecialchars($navUserFullName,   ENT_QUOTES, 'UTF-8');
$navInit      = htmlspecialchars($navUserInitials,   ENT_QUOTES, 'UTF-8');
$navDash      = htmlspecialchars($navDashboardUrl,   ENT_QUOTES, 'UTF-8');
$navDashLabel = htmlspecialchars($navDashboardLabel, ENT_QUOTES, 'UTF-8');
$navProf      = htmlspecialchars($navProfileUrl,     ENT_QUOTES, 'UTF-8');
$navRoleLabel = htmlspecialchars(ucfirst($navRole),  ENT_QUOTES, 'UTF-8');

// Detect current path relative to app base
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$basePath   = parse_url($appUrl, PHP_URL_PATH) ?? '';
$relUri     = '/' . ltrim(str_replace($basePath, '', $currentUri), '/');
// Normalise to plain / when at root
if ($relUri === '' || $relUri === '//') { $relUri = '/'; }

// Is this the home page? Use anchor nav. Otherwise use section-page routes.
$isHome = ($relUri === '/');

function pubNavLink(string $anchor, string $label, string $relUri, string $appUrl, bool $isHome): string {
    $href  = $isHome ? "#{$anchor}" : "{$appUrl}/#{$anchor}";
    $active = '';
    if (!$isHome && $relUri === "/{$anchor}") { $active = ' active" aria-current="page'; }
    if ($isHome && $relUri === '/')           { $active = ($anchor === 'home') ? ' active" aria-current="page' : ''; }
    return '<li class="nav-item"><a class="nav-link' . $active . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a></li>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $pageDesc ?>">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $appUrl ?>/public/assets/css/app.css?v=5.5">
</head>
<body class="lp-body">

<!-- Skip to main content (keyboard accessibility — Req 19.11 / WCAG 2.1 AA) -->
<a href="#main-content" class="visually-hidden-focusable position-absolute"
   style="top:0.5rem;left:0.5rem;z-index:9999;background:var(--emu-primary);color:#fff;padding:0.5rem 1rem;border-radius:4px;text-decoration:none;font-weight:600;">
    Skip to main content
</a>

<!-- ================================================================
     PUBLIC NAVIGATION BAR
     ================================================================ -->
<nav class="navbar navbar-expand-lg sticky-top navbar-light-lp" aria-label="Main navigation">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="<?= $appUrl ?>/">
            <img src="<?= $appUrl ?>/public/assets/images/biringan-logo-placeholder.svg"
                 alt="Biringan City Administration"
                 class="biringan-logo-md flex-shrink-0">
            <div>
                <div class="navbar-brand-name"><?= $appName ?></div>
                <div class="navbar-brand-sub">Biringan City</div>
            </div>
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#publicNav"
                aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav items -->
        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-1">
                <?= pubNavLink('home',         'Home',         $relUri, $appUrl, $isHome) ?>
                <?= pubNavLink('track',        'Track Request',$relUri, $appUrl, $isHome) ?>
                <?= pubNavLink('about',        'About',        $relUri, $appUrl, $isHome) ?>
                <?= pubNavLink('how-it-works', 'How It Works', $relUri, $appUrl, $isHome) ?>
                <?= pubNavLink('services',     'Services',     $relUri, $appUrl, $isHome) ?>
                <?= pubNavLink('faqs',         'FAQs',         $relUri, $appUrl, $isHome) ?>
                <?= pubNavLink('contact',      'Contact',      $relUri, $appUrl, $isHome) ?>
            </ul>
            <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                <?php if ($navIsLoggedIn): ?>
                <!-- ── Logged-in: role dashboard button + profile dropdown ── -->

                <!-- Dashboard button — outlined, role-labelled, role-specific icon -->
                <?php
                $navDashIcon = match($navRole) {
                    'staff' => 'bi-grid',
                    'admin' => 'bi-shield-check',
                    default => 'bi-house',
                };
                ?>
                <a href="<?= $navDash ?>"
                   class="lp-dash-btn"
                   aria-label="Go to <?= $navDashLabel ?>">
                    <i class="bi <?= htmlspecialchars($navDashIcon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                    <?= $navDashLabel ?>
                </a>

                <!-- Profile dropdown trigger + menu -->
                <div class="dropdown lp-user-dropdown">
                    <button class="lp-user-btn"
                            type="button"
                            data-bs-toggle="dropdown"
                            data-bs-offset="0,6"
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-label="Account menu for <?= $navName ?>">
                        <span class="lp-user-avatar" aria-hidden="true"><?= $navInit ?></span>
                        <span class="lp-user-name d-none d-lg-inline"><?= $navName ?></span>
                        <i class="bi bi-chevron-down lp-chevron d-none d-lg-inline" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end lp-dropdown-menu" role="menu">
                        <!-- Name + role header -->
                        <li role="none">
                            <div class="lp-dropdown-header">
                                <div class="lp-dropdown-name"><?= $navName ?></div>
                                <span class="lp-dropdown-role"><?= $navRoleLabel ?></span>
                            </div>
                        </li>
                        <li role="none"><hr class="dropdown-divider"></li>
                        <!-- Dashboard -->
                        <li role="none">
                            <a class="dropdown-item lp-dropdown-item" href="<?= $navDash ?>" role="menuitem">
                                <i class="bi bi-grid me-2" aria-hidden="true"></i><?= $navDashLabel ?>
                            </a>
                        </li>
                        <!-- My Profile -->
                        <li role="none">
                            <a class="dropdown-item lp-dropdown-item" href="<?= $navProf ?>" role="menuitem">
                                <i class="bi bi-person me-2" aria-hidden="true"></i>My Profile
                            </a>
                        </li>
                        <li role="none"><hr class="dropdown-divider"></li>
                        <!-- Sign Out -->
                        <li role="none">
                            <button type="button"
                                    class="dropdown-item lp-dropdown-item lp-dropdown-item--danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#lpLogoutModal"
                                    role="menuitem"
                                    aria-haspopup="dialog">
                                <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sign Out
                            </button>
                        </li>
                    </ul>
                </div>

                <?php else: ?>
                <!-- ── Logged-out: outlined Login + pink Register ── -->
                <a href="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>/login"
                   class="btn btn-outline-primary btn-sm px-3"
                   style="font-weight:500;">
                    <i class="bi bi-person me-1" aria-hidden="true"></i>Login
                </a>
                <a href="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>/register"
                   class="btn btn-primary btn-sm px-3"
                   style="font-weight:600;">
                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Register
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Page content -->
<main id="main-content" class="lp-content">
    <?= $content ?? '' ?>
</main>

<!-- ================================================================
     PUBLIC FOOTER — compact
     ================================================================ -->
<footer class="footer-public" aria-label="Site footer">
    <div class="container">
        <div class="row g-3 align-items-start">
            <!-- Branding — compact -->
            <div class="col-12 col-md-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="<?= $appUrl ?>/public/assets/images/biringan-logo-placeholder.svg"
                         alt="" aria-hidden="true"
                         class="biringan-logo-sm flex-shrink-0">
                    <span style="color:#fff;font-weight:700;font-size:0.9375rem;font-family:'Montserrat','Inter',sans-serif;"><?= $appName ?></span>
                </div>
                <p class="mb-1" style="font-size:0.8rem;color:rgba(255,255,255,0.70);">
                    Online Municipal Document Request &amp; Management System
                </p>
                <p class="mb-0" style="font-size:0.75rem;color:rgba(255,255,255,0.50);">
                    Biringan City Administration
                </p>
            </div>
            <!-- Quick Links -->
            <div class="col-6 col-md-2">
                <h6 class="footer-heading">Quick Links</h6>
                <ul class="list-unstyled mb-0">
                    <li><a href="<?= $appUrl ?>/#home">Home</a></li>
                    <li><a href="<?= $appUrl ?>/#about">About</a></li>
                    <li><a href="<?= $appUrl ?>/#services">Services</a></li>
                    <li><a href="<?= $appUrl ?>/#faqs">FAQs</a></li>
                    <li><a href="<?= $appUrl ?>/#contact">Contact</a></li>
                </ul>
            </div>
            <!-- Account -->
            <div class="col-6 col-md-2">
                <h6 class="footer-heading">Account</h6>
                <ul class="list-unstyled mb-0">
                    <li><a href="<?= $appUrl ?>/login">Sign In</a></li>
                    <li><a href="<?= $appUrl ?>/register">Register</a></li>
                    <li><a href="<?= $appUrl ?>/#track">Track Request</a></li>
                </ul>
            </div>
            <!-- Legal -->
            <div class="col-6 col-md-2">
                <h6 class="footer-heading">Legal</h6>
                <ul class="list-unstyled mb-0">
                    <li><a href="<?= $appUrl ?>/policy/privacy">Privacy Policy</a></li>
                    <li><a href="<?= $appUrl ?>/policy/terms">Terms &amp; Conditions</a></li>
                    <li><a href="<?= $appUrl ?>/policy/moderation">Moderation Policy</a></li>
                    <li><a href="<?= $appUrl ?>/policy/copyright">Copyright Policy</a></li>
                </ul>
            </div>
            <!-- Academic Demonstration -->
            <div class="col-6 col-md-2">
                <h6 class="footer-heading">Notice</h6>
                <p class="mb-0" style="font-size:0.75rem;color:rgba(255,255,255,0.65);line-height:1.65;">
                    <strong>Biringan City</strong> and all services, documents, and characters in this system are <strong>fictional</strong>, created for academic demonstration. Inspired by Philippine folklore. <strong>Not an official government service.</strong>
                </p>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p class="mb-0 text-center">
                &copy; <?= date('Y') ?> Biringan City Administration — eMunicipal Academic Demonstration. All rights reserved.
            </p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="<?= $appUrl ?>/public/assets/js/app.js?v=3.5"></script>

<?php if ($navIsLoggedIn): ?>
<!-- Hidden logout form — CSRF-protected, submitted by modal confirm button -->
<form id="lpLogoutForm" method="POST" action="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>/logout" class="d-none">
    <input type="hidden" name="_csrf_token"
           value="<?= htmlspecialchars(SecurityHelper::getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
</form>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="lpLogoutModal" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false"
     aria-labelledby="lpLogoutTitle" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h5 class="modal-title" id="lpLogoutTitle">
                    <i class="bi bi-box-arrow-right text-danger me-2" aria-hidden="true"></i>Sign Out
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0" style="font-size:0.9rem;">
                Are you sure you want to log out? You will need to sign in again to access your account.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm"
                        id="lpLogoutConfirmBtn">Log Out</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var btn  = document.getElementById('lpLogoutConfirmBtn');
    var form = document.getElementById('lpLogoutForm');
    if (btn && form) {
        btn.addEventListener('click', function () { form.submit(); });
    }
}());
</script>
<?php endif; ?>
</body>
</html>
