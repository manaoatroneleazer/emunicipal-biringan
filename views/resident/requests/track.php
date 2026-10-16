<?php
/**
 * Request Tracking page — design.md §8.5, Req 8.4
 * Variables: $referenceNumber, $request, $history, $notFound
 */

$appUrl          = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e               = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$referenceNumber = $referenceNumber ?? '';
$request         = $request         ?? null;
$history         = $history         ?? [];
$notFound        = $notFound        ?? false;

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/resident/dashboard'],
    ['label' => 'Track Request'],
];

$statusLabels = [
    'pending'                  => 'Pending',
    'under_verification'       => 'Under Verification',
    'additional_info_required' => 'Additional Information Required',
    'approved'                 => 'Approved',
    'rejected'                 => 'Rejected',
    'ready_for_claiming'       => 'Ready for Claiming',
    'completed'                => 'Completed',
    'cancelled'                => 'Cancelled',
];
?>

<h1 class="mb-4 mt-1" style="font-size:1.5rem;font-weight:700;">Track Your Request</h1>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">

        <!-- Search form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="<?= $e($appUrl) ?>/resident/track"
                      novalidate data-loading="Searching…">
                    <label for="ref" class="form-label fw-500">Enter your Reference Number</label>
                    <div class="input-group">
                        <span class="input-group-text" aria-hidden="true">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="ref" name="ref"
                               class="form-control text-reference"
                               value="<?= $e($referenceNumber) ?>"
                               placeholder="EMU-YYYY-000000"
                               maxlength="20"
                               aria-label="Reference number">
                        <button type="submit" class="btn btn-primary"
                                aria-label="Track request">Track</button>
                    </div>
                    <div class="form-text">
                        Your reference number was provided when you submitted your request.
                    </div>
                </form>
            </div>
        </div>

        <!-- Not found -->
        <?php if ($notFound): ?>
        <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
            <div>
                No request found for reference number
                <strong class="text-reference"><?= $e($referenceNumber) ?></strong>.
                Please check the number and try again.
            </div>
        </div>
        <?php endif; ?>

        <!-- Result -->
        <?php if ($request): ?>
        <?php
        $status    = strtolower($request['status'] ?? '');
        $statusKey = $status;
        $statusIconMap = [
            'pending'                  => 'bi-clock',
            'under_verification'       => 'bi-search',
            'additional_info_required' => 'bi-exclamation-circle',
            'approved'                 => 'bi-check-circle',
            'rejected'                 => 'bi-x-circle',
            'ready_for_claiming'       => 'bi-building',
            'completed'                => 'bi-check-circle-fill',
            'cancelled'                => 'bi-slash-circle',
        ];
        $statusLabel = $statusLabels[$statusKey] ?? ucfirst(str_replace('_', ' ', $status));
        $statusIcon  = $statusIconMap[$statusKey] ?? 'bi-circle';
        $terminalStatuses = ['rejected', 'cancelled', 'completed'];
        ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="fw-600"><?= $e($request['document_type_name'] ?? '') ?></div>
                    <div class="text-reference" style="font-size:0.875rem;color:var(--emu-primary);">
                        <?= $e($request['reference_number'] ?? '') ?>
                    </div>
                </div>
                <span class="status-badge status-<?= $e($statusKey) ?>">
                    <i class="bi <?= $e($statusIcon) ?>" aria-hidden="true"></i>
                    <?= $e($statusLabel) ?>
                </span>
            </div>
            <div class="card-body">
                <dl class="row mb-4" style="font-size:0.9rem;">
                    <dt class="col-sm-4 text-label">Submitted</dt>
                    <dd class="col-sm-8">
                        <?= $e(date('F j, Y \a\t g:i A', strtotime($request['created_at']))) ?>
                    </dd>
                    <dt class="col-sm-4 text-label">Document Type</dt>
                    <dd class="col-sm-8"><?= $e($request['document_type_name'] ?? '') ?></dd>
                </dl>

                <!-- Status timeline -->
                <?php if (!empty($history)): ?>
                <h3 class="mb-3" style="font-size:0.9rem;font-weight:600;">Status Timeline</h3>
                <div class="timeline">
                    <?php foreach ($history as $idx => $entry):
                        $entryStatus = strtolower($entry['new_status'] ?? '');
                        $isLast      = ($idx === count($history) - 1);
                        $isCurrent   = $isLast && !in_array($entryStatus, $terminalStatuses);
                        $cssClass    = match($entryStatus) {
                            'rejected'  => 'rejected',
                            'cancelled' => 'cancelled',
                            default     => $isCurrent ? 'current' : 'completed',
                        };
                        $entryLabel    = $statusLabels[$entryStatus] ?? ucfirst(str_replace('_', ' ', $entryStatus));
                        $actorLabel    = !empty($entry['changed_by_role']) ? ucfirst($entry['changed_by_role']) : 'System';
                    ?>
                    <div class="timeline-item <?= $e($cssClass) ?>">
                        <div style="font-size:0.875rem;">
                            <span class="status-badge status-<?= $e($entryStatus) ?>">
                                <?= $e($entryLabel) ?>
                            </span>
                        </div>
                        <div class="text-muted-sm mt-1">
                            <?= $e(date('M j, Y \a\t g:i A', strtotime($entry['created_at']))) ?>
                            · <?= $e($actorLabel) ?>
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

                <div class="mt-4">
                    <a href="<?= $e($appUrl) ?>/resident/requests/<?= (int)$request['id'] ?>"
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>
                        View Full Request Details
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
