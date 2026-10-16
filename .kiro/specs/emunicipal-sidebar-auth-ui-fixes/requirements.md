# Requirements Document

## Introduction

This feature addresses a collection of bug fixes and UI improvements across the eMunicipal PHP monolith. The changes span the authenticated sidebar (active state correctness, scrollbar hiding, logout styling and confirmation), the login page redesign, the registration page redesign with live validation, AJAX form submission for both auth pages, and full preservation of all existing backend authentication and request workflow behavior. All changes are confined to view, layout, CSS, and JS layers. The PHP backend (routes, controllers, models, sessions, CSRF, auth middleware) is not modified except for the one-line `$activePage` bug fix in `RequestController::track()`.

## Glossary

- **Auth_Layout**: The shared authenticated layout rendered by `views/layouts/auth.php`, used by Resident, Staff, and Admin pages.
- **Minimal_Layout**: The unauthenticated layout rendered by `views/layouts/minimal.php`, used by login and register pages.
- **Sidebar**: The left-side navigation panel rendered inside `Auth_Layout`.
- **Sidebar_Nav**: The `<ul class="sidebar-nav">` element inside `Sidebar` that contains navigation links.
- **Active_Page**: The `$activePage` PHP variable passed from a controller to `Auth_Layout` to identify the currently active sidebar link.
- **Logout_Form**: The `<form method="POST" action="/logout">` element inside `Sidebar` that submits the logout POST request.
- **Logout_Modal**: A Bootstrap 5 modal dialog shown before the logout POST is submitted, requiring explicit user confirmation.
- **Login_Page**: The view rendered by `views/auth/login.php` inside `Minimal_Layout`.
- **Register_Page**: The view rendered by `views/auth/register.php` inside `Minimal_Layout`.
- **PW_Toggle**: A `<button type="button">` element that toggles a password input between `type="password"` and `type="text"`.
- **Live_Validation**: JavaScript-driven real-time feedback on input fields that updates on the `input` event without server round-trips.
- **Password_Checklist**: A list of password strength criteria shown below the password field, each updated live as the user types.
- **CSRF_Token**: The `_csrf_token` hidden field required by all POST forms, validated server-side by `SecurityHelper::validateCsrfToken()`.
- **RequestController**: `app/Controllers/Resident/RequestController.php` — the controller that handles resident request actions including `track()`.
- **Staff_Action_Modal**: An existing Bootstrap modal used by staff pages to confirm request status changes (Verify, Approve, Reject, etc.).
- **Split_Layout**: The two-panel (decorative left + form right) responsive layout defined by `.minimal-split-*` CSS classes already present in `app.css`.

---

## Requirements

### Requirement 1: Track Request Sidebar Active State Bug Fix

**User Story:** As a resident, I want the "Track Request" sidebar link to appear active when I am on the Track Request page, so that I can clearly see which section I am currently viewing.

#### Acceptance Criteria

1. WHEN a resident navigates to `/resident/track`, THE Auth_Layout SHALL render the "Track Request" sidebar link with the CSS class `active` and the attribute `aria-current="page"`.
2. WHEN a resident navigates to `/resident/track`, THE Auth_Layout SHALL NOT render the "My Requests" sidebar link with the CSS class `active`.
3. WHEN a resident navigates to `/resident/requests`, THE Auth_Layout SHALL render the "My Requests" sidebar link with the CSS class `active` and SHALL NOT render "Track Request" as active.
4. THE RequestController SHALL set `$activePage` to the string `'track'` when rendering the track view.
5. THE Auth_Layout SHALL determine sidebar link active state exclusively by strict equality comparison `$activePage === '<identifier>'` for each link.

---

### Requirement 2: Sidebar Scrollbar Hidden

**User Story:** As a user, I want the sidebar to not show a visible scrollbar, so that the navigation panel has a clean, uncluttered appearance.

#### Acceptance Criteria

1. THE Sidebar SHALL hide its native scrollbar on all modern browsers while retaining overflow scroll functionality for content that exceeds viewport height.
2. THE Sidebar SHALL apply `scrollbar-width: none` (Firefox) and `-ms-overflow-style: none` (IE/Edge) to hide the scrollbar.
3. THE Sidebar SHALL apply `::-webkit-scrollbar { display: none }` to hide the scrollbar in WebKit browsers (Chrome, Safari).
4. WHEN Sidebar content overflows viewport height, THE Sidebar SHALL remain scrollable by touch and mouse wheel even with the scrollbar hidden.

---

### Requirement 3: Logout Danger Styling

**User Story:** As a user, I want the logout button to be visually styled as a danger/warning action, so that it is clearly distinct from regular navigation links and I do not accidentally click it.

#### Acceptance Criteria

1. THE Auth_Layout SHALL render the logout button with a red/danger text color consistent with `--emu-danger` (`#b91c1c`) or a visually equivalent danger tone suitable for the dark sidebar theme.
2. THE Auth_Layout SHALL render the logout button icon as `bi-box-arrow-right` in a danger/red color.
3. WHEN a user hovers over the logout button, THE Auth_Layout SHALL display a subtle danger/red hover background on the logout item.
4. THE logout button danger styling SHALL be visually consistent with the dark sidebar background defined by `--emu-primary` (`#1a3a5c`).
5. THE logout button danger styles SHALL apply when the authenticated user role is resident, staff, or admin (shared Auth_Layout).

---

### Requirement 4: Logout Confirmation Modal

**User Story:** As a user, I want to see a confirmation dialog before being logged out, so that I do not lose my session accidentally by clicking logout.

#### Acceptance Criteria

1. WHEN a user clicks the logout button, THE Auth_Layout SHALL display the Logout_Modal before submitting the logout POST request.
2. THE Logout_Modal SHALL display the title "Sign Out".
3. THE Logout_Modal SHALL display the body text "Are you sure you want to log out? You will need to sign in again to access your account."
4. THE Logout_Modal SHALL contain a cancel button labeled "Cancel" styled as secondary/outline that dismisses the modal without logging out.
5. THE Logout_Modal SHALL contain a confirm button labeled "Log Out" styled as danger that submits the Logout_Form.
6. WHEN the confirm button in Logout_Modal is clicked, THE Auth_Layout SHALL submit the Logout_Form as a POST request to `/logout` with the `_csrf_token` field intact.
7. THE Auth_Layout SHALL NOT use `window.confirm()` for the logout confirmation.
8. THE Auth_Layout SHALL implement the Logout_Modal using Bootstrap 5 modal JavaScript.
9. THE Logout_Form SHALL NOT be submitted directly by the logout button; it SHALL only be submitted via the Logout_Modal confirm button.
10. THE Logout_Modal trigger SHALL be scoped to the logout button exclusively and SHALL NOT affect other forms in Auth_Layout.

---

### Requirement 5: Staff Request Action Modals Unaffected

**User Story:** As a staff member, I want my existing request action buttons (Verify, Approve, Reject, etc.) to continue working with their existing confirmation behavior, so that the logout modal change does not interfere with request processing.

#### Acceptance Criteria

1. THE Staff_Action_Modal behavior for Verify, Approve, Reject, Additional Info, Mark Ready, Mark Completed, and Cancel actions SHALL remain unchanged after the logout modal is introduced.
2. THE Auth_Layout SHALL NOT attach any additional confirmation behavior to forms other than the Logout_Form.
3. THE Auth_Layout SHALL NOT use `window.confirm()` for any request action.
4. THE Logout_Modal trigger SHALL be bound exclusively to the logout button element and SHALL NOT propagate to other form submit events.

---

### Requirement 6: Login Page Redesign

**User Story:** As a visitor, I want to see a visually modern and welcoming login page, so that signing in feels professional and easy.

#### Acceptance Criteria

1. THE Login_Page SHALL use the Split_Layout (`minimal-split` CSS class), presenting a decorative brand panel on the left and the login form on the right on desktop viewports (≥768px).
2. THE Login_Page SHALL render the form panel with a white/light card (`form-card` class) with the heading "Welcome back" and the subtitle "Sign in to continue to eMunicipal".
3. THE Login_Page SHALL render the email field with a `bi-envelope` icon prefix inside an input group.
4. THE Login_Page SHALL render the password field with a `bi-lock` icon prefix inside an input group.
5. THE Login_Page SHALL retain the existing PW_Toggle button on the password field (`data-pw-toggle="password"`).
6. THE Login_Page SHALL render the primary submit button with full width and label "Sign In" styled with `btn-primary`.
7. THE Login_Page SHALL render a link to the register page with the text "Don't have an account? Create an account".
8. WHEN the viewport width is less than 768px, THE Login_Page SHALL stack the brand panel above the form panel and the brand panel SHALL reduce to a compact header height.
9. THE Login_Page SHALL preserve the existing CSRF_Token hidden field, form action `/login`, and method `POST`.
10. THE Login_Page SHALL display server-side error messages (from `$errors['form']`) in a visible alert near the top of the form card.

---

### Requirement 7: Register Page Redesign

**User Story:** As a new visitor, I want to see a clean, modern registration form, so that creating an account is easy and does not feel overwhelming.

#### Acceptance Criteria

1. THE Register_Page SHALL use the Split_Layout, presenting a decorative brand panel on the left and the registration form on the right on desktop viewports (≥768px).
2. THE Register_Page SHALL render the form panel with the heading "Create your account" and the subtitle "Fill in your information to get started."
3. THE Register_Page SHALL render First Name and Last Name fields side-by-side in a two-column grid on viewports ≥576px.
4. WHEN the viewport width is less than 576px, THE Register_Page SHALL stack First Name and Last Name fields into a single column.
5. THE Register_Page SHALL retain all existing fields: first_name, last_name, email, contact_number, address, password, and password_confirmation.
6. THE Register_Page SHALL render the password field with a `bi-lock` icon prefix inside an input group.
7. THE Register_Page SHALL render the confirm password field with a `bi-lock` icon prefix inside an input group.
8. THE Register_Page SHALL preserve PW_Toggle buttons on both the password and confirm password fields.
9. THE Register_Page SHALL preserve the existing CSRF_Token hidden field, form action `/register`, and method `POST`.
10. THE Register_Page SHALL display server-side error messages from `$errors` in their respective field locations.
11. WHEN the viewport width is less than 768px, THE Register_Page SHALL stack the brand panel above the form and compact it as a header.

---

### Requirement 8: Live Password Strength Validation (Register Page)

**User Story:** As a new user filling out the registration form, I want to see real-time feedback on my password strength as I type, so that I know exactly what the password requirements are and whether I have met them.

#### Acceptance Criteria

1. THE Register_Page SHALL display a Password_Checklist below the password field containing four criteria items:
   - "Password must be over 8 characters"
   - "Password must contain at least 1 number"
   - "Password must contain at least 1 special character"
   - "Password must contain uppercase and lowercase letters"
2. WHEN a user types in the password field, THE Register_Page SHALL update each Password_Checklist item on every `input` event.
3. WHEN a Password_Checklist criterion is not satisfied, THE Register_Page SHALL render that criterion with a red/danger icon (`bi-x-circle` or equivalent) and muted/gray text.
4. WHEN a Password_Checklist criterion is satisfied, THE Register_Page SHALL render that criterion with a green/success icon (`bi-check-circle` or equivalent) and green text.
5. THE Password_Checklist criteria SHALL map to the following JavaScript rules:
   - "Over 8 characters": `password.length > 8`
   - "At least 1 number": `/[0-9]/.test(password)`
   - "At least 1 special character": `/[^A-Za-z0-9]/.test(password)`
   - "Uppercase and lowercase letters": `/[A-Z]/.test(password) && /[a-z]/.test(password)`
6. THE Password_Checklist SHALL be purely client-side and SHALL NOT replace server-side validation in `Validator::passwordStrength()`.

---

### Requirement 9: Live Confirm Password Match Validation (Register Page)

**User Story:** As a new user, I want to see immediate feedback when my confirm password field matches or mismatches my chosen password, so that I can correct errors before submitting the form.

#### Acceptance Criteria

1. THE Register_Page SHALL display a match-status message below the confirm password field.
2. WHEN the confirm password field value does not match the password field value AND the confirm password field is non-empty, THE Register_Page SHALL display "Passwords do not match" in red/danger color with a `bi-x-circle` icon.
3. WHEN the confirm password field value matches the password field value AND the confirm password field is non-empty, THE Register_Page SHALL display "Passwords match" in green/success color with a `bi-check-circle` icon.
4. WHEN the confirm password field is empty, THE Register_Page SHALL hide the match-status message.
5. THE match-status message SHALL update on every `input` event on either the password field or the confirm password field.

---

### Requirement 10: Password Visibility Toggle

**User Story:** As a user on the login or registration page, I want to toggle password field visibility, so that I can verify what I have typed.

#### Acceptance Criteria

1. THE Login_Page SHALL provide a PW_Toggle button adjacent to the password field that toggles the field between `type="password"` and `type="text"`.
2. THE Register_Page SHALL provide a PW_Toggle button adjacent to the password field that toggles the field between `type="password"` and `type="text"`.
3. THE Register_Page SHALL provide a PW_Toggle button adjacent to the confirm password field that toggles the field between `type="password"` and `type="text"`.
4. WHEN the password is visible (type="text"), THE PW_Toggle button SHALL display the `bi-eye-slash` icon and carry the `aria-label` "Hide password".
5. WHEN the password is hidden (type="password"), THE PW_Toggle button SHALL display the `bi-eye` icon and carry the `aria-label` "Show password".
6. THE PW_Toggle button SHALL be of type `button` (not `submit`) to prevent accidental form submission.

---

### Requirement 11: Login AJAX Form Submission

**User Story:** As a user signing in, I want validation errors to appear inline without a full page reload, so that the sign-in experience feels smooth and fast.

#### Acceptance Criteria

1. WHEN the login form is submitted, THE Login_Page SHALL intercept the submission with `event.preventDefault()` and send the form data as a `fetch()` POST request to the existing `/login` endpoint.
2. THE Login_Page fetch request SHALL include the `_csrf_token` field from the existing hidden input.
3. WHEN the server returns a successful response (redirect to a dashboard URL), THE Login_Page SHALL redirect the browser to the role-based dashboard URL returned by the server.
4. WHEN the server returns a response indicating authentication failure (redirect back to `/login`), THE Login_Page SHALL display the error message inline in the form without a full page reload.
5. DURING the fetch request, THE Login_Page SHALL disable the submit button and display the loading label "Signing in..." with a spinner indicator.
6. AFTER the fetch request completes (success or failure), THE Login_Page SHALL re-enable the submit button if the page has not navigated away.
7. THE Login_Page AJAX handler SHALL detect server success by inspecting the final `response.url` after following redirects and navigating when it does not point back to `/login`.
8. THE Login_Page SHALL NOT create any new server endpoints; it SHALL use the existing `POST /login` endpoint.

---

### Requirement 12: Register AJAX Form Submission

**User Story:** As a new user registering, I want registration errors to appear inline without a full page reload, so that I can quickly correct mistakes.

#### Acceptance Criteria

1. WHEN the register form is submitted, THE Register_Page SHALL first execute client-side Password_Checklist validation and SHALL NOT submit if any criterion fails.
2. WHEN client-side validation passes, THE Register_Page SHALL intercept the submission with `event.preventDefault()` and send the form data as a `fetch()` POST request to the existing `/register` endpoint.
3. THE Register_Page fetch request SHALL include the `_csrf_token` field from the existing hidden input.
4. WHEN the server returns a successful response (redirect to `/login`), THE Register_Page SHALL redirect the browser to `/login`.
5. WHEN the server returns a response indicating validation errors (redirect back to `/register`), THE Register_Page SHALL display server error messages inline in the form.
6. DURING the fetch request, THE Register_Page SHALL disable the submit button and display the loading label "Creating account..." with a spinner indicator.
7. AFTER the fetch request completes, THE Register_Page SHALL re-enable the submit button if the page has not navigated away.
8. THE Register_Page AJAX handler SHALL detect server success by inspecting the final `response.url` and navigating when it points to `/login`.
9. THE Register_Page SHALL NOT create any new server endpoints; it SHALL use the existing `POST /register` endpoint.

---

### Requirement 13: Error Display and Field-Level Accessibility

**User Story:** As a user experiencing form errors, I want error messages to appear near the relevant fields and be accessible to screen readers, so that I can understand and correct errors efficiently.

#### Acceptance Criteria

1. THE Login_Page and Register_Page SHALL display form-level error messages in a visible `role="alert"` element near the top of the form card.
2. THE Register_Page SHALL display field-level server validation error messages immediately below their respective input fields using Bootstrap's `.invalid-feedback` pattern.
3. WHEN a field has a server validation error, THE Register_Page SHALL apply the `is-invalid` class to that input and the `aria-invalid="true"` attribute.
4. THE Login_Page and Register_Page form inputs SHALL have matching `id` and `for` attributes on their `<label>` elements.
5. THE Login_Page and Register_Page form inputs SHALL include appropriate `autocomplete` attributes: `email` for email fields, `current-password` for login password, `new-password` for registration password fields.
6. WHEN the AJAX error display renders server errors returned via flash data in the redirected HTML page, THE Login_Page and Register_Page SHALL parse and render these errors in the inline error containers.

---

### Requirement 14: Responsive Design

**User Story:** As a user on any device, I want the login and register pages to be usable and well-presented on both desktop and mobile viewports, so that I can access municipal services from any device.

#### Acceptance Criteria

1. THE Login_Page and Register_Page SHALL be usable and visually correct at viewport widths: 1440px, 1280px, 1024px, 768px, 390px, and 375px.
2. WHEN the viewport width is less than 768px, THE Login_Page and Register_Page SHALL render the Split_Layout left panel as a compact header row above the form card.
3. WHEN the viewport width is greater than or equal to 768px, THE Login_Page and Register_Page SHALL render the Split_Layout with the decorative panel on the left and the form on the right.
4. THE Login_Page form card SHALL have a maximum width of approximately 420px on desktop.
5. THE Register_Page form card SHALL expand to fill available width on narrow viewports.

---

### Requirement 15: Preservation of Existing Functionality

**User Story:** As a system maintainer, I want all existing authentication, authorization, and request workflow functionality to remain unbroken after the UI changes, so that users are not disrupted.

#### Acceptance Criteria

1. THE Auth_Layout SHALL preserve all existing routes, controller logic, session management, CSRF validation, and role-based access controls unchanged.
2. THE Logout_Form SHALL continue to POST to `/logout` with a valid `_csrf_token` after the Logout_Modal is introduced.
3. THE Login_Page form action SHALL remain `POST /login` and the CSRF_Token hidden field SHALL remain present.
4. THE Register_Page form action SHALL remain `POST /register` and the CSRF_Token hidden field SHALL remain present.
5. THE existing sidebar active states for dashboard, requests (My Requests), new-request, notifications, profile, users, and document-types links SHALL continue to function correctly via `$activePage` strict equality comparison.
6. THE Staff_Action_Modal behavior for all request status changes SHALL remain unchanged.
7. THE existing PW_Toggle JavaScript in `app.js` (`data-pw-toggle` attribute pattern) SHALL continue to function on both login and register pages.
8. THE existing double-submit prevention mechanism (`data-loading` attribute on forms) SHALL NOT conflict with the new AJAX submission behavior.
9. THE sidebar mobile toggle behavior (`#sidebarToggle`, `sidebar-open` class, overlay) SHALL remain unchanged.
10. THE existing flash message display (success, error, info alerts with auto-dismiss) in Auth_Layout SHALL remain unchanged.
