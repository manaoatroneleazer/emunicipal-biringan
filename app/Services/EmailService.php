<?php

namespace Emunicipal\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;
use Emunicipal\Models\EmailLog;
use Emunicipal\Core\Logger;

/**
 * EmailService — Mailtrap SMTP email sender with full email_logs recording.
 *
 * Security requirements implemented:
 *  Req 10.2 — Credentials stored exclusively in environment variables; never hardcoded
 *  Req 10.3 — Every send attempt (success or failure) logged in email_logs table
 *  Req 10.4 — Email failure does NOT roll back the triggering operation
 *  Req 10.5 — Consistent, professionally formatted HTML email template
 *  Req 10.6 — Reference number included in subject for all request-related emails
 *  Req 10.7 — Credentials never exposed in logs, responses, or client-side code
 *
 * Notification types match database.md §9.2 and EmailLog constants.
 */
class EmailService
{
    // Notification type constants — match database.md §9.2 and Notification model
    public const TYPE_REGISTRATION               = 'registration';
    public const TYPE_REQUEST_SUBMITTED          = 'request_submitted';
    public const TYPE_UNDER_VERIFICATION         = 'under_verification';
    public const TYPE_ADDITIONAL_INFO_REQUIRED   = 'additional_info_required';
    public const TYPE_APPROVED                   = 'approved';
    public const TYPE_REJECTED                   = 'rejected';
    public const TYPE_READY_FOR_CLAIMING         = 'ready_for_claiming';
    public const TYPE_COMPLETED                  = 'completed';
    public const TYPE_CANCELLED                  = 'cancelled';

    // -------------------------------------------------------------------------
    // Public send methods — one per notification type (Req 10.1)
    // -------------------------------------------------------------------------

    /**
     * Send a registration welcome email (Req 1.6).
     * @param array $user  User record (id, first_name, last_name, email)
     */
    public static function sendRegistrationConfirmation(array $user): void
    {
        $name    = htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']), ENT_QUOTES, 'UTF-8');
        $appUrl  = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');

        $subject = 'Welcome to eMunicipal — Your Account Has Been Created';
        $body    = "
            <h2 style='color:#4A1F46;'>Welcome to eMunicipal, {$name}!</h2>
            <p>Your account has been successfully created with the Biringan City Administration eMunicipal Online Document Request System.</p>
            <p>You can now log in and submit community document requests online:</p>
            <a href='{$appUrl}/login' class='btn' style='display:inline-block;background:#4A1F46;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;margin-top:12px;'>Sign In to eMunicipal</a>
            <p style='margin-top:24px;font-size:13px;color:#756C78;line-height:1.6;'>
                If you did not create this account, please contact the Biringan City Administration office immediately.
            </p>
        ";

        self::send(
            $user['email'],
            $name,
            $subject,
            $body,
            self::TYPE_REGISTRATION,
            null
        );
    }

    /**
     * Send a request submission confirmation (Req 10.1 — Request Submitted).
     * @param array $user    Resident record
     * @param array $request Request record (reference_number, document_type_name, created_at)
     */
    public static function sendRequestSubmitted(array $user, array $request): void
    {
        $ref    = htmlspecialchars($request['reference_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $type   = htmlspecialchars($request['document_type_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $date   = !empty($request['created_at'])
                  ? date('F j, Y \a\t g:i A', strtotime($request['created_at']))
                  : date('F j, Y');
        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
        $name   = htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']), ENT_QUOTES, 'UTF-8');

        $subject = "[{$ref}] Your Document Request Has Been Submitted";
        $body    = "
            <h2 style='color:#4A1F46;'>Request Submitted Successfully</h2>
            <p>Dear {$name},</p>
            <p>Your document request has been received. Our staff will review it shortly.</p>
            <div class='ref-box' style='background:#EEF2F7;border-left:4px solid #4A1F46;padding:16px 20px;margin:24px 0;border-radius:0 6px 6px 0;'>
                <div class='label' style='font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#756C78;margin-bottom:6px;'>Reference Number</div>
                <div class='ref' style='font-family:\"Courier New\",Courier,monospace;font-size:1.3em;font-weight:700;color:#4A1F46;letter-spacing:0.02em;word-break:break-all;'>{$ref}</div>
            </div>
            <table class='info' style='width:100%;border-collapse:collapse;margin:20px 0;font-size:14px;'>
                <tr><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;width:42%;font-weight:600;color:#756C78;vertical-align:top;'>Document Type</td><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;color:#241C29;vertical-align:top;'>{$type}</td></tr>
                <tr><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;width:42%;font-weight:600;color:#756C78;vertical-align:top;'>Date Submitted</td><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;color:#241C29;vertical-align:top;'>{$date}</td></tr>
                <tr><td style='padding:10px 0;width:42%;font-weight:600;color:#756C78;vertical-align:top;'>Status</td><td style='padding:10px 0;color:#241C29;vertical-align:top;'><span class='status-badge status-pending' style='display:inline-block;padding:5px 12px;border-radius:5px;font-size:13px;font-weight:600;background:#E5E7EB;color:#374151;'>Pending</span></td></tr>
            </table>
            <p>You can track the progress of your request online using your reference number.</p>
            <a href='{$appUrl}/resident/track?ref=" . urlencode($request['reference_number'] ?? '') . "' class='btn' style='display:inline-block;background:#4A1F46;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;margin-top:12px;'>Track My Request</a>
        ";

        self::send($user['email'], $name, $subject, $body, self::TYPE_REQUEST_SUBMITTED, $request['reference_number'] ?? null);
    }

    /**
     * Send a status change notification.
     * Covers: under_verification, additional_info_required, approved, rejected,
     *         ready_for_claiming, completed, cancelled.
     *
     * @param array  $user    Resident record
     * @param array  $request Request record
     * @param string $type    One of the TYPE_* constants
     * @param ?string $remark Staff remark / rejection reason (where applicable)
     * @param ?string $cancelledBy 'Resident' or 'Staff' (for cancellation email)
     */
    public static function sendStatusUpdate(
        array   $user,
        array   $request,
        string  $type,
        ?string $remark      = null,
        ?string $cancelledBy = null
    ): void {
        $ref    = htmlspecialchars($request['reference_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $docType = htmlspecialchars($request['document_type_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
        $name   = htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']), ENT_QUOTES, 'UTF-8');
        $trackUrl = $appUrl . '/resident/track?ref=' . urlencode($request['reference_number'] ?? '');

        [$subject, $body] = match ($type) {
            self::TYPE_UNDER_VERIFICATION => [
                "[{$ref}] Your Request Is Under Verification",
                "
                <h2 style='color:#4A1F46;'>Request Under Verification</h2>
                <p>Dear {$name},</p>
                <p>Our staff is now reviewing your request for <strong>{$docType}</strong>.</p>
                <div class='ref-box' style='background:#EEF2F7;border-left:4px solid #4A1F46;padding:16px 20px;margin:24px 0;border-radius:0 6px 6px 0;'>
                    <div class='label' style='font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#756C78;margin-bottom:6px;'>Reference Number</div>
                    <div class='ref' style='font-family:\"Courier New\",Courier,monospace;font-size:1.3em;font-weight:700;color:#4A1F46;letter-spacing:0.02em;word-break:break-all;'>{$ref}</div>
                </div>
                <p>You will be notified when the review is complete. Estimated processing time varies by document type.</p>
                <a href='{$trackUrl}' class='btn' style='display:inline-block;background:#4A1F46;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;margin-top:12px;'>Track My Request</a>
                "
            ],
            self::TYPE_ADDITIONAL_INFO_REQUIRED => [
                "[{$ref}] Additional Information Required for Your Request",
                "
                <h2 style='color:#4A1F46;'>Additional Information Required</h2>
                <p>Dear {$name},</p>
                <p>Our staff has reviewed your request for <strong>{$docType}</strong> and requires additional information or documents.</p>
                <div class='ref-box' style='background:#EEF2F7;border-left:4px solid #4A1F46;padding:16px 20px;margin:24px 0;border-radius:0 6px 6px 0;'>
                    <div class='label' style='font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#756C78;margin-bottom:6px;'>Reference Number</div>
                    <div class='ref' style='font-family:\"Courier New\",Courier,monospace;font-size:1.3em;font-weight:700;color:#4A1F46;letter-spacing:0.02em;word-break:break-all;'>{$ref}</div>
                </div>
                " . ($remark ? "<div class='remark-box' style='background:#FEF3C7;border:1px solid #F59E0B;border-radius:6px;padding:14px 18px;margin:20px 0;font-size:14px;line-height:1.6;'><strong style='display:block;margin-bottom:8px;color:#241C29;'>Staff Note:</strong>" . htmlspecialchars($remark, ENT_QUOTES, 'UTF-8') . "</div>" : "") . "
                <p>Please log in to the portal and upload the requested documents.</p>
                <a href='{$appUrl}/resident/requests' class='btn' style='display:inline-block;background:#4A1F46;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;margin-top:12px;'>Upload Documents</a>
                "
            ],
            self::TYPE_APPROVED => [
                "[{$ref}] Your Request Has Been Approved",
                "
                <h2 style='color:#4A1F46;'>Request Approved</h2>
                <p>Dear {$name},</p>
                <p>Great news! Your request for <strong>{$docType}</strong> has been approved.</p>
                <div class='ref-box' style='background:#EEF2F7;border-left:4px solid #4A1F46;padding:16px 20px;margin:24px 0;border-radius:0 6px 6px 0;'>
                    <div class='label' style='font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#756C78;margin-bottom:6px;'>Reference Number</div>
                    <div class='ref' style='font-family:\"Courier New\",Courier,monospace;font-size:1.3em;font-weight:700;color:#4A1F46;letter-spacing:0.02em;word-break:break-all;'>{$ref}</div>
                </div>
                <p>Your document is now being prepared. You will be notified when it is ready for claiming.</p>
                <a href='{$trackUrl}' class='btn' style='display:inline-block;background:#4A1F46;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;margin-top:12px;'>Track My Request</a>
                "
            ],
            self::TYPE_REJECTED => [
                "[{$ref}] Your Request Has Been Rejected",
                "
                <h2 style='color:#4A1F46;'>Request Rejected</h2>
                <p>Dear {$name},</p>
                <p>Unfortunately, your request for <strong>{$docType}</strong> could not be fulfilled.</p>
                <div class='ref-box' style='background:#EEF2F7;border-left:4px solid #4A1F46;padding:16px 20px;margin:24px 0;border-radius:0 6px 6px 0;'>
                    <div class='label' style='font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#756C78;margin-bottom:6px;'>Reference Number</div>
                    <div class='ref' style='font-family:\"Courier New\",Courier,monospace;font-size:1.3em;font-weight:700;color:#4A1F46;letter-spacing:0.02em;word-break:break-all;'>{$ref}</div>
                </div>
                " . ($remark ? "<div class='remark-box rejection' style='background:#FEE2E2;border:1px solid #B91C1C;border-radius:6px;padding:14px 18px;margin:20px 0;font-size:14px;line-height:1.6;'><strong style='display:block;margin-bottom:8px;color:#241C29;'>Rejection Reason:</strong>" . htmlspecialchars($remark, ENT_QUOTES, 'UTF-8') . "</div>" : "") . "
                <p>If you believe this is an error, please contact the municipal office or submit a new request with the correct documents.</p>
                <a href='{$trackUrl}' class='btn' style='display:inline-block;background:#4A1F46;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;margin-top:12px;'>View Request Details</a>
                "
            ],
            self::TYPE_READY_FOR_CLAIMING => [
                "[{$ref}] Your Document Is Ready for Claiming",
                "
                <h2 style='color:#4A1F46;'>Document Ready for Claiming</h2>
                <p>Dear {$name},</p>
                <p>Your document — <strong>{$docType}</strong> — is now ready for pick-up at the Biringan City Civic Center.</p>
                <div class='ref-box' style='background:#EEF2F7;border-left:4px solid #4A1F46;padding:16px 20px;margin:24px 0;border-radius:0 6px 6px 0;'>
                    <div class='label' style='font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#756C78;margin-bottom:6px;'>Reference Number</div>
                    <div class='ref' style='font-family:\"Courier New\",Courier,monospace;font-size:1.3em;font-weight:700;color:#4A1F46;letter-spacing:0.02em;word-break:break-all;'>{$ref}</div>
                </div>
                <table class='info' style='width:100%;border-collapse:collapse;margin:20px 0;font-size:14px;'>
                    <tr><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;width:42%;font-weight:600;color:#756C78;vertical-align:top;'>Office</td><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;color:#241C29;vertical-align:top;'>Biringan City Administration</td></tr>
                    <tr><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;width:42%;font-weight:600;color:#756C78;vertical-align:top;'>Address</td><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;color:#241C29;vertical-align:top;'>Biringan City Civic Center, Central District, Biringan City</td></tr>
                    <tr><td style='padding:10px 0;width:42%;font-weight:600;color:#756C78;vertical-align:top;'>Office Hours</td><td style='padding:10px 0;color:#241C29;vertical-align:top;'>Monday–Friday, 8:00 AM – 5:00 PM</td></tr>
                </table>
                <p>Please bring a valid community identification when claiming your document.</p>
                "
            ],
            self::TYPE_COMPLETED => [
                "[{$ref}] Your Request Has Been Completed",
                "
                <h2 style='color:#4A1F46;'>Request Completed</h2>
                <p>Dear {$name},</p>
                <p>Your request for <strong>{$docType}</strong> has been marked as completed. Thank you for using the eMunicipal Online Document Request System.</p>
                <div class='ref-box' style='background:#EEF2F7;border-left:4px solid #4A1F46;padding:16px 20px;margin:24px 0;border-radius:0 6px 6px 0;'>
                    <div class='label' style='font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#756C78;margin-bottom:6px;'>Reference Number</div>
                    <div class='ref' style='font-family:\"Courier New\",Courier,monospace;font-size:1.3em;font-weight:700;color:#4A1F46;letter-spacing:0.02em;word-break:break-all;'>{$ref}</div>
                </div>
                <p>We hope the service was convenient. Please visit us again for your future document needs.</p>
                <a href='" . htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') . "' class='btn' style='display:inline-block;background:#4A1F46;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;margin-top:12px;'>Back to eMunicipal</a>
                "
            ],
            self::TYPE_CANCELLED => [
                "[{$ref}] Your Request Has Been Cancelled",
                "
                <h2 style='color:#4A1F46;'>Request Cancelled</h2>
                <p>Dear {$name},</p>
                <p>Your request for <strong>{$docType}</strong> has been cancelled" .
                ($cancelledBy ? " by <strong>" . htmlspecialchars($cancelledBy, ENT_QUOTES, 'UTF-8') . "</strong>" : "") . ".</p>
                <div class='ref-box' style='background:#EEF2F7;border-left:4px solid #4A1F46;padding:16px 20px;margin:24px 0;border-radius:0 6px 6px 0;'>
                    <div class='label' style='font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#756C78;margin-bottom:6px;'>Reference Number</div>
                    <div class='ref' style='font-family:\"Courier New\",Courier,monospace;font-size:1.3em;font-weight:700;color:#4A1F46;letter-spacing:0.02em;word-break:break-all;'>{$ref}</div>
                </div>
                " . ($remark ? "<div class='remark-box' style='background:#FEF3C7;border:1px solid #F59E0B;border-radius:6px;padding:14px 18px;margin:20px 0;font-size:14px;line-height:1.6;'><strong style='display:block;margin-bottom:8px;color:#241C29;'>Reason:</strong>" . htmlspecialchars($remark, ENT_QUOTES, 'UTF-8') . "</div>" : "") . "
                <p>If you still need this document, you may submit a new request at any time.</p>
                <a href='" . htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') . "/resident/requests/new' class='btn' style='display:inline-block;background:#4A1F46;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;margin-top:12px;'>Submit New Request</a>
                "
            ],
            default => [
                "[{$ref}] Update on Your Request",
                "<h2 style='color:#4A1F46;'>Request Update</h2><p>Dear {$name}, your request {$ref} has been updated. Please log in for details.</p>"
            ],
        };

        self::send($user['email'], $name, $subject, $body, $type, $request['reference_number'] ?? null);
    }

    // -------------------------------------------------------------------------
    // Contact form send methods
    // -------------------------------------------------------------------------

    /**
     * Send a contact form message to the website owner.
     *
     * Completely separate from resident notification flow.
     * Uses the same underlying SMTP config but a distinct notification type.
     *
     * @param string $ownerEmail  Destination — website owner (from .env CONTACT_OWNER_EMAIL)
     * @param string $visitorName Submitted visitor name
     * @param string $visitorEmail Submitted visitor email (set as Reply-To)
     * @param string $subject     Submitted subject
     * @param string $message     Submitted message body
     */
    public static function sendContactMessage(
        string $ownerEmail,
        string $visitorName,
        string $visitorEmail,
        string $subject,
        string $message
    ): void {
        $appName   = htmlspecialchars($_ENV['APP_NAME'] ?? 'eMunicipal', ENT_QUOTES, 'UTF-8');
        $submitted = date('F j, Y \a\t g:i A');
        $eName     = htmlspecialchars($visitorName,  ENT_QUOTES, 'UTF-8');
        $eEmail    = htmlspecialchars($visitorEmail, ENT_QUOTES, 'UTF-8');
        $eSubject  = htmlspecialchars($subject,      ENT_QUOTES, 'UTF-8');
        $eMessage  = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        $emailSubject = "[Website Contact] {$eSubject}";
        $body = "
            <h2 style='color:#4A1F46;'>New Contact Form Message</h2>
            <table class='info' style='width:100%;border-collapse:collapse;margin:20px 0;font-size:14px;'>
                <tr><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;width:42%;font-weight:600;color:#756C78;vertical-align:top;'><strong>Name</strong></td><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;color:#241C29;vertical-align:top;'>{$eName}</td></tr>
                <tr><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;width:42%;font-weight:600;color:#756C78;vertical-align:top;'><strong>Email</strong></td><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;color:#241C29;vertical-align:top;'>{$eEmail}</td></tr>
                <tr><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;width:42%;font-weight:600;color:#756C78;vertical-align:top;'><strong>Subject</strong></td><td style='padding:10px 0;border-bottom:1px solid #E8E0E8;color:#241C29;vertical-align:top;'>{$eSubject}</td></tr>
                <tr><td style='padding:10px 0;width:42%;font-weight:600;color:#756C78;vertical-align:top;'><strong>Submitted</strong></td><td style='padding:10px 0;color:#241C29;vertical-align:top;'>{$submitted}</td></tr>
            </table>
            <div class='remark-box' style='background:#FEF3C7;border:1px solid #F59E0B;border-radius:6px;padding:14px 18px;margin:20px 0;font-size:14px;line-height:1.6;'>
                <strong style='display:block;margin-bottom:8px;color:#241C29;'>Message:</strong><br>
                {$eMessage}
            </div>
            <p style='margin-top:1.5rem;font-size:12px;color:#756C78;'>
                Reply directly to this email to respond to the visitor.
            </p>
        ";

        // Use a distinct 'contact' notification type — separate from resident notifications
        // Passes the owner as recipient, visitor email as Reply-To via custom send
        self::sendContactRaw($ownerEmail, $appName, $emailSubject, $body, $visitorEmail);
    }

    /**
     * Send a contact email with a custom Reply-To header.
     * Private — used only by sendContactMessage().
     *
     * Uses CONTACT_SMTP_* variables from config/mail.php — completely separate
     * from the Mailtrap/MAIL_* transport used for resident notifications.
     * Throws on failure so HomeController can return an honest error to the visitor.
     */
    private static function sendContactRaw(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBodyContent,
        string $replyTo
    ): void {
        $htmlBody = self::renderTemplate($subject, $htmlBodyContent);
        $status   = EmailLog::STATUS_SENT;
        $errorMsg = null;

        try {
            $config = require dirname(__DIR__, 2) . '/config/mail.php';

            // Use the dedicated contact SMTP keys (CONTACT_SMTP_*).
            // These are separate from the Mailtrap MAIL_* variables used for
            // resident request-status notifications — the two transports never mix.
            $contactHost     = $config['contact_host']         ?? '';
            $contactPort     = $config['contact_port']         ?? 587;
            $contactUser     = $config['contact_username']     ?? '';
            $contactPass     = $config['contact_password']     ?? '';
            $contactFrom     = $config['contact_from_address'] ?? '';
            $contactFromName = $config['contact_from_name']    ?? ($_ENV['APP_NAME'] ?? 'eMunicipal');
            $contactEncrypt  = $config['contact_encryption']   ?? 'tls';

            if (empty($contactHost) || empty($contactUser) || empty($contactPass)) {
                // SMTP not configured — log and throw so the controller returns a real error
                $errorMsg = 'Contact SMTP not configured (CONTACT_SMTP_HOST/USERNAME/PASSWORD missing in .env)';
                $status   = EmailLog::STATUS_FAILED;
                Logger::warning('EmailService: contact email not sent — CONTACT_SMTP credentials not configured');
                throw new \RuntimeException('Contact form SMTP is not configured.');
            }

            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host       = $contactHost;
            $mailer->Port       = (int)$contactPort;
            $mailer->SMTPAuth   = true;
            $mailer->Username   = $contactUser;
            $mailer->Password   = $contactPass;
            $mailer->SMTPSecure = strtolower($contactEncrypt) === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->SMTPDebug  = SMTP::DEBUG_OFF; // credentials never leaked

            $mailer->setFrom(
                $contactFrom ?: 'noreply@emunicipal.local',
                $contactFromName
            );
            $mailer->addAddress($toEmail, $toName);

            // Reply-To = visitor's email so the owner can reply directly
            if (filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $mailer->addReplyTo($replyTo);
            }

            $mailer->isHTML(true);
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->Subject = $subject;
            $mailer->Body    = $htmlBody;
            $mailer->AltBody = strip_tags(
                str_replace(['<br>', '<br/>', '<p>', '</p>'], "\n", $htmlBodyContent)
            );

            $mailer->send();

        } catch (MailerException $e) {
            $status   = EmailLog::STATUS_FAILED;
            $errorMsg = 'Mailer error: ' . $e->getMessage();
            Logger::error('EmailService contact send failed'); // credentials intentionally omitted
            throw new \RuntimeException('Contact email could not be sent.');
        } catch (\RuntimeException $e) {
            // Re-throw config/runtime errors so the controller can surface them
            throw $e;
        } catch (\Throwable $e) {
            $status   = EmailLog::STATUS_FAILED;
            $errorMsg = 'Unexpected error: ' . $e->getMessage();
            Logger::error('EmailService contact unexpected error');
            throw new \RuntimeException('Contact email could not be sent.');
        } finally {
            // Always write to email_logs — even on failure (mirrors Req 10.3 pattern)
            // Use 'contact' type to keep it separate from resident notification logs
            try {
                (new EmailLog())->insert([
                    'recipient_email'   => $toEmail,
                    'notification_type' => 'contact',
                    'reference_number'  => null,
                    'subject'           => $subject,
                    'status'            => $status,
                    'error_message'     => $errorMsg,
                ]);
            } catch (\Throwable $logEx) {
                Logger::error('EmailService: failed to write contact email_log: ' . $logEx->getMessage());
            }
        }
    }

    // -------------------------------------------------------------------------
    // Core send method
    // -------------------------------------------------------------------------

    /**
     * Send a single HTML email via Mailtrap SMTP.
     * Always logs the attempt in email_logs (Req 10.3).
     * Failures are caught and logged — never thrown to callers (Req 10.4).
     *
     * @param string  $toEmail         Recipient email address
     * @param string  $toName          Recipient display name
     * @param string  $subject         Email subject line
     * @param string  $htmlBodyContent HTML body (will be wrapped in the layout template)
     * @param string  $notificationType One of TYPE_* constants
     * @param ?string $referenceNumber Request reference number where applicable
     */
    private static function send(
        string  $toEmail,
        string  $toName,
        string  $subject,
        string  $htmlBodyContent,
        string  $notificationType,
        ?string $referenceNumber
    ): void {
        // Build full HTML email by wrapping body content in the layout template
        $htmlBody = self::renderTemplate($subject, $htmlBodyContent);

        $errorMessage = null;
        $status       = EmailLog::STATUS_SENT;

        try {
            $config = require dirname(__DIR__, 2) . '/config/mail.php';

            // If SMTP credentials are not configured, skip sending but log as sent=false
            if (empty($config['host']) || empty($config['username']) || empty($config['password'])) {
                $errorMessage = 'Mail not configured: MAIL_HOST, MAIL_USERNAME, or MAIL_PASSWORD is empty in .env';
                $status       = EmailLog::STATUS_FAILED;
                Logger::warning('EmailService: mail not sent (credentials not configured)', [
                    'notification_type' => $notificationType,
                    // credentials are NOT logged
                ]);
            } else {
                $mailer = new PHPMailer(true); // true = throw exceptions

                // Server settings — never expose credentials in logs
                $mailer->isSMTP();
                $mailer->Host       = $config['host'];
                $mailer->Port       = (int) $config['port'];
                $mailer->SMTPAuth   = true;
                $mailer->Username   = $config['username'];   // Req 10.2 — from env only
                $mailer->Password   = $config['password'];   // Req 10.2 — from env only
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

                // Disable debug output to prevent credential leakage (Req 10.7)
                $mailer->SMTPDebug  = SMTP::DEBUG_OFF;

                // Sender
                $mailer->setFrom(
                    $config['from_address'] ?: 'noreply@emunicipal.local',
                    $config['from_name']    ?: ($_ENV['APP_NAME'] ?? 'eMunicipal')
                );

                // Recipient
                $mailer->addAddress($toEmail, $toName);

                // Content
                $mailer->isHTML(true);
                $mailer->CharSet = PHPMailer::CHARSET_UTF8;
                $mailer->Subject = $subject;
                $mailer->Body    = $htmlBody;
                $mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], "\n", $htmlBodyContent));

                $mailer->send();
            }

        } catch (MailerException $e) {
            $status = EmailLog::STATUS_FAILED;
            // Log the error message but NOT the SMTP credentials (Req 10.7)
            $errorMessage = 'Mailer error: ' . $e->getMessage();
            Logger::error('EmailService send failed', [
                'notification_type' => $notificationType,
                'to_email'          => $toEmail,
                // 'host', 'username', 'password' intentionally omitted
            ]);
        } catch (\Throwable $e) {
            $status = EmailLog::STATUS_FAILED;
            $errorMessage = 'Unexpected error: ' . $e->getMessage();
            Logger::error('EmailService unexpected error', [
                'notification_type' => $notificationType,
            ]);
        }

        // Always log the attempt (Req 10.3) — never throw on log failure
        try {
            (new EmailLog())->insert([
                'recipient_email'   => $toEmail,
                'notification_type' => $notificationType,
                'reference_number'  => $referenceNumber,
                'subject'           => $subject,
                'status'            => $status,
                'error_message'     => $errorMessage,
            ]);
        } catch (\Throwable $logEx) {
            // Log to file if DB log fails — never let this break the operation
            Logger::error('EmailService: failed to write email_log row: ' . $logEx->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Template renderer
    // -------------------------------------------------------------------------

    /**
     * Render the email HTML by injecting the body content into the layout template.
     * Returns a plain HTML string — does not output.
     */
    private static function renderTemplate(string $subject, string $body): string
    {
        $templatePath = dirname(__DIR__, 2) . '/views/emails/layout.php';

        ob_start();
        if (file_exists($templatePath)) {
            // Expose $subject and $body to the template
            include $templatePath;
        } else {
            // Fallback: inline the body without the layout (graceful degradation)
            echo "<!DOCTYPE html><html><body>{$body}</body></html>";
        }
        return ob_get_clean();
    }
}
