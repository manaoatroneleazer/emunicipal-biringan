<?php

namespace Emunicipal\Helpers;

/**
 * UrlHelper — URL generation utilities.
 *
 * Generates absolute URLs relative to APP_URL so the application
 * works correctly under http://localhost/eMunicipal/ without
 * hard-coded paths (per Phase 3 LAMP compatibility requirement).
 */
class UrlHelper
{
    /**
     * Generate a URL relative to the application base URL.
     *
     * Example: UrlHelper::to('/login') → http://localhost/eMunicipal/login
     */
    public static function to(string $path = ''): string
    {
        $base = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
        $path = ltrim($path, '/');
        return $path === '' ? $base . '/' : $base . '/' . $path;
    }

    /**
     * Generate a URL for a public asset (CSS, JS, images).
     *
     * Example: UrlHelper::asset('css/app.css')
     *   → http://localhost/eMunicipal/public/assets/css/app.css
     */
    public static function asset(string $path): string
    {
        return self::to('public/assets/' . ltrim($path, '/'));
    }

    /**
     * Redirect the browser to a URL and stop execution.
     */
    public static function redirect(string $path): never
    {
        header('Location: ' . self::to($path));
        exit;
    }

    /**
     * Redirect to an absolute URL (for external redirects).
     */
    public static function redirectAbsolute(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}
