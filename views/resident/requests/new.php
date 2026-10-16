<?php
/**
 * New Request form — fictional Biringan City document types with search + office filter
 * Variables: $documentTypes, $selectedType, $requirements, $csrfToken, $errors, $old
 */
use Emunicipal\Helpers\SecurityHelper;

$appUrl        = rtrim($_ENV['APP_URL'] ?? 'http://localhost/eMunicipal', '/');
$e             = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$errors        = $errors        ?? [];
$old           = $old           ?? [];
$documentTypes = $documentTypes ?? [];
$selectedType  = $selectedType  ?? null;
$requirements  = $requirements  ?? [];
$csrfToken     = $csrfToken     ?? '';
$selectedId    = $selectedType ? (int)$selectedType['id'] : (int)($old['document_type_id'] ?? 0);

$breadcrumbs = [
    ['label' => 'Dashboard',    'url' => $appUrl . '/resident/dashboard'],
    ['label' => 'My Requests',  'url' => $appUrl . '/resident/requests'],
    ['label' => 'New Request'],
];

// ---------------------------------------------------------------------------
// Build office list from description prefixes for the filter
// Pattern: [Office Name] description text
// ---------------------------------------------------------------------------
$officeMap = [
    'BCRO' => 'Biringan Civil Records Office (BCRO)',
    'CRO'  => 'Community Records Office (CRO)',
    'CAO'  => 'Community Assistance Office (CAO)',
    'BSO'  => 'Business Services Office (BSO)',
    'CHO'  => 'Community Health Office (CHO)',
];

// Extract office tag from description "[TAG]..."
function extractOfficeTag(string $desc): string {
    if (preg_match('/^\[([^\]]+)\]/', $desc, $m)) {
        return trim($m[1]);
    }
    return '';
}

// Map raw tag → canonical office label
function resolveOffice(string $tag, array $officeMap): string {
    foreach ($officeMap as $key => $label) {
        if (stripos($tag, $key) !== false || stripos($key, $tag) !== false) {
            return $label;
        }
    }
    return $tag ?: 'Other';
}

// Build unique office list from current document types
$officeSet = [];
foreach ($documentTypes as $dt) {
    $tag = extractOfficeTag($dt['description'] ?? '');
    $label = resolveOffice($tag, $officeMap);
    if ($label && !in_array($label, $officeSet, true)) {
        $officeSet[] = $label;
    }
}
sort($officeSet);

// Attach office label to each document type for JS filtering
foreach ($documentTypes as &$dt) {
    $tag = extractOfficeTag($dt['description'] ?? '');
    $dt['office_label'] = resolveOffice($tag, $officeMap);
}
unset($dt);

// Also attach office_label to $selectedType if set
if ($selectedType) {
    $stag = extractOfficeTag($selectedType['description'] ?? '');
    $selectedType['office_label'] = resolveOffice($stag, $officeMap);
}
?>

<h1 class="mb-4 mt-1" style="font-size:1.5rem;font-weight:700;">New Document Request</h1>

<?php if (!empty($errors['form'])): ?>
<div class="alert alert-danger d-flex align-items-start gap-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>
    <span><?= $e($errors['form']) ?></span>
</div>
<?php endif; ?>

<form method="POST" action="<?= $e($appUrl) ?>/resident/requests"
      enctype="multipart/form-data" novalidate
      data-loading="Submitting…">
    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">

    <!-- ================================================================
         STEP 1: Select Document Type
         ================================================================ -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="mb-0" style="font-size:0.9375rem;">
                <span class="badge bg-primary me-2" style="font-size:0.8rem;">1</span>
                Select Document Type
            </h2>
        </div>
        <div class="card-body">
            <?php if (!empty($errors['document_type_id'])): ?>
            <div class="alert alert-danger py-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                <?= $e($errors['document_type_id']) ?>
            </div>
            <?php endif; ?>

            <?php if (empty($documentTypes)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
                No document types are currently available. Please check back later.
            </div>

            <?php elseif ($selectedType): ?>
            <!-- ======================================================
                 SELECTED STATE — only show the selected document
                 ====================================================== -->
            <div class="mb-3">
                <div class="p-3 rounded d-flex align-items-start gap-3"
                     style="background:var(--emu-sidebar-active);border:2px solid var(--emu-primary);">
                    <i class="bi bi-file-earmark-check-fill text-primary mt-1 flex-shrink-0"
                       style="font-size:1.5rem;" aria-hidden="true"></i>
                    <div class="flex-grow-1">
                        <div class="fw-700 mb-1" style="font-size:1rem;color:var(--emu-primary);">
                            <?= $e($selectedType['name']) ?>
                        </div>
                        <div class="text-muted-sm mb-1">
                            <?= $e($selectedType['office_label'] ?? '') ?>
                        </div>
                        <?php if ((float)($selectedType['processing_fee'] ?? 0) > 0): ?>
                        <div style="font-size:0.8125rem;font-weight:600;color:var(--emu-text-primary);">
                            <i class="bi bi-cash me-1" aria-hidden="true"></i>
                            Fee: &#8369;<?= $e(number_format((float)$selectedType['processing_fee'], 2)) ?>
                        </div>
                        <?php else: ?>
                        <div style="font-size:0.8125rem;color:var(--emu-success);font-weight:500;">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>No fee / Variable fee — see description
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="<?= $e($appUrl) ?>/resident/requests/new"
                       class="btn btn-sm btn-outline-secondary flex-shrink-0"
                       aria-label="Change selected document type">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Change
                    </a>
                </div>
                <?php if (!empty($selectedType['description'])): ?>
                <?php
                    // Strip the [Office] prefix from description for display
                    $cleanDesc = preg_replace('/^\[[^\]]+\]\s*/', '', $selectedType['description'] ?? '');
                ?>
                <?php if ($cleanDesc): ?>
                <div class="mt-2 px-1" style="font-size:0.8125rem;color:var(--emu-text-secondary);">
                    <?= $e($cleanDesc) ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <input type="hidden" name="document_type_id" value="<?= (int)$selectedType['id'] ?>">
            </div>

            <!-- Requirements checklist for selected document -->
            <?php if (!empty($requirements)): ?>
            <div class="p-3 rounded" style="background:var(--emu-bg-section);border:1px solid var(--emu-border);">
                <h3 class="mb-3" style="font-size:0.9rem;font-weight:600;">
                    <i class="bi bi-list-check me-1 text-primary" aria-hidden="true"></i>
                    Supporting Documents Required:
                </h3>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($requirements as $req): ?>
                    <li class="mb-2 d-flex align-items-start gap-2" style="font-size:0.875rem;">
                        <i class="bi <?= $req['is_required'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?> mt-1 flex-shrink-0"
                           aria-hidden="true"></i>
                        <div>
                            <strong><?= $e($req['name']) ?></strong>
                            <?php if (!$req['is_required']): ?>
                            <span class="text-muted-sm ms-1">(optional)</span>
                            <?php endif; ?>
                            <?php if (!empty($req['description'])): ?>
                            <div class="text-muted-sm"><?= $e($req['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="alert alert-info py-2">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                No specific supporting documents are required for this document type.
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- ======================================================
                 BROWSE STATE — search + filter + document grid
                 ====================================================== -->
            <p class="text-muted mb-3" style="font-size:0.875rem;">
                <i class="bi bi-hand-index me-1" aria-hidden="true"></i>
                Search or browse below, then click a document to select it.
            </p>

            <!-- Search + Office Filter controls -->
            <div class="row g-2 mb-3" id="doc-filter-row">
                <div class="col-12 col-md-7">
                    <div class="input-group">
                        <span class="input-group-text" aria-hidden="true">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                               id="doc-search"
                               class="form-control"
                               placeholder="Search documents… e.g. birth, permit, tax"
                               aria-label="Search document types"
                               autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-md-5">
                    <select id="doc-office-filter" class="form-select" aria-label="Filter by office">
                        <option value="">All Offices</option>
                        <?php foreach ($officeSet as $ol): ?>
                        <option value="<?= $e($ol) ?>"><?= $e($ol) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- No-results message (hidden by default) -->
            <div id="doc-no-results" class="alert alert-warning py-2 mb-3 d-none" role="status">
                <i class="bi bi-search me-1" aria-hidden="true"></i>
                No document types match your search. Try different keywords.
            </div>

            <!-- Document type cards grid -->
            <div class="row g-3" id="doc-grid" role="radiogroup" aria-label="Select a document type">
                <?php foreach ($documentTypes as $dt): ?>
                <div class="col-12 col-md-6 col-lg-4 doc-card-wrap"
                     data-name="<?= $e(mb_strtolower($dt['name'])) ?>"
                     data-office="<?= $e($dt['office_label'] ?? '') ?>">
                    <label class="doc-type-card d-block h-100"
                           for="dt_<?= (int)$dt['id'] ?>"
                           style="cursor:pointer;">
                        <div class="doc-type-card-icon" aria-hidden="true">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="fw-600 mb-1" style="font-size:0.875rem;">
                            <?= $e($dt['name']) ?>
                        </div>
                        <div class="text-muted-sm mb-1" style="font-size:0.78rem;">
                            <?= $e($dt['office_label'] ?? '') ?>
                        </div>
                        <?php
                            $feeAmt  = (float)$dt['processing_fee'];
                            $feeDesc = strtolower($dt['description'] ?? '');
                            $isVariable = $feeAmt === 0.0 && (
                                strpos($feeDesc, 'variable') !== false ||
                                strpos($feeDesc, 'schedule') !== false ||
                                strpos($feeDesc, 'included') !== false
                            );
                        ?>
                        <?php if ($feeAmt > 0): ?>
                        <div style="font-size:0.78rem;color:var(--emu-text-secondary);font-weight:600;">
                            Fee: &#8369;<?= $e(number_format($feeAmt, 2)) ?>
                        </div>
                        <?php elseif ($isVariable): ?>
                        <div style="font-size:0.78rem;color:var(--emu-warning);">Variable fee</div>
                        <?php else: ?>
                        <div style="font-size:0.78rem;color:var(--emu-success);">No fee</div>
                        <?php endif; ?>
                        <input type="radio" id="dt_<?= (int)$dt['id'] ?>" name="document_type_id"
                               value="<?= (int)$dt['id'] ?>"
                               class="visually-hidden">
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php if ($selectedType): ?>
    <!-- ================================================================
         STEP 2: Request Details (only shown after selecting a document)
         ================================================================ -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="mb-0" style="font-size:0.9375rem;">
                <span class="badge bg-primary me-2" style="font-size:0.8rem;">2</span>
                Request Details
            </h2>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="purpose" class="form-label">
                    Purpose of Request
                    <span class="text-danger" aria-hidden="true">*</span>
                    <span class="visually-hidden">(required)</span>
                </label>
                <textarea id="purpose" name="purpose" rows="3"
                          class="form-control<?= !empty($errors['purpose']) ? ' is-invalid' : '' ?>"
                          maxlength="1000" required autocomplete="off"
                          placeholder="e.g. For employment application, business permit renewal, etc."
                          aria-describedby="<?= !empty($errors['purpose']) ? 'purpose-error' : '' ?>"><?= $e($old['purpose'] ?? '') ?></textarea>
                <?php if (!empty($errors['purpose'])): ?>
                <div id="purpose-error" class="invalid-feedback" role="alert">
                    <?= $e($errors['purpose']) ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="mb-0">
                <label for="copies_requested" class="form-label">
                    Number of Copies
                    <span class="text-danger" aria-hidden="true">*</span>
                    <span class="visually-hidden">(required)</span>
                </label>
                <input type="number" id="copies_requested" name="copies_requested"
                       class="form-control<?= !empty($errors['copies_requested']) ? ' is-invalid' : '' ?>"
                       value="<?= $e($old['copies_requested'] ?? '1') ?>"
                       min="1" step="1" required style="max-width:120px;"
                       autocomplete="off"
                       aria-describedby="copies-help <?= !empty($errors['copies_requested']) ? 'copies-error' : '' ?>">
                <div id="copies-help" class="form-text">Must be 1 or more.</div>
                <?php if (!empty($errors['copies_requested'])): ?>
                <div id="copies-error" class="invalid-feedback" role="alert">
                    <?= $e($errors['copies_requested']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================
         STEP 3: Upload Supporting Documents
         ================================================================ -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="mb-0" style="font-size:0.9375rem;">
                <span class="badge bg-primary me-2" style="font-size:0.8rem;">3</span>
                Upload Supporting Documents
            </h2>
        </div>
        <div class="card-body">
            <?php if (!empty($errors['documents'])): ?>
            <div class="alert alert-danger py-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                <?= $e($errors['documents']) ?>
            </div>
            <?php endif; ?>

            <!-- Drop zone — JS wires drag/drop + click in the inline script below -->
            <div id="drop-zone" class="drop-zone mb-2"
                 role="button" tabindex="0"
                 aria-label="Click or drag files here to upload supporting documents">
                <i class="bi bi-cloud-upload d-block mb-2"
                   style="font-size:2rem;color:var(--emu-text-muted);" aria-hidden="true"></i>
                <p class="mb-1 fw-500" style="font-size:0.9375rem;">
                    Drag &amp; drop files here, or <span style="color:var(--emu-primary);text-decoration:underline;">click to browse</span>
                </p>
                <p class="mb-0" style="font-size:0.8rem;color:var(--emu-text-muted);">
                    Accepted: PDF, JPG, PNG &nbsp;·&nbsp; Max 5 MB each &nbsp;·&nbsp; Up to 10 files
                </p>
            </div>

            <!-- Real file input — hidden, submitted with the form as-is -->
            <input type="file" id="file-upload" name="documents[]"
                   multiple accept=".pdf,.jpg,.jpeg,.png"
                   class="visually-hidden"
                   aria-label="Upload supporting documents">

            <!-- File list rendered by JS — compact rows -->
            <div id="file-list" class="file-row-list" aria-live="polite" aria-label="Selected files"></div>
            <div id="file-count" class="file-upload-count"></div>

            <div class="form-text mt-1">
                Upload files that match the requirements listed above.
                Only PDF, JPEG, and PNG files are accepted (validated server-side).
                Maximum 5 MB per file.
            </div>
        </div>
    </div>

    <div class="alert d-flex align-items-start gap-2 mb-3"
         style="background:rgba(74,31,70,0.07);border:1px solid rgba(74,31,70,0.20);color:var(--biringan-text,#241C29);"
         role="note">
        <i class="bi bi-shield-exclamation flex-shrink-0 mt-1"
           style="color:var(--emu-primary,#4A1F46);" aria-hidden="true"></i>
        <span style="font-size:0.8125rem;">
            <strong>Privacy Notice:</strong>
            Please use fictional sample information and files for this academic demonstration.
            Do not upload real identification documents or sensitive personal records.
            <a href="<?= $e($appUrl) ?>/policy/privacy" target="_blank" rel="noopener noreferrer"
               style="color:var(--emu-primary,#4A1F46);">Privacy Policy</a>
        </span>
    </div>

    <div class="d-flex gap-3 align-items-center">
        <button type="submit" class="btn btn-primary btn-lg px-4">
            <i class="bi bi-send me-1" aria-hidden="true"></i> Submit Request
        </button>
        <a href="<?= $e($appUrl) ?>/resident/dashboard" class="btn btn-outline-secondary">Cancel</a>
    </div>

    <?php else: ?>
    <!-- Prompt to select a document first -->
    <div class="text-center py-3" style="color:var(--emu-text-muted);font-size:0.9375rem;">
        <i class="bi bi-arrow-up-circle d-block mb-2" style="font-size:2rem;" aria-hidden="true"></i>
        Please select a document type above to continue.
    </div>
    <?php endif; ?>

</form>

<script>
(function () {
    var appUrl = <?= json_encode(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>;
    var base   = appUrl + '/resident/requests/new';

    // ----------------------------------------------------------------
    // Card click → reload page with ?type_id=N (existing behaviour)
    // ----------------------------------------------------------------
    document.querySelectorAll('input[name="document_type_id"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            window.location.href = base + '?type_id=' + encodeURIComponent(this.value);
        });
    });

    // ----------------------------------------------------------------
    // Search + office filter (client-side, no AJAX)
    // ----------------------------------------------------------------
    var searchInput  = document.getElementById('doc-search');
    var officeSelect = document.getElementById('doc-office-filter');
    var noResults    = document.getElementById('doc-no-results');
    var cards        = document.querySelectorAll('.doc-card-wrap');

    if (searchInput && officeSelect) {
        function applyFilter() {
            var q      = searchInput.value.toLowerCase().trim();
            var office = officeSelect.value.toLowerCase().trim();
            var visible = 0;

            cards.forEach(function (card) {
                var name    = (card.dataset.name   || '').toLowerCase();
                var cardOff = (card.dataset.office || '').toLowerCase();

                var matchSearch = !q      || name.indexOf(q) !== -1;
                var matchOffice = !office || cardOff === office.toLowerCase();

                if (matchSearch && matchOffice) {
                    card.style.display = '';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (noResults) {
                noResults.classList.toggle('d-none', visible > 0);
            }
        }

        searchInput.addEventListener('input',  applyFilter);
        officeSelect.addEventListener('change', applyFilter);
    }

    // ----------------------------------------------------------------
    // File upload — drag & drop + click, compact file rows
    // ----------------------------------------------------------------
    var dropZone  = document.getElementById('drop-zone');
    var fileInput = document.getElementById('file-upload');
    var fileList  = document.getElementById('file-list');
    var fileCount = document.getElementById('file-count');

    if (!dropZone || !fileInput || !fileList) return;

    var MAX_FILES     = 10;
    var MAX_BYTES     = 5 * 1024 * 1024; // 5 MB — mirrors FileUploadService::MAX_BYTES
    var ALLOWED_EXTS  = ['pdf', 'jpg', 'jpeg', 'png'];
    var selectedFiles = []; // Array of File objects

    // -- Icon helpers -------------------------------------------------
    function iconClass(name) {
        var ext = (name.split('.').pop() || '').toLowerCase();
        if (ext === 'pdf')               return 'icon-pdf';
        if (ext === 'jpg' || ext === 'jpeg') return 'icon-jpg';
        if (ext === 'png')               return 'icon-png';
        return '';
    }
    function iconGlyph(name) {
        var ext = (name.split('.').pop() || '').toLowerCase();
        if (ext === 'pdf')               return 'bi-file-earmark-pdf';
        if (ext === 'jpg' || ext === 'jpeg' || ext === 'png') return 'bi-file-earmark-image';
        return 'bi-file-earmark';
    }
    function formatBytes(bytes) {
        if (bytes < 1024)           return bytes + ' B';
        if (bytes < 1024 * 1024)    return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // -- Render the file rows from selectedFiles ----------------------
    function renderList() {
        fileList.innerHTML = '';

        if (selectedFiles.length === 0) {
            fileCount.textContent = '';
            return;
        }

        selectedFiles.forEach(function (file, idx) {
            var row = document.createElement('div');
            row.className = 'file-row';
            row.setAttribute('role', 'listitem');

            var icon = document.createElement('span');
            icon.className = 'file-row-icon ' + iconClass(file.name);
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML = '<i class="bi ' + iconGlyph(file.name) + '"></i>';

            var name = document.createElement('span');
            name.className = 'file-row-name';
            name.title = file.name;
            name.textContent = file.name;

            var size = document.createElement('span');
            size.className = 'file-row-size';
            size.textContent = formatBytes(file.size);

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'file-row-remove';
            removeBtn.setAttribute('aria-label', 'Remove ' + file.name);
            removeBtn.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
            removeBtn.addEventListener('click', function () {
                selectedFiles.splice(idx, 1);
                syncInput();
                renderList();
            });

            row.appendChild(icon);
            row.appendChild(name);
            row.appendChild(size);
            row.appendChild(removeBtn);
            fileList.appendChild(row);
        });

        fileList.setAttribute('role', 'list');
        var n = selectedFiles.length;
        fileCount.textContent = n + ' file' + (n === 1 ? '' : 's') + ' selected';
    }

    // -- Sync the real <input type="file"> with selectedFiles ---------
    // We rebuild a DataTransfer object so the input reflects our list.
    function syncInput() {
        if (typeof DataTransfer !== 'undefined') {
            var dt = new DataTransfer();
            selectedFiles.forEach(function (f) { dt.items.add(f); });
            fileInput.files = dt.files;
        }
        // Fallback: if DataTransfer is unavailable (rare), the native input
        // already has whatever the user chose — server-side handles duplicates.
    }

    // -- Merge new files, enforcing limits (client-side UX only) ------
    function addFiles(newFiles) {
        var warnings = [];

        Array.from(newFiles).forEach(function (file) {
            // Extension check (UX only — server validates MIME)
            var ext = (file.name.split('.').pop() || '').toLowerCase();
            if (ALLOWED_EXTS.indexOf(ext) === -1) {
                warnings.push('"' + file.name + '" is not a supported type (PDF, JPG, PNG only).');
                return;
            }
            // Size check (UX only — server re-validates)
            if (file.size > MAX_BYTES) {
                warnings.push('"' + file.name + '" exceeds 5 MB.');
                return;
            }
            // Deduplicate by name + size
            var exists = selectedFiles.some(function (f) {
                return f.name === file.name && f.size === file.size;
            });
            if (exists) return;

            if (selectedFiles.length >= MAX_FILES) {
                warnings.push('Maximum ' + MAX_FILES + ' files allowed.');
                return;
            }
            selectedFiles.push(file);
        });

        if (warnings.length) {
            alert(warnings.join('\n'));
        }
        syncInput();
        renderList();
    }

    // -- Click to open file picker ------------------------------------
    dropZone.addEventListener('click', function () { fileInput.click(); });
    dropZone.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
    });

    fileInput.addEventListener('change', function () {
        addFiles(this.files);
        // Reset the input value so re-selecting the same file fires change again
        this.value = '';
    });

    // -- Drag and drop ------------------------------------------------
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });
    dropZone.addEventListener('dragleave', function (e) {
        if (!dropZone.contains(e.relatedTarget)) {
            dropZone.classList.remove('drag-over');
        }
    });
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        if (e.dataTransfer && e.dataTransfer.files.length) {
            addFiles(e.dataTransfer.files);
        }
    });

}());
</script>