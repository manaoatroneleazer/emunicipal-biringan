<?php

namespace Emunicipal\Controllers;

use Emunicipal\Helpers\ResponseHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Core\Logger;

/**
 * BaseController — shared functionality for all controllers.
 *
 * All controllers extend this class.
 * Business logic belongs in Services, not here.
 */
abstract class BaseController
{
    protected string $viewsPath;

    public function __construct()
    {
        $this->viewsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views';
    }

    // -------------------------------------------------------------------------
    // View rendering
    // -------------------------------------------------------------------------

    /**
     * Render a PHP view file with the provided data.
     *
     * Example: $this->render('home/index', ['title' => 'Home']);
     *
     * The view file path is relative to the views/ directory.
     * Data is extracted into the view scope.
     */
    protected function render(string $view, array $data = []): void
    {
        $viewFile = $this->viewsPath
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $view)
            . '.php';

        if (!file_exists($viewFile)) {
            Logger::error("View not found: {$viewFile}");
            http_response_code(500);
            echo '<h1>View Not Found</h1>';
            return;
        }

        // Extract data variables into the view scope
        extract($data, EXTR_SKIP);

        require $viewFile;
    }

    /**
     * Render a view wrapped in the main layout.
     *
     * @param string $view     View file relative to views/
     * @param array  $data     Data passed to the view
     * @param string $layout   Layout file relative to views/layouts/
     */
    protected function renderWithLayout(
        string $view,
        array  $data   = [],
        string $layout = 'layouts/public'
    ): void {
        // Make content available to the layout as $content
        ob_start();
        $this->render($view, $data);
        $data['content'] = ob_get_clean();

        $this->render($layout, $data);
    }

    // -------------------------------------------------------------------------
    // JSON responses (delegates to ResponseHelper)
    // -------------------------------------------------------------------------

    protected function json(mixed $data = [], string $message = 'Success.', int $status = 200): never
    {
        ResponseHelper::success($data, $message, $status);
    }

    protected function jsonError(string $message, array $errors = [], int $status = 400): never
    {
        ResponseHelper::error($message, $errors, $status);
    }

    // -------------------------------------------------------------------------
    // Redirects
    // -------------------------------------------------------------------------

    protected function redirect(string $path): never
    {
        UrlHelper::redirect($path);
    }

    // -------------------------------------------------------------------------
    // Request helpers
    // -------------------------------------------------------------------------

    /**
     * Return a sanitised POST value, or the default if not set.
     * Note: sanitisation here is for whitespace only.
     * Validation is always done in the Validator layer.
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Return all POST data (trimmed strings).
     */
    protected function allInput(): array
    {
        return array_map(
            fn($v) => is_string($v) ? trim($v) : $v,
            $_POST
        );
    }
}
