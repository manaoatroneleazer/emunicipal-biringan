<?php
/**
 * Privacy Policy — eMunicipal
 * Layout: layouts/public
 * Variables: $title, $description (set by HomeController::policyPrivacy)
 */
$appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>

<!-- ============================================================
     POLICY PAGE HERO
     ============================================================ -->
<section class="page-hero-sm" aria-labelledby="policy-heading">
    <div class="container">
        <nav aria-label="Breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0" style="font-size:0.8125rem;opacity:0.75;">
                <li class="breadcrumb-item"><a href="<?= $e($appUrl) ?>/" style="color:rgba(255,255,255,0.75);text-decoration:none;">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page" style="color:#fff;">Privacy Policy</li>
            </ol>
        </nav>
        <h1 id="policy-heading" style="font-family:'Montserrat','Inter',sans-serif;font-size:clamp(1.5rem,3vw,2.25rem);font-weight:800;color:#fff;margin-bottom:0.5rem;">
            Privacy Policy
        </h1>
        <p style="color:rgba(255,255,255,0.75);font-size:0.9375rem;margin:0;">
            Last reviewed: <?= date('F Y') ?> &nbsp;·&nbsp; eMunicipal Academic Demonstration
        </p>
    </div>
</section>

<!-- ============================================================
     ACADEMIC DISCLAIMER BANNER
     ============================================================ -->
<div style="background:rgba(200,146,42,0.12);border-bottom:1px solid rgba(200,146,42,0.30);padding:0.875rem 0;">
    <div class="container">
        <p class="mb-0 d-flex align-items-start gap-2" style="font-size:0.8125rem;color:var(--biringan-text,#241C29);">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0" style="color:var(--emu-warning,#c8922a);margin-top:0.15rem;" aria-hidden="true"></i>
            <span>
                <strong>Academic Demonstration Notice:</strong>
                Biringan City and all organizations, services, documents, records, and characters
                presented in this system are fictional and created for academic demonstration.
                This project is inspired by Philippine folklore and is
                <strong>not an official government website or service</strong>.
            </span>
        </p>
    </div>
</div>

<!-- ============================================================
     POLICY CONTENT
     ============================================================ -->
<section style="padding:3rem 0 4rem;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9 col-xl-8">

                <div class="policy-body" style="font-size:0.9375rem;line-height:1.8;color:var(--biringan-text,#241C29);">

                    <!-- Introduction -->
                    <p>
                        This Privacy Policy explains how the eMunicipal system, an academic demonstration
                        project for Biringan City, collects, uses, and handles information that users
                        provide while using the platform. Because this is a fictional demonstration system,
                        no information entered here is used for any real government purpose, and no real
                        municipal services are delivered.
                    </p>
                    <p>
                        By creating an account or submitting a document request through this system,
                        you acknowledge that you are using an academic demonstration and that this
                        Privacy Policy applies to your use.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 1. Information Collected -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        1. Information We Collect
                    </h2>
                    <p>When you use eMunicipal, the following categories of information may be collected:</p>
                    <ul>
                        <li>
                            <strong>Registration information:</strong> First name, last name, email address,
                            contact number, and home address. These are required to create an account.
                        </li>
                        <li>
                            <strong>Request information:</strong> Purpose of request, document type selected,
                            number of copies requested, and any additional fields required for a specific
                            document type.
                        </li>
                        <li>
                            <strong>Uploaded files:</strong> Supporting documents you attach to a request,
                            accepted as PDF, JPEG, or PNG files up to 5 MB each.
                        </li>
                        <li>
                            <strong>Account activity:</strong> Login events, request status changes,
                            profile updates, and other actions you perform within the system, which are
                            recorded in an audit log.
                        </li>
                        <li>
                            <strong>Session data:</strong> A session cookie is used to keep you logged
                            in while you use the system. This cookie is not used for tracking across
                            other websites.
                        </li>
                        <li>
                            <strong>Email logs:</strong> When the system sends you an email notification,
                            the recipient address, notification type, and send status are logged. Email
                            delivery uses the Mailtrap service, which is configured for demonstration
                            and development environments only.
                        </li>
                    </ul>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 2. How We Use Your Information -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        2. How We Use Your Information
                    </h2>
                    <p>The information collected within this academic demonstration system is used solely to:</p>
                    <ul>
                        <li>Create and authenticate your account.</li>
                        <li>Process and track the document requests you submit.</li>
                        <li>Send you email notifications about the status of your requests, where email is configured.</li>
                        <li>Allow authorized staff and administrators to review and act on your requests.</li>
                        <li>Maintain an audit trail of significant system actions for demonstration purposes.</li>
                    </ul>
                    <p>
                        Because this is a demonstration project, your information is not shared with
                        any real government agency, third-party service provider for production use,
                        marketing platform, or external database. No real government document processing
                        occurs.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 3. Access to Your Information -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        3. Access to Your Information
                    </h2>
                    <p>Within this system, access to your information is controlled by role:</p>
                    <ul>
                        <li>
                            <strong>Residents</strong> can view and manage only their own account
                            information and their own submitted requests.
                        </li>
                        <li>
                            <strong>Staff members</strong> can view the details of all submitted requests,
                            including the Resident's contact information and uploaded files, in order
                            to process requests.
                        </li>
                        <li>
                            <strong>Administrators</strong> can access user account records, all requests,
                            audit logs, and system settings.
                        </li>
                    </ul>
                    <p>
                        All access is enforced by the system's role-based access control, and
                        unauthorized access attempts are rejected.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 4. Data Storage and Retention -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        4. Data Storage and Retention
                    </h2>
                    <p>
                        All data entered into this system is stored in the MySQL/MariaDB database
                        configured for this academic demonstration. Uploaded files are stored on the
                        server in a directory that is not directly accessible from the web.
                    </p>
                    <p>
                        Because this is an academic project, no formal data-retention schedule is
                        enforced. Accounts that have associated requests cannot be deleted through
                        the application interface; they can only be deactivated. This preserves the
                        integrity of historical request records.
                    </p>
                    <p>
                        Data may be cleared or reset when the demonstration environment is restarted
                        or reconfigured by the project administrator.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 5. Cookies and Session Data -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        5. Cookies and Session Data
                    </h2>
                    <p>
                        eMunicipal uses a single server-side PHP session cookie named
                        <code>EMUSESSID</code> to maintain your authenticated session. This cookie:
                    </p>
                    <ul>
                        <li>Is set when you log in and expires when you close your browser or log out.</li>
                        <li>Is marked <code>HttpOnly</code>, so it cannot be accessed by client-side scripts.</li>
                        <li>Is set with <code>SameSite=Lax</code> to help protect against cross-site request forgery.</li>
                        <li>Does not track your activity across other websites.</li>
                    </ul>
                    <p>
                        No analytics cookies, advertising cookies, or third-party tracking cookies
                        are used by this system.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 6. Security Measures -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        6. Security Measures
                    </h2>
                    <p>
                        This demonstration system implements several standard security practices,
                        including:
                    </p>
                    <ul>
                        <li>Passwords are hashed using PHP's <code>password_hash()</code> function and are never stored in plain text.</li>
                        <li>All database queries use PDO prepared statements to prevent SQL injection.</li>
                        <li>CSRF tokens are validated on all form submissions.</li>
                        <li>Uploaded files are validated for MIME type and size, stored outside the web root, and renamed to prevent path traversal.</li>
                        <li>HTTP security headers are set on all responses.</li>
                    </ul>
                    <p>
                        While reasonable precautions are in place for a demonstration project, this
                        system should not be used to store real personal identification documents,
                        sensitive government records, or any confidential information.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 7. Contact -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        7. Privacy Questions
                    </h2>
                    <p>
                        Because this is an academic demonstration, there is no formal data protection
                        officer. If you have questions about how information is handled within this
                        system, you may use the
                        <a href="<?= $e($appUrl) ?>/#contact" style="color:var(--emu-primary,#4A1F46);">contact form</a>
                        on the homepage to reach the project team.
                    </p>
                    <p>
                        Requests to review or remove demonstration data should also be directed
                        through the same contact form.
                    </p>

                </div><!-- /.policy-body -->

                <!-- Back link -->
                <div class="mt-4 pt-3" style="border-top:1px solid var(--biringan-border,#E8E0E8);">
                    <a href="<?= $e($appUrl) ?>/"
                       class="d-inline-flex align-items-center gap-2 text-decoration-none"
                       style="color:var(--emu-primary,#4A1F46);font-size:0.875rem;font-weight:500;">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        Back to Home
                    </a>
                    &nbsp;&nbsp;
                    <a href="<?= $e($appUrl) ?>/policy/terms"
                       class="d-inline-flex align-items-center gap-2 text-decoration-none"
                       style="color:var(--emu-primary,#4A1F46);font-size:0.875rem;font-weight:500;">
                        Terms and Conditions
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>
