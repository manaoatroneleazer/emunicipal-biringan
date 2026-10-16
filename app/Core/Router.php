<?php

namespace Emunicipal\Core;

/**
 * Router — lightweight HTTP request dispatcher.
 *
 * Supports GET, POST, PUT, PATCH, DELETE methods.
 * Routes are registered in routes/web.php and routes/api.php.
 * Controllers handle all business logic; the router only dispatches.
 */
class Router
{
    /** @var array<string, array<string, array{controller: string, method: string}>> */
    private array $routes = [];

    private string $basePath = '';

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    // -------------------------------------------------------------------------
    // Route registration helpers
    // -------------------------------------------------------------------------

    public function get(string $path, string $controller, string $method): void
    {
        $this->add('GET', $path, $controller, $method);
    }

    public function post(string $path, string $controller, string $method): void
    {
        $this->add('POST', $path, $controller, $method);
    }

    public function put(string $path, string $controller, string $method): void
    {
        $this->add('PUT', $path, $controller, $method);
    }

    public function patch(string $path, string $controller, string $method): void
    {
        $this->add('PATCH', $path, $controller, $method);
    }

    public function delete(string $path, string $controller, string $method): void
    {
        $this->add('DELETE', $path, $controller, $method);
    }

    private function add(string $httpMethod, string $path, string $controller, string $action): void
    {
        $this->routes[$httpMethod][$path] = [
            'controller' => $controller,
            'method'     => $action,
        ];
    }

    // -------------------------------------------------------------------------
    // Request dispatch
    // -------------------------------------------------------------------------

    /**
     * Resolve the current request to a controller action and invoke it.
     *
     * Strips the application base path from the URI before matching,
     * so the app works at http://localhost/eMunicipal/ without a virtual host.
     */
    public function dispatch(): void
    {
        $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // Support _method override for PUT/PATCH/DELETE from HTML forms
        if ($httpMethod === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $httpMethod = $override;
            }
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = rawurldecode($uri);

        // Strip base path prefix (e.g. /eMunicipal) so routes are relative
        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }

        $uri = '/' . ltrim($uri, '/');

        $route = $this->match($httpMethod, $uri);

        if ($route === null) {
            $this->handleNotFound();
            return;
        }

        $controllerClass = $route['controller'];
        $action          = $route['method'];
        $params          = $route['params'] ?? [];

        if (!class_exists($controllerClass)) {
            Logger::error("Controller not found: {$controllerClass}");
            $this->handleServerError();
            return;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $action)) {
            Logger::error("Method {$action} not found on {$controllerClass}");
            $this->handleServerError();
            return;
        }

        $controller->$action(...$params);
    }

    /**
     * Match URI against registered routes, supporting {param} placeholders.
     */
    private function match(string $httpMethod, string $uri): ?array
    {
        $methodRoutes = $this->routes[$httpMethod] ?? [];

        // Exact match first
        if (isset($methodRoutes[$uri])) {
            return $methodRoutes[$uri];
        }

        // Pattern match with named placeholders {param}
        foreach ($methodRoutes as $pattern => $route) {
            $regex = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_]*\}/', '([^/]+)', $pattern);
            $regex = '@^' . $regex . '$@';

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches); // remove full match
                return array_merge($route, ['params' => $matches]);
            }
        }

        return null;
    }

    private function handleNotFound(): void
    {
        http_response_code(404);
        $viewPath = dirname(__DIR__, 2) . '/views/errors/404.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo '<h1>404 — Page Not Found</h1>';
        }
    }

    private function handleServerError(): void
    {
        http_response_code(500);
        $viewPath = dirname(__DIR__, 2) . '/views/errors/500.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo '<h1>500 — Internal Server Error</h1>';
        }
    }

    /**
     * Render the 403 Forbidden page and halt.
     * Called from middleware / controllers when access is denied.
     */
    public static function forbidden(): never
    {
        http_response_code(403);
        $viewPath = dirname(__DIR__, 2) . '/views/errors/403.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo '<h1>403 — Forbidden</h1>';
        }
        exit;
    }
}
