<?php
/**
 * Authenticated Layout — design.md —5.3, —4.3—4.6
 * Used by: Resident, Staff, and Admin pages.
 *
 * Variables expected from controller:
 *   $title         — page <title>
 *   $content       — rendered view HTML
 *   $activePage    — sidebar link identifier (e.g. 'dashboard', 'requests')
 */

use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Models\Notification;

$appName  = htmlspecialchars($_ENV['APP_NAME'] ?? 'eMunicipal', ENT_QUOTES, 'UTF-8');
$appUrl   = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$pageTitle = isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : $appName;

$role      = AuthMiddleware::getRole();
$firstName = htmlspecialchars(AuthMiddleware::firstName() ?? '', ENT_QUOTES, 'UTF-8');
$lastName  = htmlspecialchars(AuthMiddleware::lastName()  ?? '', ENT_QUOTES, 'UTF-8');
$userId    = AuthMiddleware::userId();
$activePage = $activePage ?? '';

// Unread notification count (resident, staff, and admin — Req 16.2, Req 25.6)
$unreadCount = 0;
if ($userId && in_array($role, ['resident', 'staff', 'admin'], true)) {
    try {
        $notifModel  = new Notification();
        $unreadCount = $notifModel->countUnread($userId);
    } catch (\Throwable $e) {
        // Non-fatal — continue without count
    }
}

// Role ? dashboard URL map
$dashUrl = match($role) {
    'staff' => $appUrl . '/staff/dashboard',
    'admin' => $appUrl . '/admin/dashboard',
    default => $appUrl . '/resident/dashboard',
};
$profileUrl = match($role) {
    'staff' => $appUrl . '/staff/profile',
    'admin' => $appUrl . '/admin/profile',
    default => $appUrl . '/resident/profile',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- App CSS -->
    <link rel="stylesheet" href="<?= $appUrl ?>/public/assets/css/app.css?v=4.9">
</head>
<body class="auth-body role-<?= htmlspecialchars(strtolower($role), ENT_QUOTES, 'UTF-8') ?>">

<!-- Skip to main content (keyboard accessibility — Req 19.11 / WCAG 2.1 AA) -->
<a href="#main-content" class="visually-hidden-focusable position-absolute"
   style="top:0.5rem;left:0.5rem;z-index:9999;background:var(--emu-primary);color:#fff;padding:0.5rem 1rem;border-radius:4px;text-decoration:none;font-weight:600;">
    Skip to main content
</a>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

<!-- ================================================================
     SIDEBAR (design.md —4.4—4.6)
     ================================================================ -->
<nav class="sidebar" id="appSidebar" aria-label="Sidebar navigation">
    <!-- Sidebar brand block -->
    <a href="<?= $dashUrl ?>" class="sidebar-brand" tabindex="-1" aria-hidden="true">
        <img src="<?= $appUrl ?>/public/assets/images/biringan-logo-placeholder.svg"
             alt="" class="sidebar-brand-logo biringan-logo-md">
        <div>
            <div class="sidebar-brand-name"><?= $appName ?></div>
            <div class="sidebar-brand-sub">Biringan City</div>
        </div>
    </a>
    <!-- User identity strip (replaces header user dropdown) -->
    <div class="sidebar-user px-3 py-2 border-bottom"
         style="border-color:rgba(255,255,255,0.12)!important;">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-circle text-white" style="font-size:1.2rem;" aria-hidden="true"></i>
            <div>
                <div style="font-size:0.8125rem;font-weight:600;color:#fff;line-height:1.2;">
                    <?= htmlspecialchars($firstName . ' ' . $lastName, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <span class="role-badge role-badge-<?= strtolower($role) ?>" style="font-size:0.6rem;">
                    <?= htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>
    </div>
    <ul class="sidebar-nav nav flex-column">

        <?php if ($role === 'resident'): ?>
        <!-- RESIDENT SIDEBAR (design.md —4.4) -->
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/resident/dashboard"
               <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-grid" aria-hidden="true"></i> Dashboard
            </a>
        </li>
        <li class="sidebar-section-label">Requests</li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'new-request' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/resident/requests/new"
               <?= $activePage === 'new-request' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-plus-circle" aria-hidden="true"></i> New Request
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'requests' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/resident/requests"
               <?= $activePage === 'requests' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-list-ul" aria-hidden="true"></i> My Requests
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'track' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/resident/track"
               <?= $activePage === 'track' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-search" aria-hidden="true"></i> Track Request
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'notifications' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/resident/notifications"
               <?= $activePage === 'notifications' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-bell" aria-hidden="true"></i> Notifications
                <?php if ($unreadCount > 0): ?>
                <span class="badge rounded-pill ms-1" style="background:var(--emu-danger);font-size:0.65rem;">
                    <?= $unreadCount ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-section-label">Account</li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'profile' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/resident/profile"
               <?= $activePage === 'profile' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-person" aria-hidden="true"></i> My Profile
            </a>
        </li>

        <?php elseif ($role === 'staff'): ?>
        <!-- STAFF SIDEBAR (design.md —4.5) -->
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/staff/dashboard"
               <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-grid" aria-hidden="true"></i> Dashboard
            </a>
        </li>
        <li class="sidebar-section-label">Requests</li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'requests' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/staff/requests"
               <?= $activePage === 'requests' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-inbox" aria-hidden="true"></i> Requests
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'notifications' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/staff/notifications"
               <?= $activePage === 'notifications' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-bell" aria-hidden="true"></i> Notifications
                <?php if ($unreadCount > 0): ?>
                <span class="badge rounded-pill ms-1" style="background:var(--emu-danger);font-size:0.65rem;">
                    <?= $unreadCount ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'profile' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/staff/profile"
               <?= $activePage === 'profile' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-person" aria-hidden="true"></i> My Profile
            </a>
        </li>

        <?php elseif ($role === 'admin'): ?>
        <!-- ADMIN SIDEBAR (design.md —4.6) -->
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/admin/dashboard"
               <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-grid" aria-hidden="true"></i> Dashboard
            </a>
        </li>

        <li class="sidebar-section-label">Management</li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'users' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/admin/users"
               <?= $activePage === 'users' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-people" aria-hidden="true"></i> Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'requests' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/admin/requests"
               <?= $activePage === 'requests' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-inbox" aria-hidden="true"></i> Requests
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'notifications' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/admin/notifications"
               <?= $activePage === 'notifications' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-bell" aria-hidden="true"></i> Notifications
                <?php if ($unreadCount > 0): ?>
                <span class="badge rounded-pill ms-1" style="background:var(--emu-danger);font-size:0.65rem;">
                    <?= $unreadCount ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'document-types' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/admin/document-types"
               <?= $activePage === 'document-types' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-file-text" aria-hidden="true"></i> Document Types
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'reports' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/admin/reports"
               <?= $activePage === 'reports' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-bar-chart-line" aria-hidden="true"></i> Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'audit-logs' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/admin/audit-logs"
               <?= $activePage === 'audit-logs' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-journal-text" aria-hidden="true"></i> Audit Logs
            </a>
        </li>

        <li class="sidebar-section-label">System</li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'settings' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/admin/settings"
               <?= $activePage === 'settings' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-gear" aria-hidden="true"></i> System Settings
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'profile' ? 'active' : '' ?>"
               href="<?= $appUrl ?>/admin/profile"
               <?= $activePage === 'profile' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-person" aria-hidden="true"></i> My Profile
            </a>
        </li>
        <?php endif; ?>

        <li class="sidebar-section-label">Navigation</li>
        <li class="nav-item">
            <a class="nav-link"
               href="<?= $appUrl ?>/" 
               style="color:rgba(255,255,255,0.65);">
                <i class="bi bi-house" aria-hidden="true"></i> Landing Page
            </a>
        </li>

        <li class="sidebar-section-label">Session</li>
        <li class="nav-item">
            <button type="button"
                    class="nav-link w-100 text-start border-0 bg-transparent logout-link"
                    data-bs-toggle="modal"
                    data-bs-target="#logoutConfirmModal"
                    aria-haspopup="dialog"
                    aria-label="Open sign out confirmation">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Logout
            </button>
        </li>

    </ul>
</nav>

<!-- ================================================================
     MOBILE HEADER — compact fixed top bar, hidden on desktop (≥992px)
     Contains: logo + app name + city tagline (left) | hamburger (right)
     ================================================================ -->
<header class="mobile-header d-lg-none" aria-label="Mobile navigation header">
    <a href="<?= $dashUrl ?>" class="mobile-header-brand" aria-label="<?= $appName ?> — go to dashboard">
        <img src="<?= $appUrl ?>/public/assets/images/biringan-logo-placeholder.svg"
             alt="" class="mobile-header-logo" aria-hidden="true">
        <div class="mobile-header-titles">
            <span class="mobile-header-name"><?= $appName ?></span>
            <span class="mobile-header-city">Biringan City</span>
        </div>
    </a>
    <button class="mobile-menu-toggle" id="sidebarToggle"
            aria-label="Toggle sidebar navigation"
            aria-controls="appSidebar"
            aria-expanded="false">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>
</header>

<!-- ================================================================
     MAIN CONTENT AREA
     ================================================================ -->
<main class="auth-content" id="main-content" role="main">

    <?php
    // Flash messages (set via SessionHelper::flash)
    $flashSuccess = \Emunicipal\Helpers\SessionHelper::getFlash('success');
    $flashError   = \Emunicipal\Helpers\SessionHelper::getFlash('error');
    $flashInfo    = \Emunicipal\Helpers\SessionHelper::getFlash('info');
    ?>
    <?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible alert-flash fade show mb-3" role="alert" aria-live="assertive">
        <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
        <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible alert-flash fade show mb-3" role="alert" aria-live="assertive">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
        <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($flashInfo): ?>
    <div class="alert alert-info alert-dismissible alert-flash fade show mb-3" role="alert" aria-live="assertive">
        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
        <?= htmlspecialchars($flashInfo, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close alert"></button>
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

<!-- Page-level modals (emitted here so they are direct children of <body>,
     outside .auth-content, preventing transform stacking-context issues
     with Bootstrap's position:fixed modal centering) -->
<?php if (!empty($GLOBALS['page_modals'])): ?>
<?= $GLOBALS['page_modals'] ?>
<?php endif; ?>

<!-- Hidden logout form — submitted by logout modal confirm button -->
<form id="logoutForm" method="POST" action="<?= $appUrl ?>/logout" class="d-none">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\Emunicipal\Helpers\SecurityHelper::getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
</form>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false"
     aria-labelledby="logoutConfirmTitle" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h5 class="modal-title" id="logoutConfirmTitle">
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
                        id="logoutConfirmBtn">Log Out</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<!-- App JS -->
<script src="<?= $appUrl ?>/public/assets/js/app.js?v=3.4"></script>
<!-- Logout modal: inline fallback ensures it works regardless of app.js load order -->
<script>
(function () {
    function bindLogout() {
        var btn  = document.getElementById('logoutConfirmBtn');
        var form = document.getElementById('logoutForm');
        if (btn && form) {
            // Remove any existing listener first to prevent duplicates
            var newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            newBtn.addEventListener('click', function () {
                form.submit();
            });
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindLogout);
    } else {
        bindLogout();
    }
})();
</script>
</body>
</html>

