<?php

namespace Emunicipal\Validators;

/**
 * Validator — centralised server-side field validation.
 *
 * All validation is performed server-side (Req 1.2, 2.2, 6.8).
 * Never rely on client-side validation alone.
 *
 * Usage:
 *   $v = new Validator($_POST);
 *   $v->required(['first_name','email','password']);
 *   $v->email('email');
 *   $v->passwordStrength('password');
 *   if ($v->fails()) { ... $v->errors() ... }
 */
class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        // Trim all string values
        $this->data = array_map(
            fn($v) => is_string($v) ? trim($v) : $v,
            $data
        );
    }

    // -------------------------------------------------------------------------
    // Rule methods — each returns $this for chaining
    // -------------------------------------------------------------------------

    /** All listed fields must be non-empty. */
    public function required(array $fields): static
    {
        foreach ($fields as $field) {
            if (empty($this->data[$field]) && $this->data[$field] !== '0') {
                $this->addError($field, $this->label($field) . ' is required.');
            }
        }
        return $this;
    }

    /** Field must be a valid email address with a dotted domain (TLD required). */
    public function email(string $field): static
    {
        $val = $this->data[$field] ?? '';
        if ($val === '') {
            return $this;
        }

        if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $this->label($field) . ' must be a valid email address.');
            return $this;
        }

        // G1: reject single-label domains (e.g. test@domain, test@localhost).
        // A valid registration email must have a dot in the domain portion.
        $atPos  = strrpos($val, '@');
        $domain = substr($val, $atPos + 1);
        if (strpos($domain, '.') === false) {
            $this->addError($field, $this->label($field) . ' must be a valid email address.');
        }

        return $this;
    }

    /** Field max character length. */
    public function maxLength(string $field, int $max): static
    {
        $val = $this->data[$field] ?? '';
        if (mb_strlen($val) > $max) {
            $this->addError($field, $this->label($field) . " must not exceed {$max} characters.");
        }
        return $this;
    }

    /** Field min character length. */
    public function minLength(string $field, int $min): static
    {
        $val = $this->data[$field] ?? '';
        if ($val !== '' && mb_strlen($val) < $min) {
            $this->addError($field, $this->label($field) . " must be at least {$min} characters.");
        }
        return $this;
    }

    /**
     * Password must contain ≥8 chars, ≥1 uppercase, ≥1 lowercase, ≥1 digit.
     * Per Req 1.8.
     */
    public function passwordStrength(string $field): static
    {
        $val = $this->data[$field] ?? '';
        if ($val === '') return $this; // required() handles empty

        if (mb_strlen($val) < 8) {
            $this->addError($field, 'Password must be at least 8 characters.');
            return $this;
        }
        if (!preg_match('/[A-Z]/', $val)) {
            $this->addError($field, 'Password must contain at least one uppercase letter.');
            return $this;
        }
        if (!preg_match('/[a-z]/', $val)) {
            $this->addError($field, 'Password must contain at least one lowercase letter.');
            return $this;
        }
        if (!preg_match('/[0-9]/', $val)) {
            $this->addError($field, 'Password must contain at least one number.');
            return $this;
        }
        return $this;
    }

    /**
     * Confirm field matches another field.
     * Per Req 1.10.
     */
    public function matches(string $field, string $otherField): static
    {
        $val   = $this->data[$field]      ?? '';
        $other = $this->data[$otherField] ?? '';
        if ($val !== $other) {
            $this->addError($field, 'Passwords do not match. Please re-enter your password.');
        }
        return $this;
    }

    /**
     * Philippine mobile or landline number.
     * Per Req 1.9: 09XXXXXXXXX (11 digits), +639XXXXXXXXX, or (075) XXX-XXXX style.
     */
    public function philippinePhone(string $field): static
    {
        $val = $this->data[$field] ?? '';
        if ($val === '') return $this;

        // Allow: 09XXXXXXXXX, +639XXXXXXXXX, (0XX) XXX-XXXX, 0XX-XXX-XXXX, etc.
        $digits = preg_replace('/\D/', '', $val);
        // Philippine mobile: 11 digits starting with 09; or landline 10-11 digits starting with 0
        $valid = (bool) preg_match('/^(09\d{9}|0\d{9,10}|639\d{9})$/', $digits);

        if (!$valid) {
            $this->addError(
                $field,
                $this->label($field) . ' must be a valid Philippine mobile (09XXXXXXXXX) or landline number.'
            );
        }
        return $this;
    }

    /**
     * Field must be a positive integer >= 1.
     */
    public function positiveInteger(string $field): static
    {
        $val = $this->data[$field] ?? '';
        if ($val === '') return $this;
        if (!ctype_digit((string)$val) || (int)$val < 1) {
            $this->addError($field, $this->label($field) . ' must be a positive whole number.');
        }
        return $this;
    }

    /**
     * Field must be numeric >= 0 (for fees).
     */
    public function nonNegativeDecimal(string $field): static
    {
        $val = $this->data[$field] ?? '';
        if ($val === '') return $this;
        if (!is_numeric($val) || (float)$val < 0) {
            $this->addError($field, $this->label($field) . ' must be a number of 0 or greater.');
        }
        return $this;
    }

    /**
     * Add a custom error message for a field.
     */
    public function addError(string $field, string $message): static
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    // -------------------------------------------------------------------------
    // Result accessors
    // -------------------------------------------------------------------------

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    /** All errors keyed by field name. */
    public function errors(): array
    {
        return $this->errors;
    }

    /** First error for a field, or empty string. */
    public function error(string $field): string
    {
        return $this->errors[$field] ?? '';
    }

    /** Return validated/trimmed data. */
    public function validated(): array
    {
        return $this->data;
    }

    /** Single validated value. */
    public function get(string $field, mixed $default = null): mixed
    {
        return $this->data[$field] ?? $default;
    }

    // -------------------------------------------------------------------------

    private function label(string $field): string
    {
        // Human-readable overrides for registration fields
        $overrides = [
            'first_name'           => 'First name',
            'last_name'            => 'Last name',
            'email'                => 'Email address',
            'password'             => 'Password',
            'password_confirmation'=> 'Password confirmation',
            'contact_number'       => 'Contact number',
            'house_number'         => 'House number',
            'street'               => 'Street',
            'barangay'             => 'Barangay',
            'city'                 => 'City/municipality',
            'province'             => 'Province',
            'terms_accepted'       => 'Terms acceptance',
        ];
        return $overrides[$field] ?? ucwords(str_replace(['_', '-'], ' ', $field));
    }
}
