<?php
/**
 * Resident — Demo Online Payment Selection Page (Phase 3 + QR Code)
 * Variables: $request, $docTypeName, $fee, $providers, $csrfToken,
 *            $qrDataUri (SVG data URI), $qrPayload (text shown under QR)
 *
 * DEMO ONLY — NO REAL MONEY IS CHARGED.
 */
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$request   = $request   ?? [];
$fee       = (float)($fee ?? 0.00);
$providers = $providers ?? [];
$qrDataUri = $qrDataUri ?? '';
$qrPayload = $qrPayload ?? '';
$refNum    = $request['reference_number'] ?? '';
$requestId = (int)($request['id'] ?? 0);

$breadcrumbs = [
    ['label' => 'Dashboard',   'url' => $appUrl . '/resident/dashboard'],
    ['label' => 'My Requests', 'url' => $appUrl . '/resident/requests'],
    ['label' => $e($refNum),   'url' => $appUrl . '/resident/requests/' . $requestId],
    ['label' => 'Online Payment (Demo)'],
];
?>

<!-- Demo warning banner -->
<div class="alert d-flex align-items-start gap-2 mt-3 mb-4"
     role="alert"
     style="background:rgba(200,146,42,0.12);border:1px solid var(--emu-warning);border-radius:8px;">
    <i class="bi bi-shield-exclamation flex-shrink-0 mt-1" style="font-size:1.2rem;color:var(--emu-warning);" aria-hidden="true"></i>
    <div>
        <strong style="color:var(--emu-warning);">DEMO TRANSACTION — NOT A REAL PAYMENT</strong><br>
        <span style="font-size:0.875rem;">
            This is a simulated payment for academic demonstration purposes only.
            No real money will be charged. No real payment gateway is contacted.
            Your actual financial information is not required.
        </span>
    </div>
</div>

<!-- Page header -->
<div class="mb-4">
    <h1 class="mb-1" style="font-size:1.5rem;font-weight:700;">Online Payment (Demo)</h1>
    <p class="text-muted mb-0" style="font-size:0.9rem;">
        <span class="text-reference"><?= $e($refNum) ?></span>
        &mdash; <?= $e($docTypeName ?? '') ?>
    </p>
</div>

<div class="row g-4 justify-content-center">
<div class="col-12 col-md-10 col-lg-8">

    <!-- Amount summary -->
    <div class="card mb-4">
        <div class="card-body text-center py-4">
            <p class="text-muted mb-1" style="font-size:0.875rem;">Amount to Pay</p>
            <p class="mb-0" style="font-size:2.5rem;font-weight:700;color:var(--emu-primary);">
                &#8369;<?= $e(number_format($fee, 2)) ?>
            </p>
            <p class="text-muted mt-1 mb-0" style="font-size:0.75rem;">
                Processing fee for <?= $e($docTypeName ?? '') ?>
            </p>
        </div>
    </div>

    <!-- QR code + provider selection (two-column on md+) -->
    <div class="row g-4 mb-4">

        <?php if ($qrDataUri !== ''): ?>
        <!-- QR Code panel -->
        <div class="col-12 col-md-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-qr-code" style="color:var(--emu-primary);" aria-hidden="true"></i>
                    <h2 class="mb-0" style="font-size:0.9375rem;">Demo QR Code</h2>
                </div>
                <div class="card-body text-center d-flex flex-column align-items-center py-4">

                    <!-- QR image — SVG data URI, no external request -->
                    <div style="background:#fff;border:3px solid #1B102B;border-radius:10px;padding:10px;display:inline-block;margin-bottom:0.75rem;">
                        <img src="<?= $e($qrDataUri) ?>"
                             alt="Demo QR code for payment reference <?= $e($refNum) ?>"
                             width="180" height="180"
                             style="display:block;">
                    </div>

                    <!-- Reference + amount under the QR -->
                    <p class="fw-600 mb-0" style="font-size:0.875rem;color:var(--emu-primary);">
                        <?= $e($refNum) ?>
                    </p>
                    <p class="mb-2" style="font-size:0.875rem;">
                        &#8369;<?= $e(number_format($fee, 2)) ?>
                    </p>

                    <!-- DEMO label — must be clearly visible next to QR -->
                    <div class="text-center px-2 py-1 rounded"
                         style="background:rgba(200,146,42,0.12);border:1px solid var(--emu-warning);">
                        <span style="font-size:0.7rem;font-weight:700;color:var(--emu-warning);letter-spacing:0.04em;">
                            DEMO QR PAYMENT<br>NO REAL MONEY WILL BE CHARGED
                        </span>
                    </div>

                    <p class="text-muted mt-3 mb-0" style="font-size:0.75rem;">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        Scan to identify this demo payment. Return here to simulate payment.
                    </p>

                    <?php if ($qrPayload !== ''): ?>
                    <!-- QR content label — no sensitive info -->
                    <details class="mt-2 w-100" style="font-size:0.72rem;">
                        <summary style="cursor:pointer;color:var(--emu-text-muted);">QR content (demo data only)</summary>
                        <code class="d-block mt-1 p-2 rounded text-break"
                              style="background:var(--emu-bg-alt);font-size:0.7rem;word-break:break-all;">
                            <?= $e($qrPayload) ?>
                        </code>
                    </details>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Provider selection form -->
        <div class="col-12 <?= $qrDataUri !== '' ? 'col-md-7' : '' ?>">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="mb-0" style="font-size:0.9375rem;">Select Demo Payment Method</h2>
                </div>
                <div class="card-body">
                    <form method="POST"
                          id="onlinePayForm"
                          action="<?= $e($appUrl) ?>/resident/requests/<?= $requestId ?>/payment/online"
                          data-loading="Processing demo payment…">
                        <input type="hidden" name="_csrf_token"     value="<?= $e($csrfToken) ?>">
                        <input type="hidden" name="simulate_result" id="simulate_result" value="success">

                        <!-- Provider radio buttons -->
                        <div class="d-grid gap-3 mb-4" role="group" aria-label="Select demo payment provider">
                            <?php foreach ($providers as $key => $label): ?>
                            <label class="pay-provider-option border rounded p-3 d-flex align-items-center gap-3"
                                   style="cursor:pointer;transition:border-color 0.15s,background 0.15s;"
                                   for="provider_<?= $e($key) ?>">
                                <input type="radio"
                                       id="provider_<?= $e($key) ?>"
                                       name="provider"
                                       value="<?= $e($key) ?>"
                                       class="form-check-input mt-0"
                                       style="width:1.125rem;height:1.125rem;"
                                       required
                                       <?= $key === array_key_first($providers) ? 'checked' : '' ?>>
                                <span>
                                    <strong style="font-size:0.9375rem;"><?= $e($label) ?></strong>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="btnSuccess">
                                <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                                Simulate Successful Payment
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btnFail">
                                <i class="bi bi-x-circle me-1" aria-hidden="true"></i>
                                Simulate Failed Payment (for testing)
                            </button>
                        </div>

                        <p class="text-muted mt-3 mb-0 text-center" style="font-size:0.75rem;">
                            <i class="bi bi-lock me-1" aria-hidden="true"></i>
                            No real credentials, PINs, OTPs, or card details are collected or stored.
                            This is a demonstration system only.
                        </p>
                    </form>
                </div>
            </div>
        </div>

    </div><!-- /.row QR+form -->

    <!-- Back link -->
    <div class="text-center mt-1">
        <a href="<?= $e($appUrl) ?>/resident/requests/<?= $requestId ?>"
           style="font-size:0.875rem;color:var(--emu-text-muted);text-decoration:none;">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to Request
        </a>
    </div>

</div>
</div>

<style>
.pay-provider-option:hover              { border-color: var(--emu-primary) !important; background: rgba(74,31,70,0.04); }
.pay-provider-option:has(input:checked) { border-color: var(--emu-primary) !important; background: rgba(74,31,70,0.07); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var btnFail  = document.getElementById('btnFail');
    var simInput = document.getElementById('simulate_result');
    var form     = document.getElementById('onlinePayForm');

    if (btnFail && simInput && form) {
        btnFail.addEventListener('click', function () {
            simInput.value = 'fail';
            form.submit();
        });
    }
});
</script>
