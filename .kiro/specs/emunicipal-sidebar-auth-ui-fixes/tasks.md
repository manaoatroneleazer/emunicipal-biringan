# Implementation Plan: eMunicipal Sidebar & Auth UI Fixes

## Overview

All implementation is confined to view, CSS, and JS files. The only PHP logic change is a single `$activePage` value fix in `RequestController::track()`. No new routes, endpoints, controllers, models, or database changes are needed. Tasks are ordered from smallest/most-isolated to most-complex, with JS property tests placed close to the JS logic they validate.

Property-based tests use **fast-check** (JavaScript). Install via:
```
npm install --save-dev fast-check
```
or include via CDN in a standalone test HTML file if no npm setup exists.

---

## Tasks

- [ ] 1. Fix Track Request active state bug in RequestController
  - In `app/Controllers/Resident/RequestController.php`, locate the `track()` method's `renderWithLayout` call
  - Change `'activePage' => 'requests'` to `'activePage' => 'track'`
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [ ] 2. Sidebar CSS improvements
  - [ ] 2.1 Add sidebar scrollbar-hiding CSS to `public/assets/css/app.css`
    - Append rules under section 27 (Dark Sidebar) to `.sidebar`: `scrollbar-width: none`, `-ms-overflow-style: none`
    - Append `.sidebar::-webkit-scrollbar { display: none }` rule
    - Verify `overflow-y: auto` remains in section 7 (not removed)
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  - [ ] 2.2 Add logout danger styling CSS to `public/assets/css/app.css`
    - Add `.sidebar-nav .nav-link.logout-link` with danger text color `#fca5a5`
    - Add `.sidebar-nav .nav-link.logout-link i` with matching danger color
    - Add hover rule `rgba(239, 68, 68, 0.18)` background for `.logout-link:hover`
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 3. Logout confirmation modal — auth.php restructure
  - [ ] 3.1 Refactor the logout section in `views/layouts/auth.php`
    - Change the logout `<button type="submit">` to `<button type="button">` with class `logout-link`, `data-bs-toggle="modal"`, `data-bs-target="#logoutConfirmModal"`, and `aria-haspopup="dialog"`
    - Remove the `<form method="POST" action=".../logout">` that wraps the button
    - Add a separate hidden `<form id="logoutForm" method="POST" action="{$appUrl}/logout" class="d-none">` containing only the `_csrf_token` hidden input
    - Place the hidden form outside `.sidebar-nav` but before the Bootstrap `</body>` scripts
    - Remove the inline `style="color:rgba(255,255,255,0.75)!important;"` from the logout button (CSS class handles it now)
    - _Requirements: 4.6, 4.9, 4.10, 15.2_
  - [ ] 3.2 Add the Bootstrap 5 Logout_Modal HTML to `views/layouts/auth.php`
    - Add the modal `<div id="logoutConfirmModal">` before the Bootstrap JS `<script>` tag
    - Modal content: title "Sign Out", body text per Requirement 4.3, Cancel button (`data-bs-dismiss="modal"`), "Log Out" button with `id="logoutConfirmBtn"` and `btn-danger` class
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.7, 4.8_

- [ ] 4. Logout modal JavaScript in app.js
  - In `public/assets/js/app.js`, inside the `DOMContentLoaded` block, add event listener on `#logoutConfirmBtn` that calls `document.getElementById('logoutForm').submit()`
  - Guard with null checks: only bind if both `logoutConfirmBtn` and `logoutForm` exist in the DOM
  - _Requirements: 4.5, 4.6, 5.3, 5.4_

- [ ] 5. Checkpoint — Sidebar fixes
  - Ensure all tests pass. Manually verify: (a) Track Request page shows "Track Request" as active, (b) scrollbar is hidden on sidebar, (c) logout button is red/danger-tinted, (d) clicking logout shows the Bootstrap modal, (e) Cancel dismisses without logging out, (f) "Log Out" submits and logs out.
  - Ask the user if questions arise.

- [ ] 6. Refactor Minimal Layout to split layout (`minimal.php`)
  - Replace the single-column card structure in `views/layouts/minimal.php` with the `.minimal-split` two-panel structure
  - Left panel (`.minimal-split-left`): brand seal icon (`bi-buildings`), app name, tagline, feature list (3 bullet points with `bi-check-circle-fill` icons and `--emu-gold-light` color)
  - Right panel (`.minimal-split-right`): contains `<div class="form-card">` wrapping `<?= $content ?>`
  - Remove the old `.minimal-layout` wrapper div and old centered card markup
  - Retain Bootstrap/icon/font/CSS `<link>` tags and Bootstrap JS `<script>` tag unchanged
  - Retain `$appName`, `$appUrl`, `$pageTitle` PHP variables
  - Retain `app.js` script tag
  - _Requirements: 6.1, 6.8, 7.1, 7.11, 14.2, 14.3_

- [ ] 7. Redesign login.php
  - Replace heading with `<h1>Welcome back</h1>` and subtitle "Sign in to continue to eMunicipal"
  - Wrap email field in `input-group` with `<span class="input-group-text"><i class="bi bi-envelope"></i></span>` prefix
  - Wrap password field in `input-group` with `<span class="input-group-text"><i class="bi bi-lock"></i></span>` prefix, retaining `data-pw-toggle="password"` toggle button
  - Change submit button label to "Sign In" (no icon needed, or retain `bi-box-arrow-in-right`)
  - Change footer link text to "Don't have an account? Create an account"
  - Remove `data-loading="Signing in…"` attribute from the `<form>` tag (AJAX handler manages loading state)
  - Add `id="loginForm"` to the form element and `id="loginSubmitBtn"` to the submit button
  - Add `id="loginErrorContainer"` to the form-level error `<div>` (or create one if absent)
  - Preserve `_csrf_token` hidden field, `method="POST"`, `action=".../login"`, and all `autocomplete` attributes
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.9, 6.10, 10.1, 10.5, 10.6, 13.4, 13.5_

- [ ] 8. Redesign register.php
  - Replace heading with `<h1>Create your account</h1>` and subtitle "Fill in your information to get started."
  - Add `bi-lock` icon prefix to password field input-group (retain existing `data-pw-toggle="password"` button)
  - Add `bi-lock` icon prefix to confirm password field input-group (retain existing `data-pw-toggle="password_confirmation"` button)
  - Add Password_Checklist `<ul id="pwChecklist">` with four `<li>` items below the password field (per Component 7 HTML in design.md)
  - Add confirm-match `<div id="pwMatchMsg">` below the confirm password field
  - Remove `data-loading="Creating account…"` from the `<form>` tag
  - Add `id="registerForm"` to the form and `id="registerSubmitBtn"` to the submit button
  - Add `id="registerErrorContainer"` to the form-level error `<div>`
  - Preserve all existing fields (first_name, last_name, email, contact_number, address, password, password_confirmation), their `autocomplete` attributes, and the two-column name row layout
  - Preserve `_csrf_token` hidden field, `method="POST"`, `action=".../register"`
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 7.10, 8.1, 9.1, 10.2, 10.3, 10.4, 10.6, 13.2, 13.3, 13.4, 13.5_

- [ ] 9. Add CSS for password checklist states (`app.css`)
  - Add `.pwc-item` base styles: small font, flex layout with gap, muted default color
  - Add `.pwc-valid` styles: `color: var(--emu-success)` (green)
  - Add `.pwc-invalid` styles: `color: var(--emu-text-muted)` (gray)
  - _Requirements: 8.3, 8.4_

- [ ] 10. Add AJAX and live validation JavaScript (`app.js`)
  - [ ] 10.1 Add `updatePasswordChecklist(password)` function
    - Accepts a password string, evaluates four regex predicates
    - For each of the four `<li>` items: toggles `pwc-valid`/`pwc-invalid` class and swaps icon between `bi-check-circle-fill` and `bi-x-circle-fill`
    - Bind to `#password` on the register form via `input` event
    - _Requirements: 8.2, 8.3, 8.4, 8.5_
  - [ ]* 10.2 Write property test for `updatePasswordChecklist` (Property 1)
    - **Property 1: Password Checklist Correctness**
    - **Validates: Requirements 8.2, 8.5**
    - Use fast-check `fc.string()` to generate arbitrary passwords; assert each criterion matches its predicate independently
    - Tag: `Feature: emunicipal-sidebar-auth-ui-fixes, Property 1: Password Checklist Correctness`
  - [ ] 10.3 Add `updateConfirmMatch(password, confirm)` function
    - Updates `#pwMatchMsg` inner HTML: empty when `confirm === ''`, success icon+text when `password === confirm`, danger icon+text otherwise
    - Bind to `#password` and `#password_confirmation` on the register form via `input` event
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_
  - [ ]* 10.4 Write property test for `updateConfirmMatch` (Property 2)
    - **Property 2: Confirm Password Match Indicator Idempotence**
    - **Validates: Requirements 9.2, 9.3, 9.4**
    - Use fast-check `fc.string()` pairs; assert same output on repeated calls with same input; assert match iff `p === c && c !== ''`
    - Tag: `Feature: emunicipal-sidebar-auth-ui-fixes, Property 2: Confirm Password Match Indicator Idempotence`
  - [ ] 10.5 Add `isLoginSuccess(responseUrl)` predicate
    - Returns `true` if `responseUrl` does NOT contain `/login` as a path segment
    - _Requirements: 11.3, 11.7_
  - [ ]* 10.6 Write property test for `isLoginSuccess` (Property 3)
    - **Property 3: Login AJAX Success URL Detection**
    - **Validates: Requirements 11.3, 11.7**
    - Use fast-check to generate dashboard URLs, login URLs, and arbitrary paths; verify predicate is true iff URL does not contain `/login`
    - Tag: `Feature: emunicipal-sidebar-auth-ui-fixes, Property 3: Login AJAX Success URL Detection`
  - [ ] 10.7 Add `isRegisterSuccess(responseUrl)` predicate
    - Returns `true` if `responseUrl` contains `/login` as a path segment
    - _Requirements: 12.4, 12.8_
  - [ ]* 10.8 Write property test for `isRegisterSuccess` (Property 4)
    - **Property 4: Register AJAX Success URL Detection**
    - **Validates: Requirements 12.4, 12.8**
    - Use fast-check to generate `/login`-containing URLs and `/register`-containing URLs; verify predicate classifies correctly
    - Tag: `Feature: emunicipal-sidebar-auth-ui-fixes, Property 4: Register AJAX Success URL Detection`
  - [ ] 10.9 Add login AJAX form handler
    - Attach `submit` listener to `#loginForm`; call `e.preventDefault()`
    - Disable `#loginSubmitBtn` and show "Signing in..." spinner
    - `fetch(form.action, { method:'POST', body: new FormData(form), redirect:'follow', credentials:'same-origin' })`
    - On success (`isLoginSuccess(resp.url)`): `window.location.href = resp.url`
    - On failure: parse `alert-danger` from response HTML using `DOMParser`; render text in `#loginErrorContainer`; re-enable button
    - Catch network errors: show generic error message; re-enable button
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 11.8_
  - [ ] 10.10 Add register AJAX form handler
    - Attach `submit` listener to `#registerForm`; call `e.preventDefault()`
    - Check `allChecklistItemsValid()` — if false, show inline error and return
    - Disable `#registerSubmitBtn` and show "Creating account..." spinner
    - `fetch(form.action, { method:'POST', body: new FormData(form), redirect:'follow', credentials:'same-origin' })`
    - On success (`isRegisterSuccess(resp.url)`): `window.location.href = resp.url`
    - On failure: parse `.invalid-feedback` and `alert-danger` from response HTML; render inline errors; re-enable button
    - Catch network errors: show generic error; re-enable button
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8, 12.9_

- [ ] 11. Checkpoint — Auth pages
  - Ensure all tests pass. Manually verify: (a) Login page shows split layout at 1024px+ and stacks at 375px, (b) Login error appears inline without page reload, (c) Successful login redirects correctly, (d) Register page checklist updates live as you type, (e) Confirm match indicator works, (f) Registration error appears inline, (g) Successful register redirects to login, (h) Staff action modals still work without interference.
  - Ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP delivery.
- The backend (PHP routes, controllers, AuthController, sessions, CSRF, role auth) is unchanged except the single `$activePage` fix in task 1.
- The `data-loading` attribute is deliberately removed from login/register `<form>` tags (tasks 7 and 8) because AJAX handlers manage loading state manually. Leaving both would cause the `data-loading` mechanism to also fire and conflict.
- Property tests require fast-check. If no npm toolchain exists, they can be run as standalone browser-based tests using the fast-check CDN build.
- All correctness properties reference pure JavaScript functions that are extracted into named functions — this makes them independently testable without mocking DOM or fetch.
- The `.minimal-split-*` CSS classes already exist in `app.css` section 29 — no new CSS is needed for the split layout structure itself.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1", "2.1", "2.2"] },
    { "id": 1, "tasks": ["3.1", "9"] },
    { "id": 2, "tasks": ["3.2", "6"] },
    { "id": 3, "tasks": ["4", "7", "8"] },
    { "id": 4, "tasks": ["10.1", "10.3", "10.5", "10.7"] },
    { "id": 5, "tasks": ["10.2", "10.4", "10.6", "10.8", "10.9", "10.10"] }
  ]
}
```
