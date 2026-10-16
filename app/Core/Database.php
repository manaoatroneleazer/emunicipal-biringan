<?php

namespace Emunicipal\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database — PDO connection manager
 *
 * Provides a singleton PDO instance for the application.
 * All database interactions in later phases must use this connection.
 *
 * Uses PDO prepared statements exclusively — never raw string concatenation
 * with user input (per Req 14.2, database.md Section 18.3).
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Returns the shared PDO instance, creating it on first call.
     *
     * Throws a RuntimeException with a safe message if the connection fails,
     * so raw PDO/MySQL errors are never exposed to end users.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            self::$instance = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            // Log technical detail server-side; surface a safe message to caller
            Logger::error('Database connection failed: ' . $e->getMessage());

            throw new RuntimeException(
                'Unable to connect to the database. Please check your configuration.'
            );
        }

        return self::$instance;
    }

    /**
     * Resets the singleton (useful for testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    // Prevent direct instantiation — use getInstance()
    private function __construct() {}
    private function __clone() {}
}
