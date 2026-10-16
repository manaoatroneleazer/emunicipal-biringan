# Requirements Document

## Introduction

This document specifies the UI/UX improvement and integration pass for the **eMunicipal — Online Municipal Document Request and Management System**. The system is a functional PHP/MySQL/XAMPP monolithic MVC application with Bootstrap 5 and a defined design token system (`app.css`). The core backend is complete and tested.

The goal is a **polish pass**: resolve visual and interaction inconsistencies, add missing UI states, standardize component usage, and produce a presentation-quality Philippine municipal government web application — without rebuilding the backend, changing the database schema, or introducing new frameworks.

All requirements operate within the existing constraints: PHP views, Bootstrap 5, Vanilla JS (`app.js`), and the established CSS design token system (`--emu-*` variables).

---

## Glossary

- **Design System**: The existing set of CSS custom properties (`--emu-*`), component classes (`.status-badge`, `.metric-card`, `.empty-state`, etc.), and Bootstrap 5 overrides defined in `public/assets/css/app.css`.
- **Auth Layout**: The authenticated two-column layout (`views/layouts/auth.php`) consisting of a fixed header, collapsible sidebar, and main content area (`.auth-content`).
- **Minimal Layout**: The centered single-column layout (`views/layouts/minimal.php`) used for login and registration pages.
- **Public Layout**: The marketing layout (`views/layouts/public.php`) with `.navbar-public` and `.footer-public`.
- **Status Badge**: An inline element using the `.status-badge` class plus a per-status modifier (e.g., `.status-pending`, `.status-approved`) to display request status values.
- **Status Value**: One of eight values — `PENDING`, `UNDER_VERIFICATION`, `ADDITIONAL_INFO_REQUIRED`, `APPROVED`, `REJECTED`, `READY_FOR_CLAIMING`, `COMPLETED`, `CANCELLED`.
- **Empty State**: A `.empty-state` block shown in place of a list or table when no records exist.
- **Flash Message**: A session-based one-time alert rendered as a Bootstrap `.alert` with the `.alert-flash` class for auto-dismiss behavior.
- **Double-Submit Prevention**: Disabling the submit button and showing a loading spinner after the first click to prevent duplicate form submissions.
- **Confirmation Dialog**: A browser `confirm()` or Bootstrap modal that requires explicit user acknowledgment before executing a destructive or irreversible action.
- **app.js**: The single Vanilla JS file at `public/assets/js/app.js` containing all application-level JavaScript logic.
- **Resident**: An authenticated user with the `resident` role who submits and tracks document requests.
- **Staff**: An authenticated user with the `staff` role who reviews and processes requests.
- **Admin**: An authenticated user with the `admin` role who manages users, document types, and requirements.
- **Breadcrumb**: A `.breadcrumb` navigation trail showing the current page's position within the application hierarchy.
- **View File**: A PHP file under `views/` that renders HTML for a specific page or partial.

---

## Requirements

### Requirement 1 — Design System Consistency

**User Story:** As a user, I want every page to use the same visual components so that the application feels coherent and professional throughout.

#### Acceptance Criteria

1. THE System SHALL render all status values exclusively using the `.status-badge` class combined with the corresponding status modifier class (`.status-pending`, `.status-under_verification`, `.status-additional_info_required`, `.status-approved`, `.status-rejected`, `.status-ready_for_claiming`, `.status-completed`, `.status-cancelled`) in every view file where a status is displayed.
2. THE System SHALL apply `--emu-*` CSS custom property tokens for all color, spacing, shadow, and typography values added or modified during the UI pass, with no hardcoded hex or RGB color values introduced outside `app.css`.
3. THE System SHALL render all primary action buttons using `.btn.btn-primary`, all secondary action buttons using `.btn.btn-outline-secondary` or `.btn.btn-outline-primary`, and all destructive action buttons using `.btn.btn-danger`, consistently across all view files.
4. THE System SHALL apply the `.card` class with `.card-header` and `.card-body` for all content groupings that are currently presented as unstyled `<div>` blocks in view files.
5. WHEN a Flash Message is rendered, THE System SHALL output it as a Bootstrap `.alert.alert-flash.alert-dismissible` element with an accessible close button and a Bootstrap Icons icon matching the alert type (success, warning, danger, info).

---

### Requirement 2 — Global Layout Polish

**User Story:** As a user, I want a consistent, recognizable header, sidebar, and footer on every authenticated page so that navigation is always predictable.

#### Acceptance Criteria

1. THE Auth Layout SHALL display a visible application name or logo mark in the fixed header alongside the sidebar toggle button, a notification bell icon with unread count badge, and a user menu dropdown showing the authenticated user's name and role.
2. THE Auth Layout SHALL include a `.sidebar-overlay` `<div>` element immediately after the sidebar `<nav>` element for mobile backdrop dismissal.
3. WHEN the viewport width is less than 992px, THE Auth Layout SHALL hide the sidebar off-canvas and render the `.btn-sidebar-toggle` button in the header as the sole sidebar trigger; THE `.btn-sidebar-toggle` button SHALL function to toggle sidebar visibility regardless of the current sidebar visibility state.
4. THE Auth Layout SHALL render a `.breadcrumb` navigation element at the top of the `.auth-content` area in every view, showing at minimum the root ("Home") link and the current page label.
5. THE Public Layout SHALL render a footer with the `.footer-public` class containing the municipality name, contact information placeholder, and a copyright line using the `.footer-bottom` class; the Auth Layout and Minimal Layout SHALL NOT be required to render municipality branding in a footer.
6. THE Minimal Layout SHALL render the eMunicipal logo or municipality name above the form card and a brief tagline below it, vertically and horizontally centered within the `.minimal-layout` wrapper.

---

### Requirement 3 — Login and Registration Pages

**User Story:** As a user, I want the login and registration pages to look polished and professional so that first impressions of the system are trustworthy.

#### Acceptance Criteria

1. THE Login Page SHALL render within the `.minimal-layout` wrapper with a card of maximum width 440px, containing the municipality seal or name, a heading, and the login form.
2. WHEN the user clicks the password visibility toggle button on the Login Page, THE System SHALL toggle the `<input>` `type` attribute between `password` and `text` and update the toggle button icon between `bi-eye` and `bi-eye-slash`.
3. WHEN the user submits the login form, THE System SHALL disable the submit button and replace its label with a Bootstrap spinner and the text "Signing in…" until the page navigates away or an error is returned.
4. IF a login validation error exists in the session flash, THEN THE Login Page SHALL render it inside an `.alert.alert-danger` element above the form fields.
5. THE Registration Page SHALL apply the same `.minimal-layout` wrapper, password toggle, and Double-Submit Prevention behavior as the Login Page.

---

### Requirement 4 — Resident Dashboard

**User Story:** As a Resident, I want my dashboard to show a clear summary of my request activity so that I can immediately understand the state of my pending and recent requests.

#### Acceptance Criteria

1. THE Resident Dashboard SHALL display metric cards using the `.metric-card` class for at minimum the following counts: total requests, pending requests, and completed requests.
2. WHEN the Resident has no submitted requests, THE Resident Dashboard SHALL display an `.empty-state` block with a descriptive message and a link to submit a new request, and SHALL NOT render the recent requests table simultaneously.
3. WHEN the Resident has one or more submitted requests, THE Resident Dashboard SHALL display the five most recent requests in a table or card list, each row showing the reference number, document type name, submission date, and a Status Badge.
4. WHEN a recent request row is clicked or activated via keyboard, THE System SHALL navigate to the request detail page for that request.

---

### Requirement 5 — New Request Form (Resident)

**User Story:** As a Resident, I want the new request form to be clear, guided, and error-resistant so that I submit complete and valid requests on the first attempt.

#### Acceptance Criteria

1. THE New Request Form SHALL group document type selection, request details, and file upload into visually distinct sections using `.card` elements with descriptive `.card-header` labels.
2. THE New Request Form SHALL display a required-field indicator (an asterisk `*` styled in `--emu-danger` color) adjacent to every required `<label>` element.
3. WHEN a document type is selected, THE New Request Form SHALL display the list of required supporting documents for that document type within the form before the file upload section.
4. WHEN the user submits the New Request Form and a validation error exists, THE System SHALL re-render the form with each invalid field marked using Bootstrap's `.is-invalid` class and a `.invalid-feedback` element containing the specific error message.
5. WHEN the user clicks the submit button on the New Request Form, THE System SHALL apply Double-Submit Prevention by disabling the button and displaying a spinner until the server responds; the prevention state SHALL NOT be applied on page load, validation events, or any trigger other than an actual button click.
6. WHEN the user selects one or more files in the file upload input, THE System SHALL display each selected filename below the input before submission.

---

### Requirement 6 — My Requests List (Resident)

**User Story:** As a Resident, I want to see all my requests in a clear, scannable list so that I can monitor progress and take action where needed.

#### Acceptance Criteria

1. THE My Requests Page SHALL render all requests in a responsive table with columns for reference number, document type, date submitted, status (as a Status Badge), and an actions column.
2. WHEN the Resident has no submitted requests, THE My Requests Page SHALL display an `.empty-state` block containing at minimum a message; WHERE contextually appropriate, THE System SHALL also include a Bootstrap Icons icon element and a button linking to the new request page.
3. THE My Requests Page SHALL display requests sorted by submission date descending by default.

---

### Requirement 7 — Request Detail Page (Resident)

**User Story:** As a Resident, I want to see the full details and history of my request so that I know exactly where my request stands and what is required of me.

#### Acceptance Criteria

1. THE Request Detail Page (Resident) SHALL display the reference number, document type, purpose, copies requested, submission date, and current status in a structured layout using `.card` elements.
2. THE Request Detail Page (Resident) SHALL render the status history as a timeline using the `.timeline` and `.timeline-item` classes, with each entry showing the new status (as a Status Badge), the date, and any staff remark.
3. WHEN the request status is `ADDITIONAL_INFO_REQUIRED`, THE Request Detail Page (Resident) SHALL display a prominent `.alert.alert-warning` block above the timeline identifying that additional information is needed.
4. WHEN the request status is one of `PENDING` or `UNDER_VERIFICATION` or `ADDITIONAL_INFO_REQUIRED`, THE Request Detail Page (Resident) SHALL render a "Cancel Request" button that triggers a Confirmation Dialog before submitting the cancellation form.
5. WHEN the Resident confirms the cancellation, THE System SHALL submit the cancellation form regardless of whether Double-Submit Prevention activates; WHERE Double-Submit Prevention is active, THE System SHALL apply it to the confirm action without blocking form submission.
6. WHEN the request was just submitted (the `?submitted=1` query parameter is present), THE Request Detail Page (Resident) SHALL display a `.alert.alert-success.alert-flash` banner confirming submission and showing the reference number.

---

### Requirement 8 — Request Tracking Page (Resident)

**User Story:** As a Resident, I want to search for a request by reference number so that I can quickly check its current status without browsing my full list.

#### Acceptance Criteria

1. THE Track Request Page SHALL render a search form with a single reference number input and a submit button.
2. WHEN a reference number is submitted and a matching request is found, THE Track Request Page SHALL display the request status (as a Status Badge) and the status history timeline.
3. IF a reference number is submitted and no matching request is found, THEN THE Track Request Page SHALL display a `.alert.alert-warning` message indicating the reference number was not found.

---

### Requirement 9 — Staff Request List

**User Story:** As a Staff member, I want a clear, filterable list of all requests assigned to my processing queue so that I can prioritize and act on them efficiently.

#### Acceptance Criteria

1. THE Staff Request List Page SHALL render all requests in a responsive table with columns for reference number, resident name, document type, date submitted, status (as a Status Badge), and a view-detail action link.
2. WHEN the Staff Request List table contains no records, THE System SHALL display an `.empty-state` block inside the table area, regardless of whether the absence of records is due to applied filters or an initially empty dataset.
3. THE Staff Request List Page SHALL include filter controls for status with a submit button; filter state SHALL be preserved in the URL query string.

---

### Requirement 10 — Staff Request Detail and Workflow

**User Story:** As a Staff member, I want the request detail page to present all information and workflow actions in a structured layout so that I can process requests accurately without confusion.

#### Acceptance Criteria

1. THE Staff Request Detail Page SHALL use a two-column layout on viewports ≥768px: a primary column (≥8 Bootstrap grid columns) for request information and documents, and a secondary column (≤4 Bootstrap grid columns) for workflow actions.
2. THE Staff Request Detail Page SHALL display the submitted documents as a list with each document's original filename, file type, file size, and a download link.
3. THE Staff Request Detail Page SHALL render the status history timeline using `.timeline` and `.timeline-item` classes, consistent with the Resident view.
4. THE Staff Workflow Action Form SHALL render all status transitions valid for the request's current status; invalid transitions SHALL NOT be rendered as selectable options.
5. WHEN a Staff member selects the `REJECTED` or `CANCELLED` status transition, THE System SHALL require a non-empty remark before the form can be submitted.
6. WHEN a Staff member submits a status update form, THE System SHALL apply Double-Submit Prevention.
7. WHEN a Staff member selects a destructive status transition (`REJECTED`, `CANCELLED`), THE System SHALL display a Confirmation Dialog before the form submits.

---

### Requirement 11 — Admin Dashboard

**User Story:** As an Admin, I want a dashboard showing system-wide request metrics and recent activity so that I can monitor the system state at a glance.

#### Acceptance Criteria

1. THE Admin Dashboard SHALL display metric cards using the `.metric-card` class for system-wide counts of total requests, pending requests, approved requests, and completed requests.
2. WHEN the Admin Dashboard has no recent requests to display, THE System SHALL show an `.empty-state` block in the recent requests section and SHALL NOT render the recent requests table structure.
3. WHEN the Admin Dashboard has one or more recent requests to display, THE Admin Dashboard SHALL display a table of the ten most recent requests system-wide, each row showing reference number, resident name, document type, status (as a Status Badge), and a link to the admin request detail.

---

### Requirement 12 — Admin User Management

**User Story:** As an Admin, I want the user management pages to be consistent with the rest of the admin UI so that user administration is efficient and error-free.

#### Acceptance Criteria

1. THE Admin Users List Page SHALL render users in a responsive table with columns for name, email, role (as a styled badge), account status, registration date, and action links.
2. WHEN the Admin Users List has no records, THE System SHALL display an `.empty-state` block.
3. WHEN an Admin activates or deactivates a user account, THE System SHALL display a Confirmation Dialog before the action form submits.
4. THE Admin Create User Form SHALL apply the same required-field indicators, `.is-invalid` validation state rendering, and Double-Submit Prevention as specified in Requirement 5.

---

### Requirement 13 — Admin Request Management

**User Story:** As an Admin, I want to view and oversee all requests in the system with the same quality of layout as the Staff view, so that I can intervene or audit when needed.

#### Acceptance Criteria

1. THE Admin Request List Page SHALL render requests in a responsive table equivalent to the Staff Request List (Requirement 9, AC 1), with an additional column for the assigned staff member if applicable.
2. WHEN the Admin Request List contains no records, THE System SHALL display an `.empty-state` block, regardless of whether the absence of records is due to applied filters or an initially empty dataset.
3. THE Admin Request Detail Page SHALL present request information, documents, and status history using the same card-and-timeline layout pattern as the Staff Request Detail Page (Requirement 10).

---

### Requirement 14 — Document Types Management (Admin)

**User Story:** As an Admin, I want the document type management pages to visually group document types and requirements so that the relationship between them is immediately clear.

#### Acceptance Criteria

1. THE Document Types List Page SHALL render document types in a table with columns for name, office/category, active status (as a styled badge), and action links (edit, manage requirements).
2. WHEN a document type's active status is toggled and the status value will change, THE System SHALL apply a Confirmation Dialog before submitting the toggle form; WHERE a browser `confirm()` dialog is unavailable, THE System SHALL allow an alternative confirmation flow before submitting.
3. THE Document Requirements List Page SHALL display requirements grouped under their parent document type name as a visible section heading.

---

### Requirement 15 — Forms Standardization

**User Story:** As a user, I want all forms across the application to look and behave consistently so that I always know what is required and how to correct errors.

#### Acceptance Criteria

1. THE System SHALL apply the `.form-label` class to every `<label>` element in every form across all view files.
2. THE System SHALL render helper text for form fields that require format or content guidance using `<div class="form-text">` elements placed directly after the associated `<input>` or `<textarea>` element.
3. WHEN a form field fails server-side validation, THE System SHALL add the `.is-invalid` class to the field element and render a sibling `.invalid-feedback` element containing the error message.
4. THE System SHALL apply the `autocomplete` attribute with an appropriate value to all authentication form inputs (username/email, password, new-password).

---

### Requirement 16 — Loading States and Double-Submit Prevention

**User Story:** As a user, I want the system to prevent duplicate submissions and give clear feedback when an action is processing so that I do not accidentally submit forms twice.

#### Acceptance Criteria

1. THE app.js file SHALL contain a reusable function that, when called on a `<form>` element, disables the submit button and injects a Bootstrap spinner and loading label into the button upon form submission.
2. THE System SHALL apply the Double-Submit Prevention behavior to the new request form, the cancellation confirmation form, all staff workflow action forms, and all admin action forms that create or modify data.
3. WHEN a form with Double-Submit Prevention is submitted, THE System SHALL set the button's `disabled` attribute and replace the button text with a spinner icon and a context-appropriate loading message (e.g., "Submitting…", "Saving…", "Updating…").
4. IF client-side Double-Submit Prevention fails to activate, THEN THE System SHALL allow the form submission to proceed to the server without blocking; server-side processing SHALL NOT be conditional on client-side prevention having activated.

---

### Requirement 17 — Empty States

**User Story:** As a user, I want to see a helpful message when a list has no items so that I understand why the list is empty and know what action to take next.

#### Acceptance Criteria

1. THE System SHALL render an `.empty-state` block in every list or table view when the data set is empty, including: Resident My Requests, Resident Dashboard recent requests, Staff Request List, Admin Request List, Admin User List, and Document Types List.
2. THE Empty State block SHALL contain a Bootstrap Icons icon element, a short heading, a descriptive sentence explaining why the list is empty, and (where applicable) a call-to-action button or link.
3. WHEN an Empty State is rendered inside a table, THE System SHALL render it as a single `<tr>` containing a `<td colspan="[full column count]">` wrapping the `.empty-state` markup.

---

### Requirement 18 — Error Pages

**User Story:** As a user, I want error pages to look like part of the application so that I understand what happened and can navigate back without confusion.

#### Acceptance Criteria

1. THE 404 Error Page SHALL render using the Public Layout with the application header and navigation, displaying an HTTP 404 status heading, a plain-language explanation, and a link back to the homepage.
2. THE 403 Error Page SHALL render using the Public Layout, displaying an HTTP 403 status heading, a plain-language explanation, and a link back to the homepage.
3. THE 500 Error Page SHALL render using the Public Layout, displaying a generic error message with no stack trace, exception details, or file path information exposed in the HTML output.
4. THE System SHALL expose no stack trace, exception details, or file path information in the HTML output of any error page (404, 403, or 500).
5. THE System SHALL set the correct HTTP response status code (`404`, `403`, or `500`) in the response header for each respective error page, using exactly the matching status code and not a related or substitute code.

---

### Requirement 19 — Responsive Design

**User Story:** As a user on any device, I want the application to be fully usable on screens from 320px to 1440px wide so that I am not blocked from accessing services on a mobile phone.

#### Acceptance Criteria

1. THE System SHALL ensure all tables in the application are wrapped in a `<div class="table-responsive">` element so that horizontal scrolling is available on narrow viewports.
2. WHILE the viewport width is less than 576px, THE Auth Layout SHALL render sidebar navigation fully off-canvas and the main content SHALL occupy the full viewport width with appropriate padding.
3. THE New Request Form SHALL stack its document type selection, details, and file upload sections vertically on viewports narrower than 768px.
4. THE Metric Cards on all dashboards SHALL collapse from a multi-column Bootstrap grid row to a single-column stack on viewports narrower than 576px.

---

### Requirement 20 — Accessibility

**User Story:** As a user with accessibility needs, I want the application to be navigable by keyboard and screen reader so that I can access municipal services without barriers.

#### Acceptance Criteria

1. THE System SHALL associate every form `<input>`, `<select>`, and `<textarea>` element with a corresponding `<label>` element via matching `for` and `id` attributes in every view file.
2. THE System SHALL apply `aria-label` or `aria-labelledby` attributes to all icon-only buttons (sidebar toggle, password toggle, notification bell, close buttons) across all view files.
3. THE System SHALL ensure all interactive elements (links, buttons, form controls) are reachable and operable via sequential keyboard navigation (Tab key) in logical DOM order.
4. WHEN a modal or confirmation dialog is displayed on screen, THE System SHALL attempt to move keyboard focus to the modal's first focusable element and trap focus within the modal until it is dismissed; IF focus management fails, THEN THE System SHALL allow the modal to remain open and accessible.
5. THE System SHALL apply `role="alert"` to Flash Message `.alert` elements so that screen readers announce the message when it appears.

---

### Requirement 21 — Micro-interactions

**User Story:** As a user, I want subtle, restrained visual feedback on interactive elements so that the interface feels responsive without being distracting.

#### Acceptance Criteria

1. THE System SHALL apply a CSS `transition` property (duration 150ms, `ease` timing) to all interactive elements that change color or background on hover, using only properties already defined in `app.css` or added within `app.css`.
2. THE Status Badge elements SHALL NOT have hover or animation effects, as they are display-only indicators.
3. THE `.doc-type-card` selectable card elements SHALL apply a border color transition to `--emu-primary` on hover and a `box-shadow` of `var(--emu-shadow-2)` on the `:focus-within` state for keyboard accessibility.
