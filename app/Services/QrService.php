<?php

namespace Emunicipal\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QrService — generates demo QR codes for the simulated online payment page.
 *
 * ACADEMIC DEMONSTRATION ONLY.
 * The generated QR code is a visual aid showing demo payment data.
 * It does NOT connect to GCash, Maya, banks, or any real payment gateway.
 * No personal information, session IDs, CSRF tokens, or credentials
 * are encoded into the QR content.
 *
 * QR content format:
 *   EMUNICIPAL-DEMO:{reference_number}:{formatted_amount}
 *   e.g. EMUNICIPAL-DEMO:EMU-2026-000001:PHP50.00
 *
 * Output: SVG data URI (base64-encoded) — safe for use in <img src="...">.
 * Requires: chillerlan/php-qrcode ^5.0 (installed via Composer).
 * Does NOT require PHP GD or Imagick extensions.
 */
class QrService
{
    /**
     * Generate a demo QR code SVG data URI.
     *
     * @param  string $referenceNumber  EMU request reference (e.g. EMU-2026-000001)
     * @param  float  $amount           Processing fee amount
     * @return string                   SVG data URI suitable for <img src="...">
     * @throws \RuntimeException        If QR generation fails
     */
    public static function generateDemoQr(string $referenceNumber, float $amount): string
    {
        // Build the QR payload.
        // Contains only: demo prefix, reference number, formatted amount.
        // NO personal data, NO credentials, NO session IDs, NO CSRF tokens.
        $payload = sprintf(
            'EMUNICIPAL-DEMO:%s:PHP%s',
            strtoupper(preg_replace('/[^A-Z0-9\-]/', '', strtoupper($referenceNumber))),
            number_format($amount, 2)
        );

        $options = new QROptions([
            'outputType'       => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel'         => QRCode::ECC_M,   // Medium error correction — good balance
            'svgViewBox'       => true,             // Scalable, responsive
            'svgAddXmlHeader'  => false,
            'imageTransparent' => false,
            // Colours: dark modules = near-black (#1B102B matches Biringan dark),
            // light modules = white (#FFFFFF for maximum QR scanner contrast
            'svgDark'          => '#1B102B',
            'svgLight'         => '#FFFFFF',
        ]);

        return (new QRCode($options))->render($payload);
    }

    /**
     * Return the human-readable demo payload that was encoded in the QR.
     * Used for display alongside the QR image.
     */
    public static function demoPayload(string $referenceNumber, float $amount): string
    {
        return sprintf(
            'EMUNICIPAL-DEMO:%s:PHP%s',
            strtoupper($referenceNumber),
            number_format($amount, 2)
        );
    }
}
