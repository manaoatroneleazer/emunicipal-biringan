<?php
$appUrl         = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$formAction     = $appUrl . '/admin/profile';
$passwordAction = $appUrl . '/admin/profile/password';
$isResident     = false;
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>/admin/dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">My Profile</li>
    </ol>
</nav>
<?php
$initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
$fullName = htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')), ENT_QUOTES, 'UTF-8');
$email    = htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<div class="b2-profile-header mb-4">
    <div class="b2-avatar" aria-hidden="true"><?= $initials ?></div>
    <div style="position:relative;z-index:1;">
        <h1 class="b2-profile-name"><?= $fullName ?></h1>
        <p class="b2-profile-email"><?= $email ?></p>
        <span class="role-badge role-badge-admin mt-1 d-inline-block">Admin</span>
    </div>
</div>
<?php require dirname(__DIR__, 2) . '/partials/profile_form.php'; ?>
