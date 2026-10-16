<?php

namespace Emunicipal\Services;

use Emunicipal\Models\AuditLog;
use Emunicipal\Core\Logger;

/**
 * AuditLogService — centralised audit log writer.
 *
 * All significant system events are recorded here (Req 13).
 * Failures are logged to the file log but never bubble up to interrupt
 * the main operation (per Req 13: audit log must not break the app).
 *
 * IP address is read from $_SERVER — never from user-supplied input.
 */
class AuditLogService
{
    /**
     * Record an audit log entry.
     *
     * @param ?int    $userId      Acting user ID (null for system events)
     * @param ?string $userRole    Role at time of action (denormalized snapshot)
     * @param string  $action      Action constant from AuditLog::ACTION_*
     * @param string  $module      Module name: auth, users, requests, documents, files, settings, reports
     * @param ?string $recordTable Affected table name
     * @param ?int    $recordId    Affected record primary key
     * @param mixed   $oldValue    Before-state (will be JSON-encoded)
     * @param mixed   $newValue    After-state (will be JSON-encoded)
     * @param ?string $description Human-readable description
     */
    public static function log(
        ?int    $userId,
        ?string $userRole,
        string  $action,
        string  $module,
        ?string $recordTable = null,
        ?int    $recordId    = null,
        mixed   $oldValue    = null,
        mixed   $newValue    = null,
        ?string $description = null
    ): void {
        try {
            $ipAddress = self::getIpAddress();
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

            (new AuditLog())->log(
                $userId,
                $userRole,
                $action,
                $module,
                $recordTable,
                $recordId,
                $oldValue,
                $newValue,
                $description,
                $ipAddress,
                $userAgent !== '' ? $userAgent : null
            );
        } catch (\Throwable $e) {
            // Audit log failure must never break the operation (Req 13 compliance)
            Logger::error('Audit log write failed: ' . $e->getMessage(), [
                'action' => $action,
                'module' => $module,
            ]);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Get the client IP address, preferring X-Forwarded-For for proxy environments.
     * Always returns a sanitized value safe to store.
     */
    private static function getIpAddress(): string
    {
        // X-Forwarded-For may contain a comma-separated list; use the first entry
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip    = trim($parts[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            return '';
        }

        // Validate to prevent garbage being stored (max 45 chars for IPv6)
        return filter_var($ip, FILTER_VALIDATE_IP) ? substr($ip, 0, 45) : '';
    }
}
