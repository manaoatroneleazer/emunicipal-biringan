<?php
/**
 * Home Page — Biringan City Landing Page
 * Layout: layouts/public
 * Variables: $documentTypes (array, may be empty)
 *
 * FIX LOG (Phase 4 — Reference Alignment):
 *  1. Hero max-width normalised to 1200px (was 1280px).
 *  2. Feature cards section: lp-ref-features class applied to section.
 *  3. About: restructured to dark lp-ref-about layout (2-col: text left, scenic placeholder right).
 *  4. How It Works: added h3/p via lp-ref-step-title / lp-ref-step-desc classes; wrapper normalised.
 *  5. FAQ: restructured to dark lp-ref-faq layout (heading left col / accordion right col).
 *  6. Announcement: replaced inline-style div with lp-ref-announcement classes; removed undefined --biringan-burgundy.
 *  7. Contact: restructured to dark lp-ref-contact layout (scenic placeholder left / support card + form right).
 */
$appUrl        = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$documentTypes = $documentTypes ?? [];

$fallbackTypes = [
    ['name' => 'Biringan City Birth Certificate',         'description' => '[BCRO] Fictional sample certificate confirming the registered birth of a community member in Biringan City.'],
    ['name' => 'Biringan City Residency Certificate',     'description' => '[CRO] Fictional sample certificate confirming that the holder is a registered resident of Biringan City.'],
    ['name' => 'Biringan City Community Clearance',       'description' => '[CRO] Fictional sample clearance confirming no adverse records within the Biringan City community registry.'],
    ['name' => 'Certificate of Community Membership',     'description' => '[CAO] Fictional sample certificate confirming active membership in the Biringan City community.'],
    ['name' => 'Certificate of Indigency',                'description' => '[CAO] Fictional sample certificate for community financial assistance program eligibility.'],
    ['name' => 'Local Business Registration Certificate', 'description' => '[BSO] Fictional sample certificate confirming business registration within Biringan City.'],
    ['name' => 'Business Clearance Certificate',          'description' => '[BSO] Fictional sample clearance confirming a registered business has no pending compliance issues.'],
    ['name' => 'Community Health Certificate',            'description' => '[CHO] Fictional sample health certificate confirming the general health status of the applicant.'],
];
$displayTypes = !empty($documentTypes) ? $documentTypes : $fallbackTypes;
$e = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>

<!-- ============================================================
     SECTION 1: HERO
     Reference: dark purple gradient, text left, admin card right
     ============================================================ -->
<section id="home" class="lp-ref-hero">
    <div class="container" style="max-width:1200px;">
        <div class="row align-items-center g-5">

            <!-- Left: headline + CTAs -->
            <div class="col-12 col-lg-6">
                <div class="lp-ref-hero-badge">
                    <i class="bi bi-stars" aria-hidden="true"></i>
                    Biringan City Online Services
                </div>
                <h1 class="lp-ref-hero-title mb-2">
                    Welcome to the<br>
                    <span class="lp-ref-hero-gradient">Biringan City Portal</span>
                </h1>
                <p class="lp-ref-hero-subtitle mb-1">
                    Online Document Request and Management System
                </p>
                <p class="lp-ref-hero-desc mb-4">
                    A fictional academic demonstration of a digital service portal inspired by
                    Philippine folklore. Request documents, track your requests, and receive
                    notifications all online.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= $e($appUrl) ?>/login" class="btn lp-ref-btn-primary btn-lg">
                        <i class="bi bi-file-earmark-arrow-up me-2" aria-hidden="true"></i>Request a Document
                    </a>
                    <a href="<?= $e($appUrl) ?>/#about" class="btn lp-ref-btn-secondary btn-lg">
                        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Learn More
                    </a>
                </div>
            </div>

            <!-- Right: admin card -->
            <div class="col-12 col-lg-6">
                <div class="lp-ref-admin-card">
                    <img src="<?= $e($appUrl) ?>/public/assets/images/biringan-logo-placeholder.svg"
                         alt="Biringan City Administration"
                         class="lp-ref-admin-logo">
                    <div class="lp-ref-admin-label">eMunicipal</div>
                    <div class="lp-ref-admin-title">Biringan City Administration</div>
                    <p class="lp-ref-admin-subtitle">Fictional Academic Demonstration</p>
                    <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid rgba(216,176,106,0.20);">
                        <div class="row g-2 text-center">
                            <?php foreach ([
                                ['bi-file-earmark-text', 'Documents'],
                                ['bi-clock-history',     'Real-Time'],
                                ['bi-bell',              'Alerts'],
                                ['bi-shield-check',      'Secure'],
                            ] as $stat): ?>
                            <div class="col-6 col-sm-3">
                                <i class="bi <?= $stat[0] ?> d-block mb-1"
                                   style="font-size:1.25rem;color:var(--biringan-gold-light);"
                                   aria-hidden="true"></i>
                                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;
                                            letter-spacing:0.08em;color:rgba(255,255,255,0.60);">
                                    <?= $stat[1] ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 2: TRACK YOUR REQUEST — Biringan Dark Redesign
     POST target: /track  (HomeController::trackLookup)
     GET  target: /#track (navbar anchor)
     Result rendered inline — user stays on landing page.
     ============================================================ -->
<section id="track" class="track-section">

    <div class="container track-container">

        <!-- ── SECTION HEADER ──────────────────────────────── -->
        <div class="track-header">
            <div class="track-badge">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                No Login Required
            </div>
            <h2 class="track-heading">
                Check Your <span class="track-heading-accent">Request Status</span>
            </h2>
            <p class="track-subtext">
                Enter your request reference number to view the current status of your request.
            </p>
        </div>

        <!-- ── TWO-COLUMN CARDS ────────────────────────────── -->
        <div class="row g-4 align-items-stretch">

            <!-- LEFT CARD: form ───────────────────────────── -->
            <div class="col-12 col-lg-6">
                <div class="track-card track-form-card scroll-reveal-left">

                    <!-- Card icon + title -->
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="track-icon-circle">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3 class="track-card-title">Track Your Request</h3>
                            <p class="track-card-desc mb-0">
                                Enter your reference number to check the current status of your request.
                            </p>
                        </div>
                    </div>

                    <!-- Track form (POST / — stays on landing page) -->
                    <form action="<?= $e($appUrl) ?>/" method="POST"
                          aria-label="Track your document request">
                        <input type="hidden" name="_csrf_token"
                               value="<?= $e(\Emunicipal\Helpers\SecurityHelper::getCsrfToken()) ?>">

                        <label for="trackRefInput" class="track-label">Reference Number</label>
                        <div class="track-input-group">
                            <i class="bi bi-search track-input-icon" aria-hidden="true"></i>
                            <input type="text"
                                   id="trackRefInput"
                                   name="ref"
                                   class="track-input <?php
                                       $__te = $trackError ?? null;
                                       echo ($__te && $__te !== 'rate_limit' && $__te !== 'csrf') ? 'track-input-invalid' : '';
                                   ?>"
                                   placeholder="e.g. EMU-2026-000001"
                                   value="<?= $e($trackRef ?? '') ?>"
                                   maxlength="20"
                                   autocomplete="off"
                                   autocapitalize="characters"
                                   spellcheck="false"
                                   aria-label="Reference number">
                        </div>

                        <button type="submit" class="track-btn">
                            <i class="bi bi-arrow-right-circle me-2" aria-hidden="true"></i>
                            Track Request
                        </button>
                    </form>

                    <!-- Info box -->
                    <div class="track-info-box">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span>
                            No login required. Registered residents can
                            <a href="<?= $e($appUrl) ?>/login" class="track-info-link">log in</a>
                            to view full request details.
                        </span>
                    </div>

                </div><!-- /.track-form-card -->
            </div><!-- /col left -->

            <!-- RIGHT CARD: result ────────────────────────── -->
            <div class="col-12 col-lg-6">
                <div class="track-card track-result-card scroll-reveal-right scroll-reveal-delay-1"
                     aria-live="polite" aria-label="Tracking result">

                    <!-- Card icon + title (shown when no result yet) -->
                    <?php if ($trackRequest === null && $trackError === null): ?>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="track-icon-circle">
                            <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3 class="track-card-title">Request Status</h3>
                            <p class="track-card-desc mb-0">
                                Your request details will appear here.
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php
                    /* ── PHP TRACKING STATE ──────────────────────────────────────
                     * Injected by HomeController::renderTrack() via home/index.
                     * Security (CSRF, rate-limit, SQL, XSS, public-safe fields)
                     * is all handled in the controller — unchanged.
                     */
                    $trackRef     = $trackRef     ?? null;
                    $trackRequest = $trackRequest ?? null;
                    $trackHistory = $trackHistory ?? [];
                    $trackError   = $trackError   ?? null;

                    $trackErrorMessages = [
                        'empty'      => 'Please enter a reference number.',
                        'not_found'  => 'Request not found. Please check your reference number and try again.',
                        'csrf'       => 'Invalid security token. Please refresh the page and try again.',
                        'rate_limit' => 'Too many attempts. Please wait a few minutes and try again.',
                        'db_error'   => 'Unable to process your request. Please try again later.',
                    ];
                    $trackStatusConfig = [
                        'pending'                  => ['label' => 'Pending',                         'class' => 'status-pending'],
                        'under_verification'       => ['label' => 'Under Verification',              'class' => 'status-under_verification'],
                        'additional_info_required' => ['label' => 'Additional Information Required', 'class' => 'status-additional_info_required'],
                        'approved'                 => ['label' => 'Approved',                        'class' => 'status-approved'],
                        'rejected'                 => ['label' => 'Rejected',                        'class' => 'status-rejected'],
                        'ready_for_claiming'       => ['label' => 'Ready for Claiming',              'class' => 'status-ready_for_claiming'],
                        'completed'                => ['label' => 'Completed',                       'class' => 'status-completed'],
                        'cancelled'                => ['label' => 'Cancelled',                       'class' => 'status-cancelled'],
                    ];
                    ?>

                    <?php if ($trackError !== null): ?>
                    <!-- ── ERROR STATE ──────────────────────────── -->
                    <div class="track-error-box" role="alert">
                        <i class="bi <?= $trackError === 'rate_limit' ? 'bi-clock' : 'bi-exclamation-triangle' ?>"
                           aria-hidden="true"></i>
                        <?= $e($trackErrorMessages[$trackError] ?? 'An unexpected error occurred. Please try again.') ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($trackRequest !== null): ?>
                    <!-- ── SUCCESS STATE ─────────────────────────── -->
                    <?php
                    $tsCfg = $trackStatusConfig[$trackRequest['status']]
                          ?? ['label' => ucfirst(str_replace('_', ' ', $trackRequest['status'])), 'class' => ''];
                    ?>
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <h3 class="track-card-title mb-0">Request Status</h3>
                        <span class="status-badge <?= $e($tsCfg['class']) ?>">
                            <?= $e($tsCfg['label']) ?>
                        </span>
                    </div>

                    <!-- Key fields — public-safe only -->
                    <div class="track-result-fields">
                        <div class="track-field">
                            <div class="track-field-label">Reference Number</div>
                            <div class="track-field-value track-field-mono">
                                <?= $e($trackRequest['reference_number']) ?>
                            </div>
                        </div>
                        <div class="track-field">
                            <div class="track-field-label">Document Type</div>
                            <div class="track-field-value">
                                <?= $e($trackRequest['document_type_name']) ?>
                            </div>
                        </div>
                        <div class="track-field">
                            <div class="track-field-label">Date Submitted</div>
                            <div class="track-field-value">
                                <?= $e(date('F j, Y', strtotime($trackRequest['created_at']))) ?>
                            </div>
                        </div>
                        <div class="track-field">
                            <div class="track-field-label">Last Updated</div>
                            <div class="track-field-value">
                                <?= $e(date('F j, Y \a\t g:i A', strtotime($trackRequest['updated_at']))) ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($trackHistory)): ?>
                    <!-- Status timeline -->
                    <div class="track-timeline-section">
                        <div class="track-field-label mb-2">
                            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
                            Status Timeline
                        </div>
                        <ol class="track-timeline list-unstyled mb-0"
                            aria-label="Request status history">
                            <?php foreach ($trackHistory as $ti => $tEntry):
                                $tIsLast = ($ti === count($trackHistory) - 1);
                                $tCfg    = $trackStatusConfig[$tEntry['new_status']]
                                        ?? ['label' => ucfirst(str_replace('_', ' ', $tEntry['new_status'])), 'class' => ''];
                                $tDate   = !empty($tEntry['created_at'])
                                         ? date('M j, Y \a\t g:i A', strtotime($tEntry['created_at']))
                                         : '—';
                            ?>
                            <li>
                                <div class="track-timeline-dot <?= $tIsLast ? 'track-timeline-dot-current' : '' ?>">
                                    <?= $ti + 1 ?>
                                </div>
                                <div class="track-timeline-content">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="status-badge <?= $e($tCfg['class']) ?>"
                                              style="font-size:0.68rem;">
                                            <?= $e($tCfg['label']) ?>
                                        </span>
                                        <?php if ($tIsLast): ?>
                                        <span class="track-current-label">Current</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="track-timeline-date"><?= $e($tDate) ?></div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                    <?php endif; ?>

                    <!-- Login CTA -->
                    <div class="track-info-box mt-3">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <span>
                            Registered residents can view full request details by
                            <a href="<?= $e($appUrl) ?>/login" class="track-info-link">logging in</a>.
                        </span>
                    </div>

                    <?php else: ?>
                    <!-- ── EMPTY / ERROR-ONLY STATE ──────────────── -->
                    <?php if ($trackError === null): ?>
                    <div class="track-empty-state">
                        <h3 class="track-card-title">Request Status</h3>
                        <p class="track-card-desc">
                            Enter your reference number on the left to view your request status.
                        </p>
                        <div class="track-divider" aria-hidden="true">
                            <span></span>&#10022;<span></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="track-empty-state track-empty-state-sm">
                        <p class="track-card-desc mb-0">Check the reference number and try again.</p>
                    </div>
                    <?php endif; ?>
                    <?php endif; /* $trackRequest */ ?>

                </div><!-- /.track-result-card -->
            </div><!-- /col right -->

        </div><!-- /.row -->
    </div><!-- /.track-container -->

    <!-- Decorative background shapes (CSS only, no images) -->
    <div class="track-deco-bl" aria-hidden="true"></div>
    <div class="track-deco-br" aria-hidden="true"></div>

    <!-- Scroll to #track after a server-side POST result (no-JS fallback path) -->
    <?php if ($trackRef !== null || $trackError !== null || $trackRequest !== null): ?>
    <script>document.addEventListener('DOMContentLoaded',function(){var s=document.getElementById('track');if(s){var nb=document.querySelector('.navbar');var off=nb?nb.getBoundingClientRect().height:0;window.scrollTo({top:Math.max(0,s.getBoundingClientRect().top+window.scrollY-off-4),behavior:'smooth'});}});</script>
    <?php endif; ?>

</section>
<script>
/* ================================================================
   TRACK REQUEST — AJAX fetch handler
   Submits the landing-page track form via fetch() and renders
   the result into the right card without a full page reload.

   Falls back gracefully: if JS is disabled or fetch fails, the
   form submits normally via the existing POST /track PRG path.

   Security preserved end-to-end:
     - CSRF token from the hidden input is forwarded with every request
     - ?ajax=1 tells the server to return JSON; all server-side
       validation, rate-limiting, SQL prepared statements, and
       public-safe field selection run unchanged
     - All server data is rendered via textContent — no innerHTML
       injection from untrusted data
================================================================ */
(function () {
    'use strict';

    var STATUS_CONFIG = {
        pending:                  { label: 'Pending',                         cls: 'status-pending' },
        under_verification:       { label: 'Under Verification',              cls: 'status-under_verification' },
        additional_info_required: { label: 'Additional Information Required', cls: 'status-additional_info_required' },
        approved:                 { label: 'Approved',                        cls: 'status-approved' },
        rejected:                 { label: 'Rejected',                        cls: 'status-rejected' },
        ready_for_claiming:       { label: 'Ready for Claiming',              cls: 'status-ready_for_claiming' },
        completed:                { label: 'Completed',                       cls: 'status-completed' },
        cancelled:                { label: 'Cancelled',                       cls: 'status-cancelled' },
    };

    var ERROR_MESSAGES = {
        empty:      'Please enter a reference number.',
        not_found:  'Request not found. Please check your reference number and try again.',
        csrf:       'Invalid security token. Please refresh the page and try again.',
        rate_limit: 'Too many attempts. Please wait a few minutes and try again.',
        db_error:   'Unable to process your request. Please try again later.',
    };

    /* Safe HTML-escape — never use raw server strings in innerHTML */
    function esc(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str || '')));
        return d.innerHTML;
    }

    function getStatusConfig(status) {
        return STATUS_CONFIG[status] || { label: String(status).replace(/_/g, ' '), cls: '' };
    }

    function formatDate(iso, includeTime) {
        if (!iso) return '\u2014';
        var d = new Date(String(iso).replace(' ', 'T'));
        if (isNaN(d.getTime())) return String(iso);
        var months = ['January','February','March','April','May','June',
                      'July','August','September','October','November','December'];
        var base = months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
        if (!includeTime) return base;
        var h = d.getHours(), m = d.getMinutes();
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return base + ' at ' + h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
    }

    function scrollToTrack() {
        var section = document.getElementById('track');
        if (!section) return;
        var nb  = document.querySelector('.navbar');
        var off = nb ? nb.getBoundingClientRect().height : 0;
        window.scrollTo({
            top: Math.max(0, section.getBoundingClientRect().top + window.scrollY - off - 8),
            behavior: 'smooth'
        });
    }

    function buildSuccessHtml(req, history, appUrl) {
        var sl  = getStatusConfig(req.status);
        var h = '<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">'
              + '<h3 class="track-card-title mb-0">Request Status</h3>'
              + '<span class="status-badge ' + esc(sl.cls) + '">' + esc(sl.label) + '</span></div>'
              + '<div class="track-result-fields">'
              + '<div class="track-field"><div class="track-field-label">Reference Number</div>'
              + '<div class="track-field-value track-field-mono">' + esc(req.reference_number) + '</div></div>'
              + '<div class="track-field"><div class="track-field-label">Document Type</div>'
              + '<div class="track-field-value">' + esc(req.document_type_name) + '</div></div>'
              + '<div class="track-field"><div class="track-field-label">Date Submitted</div>'
              + '<div class="track-field-value">' + esc(formatDate(req.created_at, false)) + '</div></div>'
              + '<div class="track-field"><div class="track-field-label">Last Updated</div>'
              + '<div class="track-field-value">' + esc(formatDate(req.updated_at, true)) + '</div></div>'
              + '</div>';

        if (history && history.length) {
            h += '<div class="track-timeline-section">'
               + '<div class="track-field-label mb-2">'
               + '<i class="bi bi-clock-history me-1" aria-hidden="true"></i>Status Timeline</div>'
               + '<ol class="track-timeline list-unstyled mb-0" aria-label="Request status history">';
            for (var i = 0; i < history.length; i++) {
                var entry = history[i], isLast = (i === history.length - 1);
                var ec = getStatusConfig(entry.new_status);
                h += '<li><div class="track-timeline-dot ' + (isLast ? 'track-timeline-dot-current' : '') + '">'
                   + (i + 1) + '</div>'
                   + '<div class="track-timeline-content">'
                   + '<div class="d-flex flex-wrap align-items-center gap-2 mb-1">'
                   + '<span class="status-badge ' + esc(ec.cls) + '" style="font-size:0.68rem;">' + esc(ec.label) + '</span>'
                   + (isLast ? '<span class="track-current-label">Current</span>' : '')
                   + '</div><div class="track-timeline-date">' + esc(formatDate(entry.created_at, true)) + '</div>'
                   + '</div></li>';
            }
            h += '</ol></div>';
        }

        h += '<div class="track-info-box mt-3"><i class="bi bi-person-circle" aria-hidden="true"></i>'
           + '<span>Registered residents can view full request details by '
           + '<a href="' + esc(appUrl) + '/login" class="track-info-link">logging in</a>.</span></div>';
        return h;
    }

    function buildErrorHtml(errorKey) {
        var msg  = ERROR_MESSAGES[errorKey] || 'An unexpected error occurred. Please try again.';
        var icon = (errorKey === 'rate_limit') ? 'bi-clock' : 'bi-exclamation-triangle';
        return '<div class="track-error-box" role="alert">'
             + '<i class="bi ' + icon + '" aria-hidden="true"></i>'
             + esc(msg) + '</div>'
             + '<div class="track-empty-state track-empty-state-sm">'
             + '<p class="track-card-desc mb-0">Check the reference number and try again.</p></div>';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form       = document.querySelector('#track form[aria-label="Track your document request"]');
        var resultCard = document.querySelector('.track-result-card');
        var submitBtn  = form ? form.querySelector('[type="submit"]') : null;
        var refInput   = form ? form.querySelector('#trackRefInput') : null;

        if (!form || !resultCard || !submitBtn || !refInput) return;

        /* Derive app URL from the form action (e.g. "http://…/eMunicipal/") */
        var appUrl  = (form.getAttribute('action') || '').replace(/\/$/, '');
        var ajaxUrl = appUrl + '/track?ajax=1';

        var busy = false;
        var origBtn = submitBtn.innerHTML;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (busy) return;

            /* Client-side convenience guard only — server re-validates */
            var ref = refInput.value.trim();
            if (ref === '') {
                resultCard.innerHTML = buildErrorHtml('empty');
                resultCard.setAttribute('aria-label', 'Tracking result: error');
                refInput.focus();
                scrollToTrack();
                return;
            }

            busy = true;
            submitBtn.disabled  = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Searching\u2026';
            refInput.classList.remove('track-input-invalid');

            fetch(ajaxUrl, {
                method:      'POST',
                body:        new FormData(form),
                credentials: 'same-origin',
                headers:     { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (resp) {
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                return resp.json();
            })
            .then(function (data) {
                if (data.error) {
                    resultCard.innerHTML = buildErrorHtml(data.error);
                    resultCard.setAttribute('aria-label', 'Tracking result: error');
                    if (data.error !== 'rate_limit' && data.error !== 'csrf' && data.error !== 'db_error') {
                        refInput.classList.add('track-input-invalid');
                    }
                } else if (data.request) {
                    resultCard.innerHTML = buildSuccessHtml(data.request, data.history || [], appUrl);
                    resultCard.setAttribute('aria-label', 'Tracking result: ' + esc(data.request.reference_number));
                    if (data.ref) refInput.value = data.ref;
                } else {
                    resultCard.innerHTML = buildErrorHtml('db_error');
                    resultCard.setAttribute('aria-label', 'Tracking result: error');
                }
            })
            .catch(function () {
                resultCard.innerHTML = '<div class="track-error-box" role="alert">'
                    + '<i class="bi bi-wifi-off" aria-hidden="true"></i>'
                    + 'Network error. Please check your connection and try again.</div>';
                resultCard.setAttribute('aria-label', 'Tracking result: network error');
            })
            .finally(function () {
                busy = false;
                submitBtn.disabled  = false;
                submitBtn.innerHTML = origBtn;
                scrollToTrack();
                resultCard.setAttribute('tabindex', '-1');
                resultCard.focus();
            });
        });
    });
}());
</script>

<!-- ============================================================
     SECTION 2: FEATURE CARDS
     Reference: light background, 5 cards equal width
     FIX: added lp-ref-features class; ic-* modifier classes now
          resolve correctly against the updated CSS rules.
     ============================================================ -->
<section class="lp-ref-features">
    <div class="container" style="max-width:1200px;">
        <div class="row g-3 justify-content-center">
            <?php foreach ([
                ['bi-file-earmark-text', 'ic-purple', 'Online Requests',      'Submit document requests digitally from anywhere.'],
                ['bi-search',            'ic-rose',   'Real-Time Tracking',   'Monitor your request status at every step.'],
                ['bi-envelope-check',    'ic-gold',   'Email Notifications',  'Receive updates when your status changes.'],
                ['bi-shield-check',      'ic-teal',   'Secure & Private',     'Your data is protected with security best practices.'],
                ['bi-folder2-open',      'ic-purple', 'Digital Records',      'All requests are securely stored and easy to access.'],
            ] as $f): ?>
            <div class="col-12 col-sm-6 col-lg">
                <div class="lp-ref-feature-card">
                    <div class="lp-ref-feature-icon <?= $f[1] ?>">
                        <i class="bi <?= $f[0] ?>" aria-hidden="true"></i>
                    </div>
                    <h3 class="lp-ref-feature-title"><?= $e($f[2]) ?></h3>
                    <p class="lp-ref-feature-desc mb-0"><?= $e($f[3]) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 3: ABOUT BIRINGAN CITY
     Reference: dark purple bg, text+bullets left, scenic image right
     FIX: was white bg with light info cards — restructured to
          lp-ref-about dark layout matching the reference.
     ============================================================ -->
<section id="about" class="lp-ref-about">
    <div class="container" style="max-width:1200px;">
        <div class="row align-items-center g-5">

            <!-- Left: text content -->
            <div class="col-12 col-lg-6">
                <div class="lp-ref-section-accent"></div>
                <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;
                           letter-spacing:0.12em;color:var(--biringan-gold-light);
                           margin-bottom:0.5rem;font-family:'Montserrat','Inter',sans-serif;">
                    About the System
                </p>
                <h2 class="lp-ref-section-title mb-3">What is eMunicipal?</h2>
                <p class="lp-ref-about-text mb-4">
                    eMunicipal is a fictional academic demonstration of an online document request and
                    management system for the Biringan City Administration. Community members can submit
                    document requests, track status in real time, and receive email notifications —
                    without visiting the civic center.
                </p>

                <?php foreach ([
                    ['bi-file-earmark-arrow-up', 'Online Requests',         'Request community documents from anywhere, any time.'],
                    ['bi-search',                'Real-Time Tracking',      'Track your request with a reference number.'],
                    ['bi-bell-fill',             'Status Notifications',    'Receive an email whenever your status changes.'],
                    ['bi-building',              'Easy Document Claiming',  'Claim your document at the Biringan City Civic Center.'],
                ] as $item): ?>
                <div class="lp-ref-about-item">
                    <div class="lp-ref-about-icon">
                        <i class="bi <?= $item[0] ?>" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="lp-ref-about-item-title"><?= $e($item[1]) ?></div>
                        <p class="lp-ref-about-item-desc"><?= $e($item[2]) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="mt-4">
                    <a href="<?= $e($appUrl) ?>/register" class="lp-ref-link-gold">
                        Get Started — It's Free
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <!-- Right: scenic placeholder -->
            <div class="col-12 col-lg-6">
                <div class="lp-ref-about-image">
                    <div class="lp-ref-scenic-placeholder">
                        <img src="<?= $e($appUrl) ?>/public/assets/images/biringan-logo-placeholder.svg"
                             alt="" aria-hidden="true"
                             style="width:80px;height:80px;opacity:0.35;margin-bottom:1rem;">
                        <p>Biringan City<br>Province of Elaria</p>
                        <p style="font-size:0.75rem;opacity:0.6;margin-top:0.5rem;">
                            Scenic cityscape image placeholder
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 4: HOW IT WORKS
     Reference: light background, 6 numbered step cards in 3x2 grid
     FIX: lp-ref-step-item max-width was 180px — now 100% filling
          its Bootstrap col-lg-4 column; h3/p use step-title/desc classes.
     ============================================================ -->
<section id="how-it-works" style="background:#F8F6F8;padding:5rem 0;">
    <div class="container" style="max-width:1200px;">
        <div class="text-center mb-5">
            <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;
                       letter-spacing:0.12em;color:var(--biringan-rose);margin-bottom:0.5rem;
                       font-family:'Montserrat','Inter',sans-serif;">The Process</p>
            <h2 style="font-family:'Montserrat','Inter',sans-serif;
                        font-size:clamp(1.7rem,3vw,2.25rem);font-weight:800;
                        color:var(--biringan-text);margin-bottom:0.5rem;line-height:1.25;">
                How It Works
            </h2>
            <p style="font-size:0.9375rem;color:var(--biringan-muted);max-width:520px;margin:0 auto;">
                Six simple steps to request and receive your community documents.
            </p>
        </div>
        <div class="row g-4">
            <?php foreach ([
                ['bi-file-earmark-text', '1', 'Choose a Document',    'Browse available document types and select the one you need.'],
                ['bi-list-check',        '2', 'Review Requirements',  'Check what supporting documents you need to provide.'],
                ['bi-pencil',            '3', 'Provide Information',  'Fill in the request form with your details and purpose.'],
                ['bi-cloud-upload',      '4', 'Upload Files',         'Attach scanned copies of required supporting documents.'],
                ['bi-send',              '5', 'Submit Request',       'Submit and receive a reference number by email.'],
                ['bi-search',            '6', 'Track Progress',       'Monitor your request status and receive notifications.'],
            ] as $s): ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="lp-ref-step-item">
                    <div class="lp-ref-step-number"><?= $s[1] ?></div>
                    <div class="lp-ref-step-icon">
                        <i class="bi <?= $s[0] ?>" aria-hidden="true"></i>
                    </div>
                    <h3 class="lp-ref-step-title"><?= $e($s[2]) ?></h3>
                    <p class="lp-ref-step-desc"><?= $e($s[3]) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 5: DOCUMENT SERVICES
     Reference: light beige background, card grid, CTA
     (No structural change needed — already correct)
     ============================================================ -->
<section id="services" style="background:rgba(241,223,221,0.30);padding:5rem 0;">
    <div class="container" style="max-width:1200px;">
        <div class="text-center mb-5">
            <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;
                       letter-spacing:0.12em;color:var(--biringan-rose);margin-bottom:0.5rem;
                       font-family:'Montserrat','Inter',sans-serif;">What We Offer</p>
            <h2 style="font-family:'Montserrat','Inter',sans-serif;
                        font-size:clamp(1.7rem,3vw,2.25rem);font-weight:800;
                        color:var(--biringan-text);margin-bottom:0.5rem;line-height:1.25;">
                Available Document Services
            </h2>
            <p style="font-size:0.9375rem;color:var(--biringan-muted);max-width:560px;margin:0 auto;">
                Select a document type to begin your online request.
            </p>
        </div>
        <div class="row g-3">
            <?php foreach (array_slice($displayTypes, 0, 8) as $dt):
                $desc = $dt['description'];
                $dept = '';
                if (preg_match('/^\[([^\]]+)\]\s*/', $desc, $m)) {
                    $dept = $m[1];
                    $desc = trim(substr($desc, strlen($m[0])));
                }
                $shortDesc = mb_substr($desc, 0, 85);
                if (mb_strlen($desc) > 85) $shortDesc .= '…';
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="lp-service-card">
                    <div style="display:flex;align-items:flex-start;gap:0.875rem;margin-bottom:0.625rem;">
                        <div style="width:44px;height:44px;border-radius:12px;flex-shrink:0;
                                    background:rgba(74,31,70,0.10);display:flex;
                                    align-items:center;justify-content:center;
                                    font-size:1.125rem;color:var(--biringan-primary);">
                            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <h3 style="font-family:'Montserrat','Inter',sans-serif;font-size:1rem;
                                        font-weight:700;color:var(--biringan-text);
                                        margin:0 0 0.25rem;line-height:1.3;overflow-wrap:break-word;">
                                <?= $e($dt['name']) ?>
                            </h3>
                            <?php if ($dept): ?>
                            <div style="font-size:0.7rem;color:var(--biringan-rose);font-weight:600;
                                         text-transform:uppercase;letter-spacing:0.04em;">
                                <?= $e($dept) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p style="font-size:0.8125rem;color:var(--biringan-muted);
                               line-height:1.55;margin:0;overflow-wrap:break-word;">
                        <?= $e($shortDesc) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= $e($appUrl) ?>/login" class="btn btn-primary btn-lg px-4"
               style="font-weight:600;">
                <i class="bi bi-plus-circle me-2" aria-hidden="true"></i>Request a Document
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 6: FAQ
     Reference: dark purple bg, heading+intro LEFT col,
                custom accordion RIGHT col (lp-ref-faq-item)
     FIX: was white Bootstrap accordion centred layout —
          restructured to two-column dark lp-ref-faq layout.
     ============================================================ -->
<section id="faqs" class="lp-ref-faq">
    <div class="container" style="max-width:1200px;">
        <div class="row g-5">

            <!-- Left: heading + intro -->
            <div class="col-12 col-lg-4">
                <div class="lp-ref-section-accent"></div>
                <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;
                           letter-spacing:0.12em;color:var(--biringan-gold-light);
                           margin-bottom:0.5rem;font-family:'Montserrat','Inter',sans-serif;">
                    Have Questions?
                </p>
                <h2 class="lp-ref-section-title mb-3">Frequently Asked Questions</h2>
                <p class="lp-ref-faq-intro mb-4">
                    Find answers to the most common questions about creating an account,
                    submitting requests, tracking status, and claiming documents.
                </p>
                <a href="<?= $e($appUrl) ?>/#contact" class="lp-ref-link-gold">
                    Still have questions?
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <!-- Right: accordion -->
            <div class="col-12 col-lg-8">
                <?php
                $faqs = [
                    ['What is eMunicipal?',
                     'eMunicipal is a fictional academic demonstration of an online document request and management system for the Biringan City Administration, Province of Elaria. Community members can submit requests, track status in real time, and receive email notifications — without visiting the civic center.'],
                    ['How do I create an account?',
                     'Click the "Register" button at the top of the page. Fill in your first name, last name, email address, and a password. Once submitted, you can immediately log in.'],
                    ['How do I request a document?',
                     'After logging in, go to "New Request" in the sidebar. Select the document type, fill in the purpose and number of copies, upload any required supporting files, and click Submit. You will receive a reference number upon successful submission.'],
                    ['How can I track my request?',
                     'Enter your reference number (e.g., EMU-2026-000001) on the Track Request page, or view all your requests in "My Requests" in the sidebar after logging in.'],
                    ['How will I know if my status changes?',
                     'You will receive an email notification at your registered address whenever your request status is updated — from submission through to approval, rejection, or completion.'],
                    ['Where can I find my reference number?',
                     'Your reference number is displayed on screen immediately after submission and is included in the confirmation email sent to your registered address.'],
                    ['Can I cancel my request?',
                     'Yes, as long as your request is still in Pending, Under Verification, or Additional Information Required status. Open the request from "My Requests" and click "Cancel Request".'],
                    ['How do I claim my document?',
                     'Visit the Biringan City Civic Center during office hours (Mon–Fri, 8:00 AM–5:00 PM) with a valid ID. Claiming instructions are included in your approval notification email.'],
                    ['What happens if my request is rejected?',
                     'The reason is shown on your request detail page and sent by email. You may submit a new request with corrected information or complete supporting documents.'],
                ];
                foreach ($faqs as $faq): ?>
                <div class="lp-ref-faq-item">
                    <div class="lp-ref-faq-question" role="button" tabindex="0"
                         aria-expanded="false">
                        <span><?= $e($faq[0]) ?></span>
                        <i class="bi bi-chevron-down lp-ref-faq-chevron" aria-hidden="true"></i>
                    </div>
                    <div class="lp-ref-faq-answer">
                        <?= $e($faq[1]) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================
     ANNOUNCEMENT BAR
     Reference: purple gradient, megaphone icon left, text centre,
                "Contact Us" link right
     FIX: removed undefined var(--biringan-burgundy); using
          lp-ref-announcement CSS classes.
     ============================================================ -->
<div class="lp-ref-announcement" role="complementary" aria-label="Announcement">
    <div class="container" style="max-width:1200px;">
        <div class="lp-ref-announcement-content">
            <div class="lp-ref-announcement-icon">
                <i class="bi bi-megaphone-fill" aria-hidden="true"></i>
            </div>
            <p class="lp-ref-announcement-text mb-0">
                <strong>Announcement:</strong> Some documents may require personal claiming
                at the Biringan City Civic Center. Please check your request notifications
                for claiming instructions.
            </p>
            <a href="<?= $e($appUrl) ?>/#contact" class="lp-ref-announcement-link">
                Contact Us <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>

<!-- ============================================================
     SECTION 7: CONTACT
     Reference: dark purple bg, scenic placeholder left col,
                support card + contact info right col
     FIX: was light pink bg with centred heading and form —
          restructured to lp-ref-contact dark two-column layout.
     ============================================================ -->
<section id="contact" class="lp-ref-contact">
    <div class="container" style="max-width:1200px;">
        <div class="row g-5 align-items-start">

            <!-- Left: scenic placeholder + heading -->
            <div class="col-12 col-lg-5">
                <div class="lp-ref-section-accent"></div>
                <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;
                           letter-spacing:0.12em;color:var(--biringan-gold-light);
                           margin-bottom:0.5rem;font-family:'Montserrat','Inter',sans-serif;">
                    Get in Touch
                </p>
                <h2 class="lp-ref-section-title mb-3">Contact Us</h2>
                <p class="lp-ref-contact-subtitle mb-4">
                    We are here to help during office hours. Send us a message or visit
                    the Biringan City Civic Center.
                </p>

                <!-- Contact info items -->
                <div class="d-flex flex-column gap-3 mb-4">
                    <?php foreach ([
                        ['bi-geo-alt-fill',  'Address',      'Biringan City, Province of Elaria',   null],
                        ['bi-telephone-fill','Phone',        '(000) XXX-XXXX',                      null],
                        ['bi-envelope-fill', 'Email',        'contact@biringan-demo.local',          'mailto:contact@biringan-demo.local'],
                        ['bi-clock-fill',    'Office Hours', 'Mon–Fri, 8:00 AM – 5:00 PM',          null],
                    ] as $c): ?>
                    <div class="lp-ref-contact-item">
                        <i class="bi <?= $c[0] ?>" aria-hidden="true"></i>
                        <div>
                            <div class="lp-ref-contact-label"><?= $e($c[1]) ?></div>
                            <?php if ($c[3]): ?>
                            <a href="<?= $e($c[3]) ?>" class="lp-ref-contact-value"
                               style="color:var(--biringan-gold-light);text-decoration:none;">
                                <?= $e($c[2]) ?>
                            </a>
                            <?php else: ?>
                            <div class="lp-ref-contact-value"><?= $e($c[2]) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Scenic placeholder -->
                <div class="lp-ref-contact-image" style="min-height:180px;">
                    <div class="lp-ref-scenic-placeholder" style="min-height:180px;">
                        <i class="bi bi-building" aria-hidden="true"
                           style="font-size:2.5rem;color:rgba(255,255,255,0.25);margin-bottom:0.5rem;"></i>
                        <p style="font-size:0.8rem;">Biringan City Civic Center</p>
                    </div>
                </div>
            </div>

            <!-- Right: support card + contact form -->
            <div class="col-12 col-lg-7">

                <!-- Support card -->
                <div class="lp-ref-support-card mb-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="lp-ref-support-avatar">
                            <i class="bi bi-headset" aria-hidden="true"></i>
                        </div>
                        <div>
                            <div class="lp-ref-support-name">Municipal Support</div>
                            <div class="lp-ref-support-status">
                                <span class="lp-ref-status-dot"></span>
                                Available during office hours
                            </div>
                        </div>
                    </div>
                    <p style="font-size:0.875rem;color:rgba(255,255,255,0.70);margin:0;line-height:1.6;">
                        Our staff is ready to assist with document requests, status inquiries,
                        and general information about Biringan City services.
                    </p>
                </div>

                <!-- Contact form -->
                <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(216,176,106,0.20);
                             border-radius:16px;padding:2rem;">
                    <h3 style="font-size:1.125rem;font-weight:700;color:#fff;
                                margin-bottom:1.5rem;font-family:'Montserrat','Inter',sans-serif;">
                        Send us a message
                    </h3>
                    <div id="contactSuccessMsg"
                         style="display:none;background:rgba(26,122,74,0.20);border:1px solid rgba(74,222,128,0.35);
                                color:#4ade80;padding:0.875rem 1rem;border-radius:8px;margin-bottom:1rem;"
                         role="alert">
                        <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>
                        Your message has been sent. Thank you for contacting us.
                    </div>
                    <div id="contactErrorMsg"
                         style="display:none;background:rgba(185,28,28,0.20);border:1px solid rgba(252,165,165,0.35);
                                color:#fca5a5;padding:0.875rem 1rem;border-radius:8px;margin-bottom:1rem;"
                         role="alert">
                        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
                        We couldn't send your message right now. Please try again later.
                    </div>
                    <form id="contactForm" novalidate>
                        <input type="hidden" name="_csrf_token"
                               value="<?= $e(\Emunicipal\Helpers\SecurityHelper::getCsrfToken()) ?>">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label for="contact_name" class="form-label"
                                       style="font-weight:600;color:rgba(255,255,255,0.85);font-size:0.8125rem;">
                                    Full Name <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="contact_name" name="contact_name"
                                       class="form-control" placeholder="Juan dela Cruz"
                                       autocomplete="name" maxlength="100" required
                                       style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.20);
                                              color:#fff;border-radius:8px;font-size:0.9375rem;">
                                <div class="invalid-feedback">Please enter your name.</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="contact_email" class="form-label"
                                       style="font-weight:600;color:rgba(255,255,255,0.85);font-size:0.8125rem;">
                                    Email Address <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="email" id="contact_email" name="contact_email"
                                       class="form-control" placeholder="you@example.com"
                                       autocomplete="email" maxlength="255" required
                                       style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.20);
                                              color:#fff;border-radius:8px;font-size:0.9375rem;">
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="contact_subject" class="form-label"
                                   style="font-weight:600;color:rgba(255,255,255,0.85);font-size:0.8125rem;">
                                Subject <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="contact_subject" name="contact_subject"
                                   class="form-control"
                                   placeholder="e.g., Question about document requirements"
                                   maxlength="200" required
                                   style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.20);
                                          color:#fff;border-radius:8px;font-size:0.9375rem;">
                            <div class="invalid-feedback">Please enter a subject.</div>
                        </div>
                        <div class="mb-3">
                            <label for="contact_message" class="form-label"
                                   style="font-weight:600;color:rgba(255,255,255,0.85);font-size:0.8125rem;">
                                Message <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <textarea id="contact_message" name="contact_message"
                                      class="form-control" rows="5"
                                      placeholder="Type your message here…"
                                      maxlength="3000" required
                                      style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.20);
                                             color:#fff;border-radius:8px;font-size:0.9375rem;resize:vertical;">
                            </textarea>
                            <div class="invalid-feedback">Please enter your message.</div>
                        </div>
                        <button type="submit" id="contactSubmitBtn"
                                class="btn w-100"
                                style="background:var(--biringan-rose);border:none;color:#fff;
                                       padding:0.75rem;font-weight:600;border-radius:8px;font-size:0.9375rem;">
                            <i class="bi bi-send me-2" aria-hidden="true"></i>Send Message
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     ACADEMIC DISCLAIMER
     Reference: subtle warm banner at the bottom
     ============================================================ -->
<section style="background:rgba(254,243,199,0.40);padding:3rem 0;
                border-top:1px solid rgba(146,64,14,0.15);">
    <div class="container" style="max-width:800px;">
        <div class="text-center">
            <i class="bi bi-info-circle d-block mb-2"
               style="font-size:1.5rem;color:var(--biringan-rose);" aria-hidden="true"></i>
            <h3 style="font-family:'Montserrat','Inter',sans-serif;font-size:1rem;
                        font-weight:700;color:var(--biringan-text);margin-bottom:0.75rem;">
                Academic Demonstration
            </h3>
            <p style="font-size:0.875rem;color:var(--biringan-muted);line-height:1.7;margin:0;">
                <strong>Biringan City</strong> and all organizations, services, documents, records,
                and characters presented in this system are <strong>fictional</strong> and created
                for academic demonstration purposes. This project is inspired by Philippine folklore
                and is <strong>not an official government website or service</strong>.
                All data is for demonstration only.
            </p>
        </div>
    </div>
</section>
