#!/usr/bin/env php
<?php

/**
 * eMunicipal — Database Setup Script
 *
 * Run this from the project root via CLI:
 *   php database/setup.php
 *
 * What it does:
 *   1. Loads .env
 *   2. Applies schema.sql (CREATE TABLE IF NOT EXISTS — safe to re-run)
 *      schema.sql is the single source of truth for the complete database
 *      structure, including the final online payment schema (Phase 3).
 *      No separate migration files are needed for fresh installations.
 *   3. Applies seeds.sql (INSERT IGNORE — safe to re-run)
 *   4. Replaces the admin password placeholder hash using ADMIN_INITIAL_PASSWORD from .env
 *
 * For EXISTING databases created before Phase 3 (online payments):
 *   If your payments table only has ENUM('cash') and no transaction_ref column,
 *   apply the upgrade migration manually AFTER running this script:
 *     mysql -h 127.0.0.1 -u root <database> < database/migration_phase3_online_payment.sql
 *   This is only needed once per existing database. Fresh installations
 *   created by this script already include the complete final schema.
 *
 * Prerequisites:
 *   - XAMPP MySQL must be running
 *   - .env must be configured (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
 *   - Add ADMIN_INITIAL_PASSWORD=YourStrongPassword to .env before running
 *     (remove it from .env after setup is confirmed)
 *
 * Security:
 *   - ADMIN_INITIAL_PASSWORD is read only at setup time and never stored
 *   - The hash is generated server-side with password_hash()
 *   - This script is NOT accessible via the web (it is CLI-only)
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

// Load autoloader
$autoloader = $projectRoot . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    fwrite(STDERR, "ERROR: Run 'composer install' first.\n");
    exit(1);
}
require $autoloader;

// Load .env
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable($projectRoot);
try {
    $dotenv->load();
} catch (\Throwable $e) {
    fwrite(STDERR, "ERROR loading .env: " . $e->getMessage() . "\n");
    exit(1);
}

// Read DB config
$host     = $_ENV['DB_HOST']     ?? '127.0.0.1';
$port     = $_ENV['DB_PORT']     ?? '3306';
$dbname   = $_ENV['DB_DATABASE'] ?? 'emunicipal';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "eMunicipal Database Setup\n";
echo str_repeat('-', 40) . "\n";
echo "Host    : {$host}:{$port}\n";
echo "Database: {$dbname}\n";
echo "User    : {$username}\n";
echo str_repeat('-', 40) . "\n";

// Connect (without specifying dbname so we can CREATE DATABASE)
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (\PDOException $e) {
    fwrite(STDERR, "ERROR: Cannot connect to MySQL: " . $e->getMessage() . "\n");
    exit(1);
}

// Apply schema.sql
echo "\n[1/3] Applying schema.sql...\n";
$schema = file_get_contents($projectRoot . '/database/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "ERROR: Cannot read database/schema.sql\n");
    exit(1);
}

// Execute each statement
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
foreach (explodeSql($schema) as $stmt) {
    $pdo->exec($stmt);
}
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "    Schema applied.\n";

// Select the database
$pdo->exec("USE `{$dbname}`");

// Apply seeds.sql
echo "\n[2/3] Applying seeds.sql...\n";
$seeds = file_get_contents($projectRoot . '/database/seeds.sql');
if ($seeds === false) {
    fwrite(STDERR, "ERROR: Cannot read database/seeds.sql\n");
    exit(1);
}
foreach (explodeSql($seeds) as $stmt) {
    $pdo->exec($stmt);
}
echo "    Seeds applied.\n";

// Update admin password from ADMIN_INITIAL_PASSWORD env var
echo "\n[3/3] Setting administrator password...\n";
$adminPassword = $_ENV['ADMIN_INITIAL_PASSWORD'] ?? '';
if ($adminPassword === '') {
    echo "    WARNING: ADMIN_INITIAL_PASSWORD not set in .env.\n";
    echo "    The placeholder admin account uses password: Admin@1234\n";
    echo "    Add ADMIN_INITIAL_PASSWORD=YourPassword to .env and re-run,\n";
    echo "    OR change the password via the admin profile page after first login.\n";
} else {
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "UPDATE `users` SET `password_hash` = :hash WHERE `email` = 'admin@emunicipal.local' LIMIT 1"
    );
    $stmt->execute([':hash' => $hash]);
    echo "    Administrator password updated from ADMIN_INITIAL_PASSWORD.\n";
    echo "    RECOMMENDATION: Remove ADMIN_INITIAL_PASSWORD from .env after setup.\n";
}

echo "\n" . str_repeat('=', 40) . "\n";
echo "Setup complete.\n";
echo "  - Visit: http://localhost/eMunicipal/\n";
echo "  - Admin login: admin@emunicipal.local\n";
echo "  - Admin password: as configured above\n";
echo str_repeat('=', 40) . "\n\n";

// ---------------------------------------------------------------------------
// Helper: split SQL file into individual statements
// ---------------------------------------------------------------------------
function explodeSql(string $sql): array
{
    $statements = [];
    $current    = '';
    $inString   = false;
    $stringChar = '';

    $lines = explode("\n", $sql);
    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Skip pure comment lines
        if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }

        $current .= $line . "\n";

        // Detect end-of-statement semicolon (simple heuristic — sufficient for DDL)
        if (str_ends_with(rtrim($line), ';')) {
            $stmt = trim($current);
            if ($stmt !== '' && $stmt !== ';') {
                $statements[] = $stmt;
            }
            $current = '';
        }
    }

    if (trim($current) !== '') {
        $statements[] = trim($current);
    }

    return array_filter($statements);
}
