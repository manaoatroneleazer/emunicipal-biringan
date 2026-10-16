<?php
/**
 * Moderation and Removal Policy — eMunicipal
 * Layout: layouts/public
 * Variables: $title, $description (set by HomeController::policyModeration)
 */
$appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>

<!-- ============================================================
     POLICY PAGE HERO
     ============================================================ -->
<section class="page-hero-sm" aria-labelledby="moderation-heading">
    <div class="container">
        <nav aria-label="Breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0" style="font-size:0.8125rem;opacity:0.75;">
                <li class="breadcrumb-item"><a href="<?= $e($appUrl) ?>/" style="color:rgba(255,255,255,0.75);text-decoration:none;">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page" style="color:#fff;">Moderation Policy</li>
            </ol>
        </nav>
        <h1 id="moderation-heading" style="font-family:'Montserrat','Inter',sans-serif;font-size:clamp(1.5rem,3vw,2.25rem);font-weight:800;color:#fff;margin-bottom:0.5rem;">
            Moderation and Removal Policy
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
                        This Moderation and Removal Policy describes how content submitted through
                        the eMunicipal academic demonstration system is reviewed, and the circumstances
                        under which submissions, uploaded files, or accounts may be restricted or
                        removed by authorized staff and administrators.
                    </p>
                    <p>
                        All moderation actions within this system are part of the demonstration
                        workflow and do not constitute real regulatory or enforcement activity by
                        any government body.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 1. False or Abusive Submissions -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        1. False and Abusive Submissions
                    </h2>
                    <p>
                        While this system is a demonstration, it is expected to be used in good
                        faith. Submissions that are deemed abusive, disruptive, or contrary to the
                        academic purpose of the platform include:
                    </p>
                    <ul>
                        <li>Requests submitted with the intent to disrupt the demonstration for other users.</li>
                        <li>Submissions containing offensive, defamatory, or harassing content in any text field.</li>
                        <li>Multiple duplicate requests submitted to artificially inflate volume or stress-test the system without authorization.</li>
                        <li>Requests that contain real personally identifiable information about third parties who have not consented to its inclusion.</li>
                        <li>Submissions that impersonate another resident, staff member, or official.</li>
                    </ul>
                    <p>
                        Staff members reviewing requests may flag any submission that appears
                        inconsistent with the academic demonstration purpose. Flagged submissions
                        will be escalated to an administrator for review.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 2. Prohibited File Uploads -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        2. Prohibited File Uploads
                    </h2>
                    <p>Uploaded supporting documents may be reviewed by authorized staff and administrators. Files will be subject to removal if they:</p>
                    <ul>
                        <li>Are not in an accepted format (PDF, JPEG, or PNG).</li>
                        <li>Exceed the maximum file size of 5 MB.</li>
                        <li>Contain executable code, scripts, or embedded macros.</li>
                        <li>Contain real government-issued identification documents, passports, or sensitive personal records belonging to real individuals.</li>
                        <li>Contain offensive imagery, nudity, hate speech, or content that violates Philippine law.</li>
                        <li>Contain malware, ransomware, spyware, or any other harmful content.</li>
                    </ul>
                    <p>
                        The System validates file MIME types and sizes on upload. Files that do not
                        pass server-side validation are rejected automatically and are never stored.
                        Files that are accepted but later found to violate this policy may be
                        removed by an administrator.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 3. Authorized Review by Staff and Administrators -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        3. Review by Staff and Administrators
                    </h2>
                    <p>
                        Only users with the Staff or Administrator role may review the content of
                        submitted requests, including attached files. This access is controlled by
                        the System's role-based access control and enforced on every request.
                    </p>
                    <p>
                        Administrators have additional capabilities, including:
                    </p>
                    <ul>
                        <li>Viewing and exporting the system audit log.</li>
                        <li>Deactivating user accounts that have violated these policies.</li>
                        <li>Viewing and managing all requests across all residents.</li>
                        <li>Adjusting system settings and document type configurations.</li>
                    </ul>
                    <p>
                        All significant administrative actions — including account deactivation,
                        request status changes, and system setting updates — are recorded in the
                        audit log with a timestamp and the acting user's identity.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 4. System-Supported Moderation Actions -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        4. System-Supported Moderation Actions
                    </h2>
                    <p>
                        The following moderation actions are available within the System as part
                        of the demonstration workflow:
                    </p>
                    <ul>
                        <li>
                            <strong>Reject a request:</strong> Staff or administrators may reject a
                            request with a reason. The Resident is notified and the request status
                            is updated accordingly.
                        </li>
                        <li>
                            <strong>Request additional documents:</strong> Staff may update a request's
                            status to prompt the Resident to supply additional supporting files.
                        </li>
                        <li>
                            <strong>Deactivate an account:</strong> Administrators may deactivate a
                            Resident or Staff account. Deactivated accounts cannot log in, but their
                            request history is preserved.
                        </li>
                    </ul>
                    <p>
                        Permanent deletion of request records or user accounts through the
                        application interface is not supported, in order to preserve the integrity
                        of the demonstration data set.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 5. Reporting Misuse -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        5. Reporting Misuse
                    </h2>
                    <p>
                        If you observe a submission, uploaded file, or account that you believe
                        violates this policy, you may report it to the project administrator via
                        the <a href="<?= $e($appUrl) ?>/#contact" style="color:var(--emu-primary,#4A1F46);">contact form</a>
                        on the homepage. Please describe the specific request reference number or
                        username involved, along with a brief description of the concern.
                    </p>
                    <p>
                        The project administrator will review reported content and take appropriate
                        action within the demonstration environment. Because no real government
                        services are involved, no formal regulatory escalation process applies.
                    </p>

                </div><!-- /.policy-body -->

                <!-- Back / Next links -->
                <div class="mt-4 pt-3 d-flex justify-content-between flex-wrap gap-2" style="border-top:1px solid var(--biringan-border,#E8E0E8);">
                    <a href="<?= $e($appUrl) ?>/policy/terms"
                       class="d-inline-flex align-items-center gap-2 text-decoration-none"
                       style="color:var(--emu-primary,#4A1F46);font-size:0.875rem;font-weight:500;">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        Terms and Conditions
                    </a>
                    <a href="<?= $e($appUrl) ?>/policy/copyright"
                       class="d-inline-flex align-items-center gap-2 text-decoration-none"
                       style="color:var(--emu-primary,#4A1F46);font-size:0.875rem;font-weight:500;">
                        Copyright Policy
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>
