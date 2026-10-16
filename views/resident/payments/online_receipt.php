<?php
/**
 * Resident — Demo Online Payment Receipt Page (Phase 3)
 * Variables: $request, $receipt (array from session flash)
 *
 * DEMO ONLY — NOT A REAL PAYMENT RECEIPT.
 */
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$request   = $request ?? [];
$receipt   = $receipt  ?? [];
$requestId = (int)($request['id'] ?? 0);
$refNum    = $receipt['reference_number'] ?? ($request['reference_number'] ?? '');

$breadcrumbs = [
    ['label' => 'Dashboard',   'url' => $appUrl . '/resident/dashboard'],
    ['label' => 'My Requests', 'url' => $appUrl . '/resident/requests'],
    ['label' => $e($refNum),   'url' => $appUrl . '/resident/requests/' . $requestId],
    ['label' => 'Payment Receipt (Demo)'],
];
?>

<div class="row justify-content-center mt-3">
<div class="col-12 col-md-8 col-lg-6">

    <!-- Success header -->
    <div class="text-center mb-4">
        <i class="bi bi-check-circle-fill mb-2 d-block"
           style="font-size:3rem;color:var(--emu-success);" aria-hidden="true"></i>
        <h1 class="mb-1" style="font-size:1.5rem;font-weight:700;">Payment Successful!</h1>
        <p class="text-muted mb-0" style="font-size:0.875rem;">
            Your demo online payment has been processed.
        </p>
    </div>

    <!-- DEMO warning -->
    <div class="alert text-center py-2 mb-4"
         role="alert"
         style="background:rgba(200,146,42,0.12);border:1px solid var(--emu-warning);font-size:0.8rem;font-weight:600;letter-spacing:0.05em;color:var(--emu-warning);">
        ⚠️ DEMO TRANSACTION — NOT A REAL PAYMENT ⚠️
    </div>

    <!-- Receipt card -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-receipt" style="color:var(--emu-success);" aria-hidden="true"></i>
            <h2 class="mb-0" style="font-size:0.9375rem;">Payment Receipt</h2>
        </div>
        <div class="card-body">
            <dl class="row mb-0" style="font-size:0.9rem;">
                <dt class="col-5 text-label">Reference No.</dt>
                <dd class="col-7">
                    <span class="text-reference"><?= $e($refNum) ?></span>
                </dd>

                <dt class="col-5 text-label">Document</dt>
                <dd class="col-7"><?= $e($receipt['doc_type_name'] ?? '') ?></dd>

                <dt class="col-5 text-label">Amount Paid</dt>
                <dd class="col-7">
                    <strong style="font-size:1.05rem;">&#8369;<?= $e(number_format((float)($receipt['amount'] ?? 0), 2)) ?></strong>
                </dd>

                <dt class="col-5 text-label">Method</dt>
                <dd class="col-7"><?= $e($receipt['provider_label'] ?? 'Online Payment (Demo)') ?></dd>

                <dt class="col-5 text-label">Status</dt>
                <dd class="col-7" style="color:var(--emu-success);">
                    <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Paid (Demo)
                </dd>

                <dt class="col-5 text-label">Transaction Ref.</dt>
                <dd class="col-7">
                    <code style="font-size:0.8125rem;"><?= $e($receipt['transaction_ref'] ?? '—') ?></code>
                </dd>

                <dt class="col-5 text-label">Date &amp; Time</dt>
                <dd class="col-7">
                    <?= !empty($receipt['paid_at'])
                        ? $e(date('F j, Y \a\t g:i A', strtotime($receipt['paid_at'])))
                        : '—' ?>
                </dd>
            </dl>
        </div>
        <div class="card-footer text-center py-2" style="font-size:0.7rem;color:var(--emu-text-muted);">
            DEMO TRANSACTION — NOT A REAL PAYMENT — FOR ACADEMIC DEMONSTRATION ONLY
        </div>
    </div>

    <!-- Next steps -->
    <div class="card mb-4">
        <div class="card-body" style="font-size:0.875rem;">
            <p class="fw-600 mb-2"><i class="bi bi-info-circle me-1 text-primary" aria-hidden="true"></i>Next Steps</p>
            <p class="mb-1 text-muted">
                Your document is ready for collection at the Biringan City Civic Center.
                Bring your valid community ID when you visit.
            </p>
            <p class="mb-0 text-muted">
                Mon–Fri, 8:00 AM – 5:00 PM
            </p>
        </div>
    </div>

    <!-- Actions -->
    <div class="d-grid gap-2">
        <a href="<?= $e($appUrl) ?>/resident/requests/<?= $requestId ?>"
           class="btn btn-primary">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to Request
        </a>
        <a href="<?= $e($appUrl) ?>/resident/requests"
           class="btn btn-outline-secondary btn-sm">
            My Requests
        </a>
    </div>

</div>
</div>
