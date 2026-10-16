# Design Document

## Feature: eMunicipal UI/UX Finalization

---

## 1. Overview

This document describes the technical implementation of the UI/UX polish pass for the eMunicipal system. The backend is complete; no controller logic, database schema, or route changes are introduced unless a critical fix is explicitly required.

The implementation targets three layers:

1. **`public/assets/css/app.css`** — all CSS additions and modifications
2. **`public/assets/js/app.js`** — all JS additions (reusable functions, event wiring)
3. **`views/**/*.php`** — in-place edits to view files to apply design system components consistently

All changes operate within the existing constraints: PHP views, Bootstrap 5 (CDN), Vanilla JS, and the established `--emu-*` CSS token system.

---

## 2. Architecture

### 2.1 Layer Diagram

```
┌───────────────────────────────────────────────┐
│  Browser                                      │
│  ┌─────────────────────────────────────────┐  │
│  │  Bootstrap 5 (CDN)  +  Bootstrap Icons  │  │
│  │  app.css (design tokens + components)   │  │
│  │  app.js  (reusable UI behaviors)        │  │
│  └─────────────────────────────────────────┘  │
│                    ▲                           │
│                    │  HTML rendered by PHP     │
│  ┌─────────────────────────────────────────┐  │
│  │  views/layouts/auth.php                 │  │
│  │  views/layouts/minimal.php              │  │
│  │  views/layouts/public.php               │  │
│  │  views/resident/**/*.php                │  │
│  │  views/staff/**/*.php                   │  │
│  │  views/admin/**/*.php                   │  │
│  │  views/errors/404|403|500.php           │  │
│  └─────────────────────────────────────────┘  │
└───────────────────────────────────────────────┘
```

### 2.2 Layout Files

| Layout File | Used By | Key Structural Additions |
|---|---|---|
| `views/layouts/auth.php` | All authenticated pages | Breadcrumb slot, sidebar overlay, role badge in user menu |
| `views/layouts/minimal.php` | Login, Register | Logo/name + tagline above card, max-width 440px card |
| `views/layouts/public.php` | Public pages, Error pages | Already complete — footer fully populated |

### 2.3 CSS Architecture

All new CSS lives in `public/assets/css/app.css`, appended after the existing sections. New sections are numbered sequentially (§22 onwards). No hardcoded hex or RGB values are introduced in view files; all inline `style="..."` color references must use `var(--emu-*)` tokens.

### 2.4 JS Architecture

`public/assets/js/app.js` is extended with three reusable utility functions, all registered inside the existing `DOMContentLoaded` listener:

1. **`enableDoubleSubmitPrevention(form)`** — called on any `<form data-loading>`, disables the submit button on first submit and injects a spinner.
2. **`initConfirmModal(triggerId, modalId)`** — wires a trigger element to open a Bootstrap modal; focus is moved to the first focusable element inside the modal.
3. Auto-init: on `DOMContentLoaded`, `document.querySelectorAll('form[data-loading]')` calls `enableDoubleSubmitPrevention` on each found form.

---

## 3. Components

### 3.1 Status Badge

**Location:** Defined in `app.css §10`, used in every view where a request status is displayed.

**HTML Pattern:**
```php
<span class="status-badge status-<?= strtolower($status) ?>">
    <i class="bi <?= $iconMap[$status] ?>" aria-hidden="true"></i>
    <?= htmlspecialchars($statusLabels[$status], ENT_QUOTES, 'UTF-8') ?>
</span>
```

All eight status values are covered: `pending`, `under_verification`, `additional_info_required`, `approved`, `rejected`, `ready_for_claiming`, `completed`, `cancelled`.

Status badges have no hover or animation effects — they are display-only indicators.

### 3.2 Flash Messages

**Location:** Rendered in `views/layouts/auth.php` from session flash data.

**HTML Pattern:**
```html
<div class="alert alert-{type} alert-dismissible alert-flash fade show mb-3" 
     role="alert" aria-live="assertive">
    <i class="bi bi-{icon} me-2" aria-hidden="true"></i>
    {message}
    <button type="button" class="btn-close" data-bs-dismiss="alert" 
            aria-label="Close alert"></button>
</div>
```

Icon mapping by type: `success → bi-check-circle`, `warning → bi-exclamation-triangle`, `danger → bi-exclamation-triangle`, `info → bi-info-circle`.

Auto-dismiss after 5 seconds via the existing `app.js` logic.

### 3.3 Double-Submit Prevention

**Location:** `app.js`

**Implementation:**
```javascript
function enableDoubleSubmitPrevention(form) {
    form.addEventListener('submit', function () {
        var btn = form.querySelector('[type="submit"]');
        if (!btn || btn.disabled) return;
        var label = form.dataset.loading || 'Processing…';
        btn.disabled = true;
        btn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" '
            + 'role="status" aria-hidden="true"></span>'
            + label;
    });
}
```

Applied via `data-loading="Submitting…"` (or context-specific label) on the `<form>` element. Auto-wired on `DOMContentLoaded` for all matching forms.

### 3.4 Confirmation Modal

**Location:** Per-view PHP (modal markup), wired in `app.js`.

Bootstrap Modal is used — no `browser confirm()`. The modal includes:
- `aria-labelledby` on `.modal` referencing the `.modal-title` id
- `aria-modal="true"` attribute
- Focus moves to the first focusable element (Bootstrap handles this natively via `shown.bs.modal` event)
- Destructive action button is `.btn.btn-danger`

For forms where confirmation wraps a POST (e.g. cancel request, user activate/deactivate, document type toggle), the confirm button is a `type="submit"` inside the form rendered in the modal body. Double-submit prevention is also applied to that inner form.

### 3.5 Empty State

**Location:** `app.css §20`, rendered in each list/table view.

**HTML Pattern (inside table):**
```html
<tr>
    <td colspan="{N}" class="p-0">
        <div class="empty-state">
            <i class="bi bi-{icon}" aria-hidden="true"></i>
            <h5>{Heading}</h5>
            <p>{Explanation}</p>
            <!-- Optional CTA -->
            <a href="{url}" class="btn btn-primary btn-sm">{Action Label}</a>
        </div>
    </td>
</tr>
```

The `colspan` value must match the exact column count of the enclosing table header.

### 3.6 Breadcrumbs

**Location:** Each authenticated view passes a `$breadcrumbs` array; the breadcrumb is rendered as the first element inside `.auth-content`.

**PHP Pattern (in view files):**
```php
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $appUrl . '/resident/dashboard'],
    ['label' => 'My Requests', 'url' => $appUrl . '/resident/requests'],
    ['label' => $refNum], // current page — no url key
];
```

**Rendered Output (in auth.php or top of each view):**
```html
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <?php foreach ($breadcrumbs as $crumb): ?>
        <?php if (isset($crumb['url'])): ?>
        <li class="breadcrumb-item">
            <a href="<?= htmlspecialchars($crumb['url'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        </li>
        <?php else: ?>
        <li class="breadcrumb-item active" aria-current="page">
            <?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?>
        </li>
        <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
```

Note: Several views (e.g. `new.php`, `show.php`) already render their own breadcrumbs inline. During the polish pass, these are standardized to the `$breadcrumbs` array pattern without duplicating the render in the layout.

### 3.7 Status History Timeline

**Location:** `app.css §14`, used in resident request detail, staff request detail, admin request detail, and track request pages.

**PHP Pattern:**
```php
<div class="timeline">
    <?php foreach ($history as $idx => $entry):
        $isCurrent   = ($idx === count($history) - 1) && !in_array($lastStatus, $terminalStatuses);
        $cssClass    = match($entry['new_status']) {
            'rejected'  => 'rejected',
            'cancelled' => 'cancelled',
            default     => $isCurrent ? 'current' : 'completed',
        };
    ?>
    <div class="timeline-item <?= $cssClass ?>">
        <div class="fw-600" style="font-size:0.875rem;">
            <span class="status-badge status-<?= strtolower($entry['new_status']) ?>">
                <?= $statusLabels[$entry['new_status']] ?? ucfirst(str_replace('_', ' ', $entry['new_status'])) ?>
            </span>
        </div>
        <div class="text-muted-sm mt-1">
            <?= date('M j, Y \a\t g:i A', strtotime($entry['created_at'])) ?>
            · <?= ucfirst($entry['changed_by_role'] ?? 'System') ?>
        </div>
        <?php if (!empty($entry['remark'])): ?>
        <div class="mt-1 p-2 rounded" style="background:var(--emu-bg-alt);font-size:0.8125rem;">
            <em>"<?= htmlspecialchars($entry['remark'], ENT_QUOTES, 'UTF-8') ?>"</em>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
```

### 3.8 File Upload Display

**Location:** `app.js` (already implemented via `data-file-label`).

When a file input with `data-file-label="{id}"` changes, the element with the matching `id` is updated with comma-separated filenames. Each filename is displayed individually if multiple files are selected:

```javascript
// Extended in app.js to show files as a list:
var listHtml = Array.from(this.files).map(function(f) {
    return '<span class="d-block text-muted-sm">'
        + '<i class="bi bi-file-earmark me-1" aria-hidden="true"></i>'
        + f.name + '</span>';
}).join('');
label.innerHTML = listHtml || 'No files chosen';
```

### 3.9 Required Field Indicator

**Location:** View files, adjacent to `<label>` for required fields.

**HTML Pattern:**
```html
<label for="purpose" class="form-label">
    Purpose of Request <span class="text-danger" aria-hidden="true">*</span>
    <span class="visually-hidden">(required)</span>
</label>
```

The `visually-hidden` span ensures screen readers announce the required state without relying on the visual asterisk alone.

### 3.10 Form Validation Error Rendering

**Location:** View files, adjacent to each validated `<input>`, `<select>`, or `<textarea>`.

**PHP Pattern:**
```php
<input type="text" id="field_id" name="field_name"
       class="form-control<?= !empty($errors['field_name']) ? ' is-invalid' : '' ?>"
       value="<?= htmlspecialchars($old['field_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
       aria-describedby="<?= !empty($errors['field_name']) ? 'field-error' : 'field-help' ?>">
<div class="form-text" id="field-help">{Helper text}</div>
<?php if (!empty($errors['field_name'])): ?>
<div id="field-error" class="invalid-feedback" role="alert">
    <?= htmlspecialchars($errors['field_name'], ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>
```

---

## 4. Interfaces

### 4.1 Layout Variables Contract

Each view file that uses `auth.php` must provide:

| Variable | Type | Description |
|---|---|---|
| `$title` | `string` | Page `<title>` value |
| `$content` | `string` | Rendered HTML content |
| `$activePage` | `string` | Sidebar active link key |
| `$breadcrumbs` | `array` | Array of `['label' => string, 'url' => string?]` |

Each view file that uses `minimal.php` must provide:

| Variable | Type | Description |
|---|---|---|
| `$title` | `string` | Page `<title>` value |
| `$content` | `string` | Rendered HTML content |

### 4.2 View-to-JS Interface

Views communicate with `app.js` exclusively through HTML data attributes:

| Attribute | Element | Behavior |
|---|---|---|
| `data-loading="Label…"` | `<form>` | Enables double-submit prevention; value becomes the loading button label |
| `data-pw-toggle="{inputId}"` | `<button>` | Toggles password visibility on the referenced input |
| `data-file-label="{elementId}"` | `<input type="file">` | Updates the referenced element with selected filenames |
| `data-bs-toggle="modal"` | `<button>` | Bootstrap-native modal trigger (no custom attribute needed) |

### 4.3 Status Transition Table (Staff Workflow Form)

The workflow action form renders only valid next-status options based on current status. This logic is already in the backend controller; the view receives `$validTransitions` as an array of valid next-status strings.

| Current Status | Valid Transitions |
|---|---|
| `PENDING` | `UNDER_VERIFICATION`, `ADDITIONAL_INFO_REQUIRED`, `REJECTED`, `CANCELLED` |
| `UNDER_VERIFICATION` | `ADDITIONAL_INFO_REQUIRED`, `APPROVED`, `REJECTED` |
| `ADDITIONAL_INFO_REQUIRED` | `UNDER_VERIFICATION`, `REJECTED`, `CANCELLED` |
| `APPROVED` | `READY_FOR_CLAIMING` |
| `READY_FOR_CLAIMING` | `COMPLETED` |
| `REJECTED` | *(terminal — no transitions)* |
| `COMPLETED` | *(terminal — no transitions)* |
| `CANCELLED` | *(terminal — no transitions)* |

---

## 5. Data Models

No new database schema changes. The views consume existing model data passed from controllers:

| View Section | Primary Variables | Source Model |
|---|---|---|
| Status badges | `$request['status']` (string) | `Request` |
| Status history timeline | `$history` (array of `RequestStatusHistory` rows) | `RequestStatusHistory` |
| Document list | `$documents` (array of `RequestDocument` rows) | `RequestDocument` |
| Metric cards | `$metrics` (assoc array of counts) | `Request`, `User` |
| Breadcrumbs | `$breadcrumbs` (view-local array) | N/A — constructed in view |
| Flash messages | Session flash via `SessionHelper::getFlash()` | Session |

---

## 6. Error Handling

### 6.1 Error Pages

All three error pages (`404.php`, `403.php`, `500.php`) are refactored to use `views/layouts/public.php` for consistent header and footer. Each sets the correct HTTP status code before rendering:

```php
<?php http_response_code(404); // or 403, 500 ?>
```

The 500 page must never output exception messages, stack traces, file paths, or PHP error details in the HTML body. The controller/bootstrap layer is responsible for catching exceptions; the view receives no error detail variables.

### 6.2 Form Validation Errors

Validation errors are passed from controllers as `$errors` (field-keyed associative array) and `$old` (previous input values). Views apply `.is-invalid` and `.invalid-feedback` per field, and a top-level form-level error (if present in `$errors['form']`) as an `.alert.alert-danger` above the form.

### 6.3 Empty Data Sets

Every list or table view guards its table body with an empty-state check. The `$items` (or equivalent) variable is checked with `empty()`. When empty, the `<tbody>` renders a single `<tr>` containing the `.empty-state` block instead of data rows.

---

## 7. File Change Inventory

The following files are modified or newly consistent during this pass:

### CSS/JS
- `public/assets/css/app.css` — Additions: `.doc-type-card :focus-within` shadow, `.role-badge`, `.form-required-star`, `.btn-sidebar-toggle` focus ring, `transition` properties for interactive elements, confirmation modal focus styles.
- `public/assets/js/app.js` — Additions: `enableDoubleSubmitPrevention()`, extended file upload display (list format), modal focus initialization.

### Layouts
- `views/layouts/auth.php` — Add breadcrumb slot rendering, add role badge to user menu, ensure `role="alert"` on all flash alerts, add `aria-live="assertive"` to flash container.
- `views/layouts/minimal.php` — Add logo/name + tagline above the form card.
- `views/layouts/public.php` — Already complete per current implementation.

### Resident Views
- `views/resident/dashboard/index.php` — `.metric-card` grid, recent requests table with Status Badges, empty state, `table-responsive` wrapper.
- `views/resident/requests/index.php` — `table-responsive`, Status Badges, empty state inside `<tr>`, `data-loading` on any action forms.
- `views/resident/requests/new.php` — `data-loading` on submit form, required-field indicators verified, file upload list display.
- `views/resident/requests/show.php` — `alert-flash` on submission banner, `data-loading` on cancellation form, `role="alert"` on alerts.
- `views/resident/requests/track.php` — Timeline rendering, Status Badge, not-found alert.

### Staff Views
- `views/staff/requests/index.php` — `table-responsive`, Status Badges, empty state, filter URL persistence.
- `views/staff/requests/show.php` — Two-column layout, document list (filename/type/size/download), timeline, `$validTransitions`-filtered options, `data-loading` on workflow form, confirmation modal for destructive transitions.

### Admin Views
- `views/admin/dashboard/index.php` — `.metric-card` grid with all four metrics, recent requests table (max 10), empty state, Status Badges.
- `views/admin/requests/index.php` — Equivalent to Staff list + assigned staff column, empty state.
- `views/admin/requests/show.php` — Card-and-timeline layout matching Staff detail.
- `views/admin/users/index.php` — Role badge, account status badge, `table-responsive`, empty state, confirmation modal for activate/deactivate.
- `views/admin/users/create.php` — Required-field indicators, `.is-invalid`/`.invalid-feedback`, `data-loading`.
- `views/admin/document-types/index.php` — Active status badge, confirmation modal on toggle, `table-responsive`.
- `views/admin/document-requirements/index.php` — Requirements grouped under document type heading.

### Error Pages
- `views/errors/404.php` — Refactor to use `public.php` layout; set `http_response_code(404)`.
- `views/errors/403.php` — Refactor to use `public.php` layout; set `http_response_code(403)`.
- `views/errors/500.php` — Refactor to use `public.php` layout; set `http_response_code(500)`; no error detail in output.

---

## 8. CSS Additions (app.css §22+)

New CSS sections added to the end of `app.css`:

```css
/* ================================================================
   22. ROLE BADGE (user menu and user list)
   ================================================================ */
.role-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.role-badge-resident { background: #dbeafe; color: #1d4ed8; }
.role-badge-staff    { background: #fef3c7; color: #92400e; }
.role-badge-admin    { background: #f3e8ff; color: #6d28d9; }

/* ================================================================
   23. INTERACTIVE ELEMENT TRANSITIONS
   ================================================================ */
.btn,
.nav-link,
.sidebar-nav .nav-link,
.doc-type-card {
    transition: background-color 0.15s ease, color 0.15s ease,
                border-color 0.15s ease, box-shadow 0.15s ease !important;
}

/* doc-type-card keyboard accessibility */
.doc-type-card:focus-within {
    box-shadow: var(--emu-shadow-2);
    border-color: var(--emu-primary);
    outline: none;
}

/* ================================================================
   24. ACCOUNT STATUS BADGE (user list)
   ================================================================ */
.account-badge-active   { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }
.account-badge-inactive { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }

/* ================================================================
   25. CONFIRMATION MODAL FOCUS RING
   ================================================================ */
.modal:focus { outline: none; }
.modal .btn:focus-visible {
    box-shadow: 0 0 0 3px rgba(37, 99, 168, 0.35) !important;
}

/* ================================================================
   26. MINIMAL LAYOUT LOGO AREA
   ================================================================ */
.minimal-logo {
    text-align: center;
    margin-bottom: 1.5rem;
}
.minimal-logo .logo-icon {
    font-size: 2.5rem;
    color: var(--emu-primary);
    display: block;
    margin-bottom: 0.5rem;
}
.minimal-logo .logo-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--emu-primary);
}
.minimal-logo .logo-tagline {
    font-size: 0.875rem;
    color: var(--emu-text-muted);
    margin-top: 0.25rem;
}
```

---

## 9. JS Additions (app.js)

New functions appended inside the existing `DOMContentLoaded` listener:

```javascript
// ============================================================
// Double-submit prevention (Req 16.1)
// Applied automatically to all forms with data-loading attribute
// ============================================================
function enableDoubleSubmitPrevention(form) {
    form.addEventListener('submit', function () {
        var btn = form.querySelector('[type="submit"]');
        if (!btn || btn.disabled) return;
        var loadingLabel = form.dataset.loading || 'Processing…';
        btn.disabled = true;
        btn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" '
            + 'role="status" aria-hidden="true"></span>'
            + loadingLabel;
    }, { once: false });
}

document.querySelectorAll('form[data-loading]').forEach(function (form) {
    enableDoubleSubmitPrevention(form);
});

// ============================================================
// Extended file upload: show filenames as a block list (Req 5.6)
// ============================================================
document.querySelectorAll('input[type="file"][data-file-label]').forEach(function (input) {
    input.addEventListener('change', function () {
        var labelEl = document.getElementById(this.dataset.fileLabel);
        if (!labelEl) return;
        if (!this.files || this.files.length === 0) {
            labelEl.textContent = 'No files chosen';
            return;
        }
        labelEl.innerHTML = Array.from(this.files).map(function (f) {
            return '<span class="d-block text-muted-sm">'
                + '<i class="bi bi-file-earmark me-1" aria-hidden="true"></i>'
                + f.name + '</span>';
        }).join('');
    });
});
// (Replaces the simpler version above; the existing handler is removed/merged)
```

---

## 10. Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Status Badge Class Completeness

*For any* request status value in the set `{PENDING, UNDER_VERIFICATION, ADDITIONAL_INFO_REQUIRED, APPROVED, REJECTED, READY_FOR_CLAIMING, COMPLETED, CANCELLED}`, the rendered HTML output should contain an element with both the class `status-badge` and the class `status-{value_in_lowercase}`.

**Validates: Requirements 1.1**

---

### Property 2: Flash Message Structure

*For any* flash message of type success, warning, danger, or info, the rendered HTML element should carry the classes `alert`, `alert-flash`, and `alert-dismissible`; should include `role="alert"`; and should contain a Bootstrap Icons `<i>` element whose class matches the flash type (success → `bi-check-circle`, warning/danger → `bi-exclamation-triangle`, info → `bi-info-circle`).

**Validates: Requirements 1.5, 20.5**

---

### Property 3: Double-Submit Prevention Behavior

*For any* `<form>` element carrying the `data-loading` attribute, after the `submit` event fires, the form's submit button should have its `disabled` attribute set to `true` and its inner HTML should contain a `spinner-border` element and the loading label text.

**Validates: Requirements 3.3, 16.1, 16.2, 16.3**

---

### Property 4: Required Field Asterisk

*For any* form field that is marked as required, the associated `<label>` element should contain a `<span>` with `aria-hidden="true"` and text content `*`, and a visually-hidden sibling span announcing `(required)` to screen readers.

**Validates: Requirements 5.2**

---

### Property 5: Form Validation Error Rendering

*For any* form field that has a server-side validation error, the field's control element (`<input>`, `<select>`, or `<textarea>`) should carry the class `is-invalid`, and a sibling element with class `invalid-feedback` and `role="alert"` should contain the specific error message for that field.

**Validates: Requirements 5.4, 15.3**

---

### Property 6: File Name Display After Selection

*For any* set of files selected via a file input with `data-file-label`, after the `change` event fires, the element identified by the `data-file-label` value should contain each selected file's name.

**Validates: Requirements 5.6**

---

### Property 7: Request List Date Sort Order

*For any* collection of requests rendered in the My Requests list, the rows should appear in descending order of their `created_at` timestamp — meaning no row's timestamp should be earlier than the timestamp of the row that precedes it.

**Validates: Requirements 6.3**

---

### Property 8: Status History Timeline Completeness

*For any* non-empty status history array, each history entry should produce exactly one `.timeline-item` element in the rendered timeline; each `.timeline-item` should contain a Status Badge for the entry's `new_status` value and a human-readable date string derived from the entry's `created_at` field.

**Validates: Requirements 7.2, 10.3**

---

### Property 9: Filter State Preserved in URL

*For any* status filter value submitted on the Staff Request List page, the resulting page URL query string should contain `status={value}` — ensuring the filter can be bookmarked and shared.

**Validates: Requirements 9.3**

---

### Property 10: Document List Field Completeness

*For any* uploaded document associated with a request, the rendered document list entry should display: the original filename, the MIME type or derived file type label, the file size in human-readable form, and an anchor element linking to the file download URL.

**Validates: Requirements 10.2**

---

### Property 11: Valid Status Transitions Only

*For any* current request status value, the workflow action form rendered for Staff should contain `<option>` elements only for status values that are valid next transitions from that current status, as defined in the transition table (§4.3). No option for an invalid transition should appear in the rendered select element.

**Validates: Requirements 10.4**

---

### Property 12: Admin Dashboard Recent Requests Limit

*For any* set of system-wide requests, the recent requests table on the Admin Dashboard should contain at most 10 data rows (excluding empty-state and header rows).

**Validates: Requirements 11.3**

---

### Property 13: Requirements Grouped Under Document Type

*For any* collection of document requirements belonging to multiple document types, the rendered Document Requirements List page should display each requirement under a heading element whose text matches the requirement's parent document type name — ensuring no requirement appears under the wrong heading.

**Validates: Requirements 14.3**

---

### Property 14: Form Label Class Applied

*For any* `<label>` element inside a form in any view file, the element should carry the class `form-label`.

**Validates: Requirements 15.1**

---

### Property 15: Helper Text Class Applied

*For any* form field that has associated helper text, the helper text element should be a `<div>` (or `<span>`) with the class `form-text`, placed as a sibling immediately after the form control element.

**Validates: Requirements 15.2**

---

### Property 16: Empty State in Table

*For any* table-based list view rendered with an empty data collection, the `<tbody>` should contain a single `<tr>` whose sole `<td>` has a `colspan` attribute equal to the number of columns in that table's `<thead>`, and the `<td>` should contain an element with the class `empty-state` that includes an icon, a heading, and a descriptive sentence.

**Validates: Requirements 17.1, 17.2, 17.3**

---

### Property 17: No Stack Trace in 500 Page Output

*For any* server error condition that triggers the 500 error page, the rendered HTML response body should not contain file path strings (patterns matching `/[A-Za-z]:\\` or `/var/www/`), PHP exception class names (patterns matching `Exception`, `Error`, `Throwable`), or stack trace line patterns (patterns matching `#\d+` followed by a file path).

**Validates: Requirements 18.3**

---

### Property 18: Correct HTTP Status Codes for Error Pages

*For any* error page type (404, 403, 500), the HTTP response status code sent in the response header should numerically match the error type — 404 for not found, 403 for forbidden, 500 for server error.

**Validates: Requirements 18.4**

---

### Property 19: Table Responsive Wrapper

*For any* `<table>` element rendered in any view file, its immediate parent element should carry the class `table-responsive`.

**Validates: Requirements 19.1**

---

### Property 20: Label-For Association

*For any* `<input>`, `<select>`, or `<textarea>` element that has an `id` attribute, there should exist a `<label>` element in the same form with a `for` attribute whose value equals that `id`.

**Validates: Requirements 20.1**

---

### Property 21: ARIA Label on Icon-Only Buttons

*For any* `<button>` or `<a>` element whose visible content consists only of an icon element (`<i class="bi ...">`) with no adjacent non-whitespace text node, the button or anchor should carry an `aria-label` attribute with a non-empty, descriptive value.

**Validates: Requirements 20.2**

---

### Property 22: Focus Trap in Confirmation Modals

*For any* Bootstrap modal opened via a trigger on the page, keyboard focus should be constrained to interactive elements within the modal's `.modal-dialog` — Tab key should cycle through focusable elements inside the modal and not reach elements outside it until the modal is dismissed.

**Validates: Requirements 20.4**
