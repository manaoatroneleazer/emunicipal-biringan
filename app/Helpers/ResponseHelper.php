<?php

namespace Emunicipal\Helpers;

/**
 * ResponseHelper — standardised API JSON responses.
 *
 * All API endpoints must use this helper to ensure consistent
 * response structure (per Req 15.2, 15.3, 15.4):
 *
 * Success: { "success": true,  "message": "...", "data": {} }
 * Error:   { "success": false, "message": "...", "errors": {} }
 *
 * Sends the response and terminates execution.
 */
class ResponseHelper
{
    /**
     * Send a successful JSON response.
     *
     * @param mixed  $data    Response payload
     * @param string $message Human-readable success message
     * @param int    $status  HTTP status code (200 or 201)
     */
    public static function success(
        mixed  $data    = [],
        string $message = 'Operation successful.',
        int    $status  = 200
    ): never {
        self::send($status, [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    /**
     * Send an error JSON response.
     *
     * @param string $message Human-readable error description
     * @param array  $errors  Field-level validation errors keyed by field name
     * @param int    $status  HTTP status code (400, 401, 403, 404, 422, 500…)
     */
    public static function error(
        string $message = 'An error occurred.',
        array  $errors  = [],
        int    $status  = 400
    ): never {
        self::send($status, [
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ]);
    }

    /**
     * Send a 401 Unauthenticated response.
     */
    public static function unauthenticated(string $message = 'Authentication required.'): never
    {
        self::error($message, [], 401);
    }

    /**
     * Send a 403 Forbidden response.
     */
    public static function forbidden(string $message = 'You do not have permission to perform this action.'): never
    {
        self::error($message, [], 403);
    }

    /**
     * Send a 404 Not Found response.
     */
    public static function notFound(string $message = 'The requested resource was not found.'): never
    {
        self::error($message, [], 404);
    }

    /**
     * Send a 422 Unprocessable Entity response (validation failure).
     */
    public static function validationError(array $errors, string $message = 'Validation failed.'): never
    {
        self::error($message, $errors, 422);
    }

    /**
     * Send a 500 Internal Server Error response.
     * Never exposes internal exception details to the client.
     */
    public static function serverError(string $message = 'An unexpected error occurred. Please try again.'): never
    {
        self::error($message, [], 500);
    }

    // -------------------------------------------------------------------------

    private static function send(int $status, array $body): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
