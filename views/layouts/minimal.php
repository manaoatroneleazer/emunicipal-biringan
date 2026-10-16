<?php
/**
 * Minimal Layout — login, register, error pages.
 * Variables: $title, $content
 */
$appName   = htmlspecialchars($_ENV['APP_NAME'] ?? 'eMunicipal', ENT_QUOTES, 'UTF-8');
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$pageTitle = isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : $appName;
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
    <link rel="stylesheet" href="<?= $appUrl ?>/public/assets/css/app.css?v=4.8">
</head>
<body>
<!-- Skip to main content (keyboard accessibility — Req 19.11 / WCAG 2.1 AA) -->
<a href="#main-content" class="visually-hidden-focusable position-absolute"
   style="top:0.5rem;left:0.5rem;z-index:9999;background:#4A202A;color:#fff;padding:0.5rem 1rem;border-radius:4px;text-decoration:none;font-weight:600;">
    Skip to main content
</a>
<div class="minimal-split">
    <!-- Left panel: branding/decorative (aria-hidden — purely visual) -->
    <div class="minimal-split-left" aria-hidden="true">
        <div class="brand-seal">
            <img src="<?= $appUrl ?>/public/assets/images/biringan-logo-placeholder.svg"
                 alt="" class="biringan-logo-xl">
        </div>
        <div class="minimal-split-divider"></div>
        <h2 class="brand-title"><?= $appName ?></h2>
        <p class="brand-city">Biringan City</p>
        <p>Online Document Request<br>and Management System</p>
        <ul class="feature-list" aria-hidden="true">
            <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Fast community document processing</li>
            <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Track your requests online</li>
            <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Secure and confidential</li>
        </ul>
    </div>
    <!-- Right panel: form content (main content landmark) -->
    <div class="minimal-split-right">
        <main id="main-content" class="form-card">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="<?= $appUrl ?>/public/assets/js/app.js?v=3.4"></script>
</body>
</html>