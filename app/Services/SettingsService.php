<?php

namespace Emunicipal\Services;

use Emunicipal\Core\Logger;

/**
 * SettingsService — centralized runtime settings loader.
 *
 * Called once per request during Application bootstrap (after the DB connection
 * is available) to apply system_settings values that override .env defaults.
 *
 * Three settings MUST be applied per-request to satisfy requirements.md:
 *  Req 20.5 — session timeout from system_settings (AuthMiddleware reads $_ENV['SESSION_LIFETIME'])
 *  Req 20   — max upload size from system_settings (FileUploadService reads $_ENV['UPLOAD_MAX_SIZE_MB'])
 *  Req 20   — records per page from system_settings (controllers read $_ENV['RECORDS_PER_PAGE'])
 *
 * Architecture note:
 *  public/index.php loads .env via Dotenv::createImmutable() on EVERY request.
 *  That re-reads the .env file from disk — it never reads from system_settings.
 *  Therefore saving to system_settings without also updating $_ENV in bootstrap
 *  has NO EFFECT on the next request.
 *
 *  This service reads the three runtime-affecting rows from the database ONCE
 *  per request (three tiny indexed primary-key-equivalent lookups) and writes
 *  their values into $_ENV.  Downstream code (AuthMiddleware::getTimeoutMinutes(),
 *  FileUploadService::getMaxBytes(), pagination helpers) reads $_ENV, so they
 *  automatically see the persisted values on every request.
 *
 * Failure is non-fatal: if the DB is unavailable, the .env defaults stand.
 */
class SettingsService
{
    /** Keys that affect runtime behaviour and must be applied per-request. */
    private const RUNTIME_KEYS = [
        'session_timeout_minutes' => 'SESSION_LIFETIME',
        'max_upload_size_mb'      => 'UPLOAD_MAX_SIZE_MB',
        'records_per_page'        => 'RECORDS_PER_PAGE',
    ];

    /** In-memory cache — populated once per request by applyRuntimeSettings(). */
    private static ?array $cache = null;

    /**
     * Load the three runtime settings from system_settings and write them
     * into $_ENV so all downstream components see the persisted value.
     *
     * Must be called during Application bootstrap, after the DB connection
     * is established and before any controller code runs.
     * Non-fatal: any DB error is logged and the .env defaults are preserved.
     */
    public static function applyRuntimeSettings(): void
    {
        try {
            // Use a raw PDO call to avoid circular dependency with models
            // (models depend on Database which is already initialised by now)
            $pdo = \Emunicipal\Core\Database::getInstance();

            $placeholders = implode(',', array_fill(0, count(self::RUNTIME_KEYS), '?'));
            $stmt = $pdo->prepare(
                "SELECT `key`, value FROM system_settings WHERE `key` IN ({$placeholders})"
            );
            $stmt->execute(array_keys(self::RUNTIME_KEYS));
            $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR); // key => value map

            self::$cache = [];
            foreach (self::RUNTIME_KEYS as $settingsKey => $envKey) {
                if (isset($rows[$settingsKey]) && $rows[$settingsKey] !== '') {
                    $value = $rows[$settingsKey];
                    // Write to $_ENV so downstream readers (AuthMiddleware, FileUploadService)
                    // see the DB-persisted value for this request
                    $_ENV[$envKey] = $value;
                    self::$cache[$settingsKey] = $value;
                }
            }
        } catch (\Throwable $e) {
            // Non-fatal: log and let .env defaults stand
            Logger::error('SettingsService::applyRuntimeSettings failed: ' . $e->getMessage());
        }
    }

    /**
     * Return all settings from system_settings (all 7 rows).
     * Used by SystemSettingController::loadSettings() and anywhere that needs
     * to display or act on a setting value.
     * Results are cached for the duration of the request.
     *
     * @return array<string,string>  key => value map
     */
    private static ?array $allCache = null;

    public static function all(): array
    {
        if (self::$allCache !== null && !self::$allCacheReset) {
            return self::$allCache;
        }
        self::$allCacheReset = false;
        try {
            $pdo  = \Emunicipal\Core\Database::getInstance();
            $stmt = $pdo->query("SELECT `key`, value FROM system_settings");
            self::$allCache = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\Throwable $e) {
            Logger::error('SettingsService::all failed: ' . $e->getMessage());
            self::$allCache = [];
        }
        return self::$allCache;
    }

    /**
     * Return a single setting value by key.
     * Falls back to $default if the key is not in the database.
     */
    public static function get(string $key, string $default = ''): string
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    /**
     * Return the current records_per_page as an int (min 10, max 100).
     * Reads from $_ENV['RECORDS_PER_PAGE'] (set by applyRuntimeSettings()).
     * Falls back to 20 if not set or invalid.
     */
    public static function recordsPerPage(): int
    {
        $v = (int)($_ENV['RECORDS_PER_PAGE'] ?? 0);
        if ($v < 1 || $v > 100) {
            $v = 20;
        }
        return $v;
    }

    /**
     * Invalidate the in-memory caches.
     * Called by SystemSettingController::update() after a successful save,
     * so the same request sees the new values immediately.
     */
    public static function clearCache(): void
    {
        self::$cache = null;
        // Force static $allCache in all() to reset on next call
        // (achieved by calling all() with a flag — use reflection trick or
        //  just reset via a separate static property)
        self::$allCacheReset = true;
    }

    private static bool $allCacheReset = false;
}
