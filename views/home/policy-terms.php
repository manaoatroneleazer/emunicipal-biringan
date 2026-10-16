<?php
/**
 * Terms and Conditions — eMunicipal
 * Layout: layouts/public
 * Variables: $title, $description (set by HomeController::policyTerms)
 */
$appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>

<!-- ============================================================
     POLICY PAGE HERO
     ============================================================ -->
<section class="page-hero-sm" aria-labelledby="terms-heading">
    <div class="container">
        <nav aria-label="Breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0" style="font-size:0.8125rem;opacity:0.75;">
                <li class="breadcrumb-item"><a href="<?= $e($appUrl) ?>/" style="color:rgba(255,255,255,0.75);text-decoration:none;">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page" style="color:#fff;">Terms and Conditions</li>
            </ol>
        </nav>
        <h1 id="terms-heading" style="font-family:'Montserrat','Inter',sans-serif;font-size:clamp(1.5rem,3vw,2.25rem);font-weight:800;color:#fff;margin-bottom:0.5rem;">
            Terms and Conditions
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
                Biringan City and all organizations, services, documents, and characters presented in
                this system are entirely fictional and created for academic purposes. This is
                <strong>not an official government website or service</strong>.
                No real government documents are issued through this platform.
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
                        These Terms and Conditions govern your access to and use of the eMunicipal
                        Online Document Request and Management System ("the System"), an academic
                        demonstration project representing the fictional municipality of Biringan City,
                        inspired by Philippine folklore.
                    </p>
                    <p>
                        By creating an account, logging in, or submitting a request through the System,
                        you agree to be bound by these Terms. If you do not agree, you must not use
                        the System.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 1. Acceptance of Terms -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        1. Acceptance of Terms
                    </h2>
                    <p>
                        Your use of the System constitutes acceptance of these Terms and Conditions
                        in full. These Terms apply to all users of the System, including Residents,
                        Staff, and Administrators. The System is made available solely for academic
                        and demonstration purposes and must not be used for any real government
                        transaction.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 2. Account Registration and Security -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        2. Account Registration and Security
                    </h2>
                    <p>
                        To use most features of the System, you must register an account. When
                        registering, you agree to:
                    </p>
                    <ul>
                        <li>Provide accurate and complete registration information.</li>
                        <li>Keep your password confidential and not share it with any other person.</li>
                        <li>Notify the project administrator immediately if you suspect unauthorized access to your account.</li>
                        <li>Accept responsibility for all activity that occurs under your account credentials.</li>
                    </ul>
                    <p>
                        Because this is an academic demonstration, we recommend using fictional or
                        placeholder information rather than your real personal details. Do not enter
                        real government-issued identification numbers or sensitive personal records.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 3. Acceptable Use -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        3. Acceptable Use
                    </h2>
                    <p>You agree to use the System only for its intended demonstration purpose. You must:</p>
                    <ul>
                        <li>Use the System in good faith for academic exploration and evaluation.</li>
                        <li>Respect the access controls assigned to your role.</li>
                        <li>Submit requests that are relevant to the demonstration scenario.</li>
                        <li>Use fictional or sample information where personal data would ordinarily be required.</li>
                    </ul>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 4. Prohibited Activities -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        4. Prohibited Activities
                    </h2>
                    <p>You must not use the System to:</p>
                    <ul>
                        <li>Attempt to gain unauthorized access to accounts, system features, or data you are not permitted to access.</li>
                        <li>Probe, scan, or test the vulnerability of the System without authorization from the project administrator.</li>
                        <li>Introduce malware, viruses, or any other harmful code.</li>
                        <li>Submit false, misleading, or malicious requests intended to disrupt demonstration operations.</li>
                        <li>Upload files that contain executable code, offensive content, or content that violates Philippine law.</li>
                        <li>Impersonate any other user, staff member, or administrator.</li>
                        <li>Harvest, collect, or scrape data from the System by automated means.</li>
                        <li>Use the System to simulate or conduct real financial transactions.</li>
                    </ul>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 5. File Upload Restrictions -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        5. File Upload Restrictions
                    </h2>
                    <p>When uploading supporting documents to a request, you agree that:</p>
                    <ul>
                        <li>Files must be in PDF, JPEG, or PNG format and must not exceed 5 MB each.</li>
                        <li>You will not upload real government-issued identification documents, passports, birth certificates, or other sensitive personal records.</li>
                        <li>You will use fictional sample documents, blank templates, or placeholder images for this demonstration.</li>
                        <li>You will not upload files containing malicious code, executable scripts, or offensive material.</li>
                        <li>Uploaded files are stored on the server and may be reviewed by authorized staff and administrators in the demonstration environment.</li>
                    </ul>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 6. Requests and Tracking -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        6. Document Requests and Status Tracking
                    </h2>
                    <p>
                        The document request workflow in this system is a simulation. No real
                        government document will be issued as a result of any request made through
                        this platform. Status changes — including Pending, Under Review, Ready for
                        Claiming, Completed, and Rejected — are simulated for demonstration purposes
                        only.
                    </p>
                    <p>
                        The request tracking number displayed in the System is a demonstration
                        reference only and is not valid for any official government transaction.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 7. Simulated Payments -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        7. Simulated Payments
                    </h2>
                    <p>
                        The System includes a simulated payment feature that demonstrates a cash
                        payment and an online payment workflow. These are entirely fictional.
                    </p>
                    <ul>
                        <li>No real money is charged, collected, or transferred at any point.</li>
                        <li>No real payment gateway, e-wallet service (such as GCash or Maya), or banking system is connected to this platform.</li>
                        <li>Any QR code shown in the System is a demonstration reference only and does not represent a real payment instruction.</li>
                        <li>Transaction reference numbers generated by this System are fictional and have no monetary value.</li>
                    </ul>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 8. Account Suspension and Deactivation -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        8. Account Suspension and Deactivation
                    </h2>
                    <p>
                        The project administrator reserves the right to deactivate or restrict any
                        account that violates these Terms, misuses the System, or interferes with
                        the academic demonstration for other users. Deactivated accounts may not
                        log in, but their associated request records are retained for data-integrity
                        purposes.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 9. Changes to These Terms -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        9. Changes to These Terms
                    </h2>
                    <p>
                        These Terms may be updated as the academic project evolves. Continued use
                        of the System after any update constitutes acceptance of the revised Terms.
                        The "Last reviewed" date at the top of this page reflects when the Terms
                        were most recently updated.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 10. Academic Disclaimer -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        10. Academic Disclaimer
                    </h2>
                    <p>
                        eMunicipal is an academic project. Biringan City is a fictional place from
                        Philippine folklore and is not a real municipality. This System is not
                        affiliated with, endorsed by, or representative of any real Philippine local
                        government unit, national government agency, or official public service.
                    </p>
                    <p>
                        The System is provided for demonstration and educational purposes only.
                        No warranties of any kind — express or implied — are made regarding the
                        accuracy, completeness, or fitness for a particular purpose of any content
                        in this System.
                    </p>

                </div><!-- /.policy-body -->

                <!-- Back / Next links -->
                <div class="mt-4 pt-3 d-flex justify-content-between flex-wrap gap-2" style="border-top:1px solid var(--biringan-border,#E8E0E8);">
                    <a href="<?= $e($appUrl) ?>/policy/privacy"
                       class="d-inline-flex align-items-center gap-2 text-decoration-none"
                       style="color:var(--emu-primary,#4A1F46);font-size:0.875rem;font-weight:500;">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        Privacy Policy
                    </a>
                    <a href="<?= $e($appUrl) ?>/policy/moderation"
                       class="d-inline-flex align-items-center gap-2 text-decoration-none"
                       style="color:var(--emu-primary,#4A1F46);font-size:0.875rem;font-weight:500;">
                        Moderation Policy
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>
