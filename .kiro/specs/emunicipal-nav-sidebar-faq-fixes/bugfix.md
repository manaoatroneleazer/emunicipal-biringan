# Bugfix Requirements Document

## Introduction

The eMunicipal application has a series of related UI/navigation defects spanning all three portals (Resident, Staff, Admin) and the public landing page. The authenticated portal layout (`views/layouts/auth.php`) renders both a top `<header class="auth-header">` bar **and** a dark sidebar, creating duplicate eMunicipal branding and redundant profile/logout controls. The public landing page has navigation link and FAQ issues. The Admin request detail page uses a native `window.confirm()` for the "Begin Verification" action and does not visually suppress the "Mark as Complete" button when a request is already completed. These defects are purely in the view layer and JavaScript — no database schema, controller logic, or authentication/authorization changes are required.

**Inspection findings summary:**

- `views/layouts/auth.php` — the single shared layout for all portals. It contains **both** `<header class="auth-header">` (top bar with branding, notification bell, user dropdown) **and** `<nav class="sidebar">` (dark sidebar with branding, navigation links, profile, logout). This is the source of duplicate branding across all three portals.
- The sidebar already contains: eMunicipal brand, portal role label, all navigation links, "My Profile" link, "Logout" button — for all three roles (resident, staff, admin). The top header is therefore fully redundant.
- CSS in `app.css` uses `--header-height: 60px` and `.auth-content { margin-top: var(--header-height); }`. When the header is removed, the `margin-top` and `sidebar.top` offset must also be removed so content fills the freed space.
- The sidebar currently starts at `top: var(--header-height)`. Removing the header means the sidebar must start at `top: 0`.
- The Resident sidebar already has "My Profile" (`/resident/profile`) and "Logout" — the Account and Session sections are already there.
- The Resident sidebar is **missing a "Track Request" nav link**. The route `/resident/track` and view `views/resident/requests/track.php` already exist. It just needs a sidebar link.
- The public landing page `views/home/index.php` already has a `#faqs` section with Bootstrap accordion — no new FAQ page is needed.
- The public layout (`views/layouts/public.php`) uses anchor-based navigation for the landing page via a `pubNavLink()` helper. The links correctly use `#{anchor}` on the home page. However, the FAQ section uses only 6 basic questions and does not cover the full scope of what the system actually supports.
- `app.js` has a `form[data-confirm]` handler that calls `confirm()` (native browser dialog). In `views/admin/requests/show.php`, the "Begin Verification" form uses `data-confirm="Confirm: Begin Verification?"` — this is the source of the native dialog bug.
- The Admin request detail page shows action buttons via `$allowedNext`. When a request is `completed` or `cancelled`, `$allowedNext` is empty and the panel correctly shows "No further actions." The "Mark Completed" button already disappears for completed requests. However, the current terminal-state message (`No further actions — request is in a terminal state.`) is generic. It needs to be status-specific: "Already Completed" for completed, "Request Cancelled" for cancelled.
- The Staff request detail page has the same generic terminal-state message issue.
- The Resident request detail page does not show a clear terminal-state notice for completed/cancelled requests — the status badge is shown but there is no explicit "no further action needed" message.
- The `.htaccess` rules correctly hide `/public/` from browser-visible URLs. The `app.css` link in both layouts uses `$appUrl . '/public/assets/css/app.css'` — this generates `http://localhost/eMunicipal/public/assets/css/app.css`. The `.htaccess` Rule 2 passes this through, so asset URLs work correctly. No URL fix is needed for assets.
- Navigation URLs in `public.php` and `auth.php` already use `$appUrl` from `$_ENV['APP_URL']` which is `http://localhost/eMunicipal`. No `/public/` appears in page-to-page navigation links.
- Mobile sidebar toggle exists in `app.js` (sidebar-open class) and CSS (`@media (max-width: 991.98px)`). When the header toggle button is removed along with the header, the toggle button must be preserved somewhere. Since the sidebar has its own brand/content, a mobile hamburger button should be placed at the top of the sidebar or as a floating button in the main content area, OR the toggle button from the header should be moved into the main content area's top.
- The `data-confirm` / `window.confirm()` handler in `app.js` is used **only** for the "Begin Verification" form in `views/admin/requests/show.php` and the "Begin Verification" form in `views/staff/requests/show.php`. Both use `data-loading` + `data-confirm` attributes. The fix must replace only the "Begin Verification" action with a custom modal, leaving all other behaviour intact.

---

## Bug Analysis

### Current Behavior (Defect)

**Top Navbar / Duplicate Branding**

1.1 WHEN an authenticated user (Resident, Staff, or Admin) views any portal page THEN the system renders both a top header bar (`auth-header`) with eMunicipal branding and a dark sidebar with eMunicipal branding, creating duplicate logos and duplicate profile/logout controls on every page.

1.2 WHEN a Resident views their portal THEN the system shows a notification bell and user-name dropdown in the top header, even though the sidebar already contains these navigation items (My Profile, Logout).

1.3 WHEN any authenticated portal page loads THEN the main content area (`auth-content`) is pushed down by `margin-top: var(--header-height)` (60px) and the sidebar starts at `top: var(--header-height)`, consuming vertical space unnecessarily.

1.4 WHEN a user is on mobile and views a portal page THEN the sidebar toggle button is rendered inside the top header; if the header is removed there is no accessible way to open the sidebar on mobile.

**Resident Sidebar — Missing "Track Request"**

1.5 WHEN a logged-in Resident views the sidebar THEN the system does not display a "Track Request" navigation link, even though the route `/resident/track` and its view already exist and are registered in `routes/web.php`.

**FAQ Section — Incomplete Questions**

1.6 WHEN a visitor views the landing page FAQ section THEN the system shows only 6 FAQ entries, omitting questions about account creation, login, document type selection, requirements viewing, uploads, reference numbers, notifications, document claiming, and handling rejected/cancelled requests — all of which are supported by the actual system.

**Public Navigation — FAQ link scroll behaviour**

1.7 WHEN a visitor is on an inner page (not the homepage) and clicks the "FAQs" navbar link THEN the system generates an `#{anchor}` URL that does not navigate correctly back to the homepage FAQ section, because the `pubNavLink()` helper already produces `{$appUrl}/#{anchor}` for non-home pages — however the anchor-based links in the navbar do not have `aria-current` set for the active non-home page because the relUri comparison checks exact path, not anchor presence.

1.8 WHEN a visitor is on the homepage and clicks any nav link (Home, About, Services, How It Works, FAQs, Contact) THEN the system produces `#{anchor}` links which are correct for smooth-scrolling on the same page. This already works. (No defect confirmed for this case.)

**Admin "Begin Verification" — Native Dialog**

1.9 WHEN an Admin views a request in "Pending" status and clicks the "Begin Verification" button THEN the system fires a native `window.confirm()` browser dialog (`Confirm: Begin Verification?`), which is inconsistent with the in-app modal style used elsewhere in the admin interface.

1.10 WHEN an Admin clicks "Begin Verification" and the native dialog appears THEN clicking "Cancel" in the browser dialog correctly prevents submission, but the experience is visually jarring and inconsistent with the Bootstrap modal pattern used for Reject, Additional Info, and Cancel actions on the same page.

**Admin/Staff/Resident — Terminal State Messaging**

1.11 WHEN an Admin views a request that is in `completed` status THEN the Actions panel shows the generic message "No further actions — request is in a terminal state." instead of the status-specific message "Already Completed."

1.12 WHEN an Admin views a request that is in `cancelled` status THEN the Actions panel shows the generic message "No further actions — request is in a terminal state." instead of the status-specific message "Request Cancelled."

1.13 WHEN a Staff member views a request in `completed` or `cancelled` status THEN the Actions panel shows the same generic terminal message rather than a status-specific message.

1.14 WHEN a Resident views their own request that is in `completed` or `cancelled` status THEN no explicit "no further action needed" notice is shown — only the status badge in the page header provides context, and there is no informational message in the action/claiming area for these terminal states.

---

### Expected Behavior (Correct)

**Top Navbar Removal**

2.1 WHEN an authenticated user views any portal page THEN the system SHALL render only the sidebar as the primary navigation, with no top `auth-header` bar present in the DOM.

2.2 WHEN the top header is removed THEN the system SHALL update the CSS so that `.sidebar` has `top: 0` and `height: 100vh`, and `.auth-content` has `margin-top: 0`, so main content fills the full viewport height below nothing.

2.3 WHEN the sidebar is displayed without the top header THEN the system SHALL preserve the sidebar brand block (eMunicipal logo + portal role label) at the top of the sidebar, so the user still sees the application name.

2.4 WHEN the top header is removed THEN the system SHALL preserve a mobile sidebar toggle control (hamburger button) accessible from the main content area so mobile users can still open and close the sidebar. The toggle button SHALL be placed in the main content wrapper as a fixed or sticky element, or as a button visible at the top of the page content on small screens.

2.5 WHEN an authenticated user views the sidebar THEN the system SHALL show the authenticated user's first and last name (from `AuthMiddleware`) and role badge within the sidebar, so profile identity is accessible without the top header.

**Profile in Sidebar**

2.6 WHEN the top header is removed THEN the "My Profile" link SHALL remain accessible under the "ACCOUNT" section label in the sidebar for all three portals, using the existing routes (`/resident/profile`, `/staff/profile`, `/admin/profile`).

2.7 WHEN the top header is removed THEN the "Logout" button SHALL remain accessible under the "SESSION" section label in the sidebar for all three portals, using the existing `POST /logout` form with CSRF token.

2.8 WHEN a Resident views their sidebar THEN the system SHALL continue to display the unread notification count badge next to "Notifications" in the sidebar.

**Resident "Track Request" Sidebar Link**

2.9 WHEN a logged-in Resident views the sidebar THEN the system SHALL display a "Track Request" navigation link pointing to `/resident/track`, placed under the REQUESTS section alongside "New Request" and "My Requests". The `$activePage` identifier `'track'` SHALL be used to highlight it when active.

**FAQ — Expanded Questions**

2.10 WHEN a visitor views the landing page FAQ section THEN the system SHALL display accordion-style FAQ items covering (at minimum) the following questions that are directly supported by the existing system:
1. What is eMunicipal?
2. How do I create an account?
3. How do I log in?
4. How do I request a document?
5. How do I select a document type?
6. Where can I see the requirements for a document?
7. Can I upload the required documents online?
8. How can I track my request?
9. Where can I find my request reference number?
10. Can I cancel my request?
11. How will I know if my request status changes?
12. How will I receive notifications?
13. What happens after my request is approved?
14. How do I claim my document?
15. What should I do if my request is rejected or cancelled?

2.11 WHEN the FAQ accordion is rendered THEN the system SHALL use Bootstrap 5's existing accordion component (already loaded via CDN), with smooth open/close animation, plus/minus or chevron indicator, keyboard accessibility via `aria-expanded`, and responsive layout on all screen sizes — no additional JS framework required.

**Admin "Begin Verification" — Custom Modal**

2.12 WHEN an Admin clicks the "Begin Verification" button THEN the system SHALL open a Bootstrap in-app modal dialog (consistent with the Reject/Cancel modals already present on the same page) asking the admin to confirm the action.

2.13 WHEN the verification confirmation modal is open THEN clicking "Cancel" SHALL close the modal without submitting the form and without performing any status change.

2.14 WHEN the verification confirmation modal is open and the admin clicks "Begin Verification" THEN the system SHALL submit the existing POST form to `/admin/requests/{id}/status` with `new_status=under_verification` and the CSRF token, preserving all existing business logic and database behavior.

2.15 WHEN the "Begin Verification" action triggers THEN the system SHALL NOT use `window.confirm()` or `window.alert()`.

2.16 WHEN the `data-confirm` handler in `app.js` fires for the "Begin Verification" form THEN the system SHALL intercept it with a custom modal instead of the native browser dialog. Alternatively, the `data-confirm` attribute SHALL be removed from the "Begin Verification" form and replaced with a `data-bs-toggle="modal"` button backed by a Bootstrap modal, consistent with the existing remark-modal pattern.

**Terminal State Messaging**

2.17 WHEN an Admin views a request in `completed` status THEN the Actions panel SHALL display an "Already Completed" status message with a success-coloured indicator (e.g. green check icon), replacing the generic lock message.

2.18 WHEN an Admin views a request in `cancelled` status THEN the Actions panel SHALL display a "Request Cancelled" status message with a muted/grey indicator, replacing the generic lock message.

2.19 WHEN a Staff member views a request in `completed` status THEN the Actions panel SHALL display the same "Already Completed" message.

2.20 WHEN a Staff member views a request in `cancelled` status THEN the Actions panel SHALL display the same "Request Cancelled" message.

2.21 WHEN a Resident views their own request in `completed` status THEN the system SHALL display an informational message in the right-column area (currently used for "Claiming Instructions") conveying that the request is completed and no further action is needed from the resident.

2.22 WHEN a Resident views their own request in `cancelled` status THEN the system SHALL display a muted informational message indicating the request is cancelled and no further action is required.

---

### Unchanged Behavior (Regression Prevention)

3.1 WHEN a user registers, logs in, or logs out THEN the system SHALL CONTINUE TO process authentication via the existing `AuthController` and CSRF-protected forms without any change.

3.2 WHEN an authenticated user navigates using existing sidebar links (Dashboard, New Request, My Requests, Notifications, My Profile, Requests, Users, Document Types) THEN the system SHALL CONTINUE TO route to the correct controller actions using the existing routes registered in `routes/web.php`.

3.3 WHEN the sidebar is rendered THEN the system SHALL CONTINUE TO highlight the active page link using the `$activePage` variable set by controllers, applying the `active` CSS class and `aria-current="page"` attribute.

3.4 WHEN a mobile user views an authenticated portal page THEN the system SHALL CONTINUE TO support sidebar open/close toggle via a hamburger button and overlay, using the existing CSS classes `sidebar-open` and `sidebar-overlay.active` toggled by `app.js`.

3.5 WHEN a Resident submits a new document request THEN the system SHALL CONTINUE TO process the request through `RequestController::store()`, validate inputs, handle file uploads via `FileUploadService`, create records in the database, and return the reference number — with no change to this workflow.

3.6 WHEN a Staff or Admin updates a request status THEN the system SHALL CONTINUE TO POST to `/staff/requests/{id}/status` or `/admin/requests/{id}/status`, validate the CSRF token, verify the transition is permitted, update the database, send notifications, and redirect — with no change to this business logic.

3.7 WHEN an Admin or Staff member performs a status update that requires a remark (Reject, Additional Info Required, Cancel) THEN the system SHALL CONTINUE TO use the existing Bootstrap modal dialogs with textarea inputs and required validation for those actions.

3.8 WHEN a public visitor views the landing page THEN the system SHALL CONTINUE TO display all existing sections (Home hero, Features strip, About, Services, How It Works, FAQs, Contact) with no sections removed or restructured beyond expanding the FAQ content.

3.9 WHEN the landing page navbar renders on the homepage THEN the system SHALL CONTINUE TO generate `#{anchor}` links for all section nav items (Home, About, Services, How It Works, FAQs, Contact) to support smooth-scroll navigation.

3.10 WHEN an Admin views the Citizens Charter (Document Types, Requirements) management pages THEN the system SHALL CONTINUE TO function without any change — these pages are unaffected by this bugfix.

3.11 WHEN a Resident views the "Track Request" page at `/resident/track` THEN the system SHALL CONTINUE TO allow entering a reference number and displaying the request status timeline — the existing controller and view logic is unchanged.

3.12 WHEN any portal page loads CSS or JS assets THEN the system SHALL CONTINUE TO load `app.css` and `app.js` via `$appUrl . '/public/assets/css/app.css'` paths, which are correctly served by the `.htaccess` Rule 2 pass-through without exposing `/public/` in page navigation URLs.

3.13 WHEN the sidebar logout form is submitted THEN the system SHALL CONTINUE TO include a valid CSRF token via `SecurityHelper::getCsrfToken()` and POST to `/logout` — this form must not be broken when the top header logout is removed.

3.14 WHEN a Resident has unread notifications THEN the system SHALL CONTINUE TO display the unread count badge in the sidebar "Notifications" link, fetched via `Notification::countUnread($userId)`.

---

## Bug Condition Derivation

```pascal
FUNCTION isBugCondition(X)
  INPUT: X of type PageRenderContext
  OUTPUT: boolean

  // Triggers for the composite set of defects in this spec
  RETURN (
    X.layout = 'auth'                                     // Top header present on portal pages
    OR (X.layout = 'auth' AND X.role = 'resident'
        AND X.sidebarLinks NOT CONTAINS 'track-request')  // Missing Track Request link
    OR (X.layout = 'public' AND X.page = 'home'
        AND COUNT(X.faqItems) < 15)                       // Incomplete FAQ
    OR (X.layout = 'admin-request-detail'
        AND X.nextAction = 'under_verification'
        AND X.confirmMethod = 'window.confirm')            // Native dialog for Begin Verification
    OR (X.layout IN ['admin-request-detail',
                     'staff-request-detail',
                     'resident-request-detail']
        AND X.requestStatus IN ['completed', 'cancelled']
        AND X.terminalMessage = 'generic')                 // Generic terminal state message
  )
END FUNCTION
```

```pascal
// Property: Fix Checking
FOR ALL X WHERE isBugCondition(X) DO
  result ← render'(X)
  ASSERT (
    result.topHeader = absent
    AND result.sidebarContains('track-request') WHEN X.role = 'resident'
    AND COUNT(result.faqItems) >= 15 WHEN X.page = 'home'
    AND result.verifyConfirmMethod = 'bootstrap-modal' WHEN X.nextAction = 'under_verification'
    AND result.terminalMessage IN ['Already Completed', 'Request Cancelled']
        WHEN X.requestStatus IN ['completed', 'cancelled']
  )
END FOR
```

```pascal
// Property: Preservation Checking
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT render(X) = render'(X)   // All other page renders are unchanged
END FOR
```
