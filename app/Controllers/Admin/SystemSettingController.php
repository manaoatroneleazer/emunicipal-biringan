<?php

namespace Emunicipal\Controllers\Admin;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Services\SettingsService;
use Emunicipal\Core\Database;
use Emunicipal\Core\Logger;

/**
 * SystemSettingController — Admin configurable system settings.
 *
 * Implements Req 20:
 *  20.1 — Settings page for Admin
 *  20.2 — Required settings: office_name, office_address, office_contact_number,
 *          office_email, max_upload_size_mb, session_timeout_minutes, records_per_page
 *  20.3 — Validate each value before saving
 *  20.4 — Audit log on every successful change
 *  20.5 — Session timeout from system_settings applied as inactivity threshold
 *  20.6 — Transaction rollback on failure
 *
 * Routes:
 *  GET  /admin/settings
 *  POST /admin/settings
 *
 * Security:
 *  - Admin-only (AuthMiddleware::requireRole('admin'))
 *  - CSRF validated on POST
 *  - Only whitelisted keys accepted — arbitrary keys rejected
 *  - All SQL uses PDO prepared statements
 *  - No sensitive/credential fields accepted through this form
 */
class SystemSettingController extends BaseController
{
    /**
     * Whitelisted setting keys and their validation rules.
     * Format: key => [type, min, max, label]
     *   type: 'string' | 'email' | 'int'
     */
    private const SETTINGS_SCHEMA = [
        'office_name'            => ['type' => 'string', 'max' => 255, 'required' => true,  'label' => 'Office Name'],
        'office_address'         => ['type' => 'string', 'max' => 500, 'required' => true,  'label' => 'Office Address'],
        'office_contact_number'  => ['type' => 'string', 'max' => 50,  'required' => false, 'label' => 'Office Contact Number'],
        'office_email'           => ['type' => 'email',  'max' => 255, 'required' => true,  'label' => 'Office Email'],
        'max_upload_size_mb'     => ['type' => 'int',    'min' => 1,   'max' => 20,          'label' => 'Maximum Upload Size (MB)'],
        'session_timeout_minutes'=> ['type' => 'int',    'min' => 5,   'max' => 120,         'label' => 'Session Timeout (Minutes)'],
        'records_per_page'       => ['type' => 'int',    'allowed' => [10, 20, 50],           'label' => 'Records Per Page'],
    ];

    // -------------------------------------------------------------------------
    // GET /admin/settings
    // -------------------------------------------------------------------------

    public function index(): void
    {
        AuthMiddleware::requireRole('admin');

        $settings = $this->loadSettings();

        $this->renderWithLayout('admin/settings/index', [
            'title'      => 'System Settings — eMunicipal',
            'activePage' => 'settings',
            'settings'   => $settings,
            'csrfToken'  => SecurityHelper::getCsrfToken(),
            'errors'     => SessionHelper::getFlash('settings_errors', []),
        ], 'layouts/auth');
    }

    // -------------------------------------------------------------------------
    // POST /admin/settings
    // -------------------------------------------------------------------------

    public function update(): void
    {
        AuthMiddleware::requireRole('admin');

        // CSRF (Req 14.3)
        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('settings_errors', ['form' => 'Invalid security token. Please try again.']);
            UrlHelper::redirect('/admin/settings');
        }

        $adminId    = AuthMiddleware::userId();
        $submitted  = $_POST['settings'] ?? [];

        if (!is_array($submitted)) {
            SessionHelper::flash('settings_errors', ['form' => 'Invalid request format.']);
            UrlHelper::redirect('/admin/settings');
        }

        // Validate all submitted values before touching the DB
        $errors     = [];
        $validated  = [];

        foreach (self::SETTINGS_SCHEMA as $key => $schema) {
            // Only process keys actually submitted — ignore any not in the form
            if (!array_key_exists($key, $submitted)) {
                continue;
            }

            $rawValue = trim((string)($submitted[$key] ?? ''));
            $error    = $this->validateField($key, $rawValue, $schema);

            if ($error !== null) {
                $errors[$key] = $error;
            } else {
                $validated[$key] = $rawValue;
            }
        }

        // Reject any extra keys not in the schema (prevents arbitrary DB writes)
        foreach (array_keys($submitted) as $key) {
            if (!array_key_exists($key, self::SETTINGS_SCHEMA)) {
                // Silently ignore — do not treat as an error, just don't save
                Logger::error("SystemSettingController: rejected unknown setting key '{$key}'");
            }
        }

        if (!empty($errors)) {
            SessionHelper::flash('settings_errors', $errors);
            UrlHelper::redirect('/admin/settings');
        }

        if (empty($validated)) {
            SessionHelper::flash('settings_errors', ['form' => 'No settings were submitted.']);
            UrlHelper::redirect('/admin/settings');
        }

        // Load current values for audit snapshot (old values)
        $currentSettings = $this->loadSettings();

        // Persist — all updates in a single transaction (Req 20.6)
        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            try {
                $stmt = $db->prepare(
                    "UPDATE system_settings SET value = :value WHERE `key` = :key"
                );

                foreach ($validated as $key => $value) {
                    $stmt->execute([':value' => $value, ':key' => $key]);
                }

                $db->commit();

            } catch (\Throwable $inner) {
                $db->rollBack();
                throw $inner;
            }

            // Audit each changed setting (Req 20.4) — outside transaction, non-fatal
            foreach ($validated as $key => $newValue) {
                $oldValue = $currentSettings[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $label = self::SETTINGS_SCHEMA[$key]['label'] ?? $key;
                    AuditLogService::log(
                        $adminId, 'admin',
                        AuditLog::ACTION_SETTING_CHANGED,
                        'settings',
                        'system_settings', null,
                        [$key => $oldValue],
                        [$key => $newValue],
                        "Setting '{$label}' changed by admin ID {$adminId}"
                    );
                }
            }

            // Apply settings to $_ENV so they take effect for the remainder of this
            // request. On the NEXT request Application bootstrap calls
            // SettingsService::applyRuntimeSettings() which reads from DB — so the
            // persisted values are automatically picked up without any further action.
            // (Req 20.5 — session timeout; also upload size and records_per_page)
            SettingsService::clearCache();
            SettingsService::applyRuntimeSettings();

            SessionHelper::flash('success', 'System settings saved successfully.');

        } catch (\Throwable $e) {
            Logger::error('SystemSettingController::update failed: ' . $e->getMessage());
            SessionHelper::flash('settings_errors', [
                'form' => 'Settings could not be saved. The database operation failed and was rolled back.',
            ]);
        }

        UrlHelper::redirect('/admin/settings');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Load all whitelisted settings from the database.
     * Returns a flat key → value map for the 7 required settings.
     * Delegates to SettingsService::all() so the same cached data is reused.
     */
    public function loadSettings(): array
    {
        $defaults = [
            'office_name'             => '',
            'office_address'          => '',
            'office_contact_number'   => '',
            'office_email'            => '',
            'max_upload_size_mb'      => '5',
            'session_timeout_minutes' => '30',
            'records_per_page'        => '20',
        ];

        $fromDb = SettingsService::all();
        foreach (array_keys($defaults) as $key) {
            if (isset($fromDb[$key]) && $fromDb[$key] !== '') {
                $defaults[$key] = $fromDb[$key];
            }
        }
        return $defaults;
    }

    /**
     * Validate a single setting value against its schema rules.
     * Returns null on success, or an error message string on failure.
     */
    private function validateField(string $key, string $rawValue, array $schema): ?string
    {
        $label = $schema['label'] ?? $key;

        // Required check
        if (($schema['required'] ?? false) && $rawValue === '') {
            return "{$label} is required.";
        }

        switch ($schema['type']) {
            case 'email':
                if ($rawValue !== '' && !filter_var($rawValue, FILTER_VALIDATE_EMAIL)) {
                    return "{$label} must be a valid email address.";
                }
                break;

            case 'string':
                $max = $schema['max'] ?? 255;
                if (mb_strlen($rawValue) > $max) {
                    return "{$label} must not exceed {$max} characters.";
                }
                break;

            case 'int':
                if (!ctype_digit($rawValue) || (int)$rawValue < 1) {
                    return "{$label} must be a positive whole number.";
                }
                $intVal = (int)$rawValue;
                if (isset($schema['min']) && $intVal < $schema['min']) {
                    return "{$label} must be at least {$schema['min']}.";
                }
                if (isset($schema['max']) && $intVal > $schema['max']) {
                    return "{$label} must not exceed {$schema['max']}.";
                }
                if (isset($schema['allowed']) && !in_array($intVal, $schema['allowed'], true)) {
                    return "{$label} must be one of: " . implode(', ', $schema['allowed']) . '.';
                }
                break;
        }

        return null;
    }

    /**
     * Expose the schema for use in tests.
     */
    public static function getSettingsSchema(): array
    {
        return self::SETTINGS_SCHEMA;
    }
}
