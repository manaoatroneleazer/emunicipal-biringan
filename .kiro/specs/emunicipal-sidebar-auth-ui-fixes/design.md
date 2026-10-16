# Design Document: eMunicipal Sidebar & Auth UI Fixes

## Overview

This design covers a focused set of UI/UX improvements and one bug fix across the eMunicipal PHP monolith. The changes touch three layers: PHP views, CSS, and vanilla JavaScript. No new routes, controllers, models, or database tables are introduced. The PHP backend remains unchanged except for one variable assignment in `RequestController::track()`.

The work breaks into five concern areas:

1. **Sidebar fixes** — track page active state bug, scrollbar hiding, logout danger styling, logout confirmation modal.
2. **Login page redesign** — split layout, modernised card, icon-prefixed fields, improved headings.
3. **Register page redesign** — split layout, two-column name row, live password checklist, live confirm match indicator.
4. **AJAX form submission** — intercept login and register forms, use `fetch()`, handle PHP redirect-based responses, show inline errors.
5. **Preservation** — staff action modals, existing sidebar active states, CSRF, sessions, role auth all remain untouched.

### Key Technical Constraints Discovered During Inspection

- `AuthController::login()` and `AuthController::register()` return **302 redirects**, not JSON. AJAX handling must use `fetch()` with `redirect: 'follow'` and inspect `response.url` to determine success vs. failure.
- `RequestController::track()` currently passes `'activePage' => 'requests'` — this is the root cause of the active state bug. The fix is changing this single string to `'track'`.
- `Validator::passwordStrength()` requires: ≥8 chars, ≥1 uppercase, ≥1 lowercase, ≥1 digit. No special character rule exists server-side. The client-side checklist may show a special character criterion as a UX enhancement; it is not enforced by the backend.
- `app.js` already implements `data-pw-toggle` password visibility toggling and `data-loading` double-submit prevention. The `data-loading` attribute must be removed from forms that switch to AJAX submission to avoid conflict.
- CSS section 29 (`.minimal-split-*`) is already defined in `app.css` but `layouts/minimal.php` does not use it. The minimal layout needs to be adapted to use the split layout for login/register.
- The dark sidebar background is applied via CSS section 27 in `app.css` (overrides to `.sidebar` and `.sidebar-nav`). All logout danger styling must be compatible with this dark context.

---

## Architecture

The project is a custom PHP MVC monolith on XAMPP. There is no build pipeline — all asset changes are direct edits to `public/assets/css/app.css` and `public/assets/js/app.js`. PHP views are plain PHP files rendered via `BaseController::renderWithLayout()`.

```
c:\xampp\htdocs\eMunicipal\
├── app/
│   └── Controllers/
│       └── Resident/
│           └── RequestController.php   ← BUG FIX: activePage 'requests' → 'track'
├── views/
│   ├── layouts/
│   │   ├── auth.php                    ← Sidebar fixes, logout modal HTML
│   │   └── minimal.php                ← Switch to split layout
│   └── auth/
│       ├── login.php                   ← Redesigned login card + AJAX
│       └── register.php               ← Redesigned register card + AJAX + live validation
└── public/
    └── assets/
        ├── css/
        │   └── app.css                ← Sidebar scrollbar, logout danger styles
        └── js/
            └── app.js                 ← Logout modal trigger, AJAX handlers, live validation
```

No new files are required. All changes are in-place modifications to existing files.

---

## Components and Interfaces

### Component 1: Track Active State Fix (`RequestController.php`)

**Change**: Single line — `'activePage' => 'requests'` → `'activePage' => 'track'` in the `track()` method's `renderWithLayout` call.

**Interface contract**: `Auth_Layout` reads `$activePage` and applies `active` class via strict equality: `$activePage === 'track'`. The sidebar HTML already has the correct condition; only the controller assignment was wrong.

---

### Component 2: Sidebar Scrollbar Hidden (`app.css`)

**Change**: Add a CSS block to section 27 (dark sidebar overrides):

```css
.sidebar {
    scrollbar-width: none;       /* Firefox */
    -ms-overflow-style: none;    /* IE/Edge */
}
.sidebar::-webkit-scrollbar {
    display: none;               /* Chrome/Safari/Edge Chromium */
}
```

`overflow-y: auto` is already set in section 7 of `app.css`. These rules hide the scrollbar visually without removing scroll capability.

---

### Component 3: Logout Danger Styling (`app.css`)

The dark sidebar context (background `#1a3a5c`) means danger styling needs a light-red tone visible on dark blue. Design decision: use `#fca5a5` (light red, Bootstrap's `danger-200` equivalent) for text and icon color, with a hover background of `rgba(239, 68, 68, 0.15)`.

**CSS additions to section 27**:

```css
.sidebar-nav .nav-link.logout-link {
    color: #fca5a5 !important;
}
.sidebar-nav .nav-link.logout-link i {
    color: #fca5a5 !important;
}
.sidebar-nav .nav-link.logout-link:hover {
    background-color: rgba(239, 68, 68, 0.18) !important;
    color: #fca5a5 !important;
}
```

**HTML change in `auth.php`**: Add class `logout-link` to the logout `<button>` element. Remove the inline `style` attribute that hardcodes the color.

---

### Component 4: Logout Confirmation Modal (`auth.php` + `app.js`)

#### HTML (in `auth.php`)

A Bootstrap 5 modal is added at the bottom of `auth.php` (before Bootstrap JS script tag). The logout button becomes a `data-bs-toggle="modal"` trigger instead of a form submit button. The actual form submit is wired to the modal's "Log Out" confirm button.

**Structure**:

```
<div class="sidebar-nav">
  ...
  <!-- Logout button: no longer type="submit", triggers modal -->
  <button type="button" class="nav-link w-100 text-start border-0 bg-transparent logout-link"
          data-bs-toggle="modal" data-bs-target="#logoutConfirmModal"
          aria-haspopup="dialog" aria-label="Open sign out confirmation">
      <i class="bi bi-box-arrow-right"></i> Logout
  </button>
</div>

<!-- Logout form: hidden, submitted by modal confirm button -->
<form id="logoutForm" method="POST" action="{$appUrl}/logout" class="d-none">
    <input type="hidden" name="_csrf_token" value="...">
</form>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1"
     aria-labelledby="logoutConfirmTitle" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-1">
        <h5 class="modal-title" id="logoutConfirmTitle">
          <i class="bi bi-box-arrow-right text-danger me-2"></i>Sign Out
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-1">
        Are you sure you want to log out? You will need to sign in again to access your account.
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" id="logoutConfirmBtn">Log Out</button>
      </div>
    </div>
  </div>
</div>
```

The `logoutForm` is a hidden form containing only the CSRF token. The visible logout button triggers the modal (not a form submit). The modal confirm button submits `logoutForm` via `document.getElementById('logoutForm').submit()`.

**JavaScript in `app.js`** (DOMContentLoaded block):

```javascript
const logoutConfirmBtn = document.getElementById('logoutConfirmBtn');
const logoutForm = document.getElementById('logoutForm');
if (logoutConfirmBtn && logoutForm) {
    logoutConfirmBtn.addEventListener('click', function () {
        logoutForm.submit();
    });
}
```

This is the only binding needed. Bootstrap handles the modal show/hide via `data-bs-toggle` and `data-bs-dismiss` attributes.

**Scoping guarantee**: The `logoutConfirmBtn` event listener is bound to a single specific element by ID. It does not listen to any form submit event. Staff action modals are entirely separate Bootstrap modal instances bound to their own buttons in staff views — they are completely unaffected.

---

### Component 5: Minimal Layout — Split Layout (`minimal.php`)

The existing `layouts/minimal.php` uses a single-column centered card. It will be refactored to use the `.minimal-split` structure already defined in CSS section 29 of `app.css`.

**New minimal.php structure**:

```html
<body>
<div class="minimal-split">
    <!-- Left panel: branding/decorative -->
    <div class="minimal-split-left">
        <div class="brand-seal"><i class="bi bi-buildings"></i></div>
        <h2>{$appName}</h2>
        <p>Online Municipal Document Request System</p>
        <ul class="feature-list">
            <li><i class="bi bi-check-circle-fill"></i> Fast document processing</li>
            <li><i class="bi bi-check-circle-fill"></i> Track your requests online</li>
            <li><i class="bi bi-check-circle-fill"></i> Secure and confidential</li>
        </ul>
    </div>
    <!-- Right panel: form content -->
    <div class="minimal-split-right">
        <div class="form-card">
            <?= $content ?>
        </div>
    </div>
</div>
...
```

The `$content` variable is injected by `BaseController::renderWithLayout()` as before — no controller changes needed.

---

### Component 6: Login Page Redesign (`login.php`)

**New heading/subtitle**: "Welcome back" (h1) and "Sign in to continue to eMunicipal" (p).

**Icon-prefixed fields**: Both email and password fields use Bootstrap `input-group` with a `<span class="input-group-text">` containing Bootstrap Icon.

```html
<!-- Email field with icon -->
<div class="input-group mb-3">
    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
    <input type="email" id="email" name="email" class="form-control" ...>
</div>

<!-- Password field with icon + pw-toggle -->
<div class="input-group mb-4">
    <span class="input-group-text"><i class="bi bi-lock"></i></span>
    <input type="password" id="password" name="password" class="form-control" ...>
    <button class="btn btn-outline-secondary" type="button"
            data-pw-toggle="password" aria-label="Show password">
        <i class="bi bi-eye"></i>
    </button>
</div>
```

**Footer link**: "Don't have an account? Create an account" linking to `/register`.

**CSRF and form action preserved**: `<form method="POST" action=".../login">` with `_csrf_token`.

**AJAX**: The `data-loading` attribute is removed from the form tag. AJAX interception is handled via a `data-ajax-login` attribute on the form and a dedicated handler in `app.js` (see Component 8).

---

### Component 7: Register Page Redesign (`register.php`)

**Heading/subtitle**: "Create your account" (h1) and "Fill in your information to get started."

**Two-column name row**: Bootstrap `row g-3` with `col-12 col-sm-6` for first_name and last_name — same as the current layout but now inside the split card.

**All existing fields preserved**: first_name, last_name, email, contact_number, address, password, password_confirmation.

**Icon prefixes on password fields**: Both password and confirm password fields get `bi-lock` prefix icons.

**Password Checklist** (below password field):

```html
<ul class="pw-checklist list-unstyled mt-2 mb-0" id="pwChecklist" aria-live="polite">
    <li id="pwc-length"  class="pwc-item pwc-invalid">
        <i class="bi bi-x-circle-fill me-1"></i>
        <span>Password must be over 8 characters</span>
    </li>
    <li id="pwc-number"  class="pwc-item pwc-invalid">
        <i class="bi bi-x-circle-fill me-1"></i>
        <span>Password must contain at least 1 number</span>
    </li>
    <li id="pwc-special" class="pwc-item pwc-invalid">
        <i class="bi bi-x-circle-fill me-1"></i>
        <span>Password must contain at least 1 special character</span>
    </li>
    <li id="pwc-case"    class="pwc-item pwc-invalid">
        <i class="bi bi-x-circle-fill me-1"></i>
        <span>Password must contain uppercase and lowercase letters</span>
    </li>
</ul>
```

**Confirm Password Match Indicator** (below confirm password field):

```html
<div id="pwMatchMsg" class="mt-1" style="font-size:0.8125rem;" aria-live="polite"></div>
```

Content is set by JavaScript.

**AJAX**: `data-loading` removed; `data-ajax-register` attribute added. Client-side validation gate before fetch.

---

### Component 8: AJAX Form Handlers (`app.js`)

#### Why redirect-based AJAX (not JSON API)?

`AuthController` uses `UrlHelper::redirect()` which emits HTTP 302 redirects and session flash data. The backend does NOT return JSON. Three options were considered:

| Option | Pros | Cons |
|---|---|---|
| A: `fetch` with `redirect:'follow'` + check `response.url` | No backend change, works as-is | Slightly indirect detection |
| B: Add JSON response mode to AuthController | Clean API contract | Requires backend changes (violates constraints) |
| C: Keep full-page POST (no AJAX) | Simplest | No inline errors, full reload on failure |

**Decision: Option A.** The fetch follows the redirect chain. After resolution, `response.url` indicates whether authentication succeeded (redirected to dashboard) or failed (redirected back to the same page). Error messages are extracted from the HTML of the error response.

#### Login AJAX Handler

```javascript
// Pseudo-code (full implementation in app.js)
form.addEventListener('submit', async function(e) {
    e.preventDefault();
    setLoading(submitBtn, 'Signing in...');
    
    const resp = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        redirect: 'follow',
        credentials: 'same-origin'
    });
    
    const finalUrl = resp.url;
    
    if (!finalUrl.includes('/login')) {
        // Success: redirected away from login page
        window.location.href = finalUrl;
    } else {
        // Failure: still on login page — parse error from response HTML
        const html = await resp.text();
        const errorText = extractAlertText(html);
        showInlineError(errorContainer, errorText);
        resetLoading(submitBtn, 'Sign In');
    }
});
```

#### Register AJAX Handler

```javascript
form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Client-side gate: all checklist items must pass
    if (!allChecklistItemsValid()) {
        showInlineError(errorContainer, 'Please meet all password requirements before submitting.');
        return;
    }
    
    setLoading(submitBtn, 'Creating account...');
    
    const resp = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        redirect: 'follow',
        credentials: 'same-origin'
    });
    
    const finalUrl = resp.url;
    
    if (finalUrl.includes('/login')) {
        // Success: redirected to login page
        window.location.href = finalUrl;
    } else {
        // Failure: still on register page — parse errors from response HTML
        const html = await resp.text();
        showInlineErrors(html); // parse .invalid-feedback elements
        resetLoading(submitBtn, 'Create Account');
    }
});
```

#### `extractAlertText(html)` helper

Parses the `alert-danger` element from the server-rendered HTML string using `DOMParser` to extract the error message text, then displays it in the inline error container without a full page navigation.

#### Live Validation Functions

```javascript
function updatePasswordChecklist(password) {
    updateItem('pwc-length',  password.length > 8);
    updateItem('pwc-number',  /[0-9]/.test(password));
    updateItem('pwc-special', /[^A-Za-z0-9]/.test(password));
    updateItem('pwc-case',    /[A-Z]/.test(password) && /[a-z]/.test(password));
}

function updateConfirmMatch(password, confirm) {
    const msg = document.getElementById('pwMatchMsg');
    if (!msg) return;
    if (confirm === '') { msg.innerHTML = ''; return; }
    if (password === confirm) {
        msg.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>'
                      + '<span class="text-success">Passwords match</span>';
    } else {
        msg.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i>'
                      + '<span class="text-danger">Passwords do not match</span>';
    }
}
```

These are called on the `input` event of the password and confirm password fields.

#### Conflict with `data-loading` mechanism

The existing `data-loading` mechanism in `app.js` fires on `form.submit` event. Since AJAX handlers call `e.preventDefault()` and manage button state manually, the `data-loading` attribute must be removed from the login and register form elements to prevent the mechanism from also firing and locking the button in an unrecoverable state on AJAX failure.

---

## Data Models

No data model changes. This feature is entirely in the view/CSS/JS layer. The only data-adjacent change is the `$activePage` string value passed from `RequestController::track()` to the layout.

| File | Change Type | Description |
|---|---|---|
| `RequestController.php` | Bug fix | `'activePage' => 'requests'` → `'activePage' => 'track'` |
| `views/layouts/auth.php` | View | Logout modal HTML, logout button trigger, hidden logout form |
| `views/layouts/minimal.php` | View | Switch to `.minimal-split` structure |
| `views/auth/login.php` | View | Redesigned card, icon fields, AJAX form attributes |
| `views/auth/register.php` | View | Redesigned card, password checklist HTML, AJAX attributes |
| `public/assets/css/app.css` | CSS | Sidebar scrollbar hiding, logout danger styles |
| `public/assets/js/app.js` | JS | Logout modal confirm handler, AJAX handlers, live validation |

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

The password checklist and confirm password match logic are pure JavaScript functions that take string inputs and produce boolean/UI outputs. They are good candidates for property-based testing. The AJAX success-detection logic (`response.url` check) is also a pure predicate.

### Property 1: Password Checklist Correctness

*For any* password string, the "over 8 characters" criterion SHALL be satisfied if and only if `password.length > 8`, the "number" criterion if and only if `/[0-9]/.test(password)`, the "special character" criterion if and only if `/[^A-Za-z0-9]/.test(password)`, and the "case" criterion if and only if `/[A-Z]/.test(password) && /[a-z]/.test(password)`. Each criterion SHALL be logically independent of the others.

**Validates: Requirements 8.2, 8.5**

### Property 2: Confirm Password Match Indicator Idempotence

*For any* password string `p` and confirm string `c`, calling the match-update function multiple times with the same `(p, c)` inputs SHALL always produce the same match/mismatch indicator state (idempotent output). The indicator SHALL show "match" if and only if `p === c` and `c !== ''`.

**Validates: Requirements 9.2, 9.3, 9.4**

### Property 3: Login AJAX Success URL Detection

*For any* URL string `u`, the success-detection predicate SHALL return `true` if and only if `u` does not contain the pathname `/login` as a path segment, and `false` otherwise. Specifically, dashboard URLs (`/resident/dashboard`, `/staff/dashboard`, `/admin/dashboard`) SHALL always return `true`, and `/login` or any URL with `/login` in the path SHALL always return `false`.

**Validates: Requirements 11.3, 11.7**

### Property 4: Register AJAX Success URL Detection

*For any* URL string `u`, the register success-detection predicate SHALL return `true` if and only if `u` contains `/login` as a path segment (indicating redirect to login after registration). A register page URL (`/register`) SHALL always return `false`.

**Validates: Requirements 12.4, 12.8**

---

## Error Handling

### Track Page Active State
- **Risk**: If `$activePage` is not set at all (e.g., a future controller omits it), all sidebar links will show inactive — acceptable fallback. The fix is purely additive.

### Logout Modal
- **Risk**: If Bootstrap JS fails to load, `data-bs-toggle` will not work and the modal will not show. Mitigation: the logout form is separate from the button; a noscript fallback could be added, but since the entire app requires JS for other features (sidebar toggle, file upload, etc.), this is acceptable.
- **Risk**: CSRF token expiry between page load and modal confirmation. The CSRF token is valid for the session. Standard session timeout handling applies.

### AJAX Form Submission
- **Risk**: `fetch()` network error (offline, server down). Both handlers must catch this and display a generic error message.
- **Risk**: `DOMParser` parsing of error HTML fails to find the alert element. The handler must fall back to a generic error message.
- **Risk**: Session flash data is consumed on the first page load after redirect. Since AJAX follows the redirect and reads the HTML, flash data is consumed by the AJAX request (not the user's browser navigation). This is correct behavior — errors are shown inline.
- **Risk**: `data-loading` conflict. Resolved by removing `data-loading` from forms that use AJAX handlers, as documented in Component 8.

### Live Validation
- **Risk**: JavaScript errors in checklist update function prevent form submission UI. All live-validation code is wrapped in try/catch to fail silently without blocking submission.

---

## Testing Strategy

This feature primarily involves view rendering, CSS changes, and JavaScript behavior. PBT applies narrowly to the pure-function JS logic (checklist, match indicator, URL detection predicates).

### Unit Tests (JavaScript — Jasmine or plain test scripts)

- Verify `updatePasswordChecklist()` correctly sets pass/fail for each criterion with representative passwords.
- Verify `updateConfirmMatch()` shows match/mismatch/hidden state for equal, different, and empty confirm values.
- Verify the login success-URL predicate correctly classifies dashboard URLs vs. `/login` URL.
- Verify the register success-URL predicate correctly classifies `/login` redirect vs. `/register` URL.

### Property-Based Tests (JavaScript — fast-check)

Minimum 100 iterations per property. Tag format: `Feature: emunicipal-sidebar-auth-ui-fixes, Property N: <property text>`

- **Property 1**: Generate arbitrary strings. For each, verify all four checklist criteria match their respective regex predicates independently.
- **Property 2**: Generate arbitrary `(password, confirm)` string pairs. Verify match indicator idempotence — same output on repeated calls.
- **Property 3**: Generate arbitrary URL strings including generated dashboard URLs and variants of `/login`. Verify success predicate classification.
- **Property 4**: Generate arbitrary URL strings with `/login` or `/register` path. Verify register success predicate.

### Integration / Smoke Tests (Manual)

- Sign in with valid credentials → confirm redirect to correct dashboard.
- Sign in with invalid credentials → confirm inline error without page reload.
- Register → confirm redirect to login page with success flash.
- Register with duplicate email → confirm inline server error.
- Click logout button → confirm modal appears → Cancel → confirm session still active.
- Click logout button → Log Out → confirm session destroyed, redirect to home.
- Navigate to `/resident/track` → confirm "Track Request" is active in sidebar, "My Requests" is not.
- Navigate to `/resident/requests` → confirm "My Requests" is active, "Track Request" is not.
- Staff: open a request → use Verify/Approve/Reject modal → confirm no logout modal interference.
- Check sidebar on all roles (resident, staff, admin) → confirm logout button is danger-styled.
- Check sidebar for scrollbar visibility on content-heavy pages.
- Responsive checks at 375px, 768px, 1024px, 1440px on both login and register pages.
