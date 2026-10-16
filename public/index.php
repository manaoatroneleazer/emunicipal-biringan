<?php

/**
 * eMunicipal — HTTP Entry Point
 *
 * All web requests are routed through this file via .htaccess rewrite.
 * This file:
 *   1. Loads Composer autoloader
 *   2. Loads environment variables from .env
 *   3. Bootstraps the Application
 *   4. Dispatches the current request via routes/web.php
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// 1. Autoloader
// ---------------------------------------------------------------------------
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoloader)) {
    http_response_code(500);
    echo '<h1>Autoloader not found. Run <code>composer install</code>.</h1>';
    exit;
}

require $autoloader;

// ---------------------------------------------------------------------------
// 2. Environment variables (.env)
// ---------------------------------------------------------------------------
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad(); // safeLoad() does not throw if .env is missing

// ---------------------------------------------------------------------------
// 3. Bootstrap Application
// ---------------------------------------------------------------------------
use Emunicipal\Core\Application;

$app = new Application(dirname(__DIR__));

// ---------------------------------------------------------------------------
// 4. Dispatch
// ---------------------------------------------------------------------------
$app->run(dirname(__DIR__) . '/routes/web.php');
