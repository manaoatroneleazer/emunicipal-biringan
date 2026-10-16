<?php
/**
 * Public Track Request page — views/home/track.php
 * Layout: layouts/public (default)
 *
 * Variables:
 *   $prefillRef  string       Reference number to pre-fill (may be empty)
 *   $request     array|null   Public-safe request data (null = no result yet)
 *   $history     array        Public-safe status history entries
 *   $error       string|null  Error key: 'empty'|'not_found'|'csrf'|'rate_limit'|'db_error'|null
 *
 * Public-safe $request fields: reference_number, document_type_name, status, created_at, updated_at
 * Public-safe $history fields per row: new_status, previous_status, created_at
 *
 * NEVER expose: resident PII, staff identity, remarks, internal IDs, document info.
 */
$appUrl     = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e          = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$prefillRef = $prefillRef ?? '';
$request    = $request    ?? null;
$history    = $history    ?? [];
$error      = $error      ?? null;

// Error message map — generic, no internal detail
$errorMessages = [
    'empty'      => 'Please enter a reference number.',
    'not_found'  => 'Request not found. Please check your reference number and try again.',
    'csrf'       => 'Invalid security token. Please refresh the page and try again.',
    'rate_limit' => 'Too many attempts. Please wait a few minutes and try again.',
    'db_error'   => 'Unable to process your request. Please try again later.',
];
$errorMessage = $error ? ($errorMessages[$error] ?? 'An unexpected error occurred. Please try again.') : null;

// Status display config — reused from existing project pattern
$statusConfig = [
    'pending'                  => ['label' => 'Pending',                         'class' => 'status-pending'],
    'under_verification'       => ['label' => 'Under Verification',              'class' => 'status-under_verification'],
    'additional_info_required' => ['label' => 'Additional Information Required', 'class' => 'status-additional_info_required'],
    'approved'                 => ['label' => 'Approved',                        'class' => 'status-approved'],
    'rejected'                 => ['label' => 'Rejected',                        'class' => 'status-rejected'],
    'ready_for_claiming'       => ['label' => 'Ready for Claiming',              'class' => 'status-ready_for_claiming'],
    'completed'                => ['label' => 'Completed',                       'class' => 'status-completed'],
    'cancelled'                => ['label' => 'Cancelled',                       'class' => 'status-cancelled'],
];

$statusLabel = function(string $s) use ($statusConfig, $e): string {
    $cfg = $statusConfig[$s] ?? ['label' => ucfirst(str_replace('_', ' ', $s)), 'class' => ''];
    return '<span class="status-badge ' . $e($cfg['class']) . '">'
         . $e($cfg['label'])
         . '</span>';
};
?>

<!-- ============================================================
     PUBLIC TRACK REQUEST PAGE
     ============================================================ -->

<!-- Page hero — matches existing light-page pattern -->
<section class="lp-ref-hero" style="padding:3rem 0 2.5rem;">
    <div class="container" style="max-width:860px;">
        <div class="text-center">
            <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;
                       letter-spacing:0.12em;color:var(--biringan-gold-light);
                       margin-bottom:0.5rem;
                       font-family:'Montserrat','Inter',sans-serif;">
                No Login Required
            </p>
            <h1 style="font-family:'Montserrat','Inter',sans-serif;
                        font-size:clamp(1.75rem,3vw,2.5rem);font-weight:800;
                        color:#fff;margin-bottom:0.625rem;line-height:1.2;">
                Track Your Request
            </h1>
            <p style="font-size:1rem;color:rgba(255,255,255,0.75);
                       max-width:480px;margin:0 auto;">
                Enter your reference number to view the current status of your
                document request &mdash; no account needed.
            </p>
        </div>
    </div>
</section>

<!-- Main content -->
<section style="background:#F8F6F8;padding:3rem 0 4rem;">
    <div class="container" style="max-width:860px;">

        <!-- ── TRACK FORM ─────────────────────────────────── -->
        <?php if ($errorMessage): ?>
        <div class="alert <?= $error === 'rate_limit' ? 'alert-warning' : 'alert-danger' ?> mb-4"
             role="alert" style="border-radius:10px;font-size:0.9375rem;">
            <i class="bi <?= $error === 'rate_limit' ? 'bi-clock' : 'bi-exclamation-triangle' ?> me-2"
               aria-hidden="true"></i>
            <?= $e($errorMessage) ?>
        </div>
        <?php endif; ?>

        <div class="b2-card mb-4">
            <div class="b2-card-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="b2-card-header-icon">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </div>
                    <h2 style="font-size:1rem;font-weight:600;margin:0;">
                        Check Request Status
                    </h2>
                </div>
            </div>
            <div class="card-body">
                <form action="<?= $e($appUrl) ?>/track" method="POST"
                      aria-label="Track your document request">
                    <?= \Emunicipal\Helpers\SecurityHelper::csrfField() ?>
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-8">
                            <label for="trackRefPageInput" class="form-label"
                                   style="font-weight:600;font-size:0.8125rem;
                                          color:var(--biringan-text);">
                                Reference Number
                                <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="text"
                                   id="trackRefPageInput"
                                   name="ref"
                                   class="form-control form-control-lg <?= ($error && $error !== 'rate_limit') ? 'is-invalid' : '' ?>"
                                   placeholder="e.g. EMU-2026-000001"
                                   value="<?= $e($prefillRef) ?>"
                                   maxlength="20"
                                   autocomplete="off"
                                   autocapitalize="characters"
                                   spellcheck="false"
                                   aria-label="Reference number"
                                   style="font-family:'Montserrat','Inter',sans-serif;
                                          font-weight:600;letter-spacing:0.03em;">
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit"
                                    class="btn btn-primary btn-lg w-100"
                                    style="font-weight:600;">
                                <i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i>
                                Track Request
                            </button>
                        </div>
                    </div>
                </form>
                <p class="mb-0 mt-3"
                   style="font-size:0.8rem;color:var(--biringan-muted);">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    Your reference number was included in your submission confirmation email
                    (format: <code style="font-size:0.8rem;">EMU-YYYY-000000</code>).
                    Registered residents can view
                    <a href="<?= $e($appUrl) ?>/login"
                       style="color:var(--biringan-primary);font-weight:600;">full request details</a>
                    by logging in.
                </p>
            </div>
        </div>

        <!-- ── RESULT ─────────────────────────────────────── -->
        <?php if ($request !== null): ?>
        <div class="b2-card" aria-live="polite" aria-label="Tracking result">

            <!-- Result header -->
            <div class="b2-card-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="b2-card-header-icon">
                        <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                    </div>
                    <h2 style="font-size:1rem;font-weight:600;margin:0;">
                        Request Details
                    </h2>
                </div>
                <?= $statusLabel($request['status']) ?>
            </div>

            <div class="card-body">
                <!-- Key fields -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6">
                        <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;
                                     letter-spacing:0.06em;color:var(--biringan-muted);
                                     margin-bottom:0.25rem;">
                            Reference Number
                        </div>
                        <div style="font-family:'Montserrat','Inter',sans-serif;
                                     font-size:1rem;font-weight:700;
                                     color:var(--biringan-text);letter-spacing:0.03em;">
                            <?= $e($request['reference_number']) ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;
                                     letter-spacing:0.06em;color:var(--biringan-muted);
                                     margin-bottom:0.25rem;">
                            Document Type
                        </div>
                        <div style="font-size:0.9375rem;font-weight:600;
                                     color:var(--biringan-text);">
                            <?= $e($request['document_type_name']) ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;
                                     letter-spacing:0.06em;color:var(--biringan-muted);
                                     margin-bottom:0.25rem;">
                            Date Submitted
                        </div>
                        <div style="font-size:0.9375rem;color:var(--biringan-text);">
                            <?= $e(date('F j, Y', strtotime($request['created_at']))) ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;
                                     letter-spacing:0.06em;color:var(--biringan-muted);
                                     margin-bottom:0.25rem;">
                            Last Updated
                        </div>
                        <div style="font-size:0.9375rem;color:var(--biringan-text);">
                            <?= $e(date('F j, Y \a\t g:i A', strtotime($request['updated_at']))) ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($history)): ?>
                <!-- Status Timeline -->
                <div style="border-top:1px solid var(--biringan-border);padding-top:1.25rem;">
                    <h3 style="font-family:'Montserrat','Inter',sans-serif;
                                font-size:0.875rem;font-weight:700;
                                color:var(--biringan-text);
                                text-transform:uppercase;letter-spacing:0.06em;
                                margin-bottom:1rem;">
                        <i class="bi bi-clock-history me-2" aria-hidden="true"></i>
                        Status Timeline
                    </h3>
                    <ol class="list-unstyled mb-0" aria-label="Request status history">
                        <?php foreach ($history as $i => $entry):
                            $isLast   = ($i === count($history) - 1);
                            $cfg      = $statusConfig[$entry['new_status']] ?? ['label' => ucfirst(str_replace('_', ' ', $entry['new_status'])), 'class' => ''];
                            $dateStr  = !empty($entry['created_at'])
                                ? date('M j, Y \a\t g:i A', strtotime($entry['created_at']))
                                : '—';
                        ?>
                        <li style="display:flex;align-items:flex-start;gap:0.875rem;
                                    padding-bottom:<?= $isLast ? '0' : '1rem' ?>;
                                    position:relative;">
                            <!-- Timeline dot -->
                            <div style="flex-shrink:0;width:32px;height:32px;border-radius:50%;
                                         display:flex;align-items:center;justify-content:center;
                                         <?= $isLast
                                             ? 'background:var(--biringan-primary);color:#fff;'
                                             : 'background:rgba(74,31,70,0.10);color:var(--biringan-primary);' ?>
                                         font-size:0.75rem;font-weight:700;">
                                <?= $i + 1 ?>
                            </div>
                            <!-- Timeline content -->
                            <div style="flex:1;min-width:0;padding-top:0.25rem;">
                                <div style="display:flex;flex-wrap:wrap;
                                             align-items:center;gap:0.5rem;
                                             margin-bottom:0.2rem;">
                                    <span class="status-badge <?= $e($cfg['class']) ?>"
                                          style="font-size:0.7rem;">
                                        <?= $e($cfg['label']) ?>
                                    </span>
                                    <?php if ($isLast): ?>
                                    <span style="font-size:0.7rem;font-weight:600;
                                                  color:var(--biringan-rose);
                                                  text-transform:uppercase;
                                                  letter-spacing:0.05em;">
                                        Current
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:0.8rem;color:var(--biringan-muted);">
                                    <?= $e($dateStr) ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
                <?php endif; ?>

                <!-- Login CTA -->
                <div style="border-top:1px solid var(--biringan-border);
                             padding-top:1rem;margin-top:1.25rem;">
                    <p style="font-size:0.8125rem;color:var(--biringan-muted);margin:0;">
                        <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                        Registered residents can view full request details, uploaded documents,
                        and remarks by
                        <a href="<?= $e($appUrl) ?>/login"
                           style="color:var(--biringan-primary);font-weight:600;">
                            logging in
                        </a>.
                    </p>
                </div>

            </div>
        </div><!-- /.b2-card result -->
        <?php endif; ?>

    </div>
</section>
