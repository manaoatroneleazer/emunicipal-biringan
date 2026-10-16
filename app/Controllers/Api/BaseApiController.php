<?php

namespace Emunicipal\Controllers\Api;

use Emunicipal\Middleware\AuthMiddleware;

/**
 * BaseApiController — shared foundation for all API endpoints.
 *
 * Responsibilities:
 *  - Emit JSON responses with correct Content-Type and HTTP status
 *  - Enforce authentication / role checks and return JSON 401/403 on failure
 *  - Parse request body (JSON or form-encoded)
 *  - Strip sensitive fields before output
 *  - Never output HTML error pages for API routes
 *
 * Per Req 15.2–15.4 — consistent success/error envelope, HTTP status codes.
 * Per Req 14.4  — no sensitive data in responses.
 * Per Req 14.10 — 401 for unauthenticated, 403 for forbidden.
 */
abstract class BaseApiController
{
    // -------------------------------------------------------------------------
    // Response helpers
    // -------------------------------------------------------------------------

    /**
     * Emit a successful JSON response and halt.
     *
     * @param mixed  $data    Response payload
     * @param string $message Human-readable message
     * @param int    $status  HTTP status code (200 or 201)
     */
    protected function json(mixed $data = [], string $message = '', int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode(
            ['success' => true, 'data' => $data, 'message' => $message],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    /**
     * Emit an error JSON response and halt.
     *
     * @param string $message Human-readable error
     * @param int    $status  HTTP status code (4xx / 5xx)
     * @param array  $errors  Field-level validation errors
     */
    protected function error(string $message, int $status = 400, array $errors = []): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode(
            ['success' => false, 'error' => $message, 'message' => $message, 'errors' => $errors],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    // -------------------------------------------------------------------------
    // Authentication / authorization
    // -------------------------------------------------------------------------

    /**
     * Require any authenticated user; return their ID.
     * Emits HTTP 401 JSON if unauthenticated.
     */
    protected function requireAuth(): int
    {
        if (!AuthMiddleware::isAuthenticated()) {
            $this->error('Authentication required.', 401);
        }
        // Session timeout already handled by AuthMiddleware::checkSessionTimeout()
        // but we call requireAuth ourselves here to get JSON errors instead of redirects
        return (int) AuthMiddleware::userId();
    }

    /**
     * Require a specific role; return the user's ID.
     * Emits HTTP 401 if unauthenticated, 403 if wrong role.
     */
    protected function requireRole(string $role): int
    {
        $userId = $this->requireAuth();
        if (AuthMiddleware::getRole() !== $role) {
            $this->error('Access denied.', 403);
        }
        return $userId;
    }

    /**
     * Require Staff or Admin role; return user ID.
     */
    protected function requireStaffOrAdmin(): int
    {
        $userId = $this->requireAuth();
        $role   = AuthMiddleware::getRole();
        if ($role !== 'staff' && $role !== 'admin') {
            $this->error('Access denied.', 403);
        }
        return $userId;
    }

    /**
     * For a resident-owned resource: verify the authenticated user owns it,
     * or is staff/admin. Emits 403 on failure.
     *
     * @param int $resourceOwnerId The user_id on the resource
     */
    protected function requireOwnerOrStaffAdmin(int $resourceOwnerId): int
    {
        $userId = $this->requireAuth();
        $role   = AuthMiddleware::getRole();

        if ($role === 'resident' && $userId !== $resourceOwnerId) {
            $this->error('Access denied.', 403);
        }
        return $userId;
    }

    // -------------------------------------------------------------------------
    // Request body
    // -------------------------------------------------------------------------

    /**
     * Parse the request body as JSON, falling back to $_POST.
     * Returns an array (never null).
     */
    protected function body(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw     = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '', true);
            return is_array($decoded) ? $decoded : [];
        }
        return $_POST;
    }

    /**
     * Get a single value from the request body or query string.
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body()[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Get a query-string parameter.
     */
    protected function query(string $key, mixed $default = null): mixed
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    // -------------------------------------------------------------------------
    // Safe data helpers
    // -------------------------------------------------------------------------

    /**
     * Strip sensitive fields from a user record before returning it in API responses.
     * Per Req 14 — never expose password_hash, locked_until, failed_login_attempts.
     */
    protected function safeUser(array $user): array
    {
        unset(
            $user['password_hash'],
            $user['failed_login_attempts'],
            $user['locked_until']
        );
        return $user;
    }

    /**
     * Build a standard paginated response envelope.
     */
    protected function paginated(array $items, int $total, int $page, int $perPage): array
    {
        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    /**
     * Parse pagination params from the query string.
     * Returns [page, perPage].
     */
    protected function pagination(int $defaultPerPage = 20): array
    {
        $page    = max(1, (int) ($this->query('page', 1)));
        $perPage = max(1, min(100, (int) ($this->query('per_page', $defaultPerPage))));
        return [$page, $perPage];
    }
}
