<?php
/**
 * Copyright and Intellectual Property Policy — eMunicipal
 * Layout: layouts/public
 * Variables: $title, $description (set by HomeController::policyCopyright)
 */
$appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e      = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>

<!-- ============================================================
     POLICY PAGE HERO
     ============================================================ -->
<section class="page-hero-sm" aria-labelledby="copyright-heading">
    <div class="container">
        <nav aria-label="Breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0" style="font-size:0.8125rem;opacity:0.75;">
                <li class="breadcrumb-item"><a href="<?= $e($appUrl) ?>/" style="color:rgba(255,255,255,0.75);text-decoration:none;">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page" style="color:#fff;">Copyright Policy</li>
            </ol>
        </nav>
        <h1 id="copyright-heading" style="font-family:'Montserrat','Inter',sans-serif;font-size:clamp(1.5rem,3vw,2.25rem);font-weight:800;color:#fff;margin-bottom:0.5rem;">
            Copyright and Intellectual Property Policy
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
                        This Copyright and Intellectual Property Policy describes the ownership of
                        the original code, design, and content that make up the eMunicipal academic
                        demonstration system, and the terms under which third-party open-source
                        components and assets are used.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 1. Original Code and Design -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        1. Original Code and Design
                    </h2>
                    <p>
                        The original source code, database schema, application architecture,
                        visual design, and written content of the eMunicipal system were created
                        by the project's development team as an academic exercise. All original
                        work in this project is the intellectual property of its authors.
                    </p>
                    <p>
                        The thematic concept of Biringan City as a fictional Philippine municipality
                        is inspired by Philippine folklore and the public domain cultural tradition
                        of the Biringan legend. The project does not claim ownership of the folklore
                        itself, only of the original creative work expressed in this software.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 2. Third-Party Components -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        2. Third-Party Open-Source Components
                    </h2>
                    <p>
                        This project makes use of the following open-source libraries and assets,
                        each of which is used in accordance with its respective license:
                    </p>
                    <ul>
                        <li>
                            <strong>Bootstrap 5</strong> — MIT License.
                            A front-end framework used for layout, typography, and responsive design components.
                        </li>
                        <li>
                            <strong>Bootstrap Icons</strong> — MIT License.
                            Icon set used throughout the user interface.
                        </li>
                        <li>
                            <strong>Google Fonts (Montserrat, Inter)</strong> — SIL Open Font License.
                            Typography used for headings and body text.
                        </li>
                        <li>
                            <strong>Chillerz/php-qrcode</strong> — MIT License.
                            A PHP library used to generate QR codes for the simulated online payment demonstration.
                        </li>
                        <li>
                            <strong>PHPMailer</strong> — LGPL-2.1 License.
                            Used to send email notifications via the configured mail transport.
                        </li>
                        <li>
                            <strong>vlucas/phpdotenv</strong> — BSD-3-Clause License.
                            Used to load environment configuration from the <code>.env</code> file.
                        </li>
                    </ul>
                    <p>
                        Full license texts for third-party components are available in the project's
                        <code>vendor/</code> directory and in each library's respective repository.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 3. Restrictions on Copying -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        3. Restrictions on Copying and Distribution
                    </h2>
                    <p>
                        The original source code and design of eMunicipal are made available for
                        academic review and demonstration only. You must not:
                    </p>
                    <ul>
                        <li>Copy, redistribute, or sublicense the original source code for commercial purposes without explicit written permission from the authors.</li>
                        <li>Remove or alter copyright notices, attribution statements, or license headers in the source code.</li>
                        <li>Represent this system or any modified version of it as an official government service or product.</li>
                        <li>Use the eMunicipal name, Biringan City branding, or associated visual design in a manner that misrepresents their academic and fictional nature.</li>
                    </ul>
                    <p>
                        Academic use, including reading, referencing, and quoting limited portions
                        of the source code for educational purposes with proper attribution, is
                        permitted.
                    </p>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 4. Reporting Infringement -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        4. Reporting Intellectual Property Concerns
                    </h2>
                    <p>
                        If you believe that content within this academic demonstration system
                        infringes your intellectual property rights, or if you believe a third-party
                        component has been used incorrectly or without proper attribution, please
                        report the concern through the
                        <a href="<?= $e($appUrl) ?>/#contact" style="color:var(--emu-primary,#4A1F46);">contact form</a>
                        on the homepage.
                    </p>
                    <p>Please include the following in your report:</p>
                    <ul>
                        <li>Your name and contact information.</li>
                        <li>A description of the intellectual property you believe has been infringed.</li>
                        <li>The specific page, feature, or file within the System where the alleged infringement appears.</li>
                        <li>A statement explaining the basis of your claim.</li>
                    </ul>

                    <hr style="margin:2rem 0;border-color:var(--biringan-border,#E8E0E8);">

                    <!-- 5. Review Procedure -->
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--emu-primary,#4A1F46);margin-bottom:0.75rem;">
                        5. Review Procedure
                    </h2>
                    <p>
                        All intellectual property concerns will be reviewed by the project team in
                        good faith. If a valid concern is identified, the project team will take
                        appropriate corrective action, which may include attribution correction,
                        removal of the infringing content, or replacement with a compliant alternative.
                    </p>
                    <p>
                        Because this is a non-commercial academic project, the review process is
                        informal. There is no formal legal notice procedure or takedown timeline.
                        However, the project team is committed to respecting intellectual property
                        rights and will act promptly on credible reports.
                    </p>

                </div><!-- /.policy-body -->

                <!-- Back / Home links -->
                <div class="mt-4 pt-3 d-flex justify-content-between flex-wrap gap-2" style="border-top:1px solid var(--biringan-border,#E8E0E8);">
                    <a href="<?= $e($appUrl) ?>/policy/moderation"
                       class="d-inline-flex align-items-center gap-2 text-decoration-none"
                       style="color:var(--emu-primary,#4A1F46);font-size:0.875rem;font-weight:500;">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        Moderation Policy
                    </a>
                    <a href="<?= $e($appUrl) ?>/"
                       class="d-inline-flex align-items-center gap-2 text-decoration-none"
                       style="color:var(--emu-primary,#4A1F46);font-size:0.875rem;font-weight:500;">
                        Back to Home
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>
