<?php

/**
 * Database Configuration
 *
 * Returns a PDO instance configured for MySQL with:
 * - utf8mb4 character set (per database.md Section 1)
 * - Exceptions on error
 * - FETCH_ASSOC default fetch mode
 * - Emulated prepares disabled (true prepared statements)
 *
 * All credentials are read from environment variables.
 * Never hard-code credentials in this file.
 */

return [
    'driver'   => 'mysql',
    'host'     => $_ENV['DB_HOST']     ?? '127.0.0.1',
    'port'     => $_ENV['DB_PORT']     ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'emunicipal',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
