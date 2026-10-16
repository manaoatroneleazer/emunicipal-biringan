<?php

/**
 * Mail Configuration Foundation
 *
 * Reads Mailtrap SMTP settings from environment variables.
 * Mailtrap integration is implemented in a later phase.
 *
 * IMPORTANT:
 * - Never hard-code credentials here.
 * - Never log the values of these configuration entries.
 * - Credentials live exclusively in the .env file (per Req 10.2, 14.8).
 */

return [
    // ── Mailtrap / resident notification SMTP ──────────────────────────────
    // Used exclusively for resident request-status notifications.
    // Leave empty during development if Mailtrap is not yet configured.
    'host'         => $_ENV['MAIL_HOST']         ?? '',
    'port'         => (int) ($_ENV['MAIL_PORT']  ?? 587),
    'username'     => $_ENV['MAIL_USERNAME']      ?? '',
    // Password intentionally read from env only — never echoed, logged, or stored in DB
    'password'     => $_ENV['MAIL_PASSWORD']      ?? '',
    'from_address' => $_ENV['MAIL_FROM_ADDRESS']  ?? '',
    'from_name'    => $_ENV['MAIL_FROM_NAME']     ?? 'eMunicipal',
    'encryption'   => 'tls',

    // ── Contact Form SMTP ───────────────────────────────────────────────────
    // Completely separate from the Mailtrap/resident notification transport.
    // Used only by EmailService::sendContactMessage() for public Contact Form submissions.
    // Configure these with your website/municipal email provider credentials.
    // Falls back to the main MAIL_* variables if CONTACT_SMTP_HOST is not set,
    // allowing a single SMTP account to serve both purposes when appropriate.
    'contact_host'         => $_ENV['CONTACT_SMTP_HOST']         ?? ($_ENV['MAIL_HOST']         ?? ''),
    'contact_port'         => (int) ($_ENV['CONTACT_SMTP_PORT']  ?? ($_ENV['MAIL_PORT']         ?? 587)),
    'contact_username'     => $_ENV['CONTACT_SMTP_USERNAME']      ?? ($_ENV['MAIL_USERNAME']      ?? ''),
    'contact_password'     => $_ENV['CONTACT_SMTP_PASSWORD']      ?? ($_ENV['MAIL_PASSWORD']      ?? ''),
    'contact_from_address' => $_ENV['CONTACT_SMTP_FROM_ADDRESS']  ?? ($_ENV['MAIL_FROM_ADDRESS']  ?? ''),
    'contact_from_name'    => $_ENV['CONTACT_SMTP_FROM_NAME']     ?? ($_ENV['MAIL_FROM_NAME']     ?? 'eMunicipal'),
    'contact_encryption'   => $_ENV['CONTACT_SMTP_ENCRYPTION']    ?? 'tls',
];
