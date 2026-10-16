<?php

namespace Emunicipal\Helpers;

/**
 * SecurityHelper — common security utilities.
 *
 * Provides:
 * - CSRF token generation and validation
 * - Output escaping (XSS prevention)
 * - Secure random token generation
 *
 * All HTML output of user-supplied data MUST pass through escape().
 * CSRF tokens MUST be embedded in every state-changing form (per Req 14.3).
 */
class SecurityHelper
{
    private const CSRF_TOKEN_KEY    = '_csrf_token';
    private const CSRF_TOKEN_LENGTH = 32; // bytes → 64 hex chars

    // -------------------------------------------------------------------------
    // CSRF Protection
    // -------------------------------------------------------------------------

    /**
     * Generate and store a CSRF token in the session.
     * Returns the token string for embedding in forms.
     */
    public static function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(self::CSRF_TOKEN_LENGTH));
        SessionHelper::set(self::CSRF_TOKEN_KEY, $token);
        return $token;
    }

    /**
     * Return the current CSRF token, generating one if none exists.
     */
    public static function getCsrfToken(): string
    {
        $existing = SessionHelper::get(self::CSRF_TOKEN_KEY);
        if ($existing === null) {
            return self::generateCsrfToken();
        }
        return $existing;
    }

    /**
     * Validate a CSRF token from a form submission.
     * Uses hash_equals to prevent timing attacks.
     */
    public static function validateCsrfToken(string $submittedToken): bool
    {
        $storedToken = SessionHelper::get(self::CSRF_TOKEN_KEY, '');
        if ($storedToken === '' || $submittedToken === '') {
            return false;
        }
        return hash_equals($storedToken, $submittedToken);
    }

    /**
     * Emit an HTML hidden input containing the CSRF token.
     * Usage: <?= SecurityHelper::csrfField() ?>
     */
    public static function csrfField(): string
    {
        $token = self::getCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . self::escape($token) . '">';
    }

    // -------------------------------------------------------------------------
    // Output Escaping (XSS Prevention)
    // -------------------------------------------------------------------------

    /**
     * Escape a string for safe HTML output (per Req 14.4).
     * Use this on ALL user-supplied data before rendering in HTML.
     */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Escape an array of strings for safe HTML output.
     */
    public static function escapeArray(array $data): array
    {
        return array_map(
            fn($v) => is_string($v) ? self::escape($v) : $v,
            $data
        );
    }

    // -------------------------------------------------------------------------
    // Secure Tokens
    // -------------------------------------------------------------------------

    /**
     * Generate a cryptographically random hex token of the given byte length.
     */
    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Generate a UUID v4 for use as secure stored filenames (per Req 18.4).
     */
    public static function uuid4(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant RFC 4122

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
