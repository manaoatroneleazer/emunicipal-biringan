<?php
/**
 * Public information pages — About, Services, How It Works, FAQs, Contact.
 * Variables: $heading, $message (optional), $documentTypes (optional for Services)
 */
$appUrl        = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$heading       = isset($heading) ? htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') : 'eMunicipal';
$message       = isset($message) ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : '';
$documentTypes = $documentTypes ?? [];
$e             = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

// Determine which page we're rendering from $heading
$isAbout   = str_contains(strtolower($heading), 'about');
$isService = str_contains(strtolower($heading), 'service');
$isHIW     = str_contains(strtolower($heading), 'how it works');
$isFAQ     = str_contains(strtolower($heading), 'faq');
$isContact = str_contains(strtolower($heading), 'contact');
?>

<?php if ($isAbout): ?>
<!-- ============================================================
     ABOUT PAGE
     ============================================================ -->
<div class="b2-pub-hero">
    <div class="container">
        <div class="b2-pub-hero-badge">
            <i class="bi bi-info-circle" aria-hidden="true"></i> About eMunicipal
        </div>
        <h1>About eMunicipal</h1>
        <p>A fictional academic demonstration of a digital document request portal, inspired by Philippine folklore.</p>
    </div>
</div>

<div class="py-5" style="background:var(--biringan-bg);">
    <div class="container">
        <!-- System introduction -->
        <div class="row align-items-center g-5 mb-5">
            <div class="col-12 col-lg-7">
                <div class="b2-page-header-accent"></div>
                <h2 class="lp-section-title mb-3">What is eMunicipal?</h2>
                <p style="font-size:0.9375rem;color:var(--biringan-muted);line-height:1.7;">
                    eMunicipal is a fictional academic demonstration of an Online Document Request and Management System
                    created for the fictional <strong>Biringan City Administration</strong>.
                    It showcases how a digital civic portal could work — allowing community members to submit
                    document requests, track status in real time, and receive notifications without visiting an office.
                </p>
                <p style="font-size:0.9375rem;color:var(--biringan-muted);line-height:1.7;">
                    This project is inspired by Philippine folklore and is <strong>not an official government
                    website or service</strong>. All organizations, documents, and characters are entirely fictional.
                </p>
                <a href="<?= $e($appUrl) ?>/register" class="btn btn-primary mt-2">
                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i> Create an Account
                </a>
            </div>
            <div class="col-12 col-lg-5">
                <div style="background:linear-gradient(135deg,var(--biringan-primary),var(--biringan-purple));border-radius:20px;padding:2.5rem;text-align:center;border:1px solid rgba(216,176,106,0.18);">
                    <img src="<?= $e($appUrl) ?>/public/assets/images/biringan-logo-placeholder.svg"
                         alt="Biringan City Administration"
                         style="width:90px;height:90px;margin-bottom:1.25rem;filter:drop-shadow(0 4px 16px rgba(216,176,106,0.25));">
                    <p class="mb-1" style="font-family:'Montserrat','Inter',sans-serif;font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.20em;color:var(--biringan-gold-light);">eMunicipal</p>
                    <p class="mb-0" style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:800;color:#fff;">Biringan City</p>
                    <p class="mb-0" style="font-size:0.75rem;color:rgba(255,255,255,0.55);">Administration — Academic Demonstration</p>
                </div>
            </div>
        </div>

        <!-- Feature cards -->
        <div class="row g-3 mb-5">
            <?php foreach ([
                ['bi-file-earmark-text','ic-purple','Online Requests','Submit document requests digitally without visiting the office.'],
                ['bi-search','ic-rose','Real-Time Tracking','Check request status and get updates at every step.'],
                ['bi-envelope-check','ic-gold','Email Notifications','Receive email notifications when your request status changes.'],
                ['bi-shield-check','ic-teal','Secure & Private','Your data is handled with security best practices.'],
            ] as $f): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="b2-feature-card">
                    <div class="b2-feature-card-icon <?= $f[1] ?>">
                        <i class="bi <?= $f[0] ?>" aria-hidden="true"></i>
                    </div>
                    <h3><?= $f[2] ?></h3>
                    <p><?= $f[3] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Academic disclaimer -->
        <div class="d-flex align-items-start gap-3 p-4 rounded-3" style="background:#fef3c7;border:1px solid #f59e0b;max-width:800px;margin:0 auto;">
            <i class="bi bi-info-circle-fill flex-shrink-0 mt-1" style="font-size:1.25rem;color:#92400e;" aria-hidden="true"></i>
            <div>
                <div style="font-size:0.875rem;font-weight:700;color:#92400e;margin-bottom:4px;">Fictional Academic Demonstration</div>
                <p class="mb-0" style="font-size:0.8125rem;color:#78350f;line-height:1.6;">
                    Biringan City and all organizations, services, records, documents, and characters presented in this
                    system are fictional and created for academic demonstration. This project is inspired by Philippine
                    folklore and is <strong>not an official government website or service</strong>.
                </p>
            </div>
        </div>
    </div>
</div>

<?php elseif ($isService): ?>
<!-- ============================================================
     SERVICES PAGE
     ============================================================ -->
<div class="b2-pub-hero">
    <div class="container">
        <div class="b2-pub-hero-badge">
            <i class="bi bi-file-earmark-text" aria-hidden="true"></i> Community Services
        </div>
        <h1>Available Document Services</h1>
        <p>Select a document type to begin your online request. All documents are fictional sample records for academic demonstration.</p>
    </div>
</div>

<div class="py-5" style="background:var(--biringan-bg);">
    <div class="container">
        <?php if (empty($documentTypes)): ?>
        <div class="b2-empty">
            <div class="b2-empty-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></div>
            <h5>No document services available</h5>
            <p>Please check back later or contact the administration.</p>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($documentTypes as $dt):
                // Extract department prefix [DEPT] from description
                $dept = '';
                if (preg_match('/^\[([^\]]+)\]/', $dt['description'] ?? '', $m)) {
                    $deptMap = [
                        'BCRO' => 'Biringan Civil Records Office',
                        'CRO'  => 'Community Records Office',
                        'CAO'  => 'Community Assistance Office',
                        'BSO'  => 'Business Services Office',
                        'CHO'  => 'Community Health Office',
                    ];
                    $dept = $deptMap[trim($m[1])] ?? trim($m[1]);
                }
                $descClean = trim(preg_replace('/^\[[^\]]+\]\s*/', '', $dt['description'] ?? ''));
                $descShort = mb_strlen($descClean) > 100 ? mb_substr($descClean, 0, 100) . '…' : $descClean;
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="b2-service-catalog-card">
                    <div class="b2-service-catalog-icon">
                        <i class="bi bi-file-earmark-check" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="b2-service-catalog-name"><?= $e($dt['name']) ?></div>
                        <?php if ($dept): ?>
                        <div class="b2-service-catalog-dept"><?= $e($dept) ?></div>
                        <?php endif; ?>
                        <p class="b2-service-catalog-desc"><?= $e($descShort) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= $e($appUrl) ?>/login" class="btn btn-primary px-4">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Request a Document
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($isHIW): ?>
<!-- ============================================================
     HOW IT WORKS PAGE
     ============================================================ -->
<div class="b2-pub-hero">
    <div class="container">
        <div class="b2-pub-hero-badge">
            <i class="bi bi-map" aria-hidden="true"></i> The Process
        </div>
        <h1>How It Works</h1>
        <p>Six simple steps to request and receive your community documents online.</p>
    </div>
</div>

<div class="py-5" style="background:var(--biringan-bg);">
    <div class="container">
        <div class="row g-3 mb-5">
            <?php
            $steps = [
                ['bi-file-earmark-text','1','Choose a Document','Browse the available document types and select the one you need.'],
                ['bi-list-check','2','Review Requirements','Check what supporting documents or information you need to provide.'],
                ['bi-pencil','3','Provide Information','Fill in the request form with your details and purpose.'],
                ['bi-cloud-upload','4','Upload Supporting Files','Attach scanned copies of required supporting documents.'],
                ['bi-send','5','Submit Request','Submit your request and receive a reference number by email.'],
                ['bi-search','6','Track Progress','Monitor your request status in real time and receive email notifications.'],
            ];
            foreach ($steps as $s):
            ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="b2-hiw-step-card">
                    <div class="b2-hiw-num"><?= $s[1] ?></div>
                    <div class="b2-hiw-icon">
                        <i class="bi <?= $s[0] ?>" aria-hidden="true"></i>
                    </div>
                    <h3><?= $s[2] ?></h3>
                    <p><?= $s[3] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA -->
        <div class="text-center">
            <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.25rem;font-weight:700;margin-bottom:0.5rem;">Ready to get started?</h2>
            <p style="font-size:0.9375rem;color:var(--biringan-muted);margin-bottom:1.5rem;">Create your account and submit your first request in minutes.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="<?= $e($appUrl) ?>/register" class="btn btn-primary px-4">
                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i> Register Now
                </a>
                <a href="<?= $e($appUrl) ?>/#contact" class="btn btn-outline-primary px-4">
                    <i class="bi bi-envelope me-1" aria-hidden="true"></i> Contact Us
                </a>
            </div>
        </div>
    </div>
</div>

<?php elseif ($isFAQ): ?>
<!-- ============================================================
     FAQ PAGE
     ============================================================ -->
<div class="b2-pub-hero">
    <div class="container">
        <div class="b2-pub-hero-badge">
            <i class="bi bi-question-circle" aria-hidden="true"></i> Help
        </div>
        <h1>Frequently Asked Questions</h1>
        <p>Find answers to common questions about using the eMunicipal portal.</p>
    </div>
</div>

<div class="py-5" style="background:var(--biringan-bg);">
    <div class="container" style="max-width:760px;">
        <?php
        $faqs = [
            ['What is eMunicipal?',
             'eMunicipal is a fictional academic demonstration of an online document request and management system for the Biringan City Administration. It is inspired by Philippine folklore and is not an official government website or service.'],
            ['How do I create an account?',
             'Click the "Register" button on the top navigation bar. Fill in your name, email address, password, and address. Once submitted, you can immediately log in.'],
            ['How do I submit a document request?',
             'After logging in, go to "New Request" in the sidebar. Select a document type, review the requirements, fill in the request details, upload supporting files, and click Submit.'],
            ['How can I track my request?',
             'Use the "Track My Request" button on the homepage or go to "Track Request" in the sidebar after logging in. Enter your reference number (e.g., EMU-2026-000001) to see the full status history.'],
            ['What documents can I request?',
             'The system offers 8 fictional sample document types including Birth Certificate, Residency Certificate, Community Clearance, and more. All documents are fictional and created for academic demonstration.'],
            ['Can I cancel my request?',
             'Yes, as long as your request is in Pending, Under Verification, or Additional Information Required status. Open the request from My Requests and click Cancel Request.'],
            ['How will I know when my request status changes?',
             'You will receive an email notification whenever your request status is updated. You can also view notifications in the sidebar.'],
            ['How do I claim my document?',
             'Once your request is approved and marked Ready for Claiming, visit the Biringan City Civic Center, Central District on a weekday between 8:00 AM and 5:00 PM with a valid community identification.'],
            ['What should I do if my request is rejected?',
             'The rejection reason will be shown on your request detail page and sent by email. You may submit a new request with the corrected information or complete supporting documents.'],
        ];
        ?>
        <?php foreach ($faqs as $i => $faq): ?>
        <div class="b2-faq-item <?= $i === 0 ? 'faq-open' : '' ?>"
             tabindex="0"
             role="button"
             aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>"
             aria-label="<?= $e($faq[0]) ?>">
            <div class="b2-faq-question"><?= $e($faq[0]) ?></div>
            <div class="b2-faq-answer"><?= $e($faq[1]) ?></div>
        </div>
        <?php endforeach; ?>

        <div class="text-center mt-5">
            <p style="font-size:0.875rem;color:var(--biringan-muted);">Still have questions?</p>
            <a href="<?= $e($appUrl) ?>/#contact" class="btn btn-outline-primary px-4">
                <i class="bi bi-envelope me-1" aria-hidden="true"></i> Contact Us
            </a>
        </div>
    </div>
</div>

<?php elseif ($isContact): ?>
<!-- ============================================================
     CONTACT PAGE
     ============================================================ -->
<div class="b2-pub-hero">
    <div class="container">
        <div class="b2-pub-hero-badge">
            <i class="bi bi-telephone" aria-hidden="true"></i> Get in Touch
        </div>
        <h1>Contact Us</h1>
        <p>We're here to help. Send us a message during office hours.</p>
    </div>
</div>

<div class="py-5" style="background:var(--biringan-bg);">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <!-- Contact info -->
            <div class="col-12 col-lg-4">
                <?php
                $contactItems = [
                    ['bi-geo-alt-fill', 'Address', 'Biringan City Civic Center, Central District, Biringan City', null],
                    ['bi-telephone-fill','Phone',   '(000) 000-0000', null],
                    ['bi-envelope-fill', 'Email',   'emunicipal.portal@gmail.com', 'mailto:emunicipal.portal@gmail.com'],
                    ['bi-clock-fill',    'Office Hours', 'Mon–Fri, 8:00 AM – 5:00 PM', null],
                ];
                foreach ($contactItems as $c):
                ?>
                <div class="b2-contact-info-card">
                    <div class="b2-contact-info-icon">
                        <i class="bi <?= $c[0] ?>" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="b2-contact-info-label"><?= $e($c[1]) ?></div>
                        <p class="b2-contact-info-value">
                            <?php if ($c[3]): ?>
                            <a href="<?= $e($c[3]) ?>"><?= $e($c[2]) ?></a>
                            <?php else: ?>
                            <?= $e($c[2]) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="d-flex align-items-start gap-3 p-3 rounded-3 mt-3" style="background:#fef3c7;border:1px solid #f59e0b;">
                    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1" style="font-size:1rem;color:#92400e;" aria-hidden="true"></i>
                    <p class="mb-0" style="font-size:0.775rem;color:#78350f;line-height:1.55;">
                        Biringan City Administration is fictional and created for academic demonstration.
                        This is not an official government service.
                    </p>
                </div>
            </div>

            <!-- Contact Form — functional, connected to CONTACT_SMTP -->
            <div class="col-12 col-lg-7">
                <div class="contact-form-card">
                    <h2 style="font-family:'Montserrat','Inter',sans-serif;font-size:1.125rem;font-weight:700;color:var(--biringan-primary);margin-bottom:1.25rem;">
                        <i class="bi bi-chat-dots me-2" aria-hidden="true"></i>Send us a message
                    </h2>
                    <div id="contactSuccessMsg" role="alert">
                        <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>
                        Your message has been sent successfully. Thank you for contacting us.
                    </div>
                    <div id="contactErrorMsg" role="alert">
                        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
                        We couldn&rsquo;t send your message right now. Please try again later.
                    </div>
                    <form id="contactForm" novalidate>
                        <input type="hidden" name="_csrf_token"
                               value="<?= htmlspecialchars(\Emunicipal\Helpers\SecurityHelper::getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label for="contact_name" class="form-label">Full Name <span class="text-danger" aria-hidden="true">*</span></label>
                                <input type="text" id="contact_name" name="contact_name"
                                       class="form-control" placeholder="Amara Biringan"
                                       autocomplete="name" maxlength="100" required>
                                <div class="invalid-feedback">Please enter your name.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="contact_email" class="form-label">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
                                <input type="email" id="contact_email" name="contact_email"
                                       class="form-control" placeholder="you@example.com"
                                       autocomplete="email" maxlength="255" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="contact_subject" class="form-label">Subject <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="contact_subject" name="contact_subject"
                                   class="form-control" placeholder="e.g. Question about document requirements"
                                   maxlength="200" required>
                            <div class="invalid-feedback">Please enter a subject.</div>
                        </div>
                        <div class="mb-3">
                            <label for="contact_message" class="form-label">Message <span class="text-danger" aria-hidden="true">*</span></label>
                            <textarea id="contact_message" name="contact_message"
                                      class="form-control" rows="5"
                                      placeholder="Type your message here…"
                                      maxlength="3000" required></textarea>
                            <div class="invalid-feedback">Please enter your message.</div>
                        </div>
                        <button type="submit" id="contactSubmitBtn"
                                class="btn btn-primary w-100"
                                style="padding:0.625rem;font-weight:600;">
                            <i class="bi bi-send me-2" aria-hidden="true"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Generic fallback for any other heading -->
<div class="b2-pub-hero">
    <div class="container">
        <h1><?= $heading ?></h1>
    </div>
</div>
<div class="container py-5">
    <?php if ($message): ?>
    <div class="alert alert-info" role="status">
        <i class="bi bi-info-circle me-2" aria-hidden="true"></i><?= $message ?>
    </div>
    <?php else: ?>
    <div class="b2-empty">
        <div class="b2-empty-icon"><i class="bi bi-tools" aria-hidden="true"></i></div>
        <h5><?= $heading ?></h5>
        <p>This page is currently being developed.</p>
        <a href="<?= $e($appUrl) ?>/" class="btn btn-outline-primary">
            <i class="bi bi-house me-1" aria-hidden="true"></i>Return to Home
        </a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
