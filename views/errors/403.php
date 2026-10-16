<?php http_response_code(403); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied — eMunicipal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .error-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-icon { font-size: 5rem; color: #c8922a; }
    </style>
</head>
<body>
<div class="error-wrapper">
    <div class="text-center px-3">
        <div class="error-icon mb-3"><i class="bi bi-shield-lock"></i></div>
        <h1 class="fw-bold" style="color:#1a202c;">403 — Access Denied</h1>
        <p class="text-muted mb-4">You do not have permission to access this page.<br>If you believe this is an error, please contact the municipal office.</p>
        <a href="<?= htmlspecialchars($_ENV['APP_URL'] ?? '/eMunicipal', ENT_QUOTES, 'UTF-8') ?>/" class="btn btn-primary px-4">
            <i class="bi bi-house me-2"></i>Return to Home
        </a>
    </div>
</div>
</body>
</html>

