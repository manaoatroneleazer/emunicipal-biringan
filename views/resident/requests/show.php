<?php
/**
 * Resident Request Detail — design.md §8.4, Req 7.1–7.6
 * Variables: $request, $docTypeName, $documents, $history, $submitted, $csrfToken
 */
use Emunicipal\Models\Request as RequestModel;

$appUrl      = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e           = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$request     = $request     ?? [];
$documents   = $documents   ?? [];
$history     = $history     ?? [];
$csrfToken    = $csrfToken    ?? '';
$submitted    = $submitted    ?? false;
$uploadErrors = $uploadErrors ?? [];
$docTypeName = $docTypeName ?? ($request['document_type_name'] ?? '');
$status      = $request['status'] ?? '';
$refNum      = $request['reference_number'] ?? '';

// Breadcrumbs (Req 2.4)
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/resident/dashboard'],
    ['label' => 'My Requests', 'url' => $appUrl . '/resident/requests'],
    ['label' => $refNum],
];

// Status labels map
$statusLabels = [
    'pending'                  => 'Pending',
    'under_verification'       => 'Under Verification',
    'additional_info_required' => 'Additional Info Required',
    'approved'                 => 'Approved',
    'rejected'                 => 'Rejected',
    'ready_for_claiming'       => 'Ready for Claiming',
    'completed'                => 'Completed',
    'cancelled'                => 'Cancelled',
];

// Status badge icon map
$statusIcons = [
    'pending'                  => 'bi-clock',
    'under_verification'       => 'bi-search',
    'additional_info_required' => 'bi-exclamation-circle',
    'approved'                 => 'bi-check-circle',
    'rejected'                 => 'bi-x-circle',
    'ready_for_claiming'       => 'bi-building',
    'completed'                => 'bi-check-circle-fill',
    'cancelled'                => 'bi-slash-circle',
];

/**
 * Render a status badge <span> (Req 1.1, §3.1)
 */
function detailStatusBadge(string $status, array $labels, array $icons): string {
    $key   = strtolower($status);
    $icon  = $icons[$key]  ?? 'bi-circle';
    $label = $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
    return '<span class="status-badge status-' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '">'
         . '<i class="bi ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i> '
         . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}

// Cancellable statuses (Req 7.1, design.md §9.1)
$cancellableStatuses = ['pending', 'under_verification', 'additional_info_required'];
$isCancellable       = in_array(strtolower($status), $cancellableStatuses, true);

// Terminal statuses for timeline display logic
$terminalStatuses = ['rejected', 'cancelled', 'completed'];
$lastStatus       = !empty($history) ? strtolower(end($history)['new_status'] ?? '') : strtolower($status);
?>

<!-- Submission success banner (Req 7.5) -->
<?php if ($submitted): ?>
<div class="alert alert-success alert-flash alert-dismissible fade show d-flex align-items-start gap-2 mt-3 mb-3"
     role="alert" aria-live="assertive">
    <i class="bi bi-check-circle flex-shrink-0 mt-1" style="font-size:1.1rem;" aria-hidden="true"></i>
    <div>
        <strong>Request submitted successfully!</strong><br>
        Your reference number is
        <span class="fw-bold" style="font-size:1.1rem;color:var(--emu-primary);">
            <?= $e($refNum) ?>
        </span>.
        Keep this number to track your request.
    </div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"
            aria-label="Close alert"></button>
</div>
<?php endif; ?>

<!-- Page header -->
<div class="d-flex justify-content-between align-items-start mt-3 mb-4 flex-wrap gap-2">
    <div>
        <h1 class="mb-1" style="font-size:1.5rem;font-weight:700;"><?= $e($docTypeName) ?></h1>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="text-reference" style="font-size:1rem;color:var(--emu-primary);"><?= $e($refNum) ?></span>
            <?= detailStatusBadge($status, $statusLabels, $statusIcons) ?>
        </div>
    </div>
    <?php if ($isCancellable): ?>
    <div>
        <button type="button" class="btn btn-danger"
                data-bs-toggle="modal" data-bs-target="#cancelModal"
                aria-label="Cancel this request">
            <i class="bi bi-x-circle me-1" aria-hidden="true"></i> Cancel Request
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Additional information required alert (Req 7.6) -->
<?php if (strtolower($status) === 'additional_info_required'): ?>
<?php
// Get the most recent remark for this status from history
$latestRemark = '';
foreach (array_reverse($history) as $h) {
    if (strtolower($h['new_status']) === 'additional_info_required' && !empty($h['remark'])) {
        $latestRemark = $h['remark'];
        break;
    }
}
?>
<div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
    <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
    <div>
        <strong>Additional Information Required</strong>
        <?php if ($latestRemark): ?>
        <p class="mb-1 mt-1">
            <em>"<?= $e($latestRemark) ?>"</em>
        </p>
        <?php endif; ?>
        <p class="mb-0" style="font-size:0.875rem;">
            Please review the remark above and upload the requested documents using the form below.
        </p>
    </div>
</div>
<?php endif; ?>

<?php if (strtolower($status) === 'additional_info_required'): ?>
<!-- Additional Document Upload Form (Req 8.6–8.7) -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-upload" aria-hidden="true" style="color:var(--emu-warning);"></i>
        <h2 class="mb-0" style="font-size:0.9375rem;">Upload Additional Documents</h2>
    </div>
    <div class="card-body">
        <?php if (!empty($uploadErrors)): ?>
        <div class="alert alert-danger d-flex gap-2 py-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
            <span><?= $e(is_array($uploadErrors) ? implode(' ', $uploadErrors) : $uploadErrors) ?></span>
        </div>
        <?php endif; ?>

        <p class="text-muted mb-3" style="font-size:0.875rem;">
            Accepted formats: PDF, JPEG, PNG — maximum 5 MB per file.
            Your previous uploads are preserved; this only adds new documents.
        </p>

        <form method="POST"
              action="<?= $e($appUrl) ?>/resident/requests/<?= (int)($request['id'] ?? 0) ?>/documents"
              enctype="multipart/form-data"
              data-loading="Uploading…"
              aria-label="Upload additional documents">

            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">

            <div class="mb-3">
                <label for="additional_documents" class="form-label">
                    Select Files <span class="text-danger" aria-hidden="true">*</span>
                </label>
                <input type="file"
                       id="additional_documents"
                       name="additional_documents[]"
                       class="form-control"
                       multiple
                       accept=".pdf,.jpg,.jpeg,.png"
                       data-file-label="additionalFilesLabel"
                       aria-describedby="additionalFilesHelp"
                       required>
                <div id="additionalFilesHelp" class="form-text">
                    You may select multiple files. Each file must be under 5 MB.
                </div>
                <div id="additionalFilesLabel" class="mt-1" style="font-size:0.8125rem;color:var(--emu-text-muted);">
                    No files chosen
                </div>
            </div>

            <button type="submit" class="btn btn-warning">
                <i class="bi bi-upload me-1" aria-hidden="true"></i>
                Upload Documents
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-8">

        <!-- Request Information Card (Req 7.1) -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">Request Information</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:0.9rem;">
                    <dt class="col-sm-4 text-label">Document Type</dt>
                    <dd class="col-sm-8"><?= $e($docTypeName) ?></dd>

                    <dt class="col-sm-4 text-label">Reference No.</dt>
                    <dd class="col-sm-8">
                        <span class="text-reference"><?= $e($refNum) ?></span>
                    </dd>

                    <dt class="col-sm-4 text-label">Purpose</dt>
                    <dd class="col-sm-8"><?= $e($request['purpose'] ?? '') ?></dd>

                    <dt class="col-sm-4 text-label">Copies</dt>
                    <dd class="col-sm-8"><?= (int)($request['copies_requested'] ?? 1) ?></dd>

                    <dt class="col-sm-4 text-label">Date Submitted</dt>
                    <dd class="col-sm-8">
                        <?= !empty($request['created_at'])
                            ? $e(date('F j, Y \a\t g:i A', strtotime($request['created_at'])))
                            : '—' ?>
                    </dd>

                    <dt class="col-sm-4 text-label">Current Status</dt>
                    <dd class="col-sm-8"><?= detailStatusBadge($status, $statusLabels, $statusIcons) ?></dd>

                    <?php if (strtolower($status) === 'rejected' && !empty($request['rejection_reason'])): ?>
                    <dt class="col-sm-4 text-label text-danger">Rejection Reason</dt>
                    <dd class="col-sm-8 text-danger"><?= $e($request['rejection_reason']) ?></dd>
                    <?php endif; ?>

                    <?php if (strtolower($status) === 'cancelled' && !empty($request['cancellation_reason'])): ?>
                    <dt class="col-sm-4 text-label">Cancellation Reason</dt>
                    <dd class="col-sm-8"><?= $e($request['cancellation_reason']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Uploaded Documents Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">Uploaded Documents</h2>
            </div>
            <div class="card-body">
                <?php if (empty($documents)): ?>
                <p class="text-muted mb-0" style="font-size:0.875rem;">No files were uploaded with this request.</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($documents as $doc): ?>
                    <li class="d-flex align-items-center justify-content-between mb-2 p-2 rounded"
                        style="background:var(--emu-bg-alt);">
                        <div class="d-flex align-items-center gap-2">
                            <?php if (($doc['mime_type'] ?? '') === 'application/pdf'): ?>
                            <i class="bi bi-file-earmark-pdf text-danger" style="font-size:1.25rem;" aria-hidden="true"></i>
                            <?php else: ?>
                            <i class="bi bi-file-earmark-image text-info" style="font-size:1.25rem;" aria-hidden="true"></i>
                            <?php endif; ?>
                            <div>
                                <div style="font-size:0.875rem;font-weight:500;">
                                    <?= $e($doc['original_filename'] ?? '') ?>
                                </div>
                                <div class="text-muted-sm">
                                    <?= $e(number_format(($doc['file_size_bytes'] ?? 0) / 1024, 0)) ?> KB ·
                                    <?= !empty($doc['created_at']) ? $e(date('M j, Y', strtotime($doc['created_at']))) : '—' ?>
                                </div>
                            </div>
                        </div>
                        <a href="<?= $e($appUrl) ?>/files/<?= (int)($doc['id'] ?? 0) ?>"
                           class="btn btn-sm btn-outline-secondary"
                           aria-label="Download <?= $e($doc['original_filename'] ?? 'file') ?>">
                            <i class="bi bi-download me-1" aria-hidden="true"></i> Download
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status History Timeline Card (Req 7.2, 7.3) -->
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">Status History</h2>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                <p class="text-muted mb-0" style="font-size:0.875rem;">No history available.</p>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($history as $idx => $entry):
                        $entryStatusLower = strtolower($entry['new_status'] ?? '');
                        $isCurrent  = ($idx === count($history) - 1) && !in_array($lastStatus, $terminalStatuses, true);
                        $cssClass   = match($entryStatusLower) {
                            'rejected'  => 'rejected',
                            'cancelled' => 'cancelled',
                            default     => $isCurrent ? 'current' : 'completed',
                        };
                    ?>
                    <div class="timeline-item <?= $e($cssClass) ?>">
                        <div style="font-size:0.875rem;">
                            <?= detailStatusBadge($entryStatusLower, $statusLabels, $statusIcons) ?>
                        </div>
                        <div class="text-muted-sm mt-1">
                            <?= !empty($entry['created_at'])
                                ? $e(date('M j, Y \a\t g:i A', strtotime($entry['created_at'])))
                                : '—' ?>
                            <?php if (!empty($entry['changed_by_role'])): ?>
                            · <?= $e(ucfirst($entry['changed_by_role'])) ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($entry['remark'])): ?>
                        <div class="mt-1 p-2 rounded" style="background:var(--emu-bg-alt);font-size:0.8125rem;">
                            <em>"<?= $e($entry['remark']) ?>"</em>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.col-12.col-lg-8 -->

    <!-- Sidebar: claiming instructions for ready_for_claiming status -->
    <div class="col-12 col-lg-4">
        <?php if (strtolower($status) === 'ready_for_claiming'): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:0.9375rem;">
                    <i class="bi bi-building me-1" aria-hidden="true"></i> Claiming Instructions
                </h2>
            </div>
            <div class="card-body" style="font-size:0.875rem;">
                <p class="mb-2"><strong>Your document is ready for pick-up.</strong></p>
                <p class="mb-1"><i class="bi bi-geo-alt me-1 text-primary" aria-hidden="true"></i> Biringan City Civic Center, Central District, Biringan City</p>
                <p class="mb-1"><i class="bi bi-clock me-1 text-primary" aria-hidden="true"></i> Mon–Fri, 8:00 AM – 5:00 PM</p>
                <p class="mb-0 text-muted">Please bring a valid community identification when claiming.</p>
            </div>
        </div>

        <?php elseif (strtolower($status) === 'completed'): ?>
        <div class="card">
            <div class="card-body text-center py-4" style="font-size:0.875rem;">
                <i class="bi bi-check-circle-fill mb-2 d-block"
                   style="font-size:2rem;color:var(--emu-success);" aria-hidden="true"></i>
                <p class="fw-600 mb-1" style="color:var(--emu-success);">Request Completed</p>
                <p class="text-muted mb-0">
                    Your request has been fulfilled. No further action is required.
                </p>
            </div>
        </div>

        <?php elseif (strtolower($status) === 'cancelled'): ?>
        <div class="card">
            <div class="card-body text-center py-4" style="font-size:0.875rem;">
                <i class="bi bi-slash-circle mb-2 d-block"
                   style="font-size:2rem;color:var(--emu-neutral);" aria-hidden="true"></i>
                <p class="fw-600 mb-1" style="color:var(--emu-text-primary);">Request Cancelled</p>
                <p class="text-muted mb-0">
                    This request has been cancelled. No further action is required.
                </p>
            </div>
        </div>

        <?php endif; ?>

        <!-- ================================================================
             PAYMENT CARD (Phase 2 Cash + Phase 3 Online Demo)
             Shown only when status = ready_for_claiming AND processing_fee > 0.
             $payment is loaded by RequestController::show() from Payment model.
             ================================================================ -->
        <?php
        use Emunicipal\Models\Payment as PaymentModel;
        $payment       = $payment ?? false;
        $processingFee = (float)($docTypeFee ?? 0.00);
        $showPayment   = strtolower($status) === 'ready_for_claiming' && $processingFee > 0.00;
        ?>
        <?php if ($showPayment): ?>
        <div class="card mt-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-credit-card-2-front" style="color:var(--emu-warning);" aria-hidden="true"></i>
                <h2 class="mb-0" style="font-size:0.9375rem;">Payment</h2>
            </div>
            <div class="card-body" style="font-size:0.875rem;">

                <?php if ($payment === false): ?>
                <!-- ── No payment selected yet — show both options ── -->
                <p class="mb-1">
                    <span class="text-label">Processing Fee</span><br>
                    <strong style="font-size:1rem;">
                        &#8369;<?= $e(number_format($processingFee, 2)) ?>
                    </strong>
                </p>
                <p class="text-muted mb-3">
                    Select your payment method below.
                </p>

                <!-- Cash option -->
                <form method="POST"
                      action="<?= $e($appUrl) ?>/resident/requests/<?= (int)($request['id'] ?? 0) ?>/payment"
                      data-loading="Selecting…"
                      class="mb-2">
                    <input type="hidden" name="_csrf_token"    value="<?= $e($csrfToken) ?>">
                    <input type="hidden" name="payment_method" value="cash">
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="bi bi-cash-coin me-1" aria-hidden="true"></i>
                        Pay with Cash
                    </button>
                </form>
                <p class="text-muted mb-3" style="font-size:0.78rem;">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    Pay in-office when collecting your document. Staff will confirm receipt.
                </p>

                <!-- Divider -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <hr class="flex-grow-1 my-0">
                    <span class="text-muted" style="font-size:0.75rem;">or</span>
                    <hr class="flex-grow-1 my-0">
                </div>

                <!-- Online option (demo) -->
                <a href="<?= $e($appUrl) ?>/resident/requests/<?= (int)($request['id'] ?? 0) ?>/payment/online"
                   class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-phone me-1" aria-hidden="true"></i>
                    Online Payment (Demo)
                </a>
                <p class="text-muted mb-0" style="font-size:0.78rem;">
                    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                    GCash, Maya, Online Banking — simulated demo only.
                    No real money is charged.
                </p>

                <?php elseif (($payment['payment_status'] ?? '') === 'pending'): ?>
                <!-- ── Cash selected, awaiting Staff confirmation ── -->
                <dl class="row mb-2" style="font-size:0.875rem;">
                    <dt class="col-6 text-label">Method</dt>
                    <dd class="col-6">Cash</dd>
                    <dt class="col-6 text-label">Amount Due</dt>
                    <dd class="col-6">
                        <strong>&#8369;<?= $e(number_format((float)$payment['amount'], 2)) ?></strong>
                    </dd>
                    <dt class="col-6 text-label">Status</dt>
                    <dd class="col-6" style="color:var(--emu-warning);">
                        <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Pending
                    </dd>
                    <dt class="col-6 text-label">Selected On</dt>
                    <dd class="col-6">
                        <?= !empty($payment['created_at'])
                            ? $e(date('M j, Y', strtotime($payment['created_at'])))
                            : '—' ?>
                    </dd>
                </dl>
                <div class="alert alert-warning py-2 mb-0" role="status" style="font-size:0.8rem;">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    Please bring <strong>&#8369;<?= $e(number_format((float)$payment['amount'], 2)) ?></strong>
                    when you collect your document. Staff will confirm receipt.
                </div>

                <?php elseif (($payment['payment_status'] ?? '') === 'online_pending'): ?>
                <!-- ── Online payment initiated (should auto-confirm immediately in normal flow) ── -->
                <dl class="row mb-2" style="font-size:0.875rem;">
                    <dt class="col-6 text-label">Method</dt>
                    <dd class="col-6">Online Payment (Demo)</dd>
                    <dt class="col-6 text-label">Amount</dt>
                    <dd class="col-6">
                        <strong>&#8369;<?= $e(number_format((float)$payment['amount'], 2)) ?></strong>
                    </dd>
                    <dt class="col-6 text-label">Status</dt>
                    <dd class="col-6" style="color:var(--emu-warning);">
                        <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Processing…
                    </dd>
                </dl>

                <?php else: /* paid */ ?>
                <!-- ── Payment confirmed (cash or online) ── -->
                <?php
                $isOnline = (($payment['payment_method'] ?? '') === 'online');
                ?>
                <dl class="row mb-2" style="font-size:0.875rem;">
                    <dt class="col-6 text-label">Method</dt>
                    <dd class="col-6">
                        <?= $isOnline ? 'Online Payment (Demo)' : 'Cash' ?>
                    </dd>
                    <dt class="col-6 text-label">Amount Paid</dt>
                    <dd class="col-6">
                        <strong>&#8369;<?= $e(number_format((float)$payment['amount'], 2)) ?></strong>
                    </dd>
                    <dt class="col-6 text-label">Status</dt>
                    <dd class="col-6" style="color:var(--emu-success);">
                        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Paid
                    </dd>
                    <dt class="col-6 text-label">
                        <?= $isOnline ? 'Paid On' : 'Confirmed On' ?>
                    </dt>
                    <dd class="col-6">
                        <?= !empty($payment['confirmed_at'])
                            ? $e(date('M j, Y', strtotime($payment['confirmed_at'])))
                            : '—' ?>
                    </dd>
                    <?php if (!$isOnline && !empty($payment['confirmed_first'])): ?>
                    <dt class="col-6 text-label">Confirmed By</dt>
                    <dd class="col-6">
                        <?= $e(trim(($payment['confirmed_first'] ?? '') . ' ' . ($payment['confirmed_last'] ?? ''))) ?>
                    </dd>
                    <?php endif; ?>
                    <?php if ($isOnline && !empty($payment['transaction_ref'])): ?>
                    <dt class="col-6 text-label">Transaction Ref.</dt>
                    <dd class="col-6">
                        <code style="font-size:0.8rem;"><?= $e($payment['transaction_ref']) ?></code>
                    </dd>
                    <?php endif; ?>
                </dl>
                <?php if ($isOnline): ?>
                <p class="text-muted mb-0" style="font-size:0.75rem;">
                    DEMO TRANSACTION — NOT A REAL PAYMENT
                </p>
                <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>
        <?php endif; /* showPayment */ ?>
    </div>

</div><!-- /.row -->

<!-- Cancel Confirmation Modal (design.md §3.4, Req 7.4, 20.4) -->
<?php if ($isCancellable): ?>
<div class="modal fade" id="cancelModal" tabindex="-1"
     aria-labelledby="cancelModalLabel" aria-modal="true"
     data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title h5" id="cancelModalLabel">Cancel This Request?</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close dialog"></button>
            </div>
            <form method="POST"
                  action="<?= $e($appUrl) ?>/resident/requests/<?= (int)($request['id'] ?? 0) ?>/cancel"
                  data-loading="Cancelling…">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                    <p class="mb-3" style="font-size:0.9rem;">
                        This action cannot be undone. Your request
                        <strong class="text-reference"><?= $e($refNum) ?></strong>
                        will be cancelled.
                    </p>
                    <div class="mb-0">
                        <label for="cancellation_reason" class="form-label">
                            Reason for Cancellation <span class="text-muted">(optional)</span>
                        </label>
                        <textarea id="cancellation_reason" name="cancellation_reason"
                                  class="form-control" rows="2"
                                  placeholder="Enter reason (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Request</button>
                    <button type="submit" class="btn btn-danger">Yes, Cancel Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
