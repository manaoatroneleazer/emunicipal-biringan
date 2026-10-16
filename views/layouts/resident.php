<?php
/**
 * Resident Layout — horizontal top-navbar replacing the sidebar.
 * Used ONLY by resident pages. Staff and Admin continue to use layouts/auth.
 *
 * Variables expected from controller:
 *   $title       — page <title>
 *   $content     — rendered view HTML
 *   $activePage  — nav link identifier (dashboard | requests | new-request | track | profile | notifications | landing)
 */

use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Models\Notification;

$appName   = htmlspecialchars($_ENV['APP_NAME'] ?? 'eMunicipal', ENT_QUOTES, 'UTF-8');
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$pageTitle = isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : $appName;

// ── Authenticated user ──────────────────────────────────────────────────────
$firstName = htmlspecialchars(AuthMiddleware::firstName() ?? '', ENT_QUOTES, 'UTF-8');
$lastName  = htmlspecialchars(AuthMiddleware::lastName()  ?? '', ENT_QUOTES, 'UTF-8');
$fullName  = htmlspecialchars(AuthMiddleware::fullName(),         ENT_QUOTES, 'UTF-8');
$userId    = AuthMiddleware::userId();

// Initials fallback for avatar
$initFirst  = AuthMiddleware::firstName() ?? '';
$initLast   = AuthMiddleware::lastName()  ?? '';
$initials   = htmlspecialchars(
    mb_strtoupper(mb_substr($initFirst, 0, 1) . mb_substr($initLast, 0, 1)) ?: '?',
    ENT_QUOTES, 'UTF-8'
);

// ── Unread notification count ───────────────────────────────────────────────
$unreadCount = 0;
if ($userId) {
    try {
        $notifModel  = new Notification();
        $unreadCount = $notifModel->countUnread($userId);
    } catch (\Throwable $e) { /* non-fatal */ }
}

$activePage = $activePage ?? '';

// ── Nav link helper ─────────────────────────────────────────────────────────
$navLink = function(string $page, string $href, string $label, string $icon) use ($activePage, $appUrl): string {
    $isActive = ($activePage === $page);
    $cls      = 'rn-nav-link' . ($isActive ? ' rn-nav-link--active' : '');
    $aria     = $isActive ? ' aria-current="page"' : '';
    $url      = htmlspecialchars($appUrl . $href, ENT_QUOTES, 'UTF-8');
    return sprintf(
        '<a class="%s" href="%s"%s>'
        . '<i class="bi %s" aria-hidden="true"></i>'
        . '<span>%s</span>'
        . '</a>',
        $cls, $url, $aria,
        htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
    );
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $appUrl ?>/public/assets/css/app.css?v=5.5">
</head>
<body class="rn-body">

<!-- Skip to main content -->
<a href="#main-content" class="visually-hidden-focusable position-absolute"
   style="top:0.5rem;left:0.5rem;z-index:9999;background:var(--emu-primary);color:#fff;padding:0.5rem 1rem;border-radius:4px;text-decoration:none;font-weight:600;">
    Skip to main content
</a>

<!-- ================================================================
     RESIDENT TOP NAVBAR
     ================================================================ -->
<nav class="rn-topbar" id="rnTopbar" aria-label="Resident navigation">
    <div class="rn-topbar-inner">

        <!-- LEFT: hamburger (mobile) + brand -->
        <div class="rn-topbar-left">
            <button class="rn-hamburger" id="rnDrawerToggle"
                    aria-label="Toggle navigation drawer"
                    aria-controls="rnDrawer"
                    aria-expanded="false">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <a href="<?= $appUrl ?>/resident/dashboard"
               class="rn-brand"
               aria-label="<?= $appName ?> — Resident Dashboard">
                <img src="<?= $appUrl ?>/public/assets/images/biringan-logo-placeholder.svg"
                     alt="" aria-hidden="true" class="rn-brand-logo">
                <div class="rn-brand-text">
                    <span class="rn-brand-name"><?= $appName ?></span>
                    <span class="rn-brand-city">Biringan City</span>
                </div>
            </a>
        </div>

        <!-- CENTER: nav links (desktop) -->
        <div class="rn-topbar-center" role="navigation" aria-label="Main resident navigation">
            <?= $navLink('dashboard',    '/resident/dashboard',       'Dashboard',     'bi-grid') ?>
            <?= $navLink('requests',     '/resident/requests',        'My Requests',   'bi-list-ul') ?>
            <?= $navLink('new-request',  '/resident/requests/new',    'New Request',   'bi-plus-circle') ?>
            <?= $navLink('track',        '/resident/track',           'Track Request', 'bi-search') ?>
            <?= $navLink('profile',      '/resident/profile',         'Profile',       'bi-person') ?>
            <?= $navLink('landing',      '/',                         'Landing Page',  'bi-house-door') ?>
        </div>

        <!-- RIGHT: notification bell + user dropdown -->
        <div class="rn-topbar-right">

            <!-- Notification bell -->
            <a href="<?= $appUrl ?>/resident/notifications"
               class="rn-bell<?= $unreadCount > 0 ? ' rn-bell--has-unread' : '' ?>"
               aria-label="Notifications<?= $unreadCount > 0 ? " ($unreadCount unread)" : '' ?>">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <?php if ($unreadCount > 0): ?>
                <span class="rn-bell-badge" aria-hidden="true">
                    <?= min($unreadCount, 99) ?><?= $unreadCount > 99 ? '+' : '' ?>
                </span>
                <?php endif; ?>
            </a>

            <!-- User dropdown -->
            <div class="rn-user-menu dropdown" id="rnUserMenu">
                <button class="rn-user-btn dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Account menu for <?= $fullName ?>">
                    <span class="rn-avatar" aria-hidden="true"><?= $initials ?></span>
                    <span class="rn-user-name"><?= $fullName ?></span>
                    <i class="bi bi-chevron-down rn-chevron" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end rn-dropdown" aria-label="Account options">
                    <li>
                        <div class="rn-dropdown-header">
                            <div class="rn-dropdown-name"><?= $fullName ?></div>
                            <div class="rn-dropdown-role">Resident</div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider rn-divider"></li>
                    <li>
                        <a class="dropdown-item rn-dropdown-item" href="<?= $appUrl ?>/resident/profile">
                            <i class="bi bi-person me-2" aria-hidden="true"></i>My Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rn-dropdown-item" href="<?= $appUrl ?>/resident/notifications">
                            <i class="bi bi-bell me-2" aria-hidden="true"></i>Notifications
                            <?php if ($unreadCount > 0): ?>
                            <span class="rn-dropdown-badge ms-auto"><?= min($unreadCount, 99) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rn-dropdown-item" href="<?= $appUrl ?>/resident/track">
                            <i class="bi bi-search me-2" aria-hidden="true"></i>Track Request
                        </a>
                    </li>
                    <li><hr class="dropdown-divider rn-divider"></li>
                    <li>
                        <a class="dropdown-item rn-dropdown-item" href="<?= $appUrl ?>/">
                            <i class="bi bi-house-door me-2" aria-hidden="true"></i>Landing Page
                        </a>
                    </li>
                    <li>
                        <button type="button"
                                class="dropdown-item rn-dropdown-item rn-dropdown-item--danger"
                                data-bs-toggle="modal"
                                data-bs-target="#logoutConfirmModal"
                                aria-haspopup="dialog">
                            <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sign Out
                        </button>
                    </li>
                </ul>
            </div>

        </div><!-- /.rn-topbar-right -->
    </div><!-- /.rn-topbar-inner -->
</nav>

<!-- ================================================================
     MOBILE DRAWER (slides in from left on small screens)
     ================================================================ -->
<div class="rn-drawer-overlay" id="rnDrawerOverlay" aria-hidden="true"></div>

<nav class="rn-drawer" id="rnDrawer"
     aria-label="Mobile resident navigation"
     aria-hidden="true">

    <!-- Drawer header -->
    <div class="rn-drawer-header">
        <div class="d-flex align-items-center gap-2">
            <span class="rn-avatar rn-avatar--lg" aria-hidden="true"><?= $initials ?></span>
            <div>
                <div class="rn-drawer-username"><?= $fullName ?></div>
                <div class="rn-drawer-role">Resident</div>
            </div>
        </div>
        <button class="rn-drawer-close" id="rnDrawerClose" aria-label="Close navigation drawer">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Drawer nav links -->
    <ul class="rn-drawer-nav list-unstyled mb-0" role="list">
        <li><?= $navLink('dashboard',     '/resident/dashboard',      'Dashboard',      'bi-grid') ?></li>
        <li><?= $navLink('requests',      '/resident/requests',       'My Requests',    'bi-list-ul') ?></li>
        <li><?= $navLink('new-request',   '/resident/requests/new',   'New Request',    'bi-plus-circle') ?></li>
        <li><?= $navLink('track',         '/resident/track',          'Track Request',  'bi-search') ?></li>
        <li><?= $navLink('notifications', '/resident/notifications',  'Notifications' . ($unreadCount > 0 ? " ($unreadCount)" : ''), 'bi-bell') ?></li>
        <li><?= $navLink('profile',       '/resident/profile',        'My Profile',     'bi-person') ?></li>
        <li><?= $navLink('landing',       '/',                        'Landing Page',   'bi-house-door') ?></li>
    </ul>

    <!-- Drawer footer actions -->
    <div class="rn-drawer-footer">
        <a href="<?= $appUrl ?>/" class="rn-drawer-link">
            <i class="bi bi-house-door me-2" aria-hidden="true"></i>Landing Page
        </a>
        <button type="button"
                class="rn-drawer-link rn-drawer-link--danger"
                data-bs-toggle="modal"
                data-bs-target="#logoutConfirmModal"
                aria-haspopup="dialog">
            <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sign Out
        </button>
    </div>
</nav>

<!-- ================================================================
     MAIN CONTENT
     ================================================================ -->
<main class="rn-content" id="main-content" role="main">

    <?php
    $flashSuccess = SessionHelper::getFlash('success');
    $flashError   = SessionHelper::getFlash('error');
    $flashInfo    = SessionHelper::getFlash('info');
    ?>
    <?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible alert-flash fade show mb-3" role="alert" aria-live="assertive">
        <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
        <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible alert-flash fade show mb-3" role="alert" aria-live="assertive">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
        <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <?php if ($flashInfo): ?>
    <div class="alert alert-info alert-dismissible alert-flash fade show mb-3" role="alert" aria-live="assertive">
        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
        <?= htmlspecialchars($flashInfo, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($breadcrumbs)): ?>
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <?php foreach ($breadcrumbs as $crumb): ?>
            <?php if (isset($crumb['url'])): ?>
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($crumb['url'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            </li>
            <?php else: ?>
            <li class="breadcrumb-item active" aria-current="page">
                <?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?>
            </li>
            <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>

    <?= $content ?? '' ?>
</main>

<!-- Page-level modals -->
<?php if (!empty($GLOBALS['page_modals'])): ?>
<?= $GLOBALS['page_modals'] ?>
<?php endif; ?>

<!-- Logout form (hidden, submitted by modal) -->
<form id="logoutForm" method="POST" action="<?= $appUrl ?>/logout" class="d-none">
    <input type="hidden" name="_csrf_token"
           value="<?= htmlspecialchars(SecurityHelper::getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
</form>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false"
     aria-labelledby="rnLogoutTitle" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h5 class="modal-title" id="rnLogoutTitle">
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
                        id="rnLogoutConfirmBtn">Log Out</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<!-- App JS (shared utilities) -->
<script src="<?= $appUrl ?>/public/assets/js/app.js?v=3.4"></script>
<!-- Resident navbar JS (drawer + logout) -->
<script>
(function () {
    'use strict';

    /* ── Logout modal ──────────────────────────────────────────────── */
    var logoutBtn  = document.getElementById('rnLogoutConfirmBtn');
    var logoutForm = document.getElementById('logoutForm');
    if (logoutBtn && logoutForm) {
        logoutBtn.addEventListener('click', function () { logoutForm.submit(); });
    }

    /* ── Mobile drawer ─────────────────────────────────────────────── */
    var toggleBtn = document.getElementById('rnDrawerToggle');
    var closeBtn  = document.getElementById('rnDrawerClose');
    var overlay   = document.getElementById('rnDrawerOverlay');
    var drawer    = document.getElementById('rnDrawer');

    if (!toggleBtn || !drawer) return;

    function openDrawer() {
        drawer.classList.add('rn-drawer--open');
        drawer.setAttribute('aria-hidden', 'false');
        overlay.classList.add('rn-drawer-overlay--active');
        toggleBtn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('rn-drawer-body-lock');
        // Move focus to first link inside drawer
        var first = drawer.querySelector('a, button');
        if (first) { first.focus(); }
    }

    function closeDrawer() {
        drawer.classList.remove('rn-drawer--open');
        drawer.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('rn-drawer-overlay--active');
        toggleBtn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('rn-drawer-body-lock');
        toggleBtn.focus();
    }

    toggleBtn.addEventListener('click', function () {
        if (drawer.classList.contains('rn-drawer--open')) { closeDrawer(); }
        else { openDrawer(); }
    });

    if (closeBtn)  { closeBtn.addEventListener('click',  closeDrawer); }
    if (overlay)   { overlay.addEventListener('click',   closeDrawer); }

    // Escape key closes drawer
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer.classList.contains('rn-drawer--open')) {
            closeDrawer();
        }
    });

    // Close drawer when a nav link inside it is clicked
    drawer.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth < 992) { closeDrawer(); }
        });
    });

    /* ── Page enter animation ──────────────────────────────────────── */
    requestAnimationFrame(function () {
        document.body.classList.add('rn-page-entered');
    });

})();
</script>
</body>
</html>
