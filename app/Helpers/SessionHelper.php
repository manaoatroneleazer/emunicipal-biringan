<?php

namespace Emunicipal\Helpers;

/**
 * SessionHelper — secure PHP session management.
 *
 * Configures security-appropriate session settings:
 * - HttpOnly cookies (prevents JS access to session ID)
 * - SameSite=Lax (CSRF mitigation)
 * - Secure flag when HTTPS is available
 * - Session ID regeneration on privilege change
 *
 * Native PHP sessions are used per the approved architecture.
 * No sessions database table is required (per Phase 3 instruction 22).
 */
class SessionHelper
{
    private static bool $started = false;

    /**
     * Start a secure PHP session.
     * Safe to call multiple times — starts only once per request.
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        // G4: APP_HTTPS=true lets production deployments explicitly guarantee the
        // Secure cookie flag even when running behind a proxy that strips HTTPS headers.
        // Falls back to runtime detection so local XAMPP HTTP dev is unaffected.
        $forceHttps = filter_var($_ENV['APP_HTTPS'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

        $isHttps = $forceHttps
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

        session_set_cookie_params([
            'lifetime' => 0,            // Browser-session cookie (expires when browser closes)
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,     // Send only over HTTPS when available
            'httponly' => true,         // Inaccessible to JavaScript
            'samesite' => 'Lax',        // CSRF protection
        ]);

        session_name('EMUSESSID');
        session_start();

        self::$started = true;
    }

    /**
     * Set a session value.
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value, with an optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a session key exists.
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value.
     */
    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Regenerate the session ID.
     * Must be called on login, logout, and privilege escalation (per Req 14).
     */
    public static function regenerate(bool $deleteOld = true): void
    {
        self::start();
        session_regenerate_id($deleteOld);
    }

    /**
     * Destroy the session completely (used on logout).
     */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
        self::$started = false;
    }

    /**
     * Flash a value into the session for one request only.
     */
    public static function flash(string $key, mixed $value): void
    {
        self::set("_flash_{$key}", $value);
    }

    /**
     * Read and clear a flash value.
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $flashKey = "_flash_{$key}";
        $value    = self::get($flashKey, $default);
        self::forget($flashKey);
        return $value;
    }
}
