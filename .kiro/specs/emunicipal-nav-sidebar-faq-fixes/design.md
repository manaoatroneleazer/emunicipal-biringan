# eMunicipal Nav / Sidebar / FAQ Fixes — Bugfix Design

## Overview

The eMunicipal application has a cluster of related, view-layer-only defects spanning all
three authenticated portals (Resident, Staff, Admin) and the public landing page.

The root issues are:

1. **Duplicate branding** — `views/layouts/auth.php` renders both a fixed top `<header
   class="auth-header">` and a dark `<nav class="sidebar">`. The sidebar already contains the
   brand logo, portal label, all navigation links, profile, and logout for every role. The
   header is entirely redundant and must be removed. Removing it also requires corresponding
   CSS adjustments so content fills the freed space.

2. **Missing mobile toggle** — the hamburger button lives inside the header. After header
   removal, a replacement toggle must be embedded in the main content area.

3. **Missing Resident "Track Request" sidebar link** — the route `/resident/track` and its
   view already exist; a sidebar entry is simply absent.

4. **Incomplete FAQ** — the landing page accordion has 6 entries; 15 are required to cover
   all system-supported functionality.

5. **Native `window.confirm()` dialog on "Begin Verification"** — `app.js` intercepts all
   `form[data-confirm]` submit events and calls the native dialog. The Admin request-detail
   page uses this for "Begin Verification". It must be replaced with a Bootstrap modal
   consistent with the existing Reject/Cancel modals.

6. **Generic terminal-state messaging** — all three portals show the same lock-icon message
   regardless of whether a request is `completed` or `cancelled`. Messages must be
   status-specific for Admin, Staff, and Resident views.

All fixes are purely in the view layer (`views/`, `public/assets/css/app.css`,
`public/assets/js/app.js`). No database schema, controller, route, or authentication logic
changes are required.

---

## Glossary

- **Bug_Condition (C)**: Any `PageRenderContext` that satisfies `isBugCondition()` — see the
  formal specification in `bugfix.md`. The composite condition covers the six defect classes
  above.
- **Property (P)**: The desired rendering outcome for each bug class — header absent, sidebar
  complete, FAQ expanded, Bootstrap modal used, status-specific message shown.
- **Preservation**: All existing behaviour that must remain unchanged — routing, CSRF forms,
  sidebar active-link highlighting, mobile overlay, status-update business logic, file
  downloads, notification badge, etc.
- **auth.php**: `views/layouts/auth.php` — the single shared authenticated layout.
- **app.css**: `public/assets/css/app.css` — application stylesheet.
- **app.js**: `public/assets/js/app.js` — application JavaScript.
- **`$activePage`**: PHP variable set by controllers to drive sidebar `active` class and
  `aria-current="page"` attributes.
- **`data-confirm`**: HTML attribute on `<form>` elements intercepted by `app.js` to invoke
  `window.confirm()`. Used only for "Begin Verification" in the Admin request-detail view.
- **`data-loading`**: HTML attribute on `<form>` elements intercepted by `app.js` to disable
  the submit button and show a spinner. Must continue working after the fix.
- **Terminal statuses**: `completed`, `cancelled`, `rejected`. For the purposes of this
  fix, only `completed` and `cancelled` require the new status-specific messages;
  `rejected` already uses an inline alert and is not changed.
- **`$allowedNext`**: PHP array of permitted next-status transitions injected into Admin and
  Staff request-detail views. Empty for terminal statuses.

---

## Bug Details

### Bug Condition

Six independent display defects are grouped under one composite bug condition because they
share the same view layer and require coordinated changes.

**Formal Specification:**

```
FUNCTION isBugCondition(X)
  INPUT: X of type PageRenderContext
  OUTPUT: boolean

  RETURN (
    // Defect 1 – Redundant auth header present on portal pages
    X.layout = 'auth'

    // Defect 2 – Mobile toggle lost when header is removed (implicitly fixed with Defect 1)

    // Defect 3 – Resident sidebar missing Track Request link
    OR (X.layout = 'auth' AND X.role = 'resident'
        AND X.sidebarLinks NOT CONTAINS 'track-request')

    // Defect 4 – Incomplete FAQ (fewer than 15 items)
    OR (X.layout = 'public' AND X.page = 'home'
        AND COUNT(X.faqItems) < 15)

    // Defect 5 – Native dialog on Begin Verification
    OR (X.layout = 'auth' AND X.role = 'admin'
        AND X.nextAction = 'under_verification'
        AND X.confirmMethod = 'window.confirm')

    // Defect 6 – Generic terminal-state message
    OR (X.layout IN ['admin-request-detail',
                     'staff-request-detail',
                     'resident-request-detail']
        AND X.requestStatus IN ['completed', 'cancelled']
        AND X.terminalMessage = 'generic')
  )
END FUNCTION
```

### Examples

**Defect 1 — Duplicate Branding:**
- Admin navigates to `/admin/dashboard` → both `<header class="auth-header">` with
  eMunicipal logo/user dropdown AND the dark sidebar with eMunicipal logo/logout render
  simultaneously.

**Defect 3 — Missing Track Request:**
- Resident logs in and looks in the sidebar under "Requests" → sees "New Request" and "My
  Requests" but no "Track Request", even though `/resident/track` is a registered route.

**Defect 4 — Incomplete FAQ:**
- Visitor opens the landing page and expands the FAQ accordion → only 6 items appear;
  topics like "How do I create an account?", "What happens after approval?", and "How do I
  claim my document?" are absent.

**Defect 5 — Native Dialog:**
- Admin opens a `pending` request and clicks "Begin Verification" → browser-native
  `confirm()` dialog appears instead of an in-app Bootstrap modal.

**Defect 6 — Generic Terminal Message:**
- Admin opens a `completed` request → Actions panel shows
  `🔒 No further actions — request is in a terminal state.` instead of
  `✓ Already Completed`.
- Staff opens a `cancelled` request → same generic message instead of `Request Cancelled`.

---

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- All routing (GET/POST) through `routes/web.php` must continue to work exactly as before.
- CSRF tokens in all existing forms must remain present and valid.
- Sidebar active-link highlighting (`$activePage`, `active` CSS class, `aria-current`) must
  continue to work for all existing links across all three portals.
- Mobile sidebar open/close toggle using `.sidebar-open` class and `.sidebar-overlay.active`
  must continue to work after the header is removed.
- The existing Bootstrap modals for Reject, Additional Info Required, and Cancel actions
  (both Admin and Staff views) must continue to work unchanged.
- The `data-loading` double-submit prevention in `app.js` must continue to fire for all
  forms that carry the attribute.
- The `data-confirm` handler may remain in `app.js` for any other future use, but must NOT
  be triggered for the "Begin Verification" form (the fix removes `data-confirm` from that
  form and adds a modal instead).
- Resident notification badge (unread count) in the sidebar must continue to display.
- File downloads via `/files/{id}` must continue to work.
- All public landing-page sections (hero, features, about, services, how-it-works, FAQ,
  announcement, contact, footer) must remain present and functional.
- Flash alerts (success, error, info) displayed by the auth layout must continue to auto-
  dismiss after 5 seconds and be dismissible by the close button.
- The password show/hide toggle on login/register pages must continue to work.

**Scope:**
All page renders where `isBugCondition(X)` is false must produce exactly the same output as
before. This includes — but is not limited to — login/register pages (which use a different
`minimal-layout` and are unaffected by auth.php changes), all admin management pages
(document types, requirements, users), and the Resident/Staff/Admin dashboard and list views.

---

## Hypothesized Root Cause

### Defect 1 & 2 — Redundant Header / Lost Mobile Toggle

The layout was originally designed with a traditional header + sidebar pattern. As the
sidebar was enhanced with full branding, navigation, and user identity (Section 27 of
`app.css` "Dark Sidebar" pass), the header became redundant — but was never removed. The
mobile toggle was placed inside the header without an alternative location.

### Defect 3 — Missing Track Request Link

The `/resident/track` route was added to `routes/web.php` and the view exists
(`views/resident/requests/track.php`), but the corresponding sidebar nav entry in the
Resident block of `auth.php` was never added — likely an oversight during feature
development.

### Defect 4 — Incomplete FAQ

The landing-page FAQ was seeded with 6 placeholder questions during initial development.
The full set of system-supported Q&A was never written into `views/home/index.php`.

### Defect 5 — Native `window.confirm()` on Begin Verification

The `data-confirm` handler in `app.js` is a generic mechanism for confirming any dangerous
form submission. The "Begin Verification" form in `views/admin/requests/show.php` was wired
to this generic handler via `data-confirm="Confirm: Begin Verification?"` rather than
receiving its own Bootstrap modal, as was done for the Reject/Cancel/Additional-Info
actions.

### Defect 6 — Generic Terminal Message

The Actions panel in all three request-detail views uses a single PHP `if (empty($allowedNext))`
branch that outputs a single hard-coded message with a lock icon. No differentiation between
terminal states was implemented.

---

## Correctness Properties

Property 1: Bug Condition — Auth Header Absent, Sidebar Complete

_For any_ authenticated page render where `isBugCondition(X)` holds (i.e., the layout is
`auth`), the fixed `auth.php` SHALL render the page WITHOUT the `<header class="auth-header">`
element in the DOM, the sidebar SHALL start at `top: 0` and fill the full viewport height,
the main content area SHALL have `margin-top: 0`, and a mobile sidebar toggle button SHALL be
present inside the `<main class="auth-content">` wrapper at the top of the page on small
screens.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**

Property 2: Bug Condition — Track Request Link Present for Residents

_For any_ Resident portal page render, the fixed sidebar SHALL include a "Track Request"
navigation link pointing to `/resident/track`, placed under the REQUESTS section, with
`$activePage === 'track'` causing the `active` class and `aria-current="page"` to be applied.

**Validates: Requirements 2.9**

Property 3: Bug Condition — FAQ Expanded to 15+ Items

_For any_ render of the landing page home view (`views/home/index.php`), the fixed FAQ
accordion SHALL contain at least 15 accordion items covering account creation, login, document
requesting, tracking, reference numbers, uploads, cancellation, notifications, approval,
claiming, and rejection/cancellation handling.

**Validates: Requirements 2.10, 2.11**

Property 4: Bug Condition — Begin Verification Uses Bootstrap Modal

_For any_ Admin request-detail page render where `under_verification` is in `$allowedNext`,
the fixed view SHALL render a Bootstrap modal dialog for "Begin Verification" (NOT a
`data-confirm` attribute on the form), and the `app.js` `window.confirm()` path SHALL NOT
be triggered when the admin initiates this action.

**Validates: Requirements 2.12, 2.13, 2.14, 2.15, 2.16**

Property 5: Bug Condition — Status-Specific Terminal Messages

_For any_ Admin or Staff request-detail page render where `$allowedNext` is empty and
`$status` is `completed`, the Actions panel SHALL display "Already Completed" with a success
indicator. _For any_ such page where `$status` is `cancelled`, it SHALL display "Request
Cancelled" with a muted indicator. _For any_ Resident request-detail page where `$status`
is `completed`, the right column SHALL display a completion notice; where `$status` is
`cancelled`, a cancellation notice.

**Validates: Requirements 2.17, 2.18, 2.19, 2.20, 2.21, 2.22**

Property 6: Preservation — Non-Buggy Page Renders Unchanged

_For any_ page render where `isBugCondition(X)` does NOT hold (login, register, all
management pages, all list views, all dashboard pages), the fixed code SHALL produce
functionally identical output to the original code — same routing, same CSRF forms, same
modal patterns, same active-link state, same mobile overlay behaviour, same data-loading
spinner, same notification badge, same file download links.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12, 3.13, 3.14**

---

## Fix Implementation

### Changes Required

The fix touches exactly **five files**. No new files are introduced.

---

### File 1: `views/layouts/auth.php`

**Change 1a — Remove the entire `<header class="auth-header">` block.**

Delete lines from `<!-- Sidebar overlay -->` … actually, keep the sidebar overlay. Remove
only the `<header class="auth-header" …> … </header>` block (the top bar with the
sidebar-toggle button, brand link, notification bell, and user dropdown).

**Change 1b — Add a replacement mobile sidebar toggle inside `<main class="auth-content">`.**

Place a small `<button id="sidebarToggle" …>` at the very top of the `<main>` element,
visible only on mobile (`d-lg-none`). The existing `app.js` toggle handler already targets
`#sidebarToggle` — no JS change needed for this.

```html
<!-- Mobile sidebar toggle — only visible on small screens (replaces header toggle) -->
<button class="btn-sidebar-toggle d-lg-none mb-3" id="sidebarToggle"
        aria-label="Toggle sidebar navigation"
        aria-controls="appSidebar" aria-expanded="false">
    <i class="bi bi-list" aria-hidden="true"></i> Menu
</button>
```

**Change 1c — Add "Track Request" link to the Resident sidebar block.**

In the `<?php if ($role === 'resident'): ?>` block, under the existing "My Requests" `<li>`,
add:

```html
<li class="nav-item">
    <a class="nav-link <?= $activePage === 'track' ? 'active' : '' ?>"
       href="<?= $appUrl ?>/resident/track"
       <?= $activePage === 'track' ? 'aria-current="page"' : '' ?>>
        <i class="bi bi-search" aria-hidden="true"></i> Track Request
    </a>
</li>
```

**Change 1d — Add user identity block to the sidebar.**

After the `sidebar-brand` block, add a small user identity strip showing `$firstName
$lastName` and the `role-badge`. This preserves the identity display that previously lived
in the header dropdown.

```html
<!-- User identity strip (replaces header user dropdown) -->
<div class="sidebar-user px-3 py-2 border-bottom"
     style="border-color:rgba(255,255,255,0.12)!important;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-person-circle text-white" style="font-size:1.2rem;" aria-hidden="true"></i>
        <div>
            <div style="font-size:0.8125rem;font-weight:600;color:#fff;line-height:1.2;">
                <?= $firstName ?> <?= $lastName ?>
            </div>
            <span class="role-badge role-badge-<?= strtolower($role) ?>" style="font-size:0.6rem;">
                <?= htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </div>
</div>
```

---

### File 2: `public/assets/css/app.css`

**Change 2a — Remove header-dependent offsets from `.sidebar` and `.auth-content`.**

In Section 7 (Sidebar Navigation), change:
```css
/* BEFORE */
.sidebar {
    top: var(--header-height);
    …
}
```
to:
```css
/* AFTER */
.sidebar {
    top: 0;
    height: 100vh;
    …
}
```

In Section 8 (Authenticated Main Content Area), change:
```css
/* BEFORE */
.auth-content {
    margin-top: var(--header-height);
    min-height: calc(100vh - var(--header-height));
    …
}
```
to:
```css
/* AFTER */
.auth-content {
    margin-top: 0;
    min-height: 100vh;
    …
}
```

**Change 2b — Style the mobile toggle button that moves into `<main>`.**

The existing `.btn-sidebar-toggle` rule in Section 21 already provides the visual style.
No new rule is needed; only ensure the button uses this class.

**Change 2c — Add `.sidebar-user` subtle styling (optional, no layout impact).**

The identity strip uses inline styles for compactness; no new CSS rule is strictly required.

---

### File 3: `views/admin/requests/show.php`

**Change 3a — Replace `data-confirm` form with a Bootstrap confirmation modal for "Begin Verification".**

In the `$hasModal = …` logic inside the `foreach ($allowedNext as $next)` loop, the current
condition for rendering a modal (`isset($needsRemark[$next]) || $next === RequestModel::STATUS_CANCELLED`)
already excludes `under_verification` from the modal path, sending it down the `data-confirm`
form path.

The fix adds `under_verification` to the modal path without a remark field:

1. Change the condition so `STATUS_UNDER_VERIFICATION` also uses a modal button:
   ```php
   $hasModal = isset($needsRemark[$next])
            || $next === RequestModel::STATUS_CANCELLED
            || $next === RequestModel::STATUS_UNDER_VERIFICATION;
   ```

2. In the modal-generation loop at the bottom of the file, add a separate branch for
   `STATUS_UNDER_VERIFICATION` that renders a confirmation-only modal (no textarea):

```php
// Confirmation-only modal for Begin Verification (no remark field)
if ($next === RequestModel::STATUS_UNDER_VERIFICATION):
    $modalId  = 'modal-' . $next;
    $btnLabel = $actionLabels[$next] ?? 'Begin Verification';
    $btnCls   = $actionBtnCls[$next] ?? 'btn-primary';
?>
<div class="modal fade" id="<?= $e($modalId) ?>" tabindex="-1"
     aria-labelledby="<?= $e($modalId) ?>-label" aria-modal="true"
     data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title h5" id="<?= $e($modalId) ?>-label">
                    Begin Verification
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size:0.9rem;">
                Move this request to <strong>Under Verification</strong>?
                The resident will be notified.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancel</button>
                <form method="POST"
                      action="<?= $e($appUrl) ?>/admin/requests/<?= $requestId ?>/status"
                      data-loading="Updating…" class="d-inline">
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                    <input type="hidden" name="new_status"
                           value="<?= $e(RequestModel::STATUS_UNDER_VERIFICATION) ?>">
                    <button type="submit" class="btn <?= $e($btnCls) ?>">
                        Begin Verification
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
```

**Change 3b — Status-specific terminal message in the Actions panel.**

Replace the generic `if (empty($allowedNext)):` branch:

```php
/* BEFORE */
<?php if (empty($allowedNext)): ?>
<p class="text-muted mb-0" style="font-size:0.875rem;">
    <i class="bi bi-lock me-1" aria-hidden="true"></i>
    No further actions — request is in a terminal state.
</p>
```

```php
/* AFTER */
<?php if (empty($allowedNext)): ?>
    <?php if ($status === RequestModel::STATUS_COMPLETED): ?>
    <p class="mb-0" style="font-size:0.875rem;color:var(--emu-success);">
        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
        <strong>Already Completed</strong><br>
        <span class="text-muted">No further actions required.</span>
    </p>
    <?php elseif ($status === RequestModel::STATUS_CANCELLED): ?>
    <p class="text-muted mb-0" style="font-size:0.875rem;">
        <i class="bi bi-slash-circle me-1" aria-hidden="true"></i>
        <strong style="color:var(--emu-text-primary);">Request Cancelled</strong><br>
        No further actions available.
    </p>
    <?php else: ?>
    <p class="text-muted mb-0" style="font-size:0.875rem;">
        <i class="bi bi-lock me-1" aria-hidden="true"></i>
        No further actions — request is in a terminal state.
    </p>
    <?php endif; ?>
```

---

### File 4: `views/staff/requests/show.php`

**Change 4a — Status-specific terminal message (same pattern as Admin).**

Apply the identical three-branch replacement to the `if (empty($allowedNext)):` section
in the Staff Actions panel, using the same `RequestModel::STATUS_COMPLETED` /
`RequestModel::STATUS_CANCELLED` checks and the same HTML.

---

### File 5: `views/resident/requests/show.php`

**Change 5a — Status-specific notices in the right column.**

The Resident view's right column currently shows only the "Claiming Instructions" card for
`ready_for_claiming` status. Extend this with two new branches for `completed` and `cancelled`:

```php
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
```

---

### File 6: `views/home/index.php`

**Change 6a — Replace the 6-item FAQ array with a 15-item array.**

Replace the `$faqs = [ … ]` array in the FAQ section with:

```php
$faqs = [
    ['What is eMunicipal?',
     'eMunicipal is the official Online Municipal Document Request and Management System of the Municipal Government of Lingayen, Pangasinan. It allows residents to request official documents, track request status in real time, and receive email notifications — without visiting the office.'],
    ['How do I create an account?',
     'Click the "Register" button on the top-right of this page. Fill in your first name, last name, email address, and a password. Once submitted, you can immediately log in with your new credentials.'],
    ['How do I log in?',
     'Click "Login" at the top of the page and enter your registered email and password. If you forgot your password, contact the Municipal Hall for account assistance.'],
    ['How do I request a document?',
     'After logging in, go to "New Request" in the sidebar. Select the document type, fill in the purpose and number of copies, upload any required supporting files, and click Submit. You will receive a reference number upon successful submission.'],
    ['How do I select a document type?',
     'On the New Request page, all active document types available for online request are listed. Click the document type card that matches your need to select it before proceeding.'],
    ['Where can I see the requirements for a document?',
     'When you select a document type on the New Request form, the required supporting documents are listed directly on the page. Review them before uploading.'],
    ['Can I upload the required documents online?',
     'Yes. The request form has an upload section where you can attach scanned copies of your supporting documents as PDF, JPEG, or PNG files (up to the allowed size per file).'],
    ['How can I track my request?',
     'Use the "Track My Request" button on the homepage or the "Track Request" link in the sidebar after logging in. Enter your reference number (e.g., EMU-2026-000001) to see the full status history.'],
    ['Where can I find my request reference number?',
     'Your reference number is displayed on screen immediately after submission and is also included in the confirmation email sent to your registered address. You can also find it in "My Requests" in the sidebar.'],
    ['Can I cancel my request?',
     'Yes, as long as your request is still in Pending, Under Verification, or Additional Information Required status. Open the request from "My Requests", scroll to the top, and click "Cancel Request". Completed or approved requests cannot be cancelled.'],
    ['How will I know if my request status changes?',
     'You will receive an email notification at your registered email address whenever your request status is updated — from submission through to approval, rejection, or completion.'],
    ['How will I receive notifications in the system?',
     'In addition to email, logged-in Residents see an in-app notification bell in the sidebar. The bell shows a badge with the count of unread notifications. Click it to view your notification history.'],
    ['What happens after my request is approved?',
     'Once approved, your request is processed and eventually marked "Ready for Claiming". You will receive an email with claiming instructions. The status timeline on your request detail page will also update at every step.'],
    ['How do I claim my document?',
     'Visit the Municipal Hall at Lingayen, Pangasinan on a weekday between 8:00 AM and 5:00 PM. Bring a valid government-issued ID. The specific window or counter will be noted in your notification email.'],
    ['What should I do if my request is rejected or cancelled?',
     'If rejected, the reason is displayed on your request detail page and sent by email. You may submit a new request with the corrected information or complete supporting documents. If you cancelled the request yourself, simply submit a new request when you are ready.'],
];
```

---

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first verify the bug manifests on the
unmodified code (exploratory), then confirm the fix works and existing behaviour is
preserved (fix + preservation checking).

---

### Exploratory Bug Condition Checking

**Goal**: Confirm each defect is observable on the unmodified codebase before applying the fix.

**Test Plan**: Visually inspect rendered pages and inspect the DOM/console on the unfixed code.

**Test Cases**:

1. **Duplicate Header Test**: Load `/admin/dashboard` as an Admin → observe both
   `.auth-header` in the DOM and the dark sidebar branding (will fail fix check on unfixed
   code).
2. **Missing Track Request Test**: Log in as Resident, inspect sidebar → confirm no "Track
   Request" link is present.
3. **FAQ Count Test**: Open the landing page, count accordion items → confirm only 6 exist.
4. **Native Dialog Test**: As Admin, open a `pending` request, click "Begin Verification" →
   confirm browser-native `confirm()` dialog appears.
5. **Generic Terminal Message Test**: As Admin, open a `completed` request → confirm
   Actions panel shows the generic lock message.
6. **CSS Offset Test**: With the header removed manually (DevTools), observe that the main
   content still has `margin-top: 60px` pushing it down unnecessarily.

**Expected Counterexamples**:
- `.auth-header` element found in DOM for all authenticated pages.
- No `[href*="/resident/track"]` element in the sidebar for Resident sessions.
- FAQ accordion contains exactly 6 `accordion-item` elements.
- `window.confirm` is called when "Begin Verification" is clicked.
- Terminal message text contains "terminal state" regardless of `completed` vs `cancelled`.

---

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds, the fixed render produces
the expected output.

**Pseudocode:**
```
FOR ALL X WHERE isBugCondition(X) DO
  result := render_fixed(X)
  ASSERT (
    result.contains('.auth-header') = false                          // Defect 1
    AND result.sidebarTop = '0'                                      // Defect 1 CSS
    AND result.mainMarginTop = '0'                                   // Defect 1 CSS
    AND result.contains('#sidebarToggle') = true                     // Defect 2
    AND (X.role ≠ 'resident' OR result.sidebarContains('/resident/track'))  // Defect 3
    AND (X.page ≠ 'home' OR COUNT(result.faqItems) >= 15)           // Defect 4
    AND (X.nextAction ≠ 'under_verification'
         OR result.contains('modal-under_verification') = true)      // Defect 5
    AND (X.requestStatus ≠ 'completed'
         OR result.terminalText = 'Already Completed')               // Defect 6
    AND (X.requestStatus ≠ 'cancelled'
         OR result.terminalText = 'Request Cancelled')               // Defect 6
  )
END FOR
```

**Test Cases**:
1. **Header Removal**: All three portals → confirm no `.auth-header` in DOM.
2. **CSS Offsets**: Load any portal page → computed `margin-top` on `.auth-content` is `0`.
3. **Mobile Toggle Present**: On viewport ≤ 991px, confirm `#sidebarToggle` is visible in
   the main content area and still toggles the sidebar.
4. **Track Request Link**: Resident session → sidebar under "Requests" contains "Track
   Request" link to `/resident/track`.
5. **FAQ 15 Items**: Landing page → accordion has exactly 15 items, each opens/closes
   correctly.
6. **Begin Verification Modal**: Admin, `pending` request → clicking "Begin Verification"
   opens `#modal-under_verification` Bootstrap modal; no `window.confirm` fires.
7. **Modal Cancel**: In the Begin Verification modal, clicking "Cancel" closes modal without
   form submission.
8. **Modal Confirm**: Clicking "Begin Verification" in the modal submits POST with
   `new_status=under_verification` and CSRF token.
9. **Admin Completed Message**: Admin request in `completed` status → Actions panel shows
   "Already Completed" with green check icon.
10. **Admin Cancelled Message**: Admin request in `cancelled` status → Actions panel shows
    "Request Cancelled" with slash-circle icon.
11. **Staff Completed/Cancelled**: Same checks on Staff request-detail view.
12. **Resident Completed Notice**: Resident `completed` request → right column shows
    "Request Completed" card.
13. **Resident Cancelled Notice**: Resident `cancelled` request → right column shows
    "Request Cancelled" card.

---

### Preservation Checking

**Goal**: Verify that for all inputs where the bug condition does NOT hold, the fixed render
is functionally identical to the original.

**Pseudocode:**
```
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT render_original(X) ≅ render_fixed(X)
  // ≅ means functionally equivalent (same links, same forms, same CSRF tokens,
  //    same active states, same modal triggers for Reject/Cancel/Additional Info)
END FOR
```

**Testing Approach**: Property-based testing via manual boundary exploration and focused
regression checks on the highest-risk preserved behaviours.

**Test Cases**:
1. **Reject Modal Unchanged**: Admin and Staff request-detail, non-terminal request → Reject
   modal still opens, remark textarea still required, form still POSTs to correct endpoint.
2. **Additional Info Modal Unchanged**: Same verification for "Request Additional Info" modal.
3. **Cancel Modal Unchanged (Staff/Admin)**: Same for the Cancel action modal.
4. **CSRF Tokens Present**: All status-update forms (existing modals + new Begin Verification
   modal) carry `_csrf_token` hidden input.
5. **Sidebar Active State**: Navigate to each sidebar link → `active` class and
   `aria-current` applied only to the correct link, across all three portals.
6. **data-loading Still Fires**: Submit a direct-submit form (e.g., Approve, Mark Ready for
   Claiming) → button becomes disabled with spinner text.
7. **Notification Badge**: Resident session with unread notifications → badge still appears
   on sidebar "Notifications" link and in the (now-removed) header bell was the only other
   place. Confirm badge in sidebar is intact.
8. **Login/Register Pages Unaffected**: Both pages use `minimal-split` layout, not
   `auth.php` or the updated CSS selectors. Confirm visual appearance unchanged.
9. **Public Nav Unchanged**: Landing page header with Login/Register buttons, all anchor
   links, footer quick links — all unchanged.
10. **Mobile Overlay**: On ≤ 991px viewport, tapping the new toggle button opens the
    sidebar; tapping the overlay closes it — identical UX to before.

---

### Unit Tests

- Verify `#sidebarToggle` exists exactly once in the DOM for all authenticated pages after
  the fix (previously it was inside the now-removed header).
- Verify `.auth-header` does not appear in any authenticated page's HTML output.
- Verify the Resident sidebar contains `<a href="…/resident/track">` for all Resident pages.
- Verify `form[data-confirm]` is not present in `views/admin/requests/show.php` after fix.
- Verify `#modal-under_verification` is rendered when `under_verification` is in
  `$allowedNext`.
- Verify the FAQ `$faqs` array has exactly 15 entries in `views/home/index.php`.

---

### Property-Based Tests

- For random authenticated page renders (varying `$role`, `$activePage`): header element
  is absent, sidebar toggle is present, sidebar brand block is present — these hold for all
  combinations.
- For random Resident page renders: sidebar always contains a link matching
  `/resident/track`.
- For random Admin/Staff request-detail renders with empty `$allowedNext`: the terminal
  message text matches `completed → "Already Completed"`, `cancelled → "Request Cancelled"`,
  any other → generic fallback.
- For random `$allowedNext` sets containing `under_verification`: a `#modal-under_verification`
  modal is present in the output and no `data-confirm` attribute is on a surrounding form.

---

### Integration Tests

- Full Resident flow: login → sidebar shows "Track Request" → click → `/resident/track`
  loads correctly with `$activePage === 'track'` highlighting the link.
- Full Admin Begin-Verification flow: open `pending` request → click "Begin Verification"
  → Bootstrap modal opens → click modal "Begin Verification" → status updates to
  `under_verification` → redirected with success flash.
- Full Admin terminal-state flow: process a request to `completed` → open it → confirm
  "Already Completed" panel → no action buttons shown.
- Mobile sidebar flow: resize to < 992px → click menu toggle in `<main>` → sidebar slides
  in → tap overlay → sidebar closes.
- Landing page FAQ: open `/` → expand all 15 FAQ accordion items → confirm each opens and
  closes with correct animation; keyboard navigation (Tab + Enter/Space) works.
