<?php

namespace Emunicipal\Middleware;

use Emunicipal\Core\Router;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;

/**
 * AuthMiddleware — enforces authentication and role-based access control.
 *
 * Called at the top of every protected controller method.
 *
 * Per Req 2.9: role-based access control is enforced server-side on every request.
 * Per Req 2.10: sessions expire after 30 minutes of inactivity.
 * Per Req 3.3: wrong-role access returns HTTP 403.
 * Per Req 3.5: unauthenticated access redirects to /login.
 */
class AuthMiddleware
{
    private const ACTIVITY_KEY  = '_last_activity';
    private const TIMEOUT_MINS  = 30; // fallback if env not set

    /**
     * Return the session timeout in minutes.
     * Reads SESSION_LIFETIME from environment (set by settings page or .env).
     * Falls back to the hardcoded constant so existing behaviour is unchanged
     * when the env var has not been set.
     * Per Req 20.5 — session timeout must be driven by the system_settings value.
     */
    public static function getTimeoutMinutes(): int
    {
        $env = (int)($_ENV['SESSION_LIFETIME'] ?? 0);
        return $env > 0 ? $env : self::TIMEOUT_MINS;
    }

    // -------------------------------------------------------------------------
    // Public gate methods — call these at the top of controller actions
    // -------------------------------------------------------------------------

    /**
     * Require the user to be authenticated.
     * Redirects to /login if not authenticated or if session has expired.
     */
    public static function requireAuth(): void
    {
        self::checkSessionTimeout();

        if (!self::isAuthenticated()) {
            SessionHelper::flash('redirect_after_login', $_SERVER['REQUEST_URI'] ?? '/');
            UrlHelper::redirect('/login');
        }

        self::refreshActivity();
    }

    /**
     * Require the user to have a specific role (resident | staff | admin).
     * Calls requireAuth() first, then checks the role.
     * Returns HTTP 403 if the authenticated user's role does not match.
     */
    public static function requireRole(string $role): void
    {
        self::requireAuth();

        if (self::getRole() !== $role) {
            Router::forbidden();
        }
    }

    /**
     * Require the user to be a guest (not logged in).
     * Redirects authenticated users to their dashboard.
     * Used on login/register pages.
     */
    public static function requireGuest(): void
    {
        if (self::isAuthenticated()) {
            self::redirectToDashboard();
        }
    }

    // -------------------------------------------------------------------------
    // Session data accessors
    // -------------------------------------------------------------------------

    /**
     * Return the authenticated user's ID, or null if not logged in.
     */
    public static function userId(): ?int
    {
        $id = SessionHelper::get('user_id');
        return $id !== null ? (int) $id : null;
    }

    /**
     * Return the authenticated user's role name (resident | staff | admin), or null.
     */
    public static function getRole(): ?string
    {
        return SessionHelper::get('user_role');
    }

    /**
     * Return the authenticated user's first name, or null.
     */
    public static function firstName(): ?string
    {
        return SessionHelper::get('user_first_name');
    }

    /**
     * Return the authenticated user's last name, or null.
     */
    public static function lastName(): ?string
    {
        return SessionHelper::get('user_last_name');
    }

    /**
     * Return the authenticated user's full name.
     */
    public static function fullName(): string
    {
        $first = SessionHelper::get('user_first_name', '');
        $last  = SessionHelper::get('user_last_name', '');
        return trim($first . ' ' . $last);
    }

    /**
     * Return the authenticated user's email.
     */
    public static function email(): ?string
    {
        return SessionHelper::get('user_email');
    }

    /**
     * Return true if the current user is logged in.
     */
    public static function isAuthenticated(): bool
    {
        return SessionHelper::has('user_id');
    }

    /**
     * Store user data in the session after a successful login.
     */
    public static function loginUser(array $user): void
    {
        SessionHelper::regenerate();
        SessionHelper::set('user_id',         (int) $user['id']);
        SessionHelper::set('user_role',        $user['role']);
        SessionHelper::set('user_first_name',  $user['first_name']);
        SessionHelper::set('user_last_name',   $user['last_name']);
        SessionHelper::set('user_email',       $user['email']);
        self::refreshActivity();
    }

    /**
     * Destroy the session (logout).
     */
    public static function logoutUser(): void
    {
        SessionHelper::destroy();
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private static function checkSessionTimeout(): void
    {
        if (!SessionHelper::has('user_id')) {
            return; // Not logged in — nothing to time out
        }

        $lastActivity = SessionHelper::get(self::ACTIVITY_KEY);
        if ($lastActivity !== null) {
            $timeoutSeconds = self::getTimeoutMinutes() * 60;
            if ((time() - (int) $lastActivity) > $timeoutSeconds) {
                SessionHelper::destroy();
                UrlHelper::redirect('/login');
            }
        }
    }

    private static function refreshActivity(): void
    {
        SessionHelper::set(self::ACTIVITY_KEY, time());
    }

    private static function redirectToDashboard(): never
    {
        $role = self::getRole();
        match ($role) {
            'staff' => UrlHelper::redirect('/staff/dashboard'),
            'admin' => UrlHelper::redirect('/admin/dashboard'),
            default => UrlHelper::redirect('/resident/dashboard'),
        };
    }
}
