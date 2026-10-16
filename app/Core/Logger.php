<?php

namespace Emunicipal\Core;

/**
 * Logger — writes application events to daily log files.
 *
 * Log files are stored in storage/logs/ which is outside public/.
 * Never log passwords, hashes, credentials, or tokens (per Phase 3 instruction 21).
 *
 * Log levels: error, warning, info, debug
 * Debug entries are only written when APP_DEBUG=true.
 */
class Logger
{
    private static string $logDir = '';

    /**
     * Initialise the log directory path.
     * Called once during application bootstrap.
     */
    public static function init(string $basePath): void
    {
        self::$logDir = rtrim($basePath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'logs';

        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($debug) {
            self::write('DEBUG', $message, $context);
        }
    }

    /**
     * Write a raw log entry — used by the set_error_handler in Application
     * to route PHP warnings/notices through Logger without level filtering.
     * Public so Application can call it; not intended for general use.
     */
    public static function write_raw(string $level, string $message, array $context = []): void
    {
        self::write($level, $message, $context);
    }

    /**
     * Write a log entry to storage/logs/app-YYYY-MM-DD.log
     */
    private static function write(string $level, string $message, array $context = []): void
    {
        if (self::$logDir === '') {
            // Fallback: initialise from current working context
            self::init(dirname(__DIR__, 2));
        }

        $date    = date('Y-m-d');
        $time    = date('Y-m-d H:i:s');
        $file    = self::$logDir . DIRECTORY_SEPARATOR . "app-{$date}.log";

        $contextStr = '';
        if (!empty($context)) {
            // Sanitise context — never log password-like keys
            $safe = array_filter(
                $context,
                fn($key) => !preg_match('/password|secret|token|hash|credential/i', (string)$key),
                ARRAY_FILTER_USE_KEY
            );
            $contextStr = ' ' . json_encode($safe, JSON_UNESCAPED_UNICODE);
        }

        $line = "[{$time}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        // Append to daily log file; suppress errors — logging must not break the app
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
