<?php

namespace Emunicipal\Core;

use Emunicipal\Helpers\SessionHelper;

/**
 * Application — bootstrap and orchestration class.
 *
 * Responsibilities:
 * 1. Initialise logging.
 * 2. Configure error handling.
 * 3. Start secure PHP sessions.
 * 4. Load application configuration.
 * 5. Provide access to the router.
 *
 * public/index.php and api/index.php are thin entry points
 * that create an Application instance and call run().
 */
class Application
{
    private static ?Application $app = null;

    private array  $config = [];
    private Router $router;
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        self::$app = $this;

        // 1. Initialise logger first so subsequent steps can log errors
        Logger::init($this->basePath);

        // 2. Configure PHP error handling based on APP_DEBUG
        $this->configureErrorHandling();

        // 3. Start secure session
        SessionHelper::start();

        // 4. Load app config
        $this->config = require $this->basePath . '/config/app.php';

        // 5. Build the router (strips /eMunicipal prefix from URIs automatically)
        $urlPath = parse_url($this->config['url'] ?? '', PHP_URL_PATH) ?? '';
        $this->router = new Router($urlPath);

        // 6. Apply runtime settings from system_settings DB into $_ENV
        //    so AuthMiddleware, FileUploadService, and pagination controllers
        //    see the persisted configured values on every request. (Req 20.5)
        //    Non-fatal: if DB is unavailable, .env defaults stand.
        \Emunicipal\Services\SettingsService::applyRuntimeSettings();
    }

    /**
     * Load routes and dispatch the current request.
     */
    public function run(string $routeFile): void
    {
        if (!file_exists($routeFile)) {
            Logger::error("Route file not found: {$routeFile}");
            http_response_code(500);
            $viewPath = $this->basePath . '/views/errors/500.php';
            if (file_exists($viewPath)) {
                require $viewPath;
            } else {
                echo '<h1>500 — Internal Server Error</h1><p>Please try again later.</p>';
            }
            return;
        }

        // Routes file receives $router by reference
        $router = $this->router;
        require $routeFile;

        $this->router->dispatch();
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getConfig(string $key = ''): mixed
    {
        if ($key === '') {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public static function getInstance(): ?Application
    {
        return self::$app;
    }

    // -------------------------------------------------------------------------
    // Error handling
    // -------------------------------------------------------------------------

    private function configureErrorHandling(): void
    {
        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($debug) {
            // Development: show errors in browser for convenience
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            // Production: hide errors from users; log them instead
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(E_ALL);
        }

        // Route PHP warnings, notices, and non-fatal errors through Logger
        // so they are captured in storage/logs/ even when display_errors=0.
        // This covers errors that occur before or between exception handler calls.
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            // Respect error_reporting level (@ operator suppression sets it to 0)
            if (!(error_reporting() & $errno)) {
                return false; // let PHP's default handler run for suppressed errors
            }
            $level = match (true) {
                (bool)($errno & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) => 'ERROR',
                (bool)($errno & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING)) => 'WARNING',
                default => 'NOTICE',
            };
            Logger::write_raw($level, "PHP [{$errno}] {$errstr}", [
                'file' => basename($errfile),
                'line' => $errline,
            ]);
            // Return true = don't execute PHP internal error handler
            // (prevents display_errors from printing it even in debug mode where
            //  we already capture it)
            return false; // false = also let PHP default handler run (shows in browser when display_errors=1)
        });

        // Custom exception handler — prevents raw stack traces reaching users
        set_exception_handler(function (\Throwable $e) use ($debug) {
            Logger::error(
                'Uncaught exception: ' . $e->getMessage(),
                ['file' => $e->getFile(), 'line' => $e->getLine()]
            );

            http_response_code(500);

            // Emit security headers from PHP as a safety net in case the
            // exception fires before .htaccess mod_headers can apply them
            // (e.g., during CLI tests or non-Apache environments). (Req 14.11)
            if (!headers_sent()) {
                header('X-Content-Type-Options: nosniff');
                header('X-Frame-Options: DENY');
            }

            if ($this->isApiRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => $debug
                        ? $e->getMessage()
                        : 'An unexpected error occurred. Please try again.',
                    'errors'  => [],
                ]);
            } else {
                $viewPath = $this->basePath . '/views/errors/500.php';
                if (file_exists($viewPath)) {
                    require $viewPath;
                } else {
                    echo '<h1>500 — Internal Server Error</h1><p>Please try again later.</p>';
                }
            }
        });
    }

    /**
     * Detect whether the current request targets the /api/ path.
     */
    private function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_contains($uri, '/api/');
    }
}
