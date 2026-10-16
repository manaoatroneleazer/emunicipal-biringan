# Implementation Plan: eMunicipal UI/UX Finalization

## Overview

A focused polish pass on the existing PHP/MySQL/XAMPP MVC application. All changes are confined to `public/assets/css/app.css`, `public/assets/js/app.js`, and `views/**/*.php`. No backend logic, controller, or database schema changes are introduced. Tasks are ordered: foundations first, then Resident and Staff views, then Admin views, then error pages.

---

## Tasks

- [x] 1. CSS and JS Foundations
  - [x] 1.1 Add CSS sections §22–26 to `app.css`
    - Append `.role-badge` + role variant classes (`.role-badge-resident`, `.role-badge-staff`, `.role-badge-admin`) using `--emu-*`-compatible palette values (§22)
    - Append `transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease` to `.btn`, `.nav-link`, `.sidebar-nav .nav-link`, `.doc-type-card` (§23)
    - Append `.doc-type-card:focus-within` with `box-shadow: var(--emu-shadow-2)` and `border-color: var(--emu-primary)` (§23)
    - Append `.account-badge-active` and `.account-badge-inactive` classes (§24)
    - Append confirmation modal focus ring styles (§25)
    - Append `.minimal-logo`, `.minimal-logo .logo-icon`, `.minimal-logo .logo-name`, `.minimal-logo .logo-tagline` classes (§26)
    - No hardcoded hex values outside `app.css`; use `var(--emu-*)` tokens wherever possible
    - _Requirements: 1.2, 21.1, 21.3_

  - [ ]* 1.2 Write property test for CSS status badge class completeness
    - **Property 1: Status Badge Class Completeness**
    - **Validates: Requirements 1.1**
    - Render a minimal PHP snippet that emits a status badge for each of the 8 status values and assert that each output contains both `status-badge` and `status-{lowercase_value}` class strings

  - [x] 1.3 Add JS utilities to `app.js`
    - Inside the existing `DOMContentLoaded` listener, append `enableDoubleSubmitPrevention(form)` function that disables the submit button and injects a spinner + loading label on first submit
    - Append auto-init loop: `document.querySelectorAll('form[data-loading]').forEach(enableDoubleSubmitPrevention)`
    - Replace (or merge) the existing simple file upload handler with the block-list variant: for each `input[type="file"][data-file-label]`, on `change`, render each filename as a `<span class="d-block text-muted-sm">` with a `bi-file-earmark` icon inside the referenced element
    - _Requirements: 5.6, 16.1, 16.2, 16.3_

  - [ ]* 1.4 Write property test for double-submit prevention behavior
    - **Property 3: Double-Submit Prevention Behavior**
    - **Validates: Requirements 3.3, 16.1, 16.2, 16.3**
    - Simulate a form submit event on a `<form data-loading="Submitting…">` and assert the submit button has `disabled=true` and `innerHTML` contains `spinner-border` and "Submitting…"

  - [ ]* 1.5 Write property test for file name display after selection
    - **Property 6: File Name Display After Selection**
    - **Validates: Requirements 5.6**
    - Simulate a `change` event on a file input with `data-file-label` carrying two mock `File` objects and assert the target element's innerHTML contains both filenames

- [x] 2. Shared Layout Polish
  - [x] 2.1 Update `views/layouts/auth.php`
    - Add breadcrumb rendering slot at the top of `.auth-content`: iterate `$breadcrumbs` array and render Bootstrap `.breadcrumb` / `.breadcrumb-item` markup with `aria-label="breadcrumb"` and `aria-current="page"` on the last item
    - Add `.sidebar-overlay` `<div>` immediately after the `<nav>` sidebar element for mobile backdrop dismissal
    - Add role badge (`.role-badge .role-badge-{role}`) to the user menu dropdown showing the authenticated user's name and role
    - Ensure every flash alert `<div>` carries `role="alert"` and `aria-live="assertive"`; add Bootstrap Icons icon matching alert type (success→`bi-check-circle`, warning/danger→`bi-exclamation-triangle`, info→`bi-info-circle`)
    - _Requirements: 1.5, 2.1, 2.2, 2.4, 20.2, 20.5_

  - [x] 2.2 Update `views/layouts/minimal.php`
    - Add `.minimal-logo` block above the form card: `.logo-icon` using `bi-buildings` (or equivalent municipality icon), `.logo-name` set to application name, `.logo-tagline` set to a brief municipal service tagline
    - Ensure the form card has `max-width: 440px` and is horizontally and vertically centered within `.minimal-layout`
    - _Requirements: 2.6, 3.1_

  - [ ]* 2.3 Write property test for flash message structure
    - **Property 2: Flash Message Structure**
    - **Validates: Requirements 1.5, 20.5**
    - Render flash alert markup for each of the four types (success, warning, danger, info) and assert each contains `alert`, `alert-flash`, `alert-dismissible`, `role="alert"`, and the correct `bi-*` icon class

- [x] 3. Checkpoint — Foundations complete
  - Ensure `app.css` compiles (no syntax errors visible in browser DevTools), `app.js` has no console errors on load, and `auth.php` and `minimal.php` render correctly. Ask the user if questions arise.

- [x] 4. Resident Views — Dashboard and Request List
  - [x] 4.1 Update `views/resident/dashboard/index.php`
    - Replace any unstyled div metric blocks with Bootstrap grid row of `.metric-card` elements for: Total Requests, Pending, Completed (at minimum)
    - Wrap the recent-requests table in `<div class="table-responsive">`
    - Render status in each recent-request row as `<span class="status-badge status-{lowercase}">` with icon and label
    - Add empty-state `<tr>` (with correct `colspan`) when `$recentRequests` is empty, containing `.empty-state` block with icon, heading, description, and "Submit New Request" CTA link
    - Set `$breadcrumbs = [['label' => 'Dashboard']]` (current page, no url)
    - _Requirements: 2.4, 4.1, 4.2, 4.3, 17.1, 17.2, 17.3, 19.1, 19.4_

  - [x] 4.2 Update `views/resident/requests/index.php`
    - Wrap table in `<div class="table-responsive">`
    - Ensure columns: Reference No., Document Type, Date Submitted, Status, Actions
    - Render status column as Status Badge
    - Add empty-state `<tr>` with icon (`bi-inbox`), "No requests yet" heading, description, and "Submit New Request" button when `$requests` is empty
    - Set `$breadcrumbs = [['label' => 'Dashboard', 'url' => ...], ['label' => 'My Requests']]`
    - _Requirements: 2.4, 6.1, 6.2, 17.1, 17.2, 17.3, 19.1_

  - [ ]* 4.3 Write property test for request list date sort order
    - **Property 7: Request List Date Sort Order**
    - **Validates: Requirements 6.3**
    - Given a mock `$requests` array with out-of-order `created_at` values, assert the rendered rows are in descending timestamp order

  - [ ]* 4.4 Write property test for empty state in table
    - **Property 16: Empty State in Table**
    - **Validates: Requirements 17.1, 17.2, 17.3**
    - Render each list view with an empty data array and assert `<tbody>` contains a single `<tr>` whose `<td colspan=N>` wraps an element with class `empty-state` containing icon, heading, and description elements

- [x] 5. Resident Views — New Request Form
  - [x] 5.1 Update `views/resident/requests/new.php`
    - Group form sections into `.card` elements: "Select Document Type" card, "Request Details" card, "Upload Documents" card
    - Add required-field indicator to every required `<label>`: `<span aria-hidden="true">*</span><span class="visually-hidden">(required)</span>`
    - Add `class="form-label"` to all `<label>` elements
    - Add `autocomplete` attributes to any text inputs where applicable
    - Apply `.is-invalid` + `.invalid-feedback` to fields with errors in `$errors`; render form-level `$errors['form']` as `.alert.alert-danger` above the form if present
    - Add `data-loading="Submitting…"` to the `<form>` element; ensure submit button uses `.btn.btn-primary`
    - Add `data-file-label="file-list"` to the file input and a `<div id="file-list">No files chosen</div>` below it
    - Set `$breadcrumbs = [['label' => 'Dashboard', 'url' => ...], ['label' => 'My Requests', 'url' => ...], ['label' => 'New Request']]`
    - _Requirements: 2.4, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 15.1, 15.2, 15.3, 15.4, 20.1_

  - [ ]* 5.2 Write property test for required field asterisk rendering
    - **Property 4: Required Field Asterisk**
    - **Validates: Requirements 5.2**
    - Render the new request form and assert each required field's `<label>` contains a `<span aria-hidden="true">` with text `*` and a `.visually-hidden` sibling span containing "(required)"

  - [ ]* 5.3 Write property test for form validation error rendering
    - **Property 5: Form Validation Error Rendering**
    - **Validates: Requirements 5.4, 15.3**
    - Pass a mock `$errors` array with errors on several fields, render the form, and assert each errored control has class `is-invalid` and a sibling `.invalid-feedback[role="alert"]` with the correct message

- [x] 6. Resident Views — Request Detail and Track
  - [x] 6.1 Update `views/resident/requests/show.php`
    - Wrap request information (reference, type, purpose, copies, date, status) in `.card` elements
    - Render status as Status Badge
    - Add submission banner: when `?submitted=1` is in the query string render `.alert.alert-success.alert-flash` with `role="alert"` showing reference number
    - Add `.alert.alert-warning` above the timeline when status is `ADDITIONAL_INFO_REQUIRED`
    - Render status history as `.timeline` / `.timeline-item` blocks (each item: Status Badge, formatted date, staff remark if present)
    - Add "Cancel Request" button (`.btn.btn-danger`) that opens a Bootstrap confirmation modal; modal body contains the cancellation `<form data-loading="Cancelling…">` with the confirm button; apply Double-Submit Prevention to that inner form
    - Only show cancel button when status is `PENDING`, `UNDER_VERIFICATION`, or `ADDITIONAL_INFO_REQUIRED`
    - Add `aria-label` to all icon-only buttons (close, cancel trigger)
    - Set `$breadcrumbs = [['label' => 'Dashboard', 'url' => ...], ['label' => 'My Requests', 'url' => ...], ['label' => $refNum]]`
    - _Requirements: 2.4, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 20.2, 20.4_

  - [x] 6.2 Update `views/resident/requests/track.php`
    - Render reference number search form with `.form-label`, `aria-label` on submit button, and `data-loading="Searching…"` on the form
    - When `$request` is found: render Status Badge and `.timeline` / `.timeline-item` status history
    - When no match: render `.alert.alert-warning` with the not-found message
    - Set `$breadcrumbs = [['label' => 'Dashboard', 'url' => ...], ['label' => 'Track Request']]`
    - _Requirements: 2.4, 8.1, 8.2, 8.3_

  - [ ]* 6.3 Write property test for status history timeline completeness
    - **Property 8: Status History Timeline Completeness**
    - **Validates: Requirements 7.2, 10.3**
    - Given a mock `$history` array of N entries, render the timeline and assert exactly N `.timeline-item` elements are present, each containing a `.status-badge` element and a human-readable date string

- [x] 7. Checkpoint — Resident views complete
  - All resident-facing views should be visually polished. Verify: metric cards render on dashboard, empty states appear when data is absent, Status Badges display on all request rows, form validation highlights invalid fields, file upload shows filenames, cancel confirmation modal opens and submits. Ask the user if questions arise.

- [x] 8. Staff Views — Request List
  - [x] 8.1 Update `views/staff/requests/index.php`
    - Wrap table in `<div class="table-responsive">`
    - Ensure columns: Reference No., Resident Name, Document Type, Date Submitted, Status, Actions
    - Render Status column as Status Badge
    - Add filter controls for status as a `<select>` + submit button; serialize filter value into URL query string on submit (`action=""` GET form)
    - Add empty-state `<tr>` (correct `colspan=6`) when no records match the filter
    - Set `$breadcrumbs = [['label' => 'Dashboard', 'url' => ...], ['label' => 'Requests']]`
    - _Requirements: 2.4, 9.1, 9.2, 9.3, 17.1, 17.3, 19.1_

  - [ ]* 8.2 Write property test for filter state preserved in URL
    - **Property 9: Filter State Preserved in URL**
    - **Validates: Requirements 9.3**
    - Submit the filter form with a status value and assert the resulting GET URL contains `status={value}` in the query string

- [x] 9. Staff Views — Request Detail
  - [x] 9.1 Update `views/staff/requests/show.php`
    - Replace single-column layout with Bootstrap two-column row: primary col (`col-md-8`) for request info + documents + timeline; secondary col (`col-md-4`) for workflow action card
    - Render submitted documents as a list: for each document show original filename, file type/MIME, human-readable file size, and `<a>` download link
    - Render status history as `.timeline` / `.timeline-item` (matching Resident detail pattern)
    - Workflow action `<select>` must contain only the options in `$validTransitions`; add remark `<textarea>` with `required` on client side when `REJECTED` or `CANCELLED` is selected (JS `change` event)
    - Add `data-loading="Updating…"` to the workflow form; apply Double-Submit Prevention
    - Add Bootstrap confirmation modal for destructive transitions (REJECTED, CANCELLED): modal triggered on form submit when destructive option is selected; confirm button submits the form
    - Add `aria-label` to all icon-only action buttons
    - Set `$breadcrumbs = [['label' => 'Dashboard', 'url' => ...], ['label' => 'Requests', 'url' => ...], ['label' => $refNum]]`
    - _Requirements: 2.4, 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 20.2, 20.4_

  - [ ]* 9.2 Write property test for document list field completeness
    - **Property 10: Document List Field Completeness**
    - **Validates: Requirements 10.2**
    - Given a mock `$documents` array, render the document list and assert each entry contains filename text, file type label, human-readable size, and an `<a>` element with a download URL

  - [ ]* 9.3 Write property test for valid status transitions only
    - **Property 11: Valid Status Transitions Only**
    - **Validates: Requirements 10.4**
    - For each status value in the transition table, pass the corresponding `$validTransitions` array and assert the rendered `<select>` contains exactly those option values and no others

- [x] 10. Checkpoint — Staff views complete
  - Verify: two-column layout renders at md+ breakpoints, document list shows filename/type/size/download, workflow select only shows valid transitions, confirmation modal fires for REJECTED/CANCELLED, filter persists in URL. Ask the user if questions arise.

- [x] 11. Admin Views — Dashboard and Request Management
  - [x] 11.1 Update `views/admin/dashboard/index.php`
    - Add `.metric-card` grid for four metrics: Total Requests, Pending, Approved, Completed
    - Wrap recent-requests table in `<div class="table-responsive">`; limit display to 10 rows (controller already passes `$recentRequests` limited to 10 — confirm the table renders all passed rows and no more)
    - Render status in each row as Status Badge
    - Add empty-state `<tr>` when `$recentRequests` is empty
    - Set `$breadcrumbs = [['label' => 'Dashboard']]`
    - _Requirements: 2.4, 11.1, 11.2, 11.3, 17.1, 17.3, 19.1, 19.4_

  - [x] 11.2 Update `views/admin/requests/index.php`
    - Wrap table in `<div class="table-responsive">`
    - Ensure columns match Staff list plus an "Assigned Staff" column
    - Render Status Badges; add filter controls for status with URL query string persistence
    - Add empty-state `<tr>` when no matching records
    - Set `$breadcrumbs = [['label' => 'Dashboard', 'url' => ...], ['label' => 'All Requests']]`
    - _Requirements: 2.4, 13.1, 13.2, 17.1, 17.3, 19.1_

  - [x] 11.3 Update `views/admin/requests/show.php`
    - Apply card-and-timeline layout matching Staff Request Detail (two-column: primary col-md-8, secondary col-md-4)
    - Render document list with filename, file type, size, download link
    - Render status history timeline with `.timeline` / `.timeline-item` and Status Badges
    - Set `$breadcrumbs = [['label' => 'Dashboard', 'url' => ...], ['label' => 'All Requests', 'url' => ...], ['label' => $refNum]]`
    - _Requirements: 2.4, 13.3_

  - [ ]* 11.4 Write property test for admin dashboard recent requests limit
    - **Property 12: Admin Dashboard Recent Requests Limit**
    - **Validates: Requirements 11.3**
    - Pass a mock `$recentRequests` array of 10 items and assert the rendered `<tbody>` contains exactly 10 data `<tr>` elements (no empty state row)

- [x] 12. Admin Views — User Management
  - [x] 12.1 Update `views/admin/users/index.php`
    - Wrap table in `<div class="table-responsive">`
    - Ensure columns: Name, Email, Role (`.role-badge`), Account Status (`.account-badge-active` / `.account-badge-inactive`), Registration Date, Actions
    - Add empty-state `<tr>` when `$users` is empty
    - Add Bootstrap confirmation modal for activate/deactivate action forms; apply `data-loading="Saving…"` to those forms
    - Set `$breadcrumbs = [['label' => 'Dashboard', 'url' => ...], ['label' => 'Users']]`
    - _Requirements: 2.4, 12.1, 12.2, 12.3, 17.1, 17.3, 19.1_

  - [x] 12.2 Update `views/admin/users/create.php`
    - Add `class="form-label"` to all `<label>` elements
    - Add required-field indicators (`*` with `visually-hidden`) to all required labels
    - Add `autocomplete` attributes to name, email, and password fields
    - Apply `.is-invalid` + `.invalid-feedback` for each field in `$errors`; render form-level error as `.alert.alert-danger`
    - Add `data-loading="Creating…"` to the form; submit button `.btn.btn-primary`
    - Set `$breadcrumbs = [['label' => 'Dashboard', 'url' => ...], ['label' => 'Users', 'url' => ...], ['label' => 'Create User']]`
    - _Requirements: 2.4, 12.4, 15.1, 15.2, 15.3, 15.4, 20.1_

- [x] 13. Admin Views — Document Types and Requirements
  - [x] 13.1 Update `views/admin/document-types/index.php`
    - Wrap table in `<div class="table-responsive">`
    - Ensure columns: Name, Office/Category, Active Status (styled badge), Actions (Edit, Manage Requirements)
    - Add Bootstrap confirmation modal for active status toggle forms; apply `data-loading="Saving…"` to those forms
    - Add empty-state `<tr>` when `$documentTypes` is empty
    - Set `$breadcrumbs = [['label' => 'Dashboard', 'url' => ...], ['label' => 'Document Types']]`
    - _Requirements: 2.4, 14.1, 14.2, 17.1, 17.3, 19.1_

  - [x] 13.2 Update `views/admin/document-requirements/index.php`
    - Group requirements under a visible heading element (`<h5>` or `<h6>`) whose text matches the parent document type name
    - Wrap each group's requirement list in `<div class="table-responsive">`
    - _Requirements: 14.3_

  - [ ]* 13.3 Write property test for requirements grouped under document type
    - **Property 13: Requirements Grouped Under Document Type**
    - **Validates: Requirements 14.3**
    - Given a mock `$requirementsByType` array with requirements under two different document type names, render the page and assert each requirement appears under a heading element matching its document type name and not the other

- [x] 14. Checkpoint — Admin views complete
  - Verify: all four metric cards on admin dashboard, 10-row recent requests table, user list shows role and status badges, create user form validates inline, document types show toggle confirmation, requirements are grouped under headings. Ask the user if questions arise.

- [x] 15. Error Pages
  - [x] 15.1 Refactor `views/errors/404.php`
    - Add `<?php http_response_code(404); ?>` at the top before layout include
    - Render using `views/layouts/public.php` (header + nav + footer)
    - Display: "404 — Page Not Found" heading, plain-language explanation, "Return to Homepage" link (`.btn.btn-primary`)
    - _Requirements: 18.1, 18.4_

  - [x] 15.2 Refactor `views/errors/403.php`
    - Add `<?php http_response_code(403); ?>` at the top before layout include
    - Render using `views/layouts/public.php`
    - Display: "403 — Access Denied" heading, plain-language explanation, "Return to Homepage" link (`.btn.btn-primary`)
    - _Requirements: 18.2, 18.4_

  - [x] 15.3 Refactor `views/errors/500.php`
    - Add `<?php http_response_code(500); ?>` at the top
    - Render using `views/layouts/public.php`
    - Display: "Something went wrong" heading, generic explanation with no stack trace, exception class names, or file path information in the HTML output
    - _Requirements: 18.3, 18.4_

  - [ ]* 15.4 Write property test for correct HTTP status codes on error pages
    - **Property 18: Correct HTTP Status Codes for Error Pages**
    - **Validates: Requirements 18.4**
    - For each error page file, assert that `http_response_code()` is called with the matching numeric status (404, 403, 500) before any output is sent

  - [ ]* 15.5 Write property test for no stack trace in 500 page output
    - **Property 17: No Stack Trace in 500 Page Output**
    - **Validates: Requirements 18.3**
    - Render the 500 page with no error variables injected and assert the output HTML does not match patterns for Windows/Unix file paths, PHP exception class names, or `#N` stack trace lines

- [x] 16. Final Checkpoint — Full pass complete
  - Run a full browser walkthrough as each role (Resident, Staff, Admin). Verify: no console errors, all Status Badges render, empty states appear, breadcrumbs show on every authenticated page, confirmation modals fire for destructive actions, double-submit prevention triggers on form submit, error pages return correct HTTP status codes. Ask the user if questions arise.

---

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP pass
- All CSS changes are append-only to `app.css`; no existing rules are removed
- All JS changes are appended inside the existing `DOMContentLoaded` listener in `app.js`
- The design has 22 correctness properties; property tests marked above cover the most mechanically verifiable subset
- PHP view files receive data exclusively from controllers — views must not add queries or business logic
- Bootstrap `confirm()` is NOT used; all confirmation flows use Bootstrap Modal (`.modal`)
- `table-responsive` wrapper is required on every table (Requirement 19.1 / Property 19)
- `$breadcrumbs` array must be set in every view that uses `auth.php` (Requirement 2.4)

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.3"] },
    { "id": 1, "tasks": ["1.2", "1.4", "1.5", "2.1", "2.2"] },
    { "id": 2, "tasks": ["2.3", "4.1", "4.2", "5.1", "6.1", "6.2"] },
    { "id": 3, "tasks": ["4.3", "4.4", "5.2", "5.3", "6.3", "8.1", "9.1"] },
    { "id": 4, "tasks": ["8.2", "9.2", "9.3", "11.1", "11.2", "11.3", "12.1", "12.2", "13.1", "13.2"] },
    { "id": 5, "tasks": ["11.4", "13.3", "15.1", "15.2", "15.3"] },
    { "id": 6, "tasks": ["15.4", "15.5"] }
  ]
}
```
