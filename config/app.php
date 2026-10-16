<?php

/**
 * Application Configuration
 *
 * Reads values from environment variables (loaded from .env).
 * Never hard-code credentials or sensitive values here.
 */

return [
    // Application identity
    'name'    => $_ENV['APP_NAME']  ?? 'eMunicipal',
    'env'     => $_ENV['APP_ENV']   ?? 'production',
    'debug'   => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'     => $_ENV['APP_URL']   ?? 'http://localhost/eMunicipal',

    // Filesystem paths (portable — no Windows-specific separators hard-coded)
    'base_path'    => dirname(__DIR__),
    'app_path'     => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app',
    'config_path'  => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config',
    'storage_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage',
    'views_path'   => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views',
    'public_path'  => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public',

    // Session (duration in minutes — read from env, fallback to 30 per Req 20.2)
    'session_lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 30),

    // File uploads (max size in MB — per Req 18.3 and Req 20.2)
    'upload_max_size_mb' => (int) ($_ENV['UPLOAD_MAX_SIZE_MB'] ?? 5),
    'upload_path'        => $_ENV['UPLOAD_PATH'] ?? 'storage/uploads',
];
