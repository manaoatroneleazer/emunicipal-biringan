# UI/UX Design Specification

## eMunicipal — Online Municipal Document Request and Management System
### Municipal Government Office of Lingayen, Pangasinan

---

> **Phase 1 — UI/UX Design Only.**
> This document defines the visual design system, layout specifications, component inventory, page structures, and interaction patterns for eMunicipal.
> No backend logic, database code, API implementation, or authentication logic is described here.
> All functional requirements are sourced exclusively from `requirements.md`.

---

## Table of Contents

1. [Design System](#1-design-system)
2. [Color Palette](#2-color-palette)
3. [Typography](#3-typography)
4. [Navigation Structure](#4-navigation-structure)
5. [Page Hierarchy](#5-page-hierarchy)
6. [Component Inventory](#6-component-inventory)
7. [Public Pages — Layout Specifications](#7-public-pages--layout-specifications)
8. [Resident Pages — Layout Specifications](#8-resident-pages--layout-specifications)
9. [Staff Pages — Layout Specifications](#9-staff-pages--layout-specifications)
10. [Administrator Pages — Layout Specifications](#10-administrator-pages--layout-specifications)
11. [Request Form UI — Detailed Specification](#11-request-form-ui--detailed-specification)
12. [Request Tracking UI — Detailed Specification](#12-request-tracking-ui--detailed-specification)
13. [Notification UI — Detailed Specification](#13-notification-ui--detailed-specification)
14. [Responsive Design Behavior](#14-responsive-design-behavior)
15. [Accessibility Considerations](#15-accessibility-considerations)
16. [User Flows](#16-user-flows)

---

## 1. Design System

### 1.1 Design Philosophy

eMunicipal is a government service portal for the Municipal Government Office of Lingayen, Pangasinan. The interface must project professionalism, trustworthiness, and institutional authority while remaining accessible to residents of all technical literacy levels.

**Core principles:**

- **Clarity over decoration** — Every visual element must serve a functional purpose. Decorative elements are removed if they do not aid comprehension.
- **Institutional credibility** — The interface should look and feel like a legitimate government portal, not a commercial application.
- **Accessibility first** — Design decisions must support residents with disabilities, elderly users, and users on low-end mobile devices.
- **Progressive disclosure** — Complex workflows (e.g., document request submission) are broken into clearly labelled steps; all information is not shown at once.
- **Mobile-first** — Layouts are designed for 320px viewports first and enhanced for larger screens.

### 1.2 Design Constraints

- **Framework:** Bootstrap 5 only. No conflicting CSS frameworks.
- **JavaScript:** Vanilla JS or lightweight Bootstrap plugins only. No React, Vue, or Angular.
- **Architecture:** PHP server-rendered pages. All dynamic interactivity must degrade gracefully if JavaScript is disabled for non-critical paths.
- **Animations:** Subtle transitions only (e.g., `transition: opacity 0.15s`). No scroll animations, parallax effects, or decorative motion.
- **Iconography:** Bootstrap Icons (bi-*) as the primary icon set. Icons must always be accompanied by visible text labels for primary navigation and key actions.
- **Images:** Minimal use. Municipal seal/logo only where institutionally appropriate. No stock photography.

### 1.3 Grid System

- Bootstrap 5 12-column grid.
- Fluid containers (`container-fluid`) for full-width areas (header, hero, footer).
- Constrained containers (`container` or `container-xl`) for content areas, max-width 1320px.
- Consistent gutters: `g-3` (16px) on mobile, `g-4` (24px) on desktop.

### 1.4 Spacing Scale

Using Bootstrap 5 spacing utilities. Custom extensions where noted.

| Token    | Value  | Usage                                      |
|----------|--------|--------------------------------------------|
| `$sp-1`  | 4px    | Tight intra-component spacing              |
| `$sp-2`  | 8px    | Icon-to-label gaps, compact badges         |
| `$sp-3`  | 16px   | Standard component padding                 |
| `$sp-4`  | 24px   | Section internal padding                   |
| `$sp-5`  | 48px   | Section separators, hero padding           |
| `$sp-6`  | 64px   | Major section vertical rhythm (desktop)    |

### 1.5 Border Radius

| Context                    | Value  |
|----------------------------|--------|
| Cards                      | 6px    |
| Buttons                    | 4px    |
| Form inputs                | 4px    |
| Badges / pills             | 4px    |
| Modals                     | 8px    |
| Alerts                     | 4px    |

Avoid `border-radius: 50%` or large pill shapes on primary UI containers. Rounded corners are restrained, not decorative.

### 1.6 Elevation / Shadow

| Level   | CSS Value                                   | Usage                            |
|---------|---------------------------------------------|----------------------------------|
| Level 0 | none                                        | Flat table rows, list items      |
| Level 1 | `0 1px 3px rgba(0,0,0,0.08)`               | Cards, form panels               |
| Level 2 | `0 2px 8px rgba(0,0,0,0.12)`               | Dropdowns, active cards          |
| Level 3 | `0 4px 16px rgba(0,0,0,0.16)`              | Modals, floating action panels   |

Avoid decorative shadows on buttons or text elements.

---

## 2. Color Palette

### 2.1 Primary Brand Colors

The palette is derived from the Philippine government color tradition: deep navy/blue as the primary institutional color, gold/amber as the accent representing government authority, and neutral grays for supporting UI.

| Role               | Name               | Hex       | Bootstrap Variable Override       |
|--------------------|--------------------|-----------|-----------------------------------|
| **Primary**        | Government Blue    | `#1a3a5c` | `--bs-primary`                    |
| **Primary Dark**   | Deep Navy          | `#122844` | Used for hover states on primary  |
| **Primary Light**  | Sky Blue           | `#2563a8` | Used for links, secondary actions |
| **Secondary**      | Slate Gray         | `#4b5563` | `--bs-secondary`                  |
| **Accent**         | Government Gold    | `#c8922a` | Highlights, badges, CTAs          |
| **Accent Light**   | Warm Gold          | `#f0b429` | Icon accents, hover on gold       |

### 2.2 Background Colors

| Role                    | Name             | Hex       | Usage                                       |
|-------------------------|------------------|-----------|---------------------------------------------|
| **Page Background**     | Off-White        | `#f8f9fa` | Default `<body>` background                 |
| **Surface**             | White            | `#ffffff` | Cards, modals, form panels                  |
| **Surface Alt**         | Light Gray       | `#f1f3f5` | Alternate table rows, sidebar backgrounds   |
| **Section Background**  | Pale Blue-Gray   | `#eef2f7` | Public page feature sections                |
| **Header Background**   | Government Blue  | `#1a3a5c` | Top navigation bar                          |
| **Footer Background**   | Deep Navy        | `#122844` | Footer                                      |
| **Sidebar Background**  | White            | `#ffffff` | Authenticated sidebar                       |
| **Sidebar Active**      | Light Blue Tint  | `#e8f0fb` | Active nav item in sidebar                  |

### 2.3 Text Colors

| Role                  | Hex       | Usage                                         |
|-----------------------|-----------|-----------------------------------------------|
| **Primary Text**      | `#1a202c` | Body copy, headings                           |
| **Secondary Text**    | `#4b5563` | Subheadings, metadata, labels                 |
| **Muted Text**        | `#6b7280` | Placeholders, helper text, timestamps        |
| **Inverted Text**     | `#ffffff` | Text on dark backgrounds (header, footer)     |
| **Link Text**         | `#2563a8` | Hyperlinks                                    |
| **Link Hover**        | `#1a3a5c` | Hyperlink hover state                         |

### 2.4 Semantic / State Colors

| Role        | Name            | Hex       | Bootstrap Equivalent | Usage                                 |
|-------------|-----------------|-----------|----------------------|---------------------------------------|
| **Success** | Government Green | `#1a7a4a` | `--bs-success`       | Approved, Completed, success alerts   |
| **Warning** | Amber            | `#c8922a` | `--bs-warning`       | Additional Info Required, warnings    |
| **Danger**  | Government Red   | `#b91c1c` | `--bs-danger`        | Rejected, errors, destructive actions |
| **Info**    | Process Blue     | `#1d6fa4` | `--bs-info`          | Under Verification, informational     |
| **Neutral** | Medium Gray      | `#9ca3af` | —                    | Cancelled, disabled states            |

### 2.5 Request Status Color Mapping

Per `requirements.md` Requirement 19.10, all status labels must use consistent color coding.

| Status                          | Badge Color     | Hex (background) | Hex (text)  | Additional Indicator         |
|---------------------------------|-----------------|------------------|-------------|------------------------------|
| PENDING                         | Gray            | `#e5e7eb`        | `#374151`   | Clock icon                   |
| UNDER VERIFICATION              | Blue            | `#dbeafe`        | `#1d4ed8`   | Search/magnify icon          |
| ADDITIONAL INFORMATION REQUIRED | Orange          | `#fef3c7`        | `#92400e`   | Exclamation icon             |
| APPROVED                        | Green           | `#d1fae5`        | `#065f46`   | Check icon                   |
| REJECTED                        | Red             | `#fee2e2`        | `#991b1b`   | X icon                       |
| READY FOR CLAIMING              | Teal            | `#ccfbf1`        | `#0f766e`   | Map-pin / building icon      |
| COMPLETED                       | Dark Green      | `#bbf7d0`        | `#14532d`   | Check-circle icon            |
| CANCELLED                       | Dark Gray       | `#d1d5db`        | `#1f2937`   | Slash-circle icon            |

> **Accessibility note:** Status is never communicated by color alone. Each status badge includes an icon and text label, satisfying WCAG 2.1 SC 1.4.1 (Use of Color).

### 2.6 Border Colors

| Role                | Hex       | Usage                                      |
|---------------------|-----------|--------------------------------------------|
| **Default Border**  | `#d1d5db` | Cards, inputs, table borders               |
| **Focus Border**    | `#2563a8` | Focused input fields (3px outline)         |
| **Error Border**    | `#b91c1c` | Invalid form fields                        |
| **Success Border**  | `#1a7a4a` | Valid form fields (use sparingly)          |
| **Divider**         | `#e5e7eb` | Horizontal rules, section dividers         |

### 2.7 Contrast Compliance

All color combinations must meet WCAG 2.1 AA minimum contrast ratios:
- Normal text (< 18pt): minimum 4.5:1
- Large text (≥ 18pt or 14pt bold): minimum 3:1
- UI components and focus indicators: minimum 3:1

| Combination                        | Ratio  | Status |
|------------------------------------|--------|--------|
| Primary text `#1a202c` on white    | 16.1:1 | ✓ AAA  |
| White on Government Blue `#1a3a5c` | 9.8:1  | ✓ AAA  |
| Link `#2563a8` on white            | 5.1:1  | ✓ AA   |
| Success Green `#1a7a4a` on white   | 5.2:1  | ✓ AA   |
| Danger Red `#b91c1c` on white      | 5.6:1  | ✓ AA   |
| Muted `#6b7280` on white           | 4.6:1  | ✓ AA   |

---

## 3. Typography

### 3.1 Font Family

**Primary Font:** Inter (Google Fonts)
- Rationale: Inter is a professional, highly legible sans-serif optimized for screen rendering at small sizes. Widely used in government and enterprise applications. Free and open-source.
- Fallback stack: `Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif`

**Monospace Font (reference numbers, codes):** `'Courier New', Courier, monospace`
- Used exclusively for reference numbers (e.g., `EMU-2026-000001`) and technical codes.

### 3.2 Font Loading

```html
<!-- In <head> of base layout template -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
```

### 3.3 Type Scale

| Token      | Size     | Line Height | Weight | Usage                                        |
|------------|----------|-------------|--------|----------------------------------------------|
| `h1`       | 2rem     | 1.25        | 700    | Page titles (hero, auth pages)               |
| `h2`       | 1.75rem  | 1.3         | 700    | Section headings                             |
| `h3`       | 1.375rem | 1.35        | 600    | Card headings, subsection titles             |
| `h4`       | 1.125rem | 1.4         | 600    | Panel headings, sidebar section labels       |
| `h5`       | 1rem     | 1.4         | 600    | Component headings, table captions           |
| `h6`       | 0.875rem | 1.4         | 600    | Small headings, metadata labels              |
| Body       | 1rem     | 1.6         | 400    | Primary body copy                            |
| Body sm    | 0.875rem | 1.5         | 400    | Helper text, table cells, secondary content  |
| Body xs    | 0.75rem  | 1.5         | 400    | Timestamps, footnotes, badge labels          |
| Label      | 0.875rem | 1.4         | 500    | Form field labels                            |
| Button     | 0.9375rem| 1.4         | 500    | Button text (primary/secondary)              |
| Button sm  | 0.8125rem| 1.4         | 500    | Small button text                            |
| Code/Ref   | 0.9rem   | 1.4         | 400    | Reference numbers, monospace content         |

### 3.4 Heading Hierarchy Rules

- One `<h1>` per page — the primary page title.
- Section headings use `<h2>`.
- Card or panel headings use `<h3>` or `<h4>`.
- Never skip heading levels for visual size — use CSS classes to control size independently of semantic level.
- Dashboard metric labels use `<h4>` + metric value in `<span>` with `fs-2 fw-bold`.

### 3.5 Text Utility Classes (Custom)

```css
/* To be defined in custom.css */
.text-reference  { font-family: 'Courier New', monospace; font-size: 0.9rem; letter-spacing: 0.04em; }
.text-label      { font-size: 0.875rem; font-weight: 500; color: #4b5563; }
.text-muted-sm   { font-size: 0.75rem; color: #6b7280; }
.text-page-title { font-size: 1.75rem; font-weight: 700; color: #1a202c; }
```

### 3.6 Link Styles

- Default: `#2563a8`, no underline on hover in navigation and buttons.
- Inline body links: `#2563a8`, underline on hover.
- Visited: same as default (no purple visited state — government portals avoid this).
- Focus: 3px solid `#2563a8` outline with 2px offset.

---

## 4. Navigation Structure

### 4.1 Overview

eMunicipal has three distinct navigation contexts:

| Context        | Users          | Nav Pattern                          |
|----------------|----------------|--------------------------------------|
| Public         | All visitors   | Top horizontal navbar                |
| Authenticated  | Residents      | Fixed top header + collapsible sidebar |
| Authenticated  | Staff          | Fixed top header + collapsible sidebar |
| Authenticated  | Administrators | Fixed top header + collapsible sidebar |

All navigation must be keyboard-accessible and screen-reader compatible. Navigation landmarks use `<nav>` with `aria-label` attributes.

### 4.2 Public Navigation Bar

**Behavior:** Fixed to top on scroll. Collapses to hamburger on mobile (< 992px).

**Layout (desktop, left to right):**
```
[Municipal Seal + eMunicipal wordmark]    [Home] [About] [Services] [How It Works] [FAQs] [Contact]    [Login] [Register →]
```

**Layout (mobile):**
```
[Municipal Seal + eMunicipal]    [≡ Menu]
--- collapsed menu ---
Home / About / Services / How It Works / FAQs / Contact / Login / Register
```

**Specifications:**
- Background: Government Blue `#1a3a5c`
- Logo text: White, Inter 600, 1.1rem
- Nav links: White, Inter 400, 0.9375rem — hover: underline + slight opacity change
- Active page link: White + bottom border `2px solid #c8922a`
- Login button: Outlined white (`btn-outline-light btn-sm`)
- Register button: Gold fill (`btn-warning btn-sm`, text `#1a202c`)
- Height: 64px desktop, 56px mobile
- Municipal seal: 36px × 36px, with `alt="Municipal Seal of Lingayen, Pangasinan"`

**Bootstrap component:** `<nav class="navbar navbar-expand-lg navbar-dark">`

### 4.3 Authenticated Header (All Roles)

**Behavior:** Fixed top, full-width, sits above the sidebar. Consistent across Resident, Staff, and Admin.

**Layout (left to right):**
```
[≡ Sidebar toggle]  [Municipal Seal + eMunicipal]        [Notification Bell 🔔 (badge)]  [User Avatar + Name ▾]
```

**Dropdown under user name:**
- My Profile
- Settings (Resident and Admin only, not Staff)
- — divider —
- Logout

**Specifications:**
- Background: White `#ffffff` with bottom border `1px solid #e5e7eb`
- Height: 60px
- Logo: Government Blue text, Inter 600
- Notification icon: `bi-bell`, badge color Danger Red for unread count
- User name: Inter 500, 0.9rem, Secondary Text color
- Hamburger toggle: visible on mobile (< 992px), hides sidebar on click

### 4.4 Sidebar Navigation — Resident

**Behavior:** Fixed left sidebar on desktop (240px wide). Collapses off-canvas on mobile triggered by hamburger.

**Items:**
```
[Dashboard icon]     Dashboard
[Plus icon]          New Request
[List icon]          My Requests
[Bell icon]          Notifications         [unread badge]
[Person icon]        My Profile
```

**Specifications:**
- Background: White `#ffffff`
- Width: 240px (desktop), full-width overlay (mobile)
- Right border: `1px solid #e5e7eb`
- Item padding: `12px 20px`
- Item text: Inter 400, 0.9375rem, Primary Text
- Active item: Background `#e8f0fb`, left border `3px solid #1a3a5c`, text Government Blue, weight 600
- Hover item: Background `#f1f3f5`
- Icons: Bootstrap Icons, 1.1rem, aligned left, 8px gap to label
- Section labels (if grouped): Inter 600, 0.7rem, uppercase, letter-spacing 0.08em, Muted Text color

### 4.5 Sidebar Navigation — Staff

**Items:**
```
[Grid icon]          Dashboard
[Inbox icon]         Requests             [pending count badge]
[Person icon]        My Profile
```

**Same visual specifications as Resident sidebar.**

### 4.6 Sidebar Navigation — Administrator

**Items:**
```
[Grid icon]          Dashboard
[People icon]        Users
[Inbox icon]         Requests
[File-text icon]     Document Types
[Checklist icon]     Requirements
[Bar-chart icon]     Reports
[Shield icon]        Audit Logs
[Gear icon]          Settings
[Person icon]        My Profile
```

**Same visual specifications as Resident sidebar.** Section grouping:

```
MANAGEMENT
  Users
  Requests
  Document Types
  Requirements

REPORTS & LOGS
  Reports
  Audit Logs

SYSTEM
  Settings
  My Profile
```

### 4.7 Breadcrumb Navigation

Used on all interior authenticated pages (not on dashboards or top-level pages).

**Format:**
```
Dashboard  /  My Requests  /  EMU-2026-000001
```

**Specifications:**
- Bootstrap `<nav aria-label="breadcrumb">` + `<ol class="breadcrumb">`
- Font: Inter 400, 0.875rem
- Color: Muted Text `#6b7280`
- Active (current page): Primary Text `#1a202c`, no link
- Separator: `/` default Bootstrap breadcrumb divider
- Placement: Below the fixed header, above the page title, inside the main content area

### 4.8 Mobile Navigation Behavior

| Breakpoint    | Behavior                                                             |
|---------------|----------------------------------------------------------------------|
| < 576px       | Sidebar hidden; toggle opens full-screen overlay sidebar             |
| 576px–991px   | Sidebar hidden; toggle opens off-canvas sidebar (Bootstrap Offcanvas)|
| ≥ 992px       | Sidebar always visible, fixed to left                                |

**Public navbar:** Collapses at < 992px. Hamburger opens vertical stacked menu within the navbar.

**Touch targets:** All interactive elements minimum 44px × 44px on mobile (WCAG 2.1 SC 2.5.5).

### 4.9 Footer (Public Pages Only)

Not shown in authenticated views.

**Layout (3 columns desktop, stacked mobile):**
```
Column 1: Municipal Seal + eMunicipal + tagline + office address
Column 2: Quick Links (Home, About, Services, How It Works, FAQs, Contact)
Column 3: Contact information (address, phone, email, office hours)
```

**Bottom bar:**
```
© 2026 Municipal Government of Lingayen, Pangasinan. All rights reserved.
```

**Specifications:**
- Background: Deep Navy `#122844`
- Text: White `#ffffff` and Muted (60% white opacity) for secondary text
- Links: White, underline on hover
- Divider above footer: `1px solid #e5e7eb`
- Padding: 48px top/bottom desktop, 32px mobile

---

## 5. Page Hierarchy

### 5.1 Complete Page Inventory

#### PUBLIC (unauthenticated)

| Page             | URL (planned)      | Description                                              |
|------------------|--------------------|----------------------------------------------------------|
| Home             | `/`                | Landing page with hero, services overview, how-it-works  |
| About            | `/about`           | About the municipal office and the system                |
| Services         | `/services`        | List of available document types and descriptions        |
| How It Works     | `/how-it-works`    | Step-by-step guide for residents                         |
| FAQs             | `/faqs`            | Frequently asked questions                               |
| Contact          | `/contact`         | Office contact information and location map              |
| Login            | `/login`           | Resident / Staff / Admin login form                      |
| Register         | `/register`        | Resident self-registration form                          |

#### RESIDENT (authenticated, role = Resident)

| Page              | URL (planned)               | Description                                              |
|-------------------|-----------------------------|----------------------------------------------------------|
| Dashboard         | `/resident/dashboard`       | Stats, recent requests, quick actions                    |
| New Request       | `/resident/requests/new`    | Multi-step document request submission form              |
| My Requests       | `/resident/requests`        | Full list of own requests with search/filter             |
| Request Details   | `/resident/requests/{id}`   | Full detail view of a single request                     |
| Request Tracking  | `/resident/track`           | Search by reference number, visual status timeline       |
| Notifications     | `/resident/notifications`   | All notifications list                                   |
| Profile           | `/resident/profile`         | View and edit profile, change password                   |
| Settings          | `/resident/settings`        | Account preferences                                      |

#### STAFF (authenticated, role = Staff)

| Page              | URL (planned)              | Description                                              |
|-------------------|----------------------------|----------------------------------------------------------|
| Dashboard         | `/staff/dashboard`         | Stats by status, recent activity                         |
| Request List      | `/staff/requests`          | All requests across all residents, filterable            |
| Request Review    | `/staff/requests/{id}`     | Full request detail with status action panel             |
| Profile           | `/staff/profile`           | View and edit profile, change password                   |

#### ADMINISTRATOR (authenticated, role = Administrator)

| Page              | URL (planned)              | Description                                              |
|-------------------|----------------------------|----------------------------------------------------------|
| Dashboard         | `/admin/dashboard`         | System-wide metrics and charts                           |
| User Management   | `/admin/users`             | List, search, activate/deactivate all users              |
| User Detail       | `/admin/users/{id}`        | Individual user record                                   |
| Resident Mgmt     | `/admin/users?role=resident`| Filtered view of residents                              |
| Staff Mgmt        | `/admin/users?role=staff`  | Filtered view of staff, create staff account             |
| Request List      | `/admin/requests`          | Read-only view of all requests                           |
| Document Types    | `/admin/document-types`    | CRUD for document types                                  |
| Requirements      | `/admin/requirements`      | CRUD for requirements per document type                  |
| Reports           | `/admin/reports`           | Filterable summary reports                               |
| Audit Logs        | `/admin/audit-logs`        | Read-only paginated audit log                            |
| System Settings   | `/admin/settings`          | Configurable system parameters                           |
| Profile           | `/admin/profile`           | View and edit own profile, change password               |

### 5.2 Page Access Matrix

| Page Area         | Resident | Staff | Administrator | Public |
|-------------------|----------|-------|---------------|--------|
| Public pages      | ✓        | ✓     | ✓             | ✓      |
| Login / Register  | ✓        | —     | —             | ✓      |
| Resident area     | ✓        | —     | —             | —      |
| Staff area        | —        | ✓     | —             | —      |
| Admin area        | —        | —     | ✓             | —      |

> Note: Staff and Administrator accounts are created by Administrators directly — they do not use the public Register page. The Login page is shared across all roles. Unauthenticated access to protected pages redirects to `/login`. Wrong-role access returns HTTP 403 and renders an access-denied page.

### 5.3 Layout Templates

Three layout templates are used across all pages:

| Template            | Used by                          | Key elements                              |
|---------------------|----------------------------------|-------------------------------------------|
| `layout-public.php`  | All public pages                 | Public navbar + footer                    |
| `layout-auth.php`    | Resident / Staff / Admin pages   | Fixed header + sidebar + main content area|
| `layout-minimal.php` | Login, Register, error pages     | Centered card, no sidebar, minimal header |

---

## 6. Component Inventory

### 6.1 Buttons

All buttons use Bootstrap 5 button classes as base, with custom color overrides.

| Variant          | Class                        | Usage                                           |
|------------------|------------------------------|-------------------------------------------------|
| Primary          | `btn btn-primary`            | Main action (Submit, Save, Confirm)             |
| Secondary        | `btn btn-secondary`          | Secondary action (Cancel, Back)                 |
| Outline Primary  | `btn btn-outline-primary`    | Alternate action, view-only actions             |
| Outline Danger   | `btn btn-outline-danger`     | Destructive action preview (Reject, Deactivate) |
| Danger           | `btn btn-danger`             | Confirmed destructive actions inside modals     |
| Success          | `btn btn-success`            | Approve, mark complete                          |
| Warning          | `btn btn-warning`            | Request additional info (text `#1a202c`)        |
| Light            | `btn btn-light`              | Actions on dark backgrounds                     |
| Link             | `btn btn-link`               | Inline text actions (View, Details)             |
| Icon button      | `btn btn-icon`               | Icon-only, always with `aria-label`             |

**Size variants:** `btn-lg` for hero CTAs, `btn-sm` for table row actions, default for forms.

**Disabled state:** `disabled` attribute + `aria-disabled="true"`. Visual: 50% opacity, `not-allowed` cursor.

**Loading state:** Replace button text with spinner + "Loading…" text, disable the button.
```
[  ◌  Loading…  ]
```

### 6.2 Form Components

#### Text Input
- Base class: `form-control`
- Label: `<label class="form-label">` always present, never placeholder-only
- Focus ring: `box-shadow: 0 0 0 3px rgba(37,99,168,0.25)`, border `#2563a8`
- Error state: `is-invalid` class, `invalid-feedback` div below field, red border
- Valid state: `is-valid` used sparingly (only after submit attempt)
- Helper text: `<div class="form-text">` below input, muted, 0.8rem

#### Select
- Base class: `form-select`
- Default option: "— Select —" (never empty value that could submit silently)
- Same focus, error, valid states as text input

#### Textarea
- Base class: `form-control`
- `rows` attribute set contextually (purpose field: 3 rows; remarks: 4 rows)
- Character count display below textarea where limits apply

#### Checkbox / Radio
- Base classes: `form-check-input` / `form-check-label`
- Grouped in `<fieldset>` with `<legend>` for grouped options
- Custom checked color: `#1a3a5c`

#### Form Layout Rules
- Labels always above inputs (never floating labels — accessibility and localization concern)
- Required fields marked with `<span class="text-danger" aria-hidden="true">*</span>` + "Required" in label text for screen readers
- Error messages: adjacent to the field, using `role="alert"` for dynamic injection
- Form sections use `<fieldset>` with visible `<legend>` headings
- Mobile: full-width single column. Desktop: up to 2-column grid for short paired fields (e.g., First Name / Last Name)

### 6.3 Cards

```
┌─────────────────────────────────────┐
│  [Optional card header]             │
├─────────────────────────────────────┤
│  Card body content                  │
│                                     │
├─────────────────────────────────────┤
│  [Optional card footer]             │
└─────────────────────────────────────┘
```

- Base: `card` class, `box-shadow: Level 1`
- Border radius: 6px
- Header: `card-header` with `fw-600` title, background `#f8f9fa`
- Body padding: 24px desktop, 16px mobile
- No hover elevation change unless the card is itself a clickable item

### 6.4 Tables

**Standard data table:**
- Base: `table table-hover table-bordered`
- Header: background `#f1f3f5`, text Inter 600, 0.8rem uppercase, letter-spacing 0.05em
- Row height: minimum 48px
- Actions column: right-aligned, fixed width
- Responsive wrapper: `<div class="table-responsive">` for all tables
- Stripe: `table-striped` on long lists (users, audit logs)
- Empty state row: single `<td colspan="N">` with centered empty state component

**Density:** Default. No compact mode defined at this phase.

### 6.5 Badges / Status Labels

```
[ ● PENDING ]   [ ● UNDER VERIFICATION ]   [ ✓ APPROVED ]
```

- Base: `badge` with custom background and text colors per Section 2.5
- Always includes an icon (Bootstrap Icons) before the text label
- Font: Inter 500, 0.75rem
- Border radius: 4px
- Never rely solely on color — icon + text always present
- Used inline in tables and on detail pages

### 6.6 Alerts

| Type    | Class           | Usage                                             |
|---------|-----------------|---------------------------------------------------|
| Success | `alert-success` | Form saved, request submitted, status updated     |
| Warning | `alert-warning` | Additional info required notice                   |
| Danger  | `alert-danger`  | Validation errors, failed actions                 |
| Info    | `alert-info`    | Informational notices, instructions               |

- Dismissible alerts use `alert-dismissible fade show` + close button
- Non-dismissible inline alerts (e.g., status remarks) omit the close button
- Alert icon prepended: `bi-check-circle`, `bi-exclamation-triangle`, `bi-x-circle`, `bi-info-circle`
- Screen readers: `role="alert"` on dynamically injected alerts

### 6.7 Modals

Used for confirmations before destructive or irreversible actions.

**Standard confirmation modal structure:**
```
┌────────────────────────────────────────┐
│  Modal title (action name)         [×] │
├────────────────────────────────────────┤
│  Clear description of what will        │
│  happen and that it cannot be undone.  │
│                                        │
│  [required input field if applicable] │
├────────────────────────────────────────┤
│                  [Cancel]  [Confirm →] │
└────────────────────────────────────────┘
```

- Modal size: `modal-dialog-centered` default, `modal-lg` for forms with multiple fields
- Backdrop: static (`data-bs-backdrop="static"`) for destructive confirmations
- Confirm button: `btn-danger` for destructive, `btn-primary` for neutral confirmations
- Cancel button: always `btn-secondary`
- Close (×): present on all modals
- Focus: on open, focus moves to modal title or first input; on close, returns to trigger element

**Modals required:**
- Reject request (requires rejection reason input)
- Request additional information (requires remark input)
- Cancel request
- Deactivate user account
- Deactivate document type
- Approve request (optional confirmation)

### 6.8 Notifications Component

#### Bell Icon with Badge
```
🔔 [3]
```
- Icon: `bi-bell` in header
- Badge: small circle, Danger Red background, white text, positioned top-right of icon
- Hidden when count is zero (badge not rendered)
- `aria-label="Notifications, 3 unread"`

#### Notification Dropdown (top 5 most recent)
```
┌─────────────────────────────────────────┐
│  Notifications                [Mark all read]│
├─────────────────────────────────────────┤
│ ● [unread] Your request EMU-2026-000001 │
│           is Ready for Claiming         │
│           2 hours ago                   │
├─────────────────────────────────────────┤
│   [read]  Your request EMU-2026-000001  │
│           has been Approved             │
│           Yesterday                     │
├─────────────────────────────────────────┤
│           View All Notifications →      │
└─────────────────────────────────────────┘
```

- Unread: left border `3px solid #1a3a5c`, background `#f0f4ff`, bold title
- Read: no left border, default background, normal weight
- Non-color indicator: left border + bold text for unread (not color alone)
- Max dropdown height: 360px, scrollable
- Empty state: "No notifications yet."

#### Notifications Full Page
- Table/list of all notifications in reverse chronological order
- Columns: Type icon | Message | Request Reference | Date | Status (Read/Unread)
- "Mark all as read" button top right
- Pagination: 20 per page

### 6.9 File Upload Component

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│     📎  Drag and drop files here, or click to browse │
│                                                      │
│     Accepted: PDF, JPG, PNG  ·  Max size: 5MB each   │
│                                                      │
└──────────────────────────────────────────────────────┘

Uploaded files:
┌──────────────────────────────────────┬────────┬──────┐
│ 📄 barangay_clearance.pdf            │ 1.2 MB │ [×]  │
│ 🖼 valid_id.jpg                      │  840KB │ [×]  │
└──────────────────────────────────────┴────────┴──────┘
```

- Drop zone: dashed border `2px dashed #d1d5db`, background `#f8f9fa`, border radius 6px
- Active drag state: border color `#2563a8`, background `#e8f0fb`
- File list below drop zone: icon + original filename + size + remove button
- Remove button: `bi-x-circle`, `aria-label="Remove [filename]"`
- Error state: file exceeds 5MB or invalid type — inline error below the specific file entry
- Progress bar: Bootstrap `progress` component, shown during upload
- Accepts: `.pdf`, `.jpg`, `.jpeg`, `.png` (MIME validated server-side per Req 18)

### 6.10 Status Timeline Component

Used on Request Tracking and Request Details pages.

```
  ●  PENDING                          Jan 15, 2026 09:14
  |
  ●  UNDER VERIFICATION               Jan 15, 2026 14:32
  |
  ●  APPROVED                         Jan 16, 2026 10:05
  |
  ○  READY FOR CLAIMING               (awaiting)
  |
  ○  COMPLETED                        (awaiting)
```

- Completed steps: filled circle `●`, color per status badge color
- Current step: filled circle with pulse ring animation (subtle, 1 iteration)
- Future steps: hollow circle `○`, muted color
- REJECTED / CANCELLED: shown as a terminal step with appropriate icon and color
- ADDITIONAL INFORMATION REQUIRED: shown as a side branch or distinct step
- Connecting line: `2px solid #e5e7eb` between steps
- Each step shows: status label, date/time if available, remark if present (collapsible)
- Mobile: vertical timeline (stacked). Desktop: same vertical layout, centered or left-aligned

### 6.11 Pagination Component

- Bootstrap `<nav aria-label="Pagination">` + `<ul class="pagination">`
- Always shows: First, Previous, page numbers (up to 5 visible), Next, Last
- Current page: `active` class, `aria-current="page"`
- Disabled: `disabled` class + `aria-disabled="true"` on First/Prev when on page 1
- Page size selector: `<select class="form-select form-select-sm">` showing 10 / 20 / 50
- Default page size: 20 (per Req 12.5)

### 6.12 Breadcrumb Component

```html
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/resident/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="/resident/requests">My Requests</a></li>
    <li class="breadcrumb-item active" aria-current="page">EMU-2026-000001</li>
  </ol>
</nav>
```

### 6.13 Empty State Component

Used when a list or data set has no records.

```
        📋
   No requests found.
   You haven't submitted any document requests yet.

          [ + New Request ]
```

- Icon: large Bootstrap Icon, muted color, 3rem
- Heading: Inter 600, 1rem, Primary Text
- Subtext: Inter 400, 0.875rem, Muted Text
- CTA button: optional, only when an action is relevant (e.g., new request)
- Centered horizontally in the content area

### 6.14 Loading State

**Skeleton screen** (preferred for page-level loads):
- Gray `#e5e7eb` placeholder blocks matching the approximate layout of content
- CSS animation: `background: linear-gradient(90deg, #e5e7eb 25%, #f1f3f5 50%, #e5e7eb 75%)` shimmer

**Spinner** (for inline actions, button loading):
- Bootstrap `spinner-border spinner-border-sm` inside button
- `role="status"` + visually-hidden "Loading..." text

### 6.15 Error State Component

**Page-level error (e.g., failed to load request list):**
```
        ⚠
   Unable to load requests.
   Please try again. If the problem persists, contact support.

          [ Try Again ]
```

**Inline field error:**
```
[  input field — red border  ]
  ⚠ This field is required.
```

- `invalid-feedback` div, `role="alert"`, shown after submit attempt
- Never shows generic "An error occurred" alone — always actionable

### 6.16 Dashboard Metric Card

```
┌──────────────────────────┐
│  Total Requests          │
│                          │
│     128                  │
│                          │
│  ↑ 12 this month         │
└──────────────────────────┘
```

- Card: white background, Level 1 shadow, 6px border radius
- Metric label: Inter 500, 0.875rem, Muted Text
- Metric value: Inter 700, 2rem, Primary Text
- Sub-label: Inter 400, 0.75rem, Muted Text
- Optional left accent: 4px colored border matching the metric's semantic color
- Grid: 2 columns mobile, 3–4 columns desktop

---

## 7. Public Pages — Layout Specifications

### 7.1 Home Page (`/`)

**Purpose:** First impression. Communicates the system's value, builds trust, and directs residents to register or track a request.

---

#### Section 1: Navigation Bar
Per Section 4.2.

---

#### Section 2: Hero

```
┌─────────────────────────────────────────────────────────────────┐
│  [Government Blue background]                                   │
│                                                                 │
│  MUNICIPAL GOVERNMENT OF LINGAYEN                               │
│  PANGASINAN                                                     │
│                                                                 │
│  Request Municipal Documents Online                             │
│  Fast, convenient, and secure document requests                 │
│  from the comfort of your home.                                 │
│                                                                 │
│     [ Request a Document → ]    [ Track Your Request ]          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

- Background: Government Blue `#1a3a5c`
- Agency label: Inter 500, 0.875rem, white, uppercase, letter-spacing 0.1em
- H1: Inter 700, 2.5rem desktop / 1.75rem mobile, white
- Subtext: Inter 400, 1.1rem, white 80% opacity
- Primary CTA: Gold fill button (`btn-warning`, large) — "Request a Document"
- Secondary CTA: Outlined white button — "Track Your Request"
- Padding: 80px top/bottom desktop, 48px mobile
- No hero image/background photo — flat color, clean and fast-loading

---

#### Section 3: Services Overview

```
  Available Document Services
  ─────────────────────────────
  [Card]          [Card]          [Card]
  Barangay        Business        Birth
  Clearance       Permit          Certificate
  
  [Card]          [Card]          [Card]
  ...
  
                [ View All Services → ]
```

- Section heading: H2, centered
- Cards: Bootstrap cards, 3 per row desktop, 2 tablet, 1 mobile
- Each card: document type icon (`bi-file-earmark-text`), name, brief description
- Content sourced dynamically from database (active document types)
- "View All Services" link below grid

---

#### Section 4: How It Works

```
  How It Works
  ────────────

  [1]              [2]              [3]              [4]
  Register         Submit Request   Track Online     Claim Document
  Create your      Fill out the     Monitor your     Pick up your
  free account.    form online.     request status.  document at
                                                     the office.
```

- Background: Pale Blue-Gray `#eef2f7`
- Step numbers: large, Government Blue, Inter 700, 2.5rem, circle badge
- Step title: H4, Inter 600
- Step description: body sm, Muted Text
- Layout: 4 columns desktop, 2 columns tablet, 1 column mobile
- Connecting line between steps (desktop only): `1px solid #d1d5db`

---

#### Section 5: Benefits

Three-column benefit highlights with icons.

| Icon               | Heading           | Description                                |
|--------------------|-------------------|--------------------------------------------|
| `bi-clock`         | Save Time         | No need to travel to the office            |
| `bi-shield-check`  | Secure & Official | Government-verified document processing    |
| `bi-phone`         | Track Anytime     | Monitor your request status 24/7           |

- White background
- Icon: 2rem, Government Blue
- H4 heading, body sm description

---

#### Section 6: FAQ Preview

Show 4–5 most common FAQs in an accordion component.

- Bootstrap `accordion` component
- "View All FAQs" link below
- Background: white

---

#### Section 7: Contact Information

```
  Contact Us
  ──────────
  📍 Lingayen, Pangasinan            📞 (075) XXX-XXXX
  ✉ municipal@lingayen.gov.ph        🕐 Mon–Fri, 8AM–5PM
```

- Background: Government Blue `#1a3a5c`, white text
- Icons: Bootstrap Icons, white
- Sourced from `system_settings` table (populated at runtime)

---

#### Section 8: Footer
Per Section 4.9.

---

### 7.2 About Page (`/about`)

**Layout:** Single column, `container`, max-width 900px.

**Sections:**
1. Page hero (smaller than home, Government Blue, H1 "About eMunicipal")
2. About the Municipal Government of Lingayen (text + municipal seal)
3. About the eMunicipal System (purpose, goals)
4. System capabilities (brief bullet list)
5. Data privacy notice (brief paragraph referencing RA 10173)

---

### 7.3 Services Page (`/services`)

**Layout:** Grid of document type cards, full inventory.

**Each card:**
- Document type name (H3)
- Description
- Requirements summary (count of requirements)
- "Request This Document" button → redirects to `/login` if unauthenticated, or `/resident/requests/new?type={id}` if authenticated

**Filters:**
- All services (default)
- Only active types shown

---

### 7.4 How It Works Page (`/how-it-works`)

**Layout:** Expanded step-by-step guide.

**Sections:**
1. Page hero
2. Numbered steps (expanded from homepage version) with screenshots/illustrations described (no actual images at this phase)
3. Requirements checklist example
4. FAQ panel (same as homepage, full list)
5. CTA to register

---

### 7.5 FAQs Page (`/faqs`)

**Layout:** Full accordion, grouped by category.

**Categories:**
- Getting Started
- Submitting a Request
- Tracking Your Request
- Claiming Your Document
- Account & Profile

---

### 7.6 Contact Page (`/contact`)

**Layout:** Two columns desktop, stacked mobile.

**Left column:** Contact details (address, phone, email, office hours) sourced from `system_settings`.

**Right column:** Simple contact inquiry form (name, email, message, submit).
- Note: Contact form submission behavior is outside approved requirements scope and is UI-only at this phase. No backend processing defined.

---

### 7.7 Login Page (`/login`)

**Layout:** `layout-minimal.php`. Centered card, max-width 420px.

```
┌──────────────────────────────────────────┐
│  [Municipal Seal]  eMunicipal            │
│  Municipal Government of Lingayen        │
│  ──────────────────────────────          │
│                                          │
│  Sign In to Your Account                 │
│                                          │
│  Email Address *                         │
│  [                              ]        │
│                                          │
│  Password *                              │
│  [                           👁 ]        │
│                                          │
│  [⚠ Invalid email or password.]         │  ← error alert (hidden by default)
│                                          │
│  [        Sign In        ]               │
│                                          │
│  Don't have an account? Register here    │
└──────────────────────────────────────────┘
```

**Specifications:**
- Card: white, Level 2 shadow, 8px border radius, 40px padding
- Page background: `#eef2f7`
- Municipal seal: 48px × 48px, centered above card title
- System name: Inter 600, 1.1rem, Government Blue
- Form title: H2, Inter 700, 1.25rem
- Show/hide password: `bi-eye` / `bi-eye-slash` toggle button inside input group, `aria-label="Show password"`
- Error message: `alert-danger`, `role="alert"`, shown server-side on failed login
- No "Forgot password" link — not in approved requirements
- No "Remember me" checkbox — sessions expire per system settings (Req 20.5)

---

### 7.8 Register Page (`/register`)

**Layout:** `layout-minimal.php`. Centered card, max-width 560px.

```
┌──────────────────────────────────────────────┐
│  [Municipal Seal]  eMunicipal                │
│  Create Your Account                         │
│  ──────────────────────────────              │
│                                              │
│  Personal Information                        │
│  ─────────────────────                       │
│  First Name *          Last Name *           │
│  [             ]       [             ]       │
│                                              │
│  Contact Number *                            │
│  [                                  ]        │
│  Format: 09XXXXXXXXX or (075) XXX-XXXX       │
│                                              │
│  Home Address *                              │
│  [                                  ]        │
│                                              │
│  Account Information                         │
│  ───────────────────                         │
│  Email Address *                             │
│  [                                  ]        │
│                                              │
│  Password *                               👁 │
│  [                                  ]        │
│  Must be at least 8 characters, 1 uppercase, │
│  1 lowercase, 1 number.                      │
│                                              │
│  Confirm Password *                       👁 │
│  [                                  ]        │
│                                              │
│  [ Create My Account ]                       │
│                                              │
│  Already have an account? Sign in            │
└──────────────────────────────────────────────┘
```

**Specifications:**
- Two logical groups with `<fieldset>` + `<legend>`: "Personal Information", "Account Information"
- All validation messages shown inline below each field on submit
- Password requirements shown as helper text (visible always, not on hover)
- No CAPTCHA defined in requirements
- Privacy notice: small text below submit button referencing RA 10173

---

## 8. Resident Pages — Layout Specifications

All resident pages use `layout-auth.php` with the resident sidebar (Section 4.4).

### 8.1 Resident Dashboard (`/resident/dashboard`)

**Layout:** Fixed header + sidebar + main content area.

#### Header
Per Section 4.3.

#### Breadcrumb
None on dashboard (it is the root page for residents).

#### Page Title
```
Welcome back, [First Name].      [+ New Request]
```
- H1, Inter 700, 1.5rem
- Quick action button "New Request" — top right, `btn-primary`

#### Statistics Row

```
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Total        │ │ Pending      │ │ Ready for    │ │ Completed    │
│ Requests     │ │              │ │ Claiming     │ │              │
│    12        │ │     3        │ │     1        │ │     8        │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘
```

- 4 metric cards in a row
- 2 columns on mobile, 4 on desktop
- Colors: Total = Primary Blue accent, Pending = Gray, Ready for Claiming = Teal, Completed = Dark Green

Additional stats row (secondary, smaller):
```
Under Verification: 0   |   Additional Info Required: 0   |   Approved: 0   |   Rejected: 0
```
- Displayed as inline stat pills, not full cards

#### Recent Requests Table

```
Reference No.     Document Type       Submitted     Status            Last Updated   Action
──────────────────────────────────────────────────────────────────────────────────────────────
EMU-2026-000003   Barangay Clearance  Jan 18, 2026  ● PENDING         Jan 18, 2026   [View] [Track]
EMU-2026-000002   Business Permit     Jan 12, 2026  ● APPROVED        Jan 15, 2026   [View] [Track]
EMU-2026-000001   Birth Certificate   Jan 5, 2026   ✓ COMPLETED       Jan 10, 2026   [View]
```

- Shows most recent 5 requests
- "View All Requests →" link below table
- Reference number in monospace font class `.text-reference`
- Status badge per Section 6.5
- [View] = `btn btn-sm btn-outline-primary`
- [Track] = `btn btn-sm btn-outline-secondary` (hidden for terminal statuses)

**Empty state (no requests):**
```
        📋
   No requests yet.
   Start by requesting a document.

     [ + New Request ]
```

---

### 8.2 New Request Page (`/resident/requests/new`)

**Multi-step wizard layout.** Progress indicator at top.

#### Progress Indicator

```
  [1 Document] ──── [2 Details] ──── [3 Documents] ──── [4 Review] ──── [5 Confirm]
```

- Active step: Government Blue, filled
- Completed step: Green check, filled
- Future step: Gray, hollow
- Step labels visible on desktop; step numbers only on mobile

---

#### Step 1: Select Document Type

```
  Select the Document You Need
  ─────────────────────────────

  [Card]              [Card]              [Card]
  📄 Barangay         📄 Business         📄 Birth
  Clearance           Permit              Certificate
  
  [Card]              [Card]              ...
  📄 ...
```

- Grid of selectable cards (radio-button behavior)
- Selected card: Government Blue border `2px solid #1a3a5c`, background `#e8f0fb`
- Each card shows: document name, brief description
- Only active document types shown (Req 6.1)
- After selection, requirements list appears below:

```
  Requirements for Barangay Clearance:
  ✓ Valid Government-Issued ID
  ✓ Proof of Residency
  ✓ Barangay Indigency (if applicable)

  [ Continue → ]
```

---

#### Step 2: Request Details

```
  Request Details
  ───────────────

  Purpose of Request *
  [                                              ]
  [                                              ]

  Number of Copies *
  [  1  ]   (must be 1 or more)
```

- Textarea for purpose: 3 rows
- Number input for copies: min=1, integer only (Req 6.14)
- Document-type-specific fields rendered dynamically if defined by administrator

---

#### Step 3: Upload Supporting Documents

Per Section 6.9 (File Upload Component).

```
  Upload Required Documents
  ─────────────────────────
  Please upload the following:
  • Valid Government-Issued ID
  • Proof of Residency

  [  File upload drop zone  ]

  Uploaded Files:
  📄 valid_id.pdf     1.2MB   [×]
```

- List of required documents shown above upload zone for reference
- Per-file errors shown inline

---

#### Step 4: Review Request

```
  Review Your Request Before Submitting
  ───────────────────────────────────────

  Document Type:    Barangay Clearance
  Purpose:          For employment purposes
  Copies:           2
  Uploaded Files:   valid_id.pdf, proof_of_residency.jpg

  [ ← Back ]                [ Submit Request → ]
```

- Read-only summary of all entered data
- No fields to edit (user must go Back to change)
- Submit button is primary, large

---

#### Step 5: Submission Confirmation

```
  ✓  Request Submitted Successfully!
  ──────────────────────────────────

  Your Reference Number:
  EMU-2026-000004

  Document:    Barangay Clearance
  Submitted:   January 18, 2026, 10:32 AM
  Status:      ● PENDING

  A confirmation email has been sent to your registered email address.

  [ View Request Details ]    [ Back to Dashboard ]
```

- Green success alert header
- Reference number in large monospace: `.text-reference`, font-size 1.5rem, Government Blue
- Two navigation buttons

---

### 8.3 My Requests Page (`/resident/requests`)

**Layout:** Full list with search and filter.

```
  My Requests                               [+ New Request]
  ─────────────────────────────────────────────────────────

  Search: [ Reference No. or Document Type   🔍 ]

  Filter by Status: [ All ▾ ]     Date Range: [ From ] [ To ]   [Apply]

  ─────────────────────────────────────────────────────────────────────
  Reference No.     Document Type       Submitted     Status         Action
  ─────────────────────────────────────────────────────────────────────
  EMU-2026-000004   Barangay Clearance  Jan 18, 2026  ● PENDING      [View] [Track] [Cancel]
  EMU-2026-000003   Business Permit     Jan 12, 2026  ● APPROVED     [View] [Track]
  EMU-2026-000001   Birth Certificate   Jan 5, 2026   ✓ COMPLETED    [View]
  ─────────────────────────────────────────────────────────────────────

  [← Prev]  Page 1 of 3  [Next →]
```

- Search: by reference number or document type name
- Status filter dropdown
- Date range: two date inputs
- [Cancel] button: only shown for PENDING and ADDITIONAL INFO REQUIRED statuses (Req 7.1)
- Cancel triggers confirmation modal (Section 6.7)
- Pagination: 20 per page default

---

### 8.4 Request Details Page (`/resident/requests/{id}`)

```
  ← My Requests

  Barangay Clearance                    ● PENDING
  Reference: EMU-2026-000004

  ┌─── Request Information ───────────────────────────────┐
  │  Document Type:    Barangay Clearance                  │
  │  Purpose:          For employment purposes             │
  │  Copies:           2                                   │
  │  Date Submitted:   January 18, 2026, 10:32 AM         │
  │  Current Status:   ● PENDING                          │
  └───────────────────────────────────────────────────────┘

  ┌─── Uploaded Documents ────────────────────────────────┐
  │  📄 valid_id.pdf       1.2 MB    Jan 18, 2026         │
  │  🖼 proof_of_res.jpg    840 KB    Jan 18, 2026         │
  └───────────────────────────────────────────────────────┘

  ┌─── Status History ────────────────────────────────────┐
  │  ● PENDING         Jan 18, 2026 10:32 AM   (Submitted)│
  └───────────────────────────────────────────────────────┘

  [ Cancel Request ]
```

**When status is ADDITIONAL INFORMATION REQUIRED:**

```
  ┌─── Additional Information Required ──────────────────┐
  │  ⚠  Staff Remark:                                    │
  │  "Please upload a clearer copy of your valid ID.     │
  │   The current upload is not legible."                │
  │                                                      │
  │  [ Upload Additional Documents ]                     │
  └───────────────────────────────────────────────────────┘
```

- Alert component `alert-warning` with remark text (Req 8.6)
- Upload additional documents section opens inline (same file upload component)

**When status is READY FOR CLAIMING:**

```
  ┌─── Claiming Instructions ─────────────────────────────┐
  │  🏛  Your document is ready for pick-up.              │
  │                                                        │
  │  Office:    Municipal Civil Registrar's Office         │
  │  Address:   [from system_settings]                     │
  │  Hours:     Monday–Friday, 8:00 AM – 5:00 PM          │
  │                                                        │
  │  Please bring a valid government-issued ID.            │
  └───────────────────────────────────────────────────────┘
```

**Sections:** Request Information · Uploaded Documents · Staff Remarks (conditional) · Claiming Instructions (conditional) · Status History

---

### 8.5 Request Tracking Page (`/resident/track`)

**Layout:** Accessible via direct URL and from within the resident dashboard.

```
  Track Your Request
  ──────────────────

  Enter your Reference Number:
  [ EMU-YYYY-000000               ] [ Track ]

  ─────────────────────────────────────────

  EMU-2026-000004 — Barangay Clearance
  Submitted: January 18, 2026

  ●  PENDING               ✓  Jan 18, 2026 10:32 AM
  |
  ○  UNDER VERIFICATION        (awaiting)
  |
  ○  APPROVED                  (awaiting)
  |
  ○  READY FOR CLAIMING        (awaiting)
  |
  ○  COMPLETED                 (awaiting)
```

- Reference number search field at top (also works when arriving via link with `?ref=EMU-2026-000004`)
- Visual status timeline per Section 6.10
- REJECTED/CANCELLED shown as terminal steps with appropriate styling
- No personal data of other residents exposed (Req 8.8)

---

### 8.6 Notifications Page (`/resident/notifications`)

Per Section 13 (Notification UI — Detailed Specification). The compact summary component (bell + dropdown) is defined in Section 6.8.

```
  Notifications                     [ Mark All as Read ]
  ─────────────────────────────────────────────────────

  ● [unread]  Your request EMU-2026-000004 is Ready for Claiming.
              Please visit the office to claim your document.
              2 hours ago                               [ View Request ]

    [read]    Your request EMU-2026-000004 has been Approved.
              January 17, 2026 3:15 PM                 [ View Request ]

  ─────────────────────────────────────────────────────
  [← Prev]  Page 1 of 1  [Next →]
```

---

### 8.7 Resident Profile Page (`/resident/profile`)

**Layout:** Two-panel card layout.

```
  My Profile
  ──────────

  ┌─── Personal Information ──────────────────────────────┐
  │  First Name:   Juan                                    │
  │  Last Name:    dela Cruz                               │
  │  Email:        juan@email.com  (cannot change)        │
  │  Contact:      09171234567                            │
  │  Address:      123 Main St., Lingayen, Pangasinan      │
  │                                                        │
  │                          [ Edit Profile ]              │
  └───────────────────────────────────────────────────────┘

  ┌─── Change Password ───────────────────────────────────┐
  │  Current Password *                                    │
  │  [                                           ]         │
  │                                                        │
  │  New Password *                                        │
  │  [                                           ]         │
  │                                                        │
  │  Confirm New Password *                                │
  │  [                                           ]         │
  │                                                        │
  │                     [ Update Password ]                │
  └───────────────────────────────────────────────────────┘
```

- Email shown as read-only with note "(Contact administrator to change)"
- Edit Profile: toggles fields to editable mode inline, shows Save/Cancel
- Password change: separate form section, always visible
- Success/error alerts shown at top of respective section after submit

---

### 8.8 Resident Settings Page (`/resident/settings`)

**Layout:** Single card, simple settings list.

Scope limited to approved system settings (Req 20). No unapproved preferences added.

```
  Account Settings
  ────────────────

  This page will display configurable account preferences as defined
  by the system administrator.
```

Note: Specific resident-level settings are not defined in the current requirements beyond what is in `system_settings`. This page is a placeholder for future expansion and currently displays only informational content.

---

## 9. Staff Pages — Layout Specifications

All staff pages use `layout-auth.php` with the staff sidebar (Section 4.5).

### 9.1 Staff Dashboard (`/staff/dashboard`)

**Purpose:** At-a-glance workload overview. Staff land here after login.

#### Page Title
```
Staff Dashboard
```
H1, Inter 700, 1.5rem. No quick-action button on dashboard.

#### Statistics Row — Request Counts by Status

```
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  Pending     │ │  Under       │ │  Add. Info   │ │  Approved    │
│              │ │  Verification│ │  Required    │ │              │
│     14       │ │      7       │ │      3       │ │      5       │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘

┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  Rejected    │ │  Ready for   │ │  Completed   │
│              │ │  Claiming    │ │              │
│      2       │ │      4       │ │     88       │
└──────────────┘ └──────────────┘ └──────────────┘
```

- 4 columns desktop, 2 columns tablet, 2 columns mobile
- Each card uses the left accent border color matching the status (Section 2.5)
- Cards are clickable — clicking filters the Request List to that status
- `aria-label="View [N] Pending requests"` on each card link

#### Recent Activity Table

Shows the 10 most recently updated requests across all residents.

```
Reference No.     Resident            Document Type       Status              Last Updated
─────────────────────────────────────────────────────────────────────────────────────────────
EMU-2026-000012   Santos, Maria       Barangay Clearance  ● PENDING           Jan 18, 2026
EMU-2026-000011   Cruz, Jose          Business Permit     ● UNDER VERIF.      Jan 18, 2026
EMU-2026-000010   Reyes, Ana          Birth Certificate   ✓ COMPLETED         Jan 17, 2026
```

- Columns: Reference No., Resident, Document Type, Status, Last Updated (per Req 9.2)

- "View All Requests →" link below the table
- Status badge per Section 6.5
- Reference number in `.text-reference` monospace

---

### 9.2 Staff Request List (`/staff/requests`)

**Purpose:** Full filterable list of all requests across all residents.

```
  All Requests                                          [ Search ]
  ────────────────────────────────────────────────────────────────

  Status: [ All ▾ ]   Document Type: [ All ▾ ]   Date: [ From ] [ To ]   [ Apply ]

  Showing 1–20 of 147 requests

  ─────────────────────────────────────────────────────────────────────────────────
  Ref. No.          Resident             Doc. Type           Submitted     Status        Action
  ─────────────────────────────────────────────────────────────────────────────────
  EMU-2026-000014   Santos, Maria        Barangay Clearance  Jan 18, 2026  ● PENDING     [Review]
  EMU-2026-000013   Cruz, Jose           Business Permit     Jan 17, 2026  ● UNDER VER.  [Review]
  EMU-2026-000012   Reyes, Ana           Birth Certificate   Jan 16, 2026  ⚠ ADD. INFO   [Review]
  ─────────────────────────────────────────────────────────────────────────────────

  [← Prev]  Page 1 of 8  [Next →]                     Per page: [ 20 ▾ ]
```

**Filters:**
- Status dropdown: All / Pending / Under Verification / Additional Info Required / Approved / Rejected / Ready for Claiming / Completed / Cancelled
- Document type dropdown: populated from active document types
- Date range: from/to date inputs
- Search: by reference number or resident name (free text)
- Filters persist in URL query parameters for bookmarking/sharing

**Sorting:**
- Columns sortable: Reference No., Submitted date, Status, Last Updated
- Sort indicator: `▲` / `▼` in column header
- Default sort: Submitted date, newest first

**Actions:**
- [Review] = `btn btn-sm btn-primary` → navigates to Staff Request Review page

---

### 9.3 Staff Request Review (`/staff/requests/{id}`)

**Purpose:** Full request detail view with all staff actions.

**Layout:** Two-column desktop (main content left, action panel right), single column mobile (action panel below content).

---

#### Breadcrumb
```
Dashboard  /  Requests  /  EMU-2026-000014
```

---

#### Left Column — Request Content

**Block 1: Request Header**
```
  Barangay Clearance                         ● PENDING
  Reference: EMU-2026-000014
  Submitted: January 18, 2026, 9:14 AM
```
- H2 document type name
- Status badge (large variant)
- Reference number in `.text-reference`

---

**Block 2: Resident Information**
```
  ┌─── Resident Information ──────────────────────────────┐
  │  Full Name:     Maria Santos                           │
  │  Email:         maria.santos@email.com                 │
  │  Contact No.:   09171234567                           │
  │  Address:       456 Rizal Ave., Lingayen, Pangasinan   │
  └───────────────────────────────────────────────────────┘
```

---

**Block 3: Request Information**
```
  ┌─── Request Information ───────────────────────────────┐
  │  Document Type:    Barangay Clearance                  │
  │  Purpose:          For employment application          │
  │  Copies:           1                                   │
  │  Date Submitted:   January 18, 2026                   │
  └───────────────────────────────────────────────────────┘
```

---

**Block 4: Uploaded Documents**
```
  ┌─── Uploaded Documents ────────────────────────────────┐
  │  📄 valid_id.pdf         1.2 MB    Jan 18, 2026   [↓ Download] │
  │  🖼 proof_of_res.jpg      840 KB    Jan 18, 2026   [↓ Download] │
  └───────────────────────────────────────────────────────┘
```

- File served through PHP download handler (Req 9.5) — no direct URL exposed
- Download button: `btn btn-sm btn-outline-secondary` with `bi-download` icon
- File type icon: PDF = `bi-file-earmark-pdf`, image = `bi-file-earmark-image`

---

**Block 5: Status History Timeline**
```
  ┌─── Status History ────────────────────────────────────┐
  │                                                        │
  │  ●  PENDING        Jan 18, 2026 9:14 AM   System      │
  │  |                                                     │
  │  ○  (awaiting next action)                             │
  │                                                        │
  └───────────────────────────────────────────────────────┘
```

- Uses Status Timeline Component (Section 6.10)
- Displays acting user role (not personal name) per Req 8.3
- Shows remark/reason for each step if applicable

---

#### Right Column — Staff Action Panel

```
  ┌─── Actions ───────────────────────────────────────────┐
  │  Current Status: ● PENDING                             │
  │  ─────────────────────────────────────────────────     │
  │                                                        │
  │  [ Begin Verification ]                               │
  │                                                        │
  │  ─────────────────────────────────────────────────     │
  │  [ Cancel Request ]                                    │
  └───────────────────────────────────────────────────────┘
```

**Action buttons vary by current status:**

| Current Status              | Available Actions                                                              |
|-----------------------------|--------------------------------------------------------------------------------|
| PENDING                     | Begin Verification, Cancel                                                     |
| UNDER VERIFICATION          | Approve, Reject, Request Additional Info, Cancel                               |
| ADDITIONAL INFO REQUIRED    | Approve, Reject, Cancel                                                        |
| APPROVED                    | Mark Ready for Claiming                                                        |
| READY FOR CLAIMING          | Mark Completed                                                                 |
| COMPLETED / REJECTED / CANCELLED | No actions available — read-only                                          |

**Action button styles:**
- Begin Verification: `d-grid` wrapper + `btn btn-primary w-100`
- Approve: `d-grid` wrapper + `btn btn-success w-100`
- Mark Ready for Claiming: `d-grid` wrapper + `btn btn-success w-100`
- Mark Completed: `d-grid` wrapper + `btn btn-success w-100`
- Reject: `d-grid` wrapper + `btn btn-outline-danger w-100`
- Request Additional Info: `d-grid` wrapper + `btn btn-warning w-100` (text `#1a202c`)
- Cancel: `d-grid` wrapper + `btn btn-outline-secondary w-100`

**Actions requiring a modal with input:**

Reject → opens modal:
```
  Reject Request
  ──────────────
  Rejection Reason *
  [                                               ]
  [                                               ]
  Please provide a clear reason for rejection.

              [ Cancel ]    [ Confirm Rejection ]
```

Request Additional Info → opens modal:
```
  Request Additional Information
  ───────────────────────────────
  Remark for Resident *
  [                                               ]
  [                                               ]
  Explain clearly what information is needed.

              [ Cancel ]    [ Send Request ]
```

Cancel Request → opens modal:
```
  Cancel This Request?
  ─────────────────────
  This action cannot be undone.

  Reason (optional)
  [                                               ]

              [ Keep Request ]    [ Confirm Cancel ]
```

All destructive modals use `data-bs-backdrop="static"`.

---

### 9.4 Staff Profile Page (`/staff/profile`)

Identical in layout to Resident Profile Page (Section 8.7) with the same two-panel structure:
- Personal Information (editable: first name, last name, contact number)
- Change Password

Email address shown as read-only. No account settings tab for Staff.

---

## 10. Administrator Pages — Layout Specifications

All admin pages use `layout-auth.php` with the admin sidebar (Section 4.6).

### 10.1 Admin Dashboard (`/admin/dashboard`)

**Purpose:** System-wide operational overview.

#### Page Title
```
Administrator Dashboard
```

#### Summary Metric Cards — Row 1

```
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│  Total Requests  │ │  This Month      │ │  Registered      │ │  Staff           │
│                  │ │  Requests        │ │  Residents       │ │  Accounts        │
│      328         │ │       42         │ │      215         │ │       8          │
└──────────────────┘ └──────────────────┘ └──────────────────┘ └──────────────────┘
```

#### Requests by Status — Row 2

Horizontal bar chart (Bootstrap-compatible, CSS-only or Chart.js minimal):

```
  Requests by Status
  ──────────────────
  Pending              ████████░░░░░░░░░  14
  Under Verification   ████░░░░░░░░░░░░░   7
  Add. Info Required   ██░░░░░░░░░░░░░░░   3
  Approved             ███░░░░░░░░░░░░░░   5
  Rejected             █░░░░░░░░░░░░░░░░   2
  Ready for Claiming   ██░░░░░░░░░░░░░░░   4
  Completed            █████████████████  88
  Cancelled            ░░░░░░░░░░░░░░░░░   1
```

- Each bar uses the corresponding status color (Section 2.5)
- Count label at end of each bar
- If Chart.js is used, use horizontal bar chart — no 3D effects, no pie charts for this view

#### Recent System Activity

Last 10 audit log entries in a compact table:

```
  Recent Activity
  ───────────────
  Timestamp           User               Action                     Record
  ──────────────────────────────────────────────────────────────────────────
  Jan 18, 2026 10:32  Maria Santos (R)   Request Submitted          EMU-2026-000014
  Jan 18, 2026 10:15  Staff (S)          Status → Under Verif.      EMU-2026-000013
  Jan 18, 2026 09:50  Admin (A)          Document Type Activated    Barangay Clearance
```

- Role abbreviation in parentheses: (R) Resident, (S) Staff, (A) Admin
- "View Full Audit Log →" link below

---

### 10.2 User Management (`/admin/users`)

```
  User Management                            [ + Create Staff Account ]
  ──────────────────────────────────────────────────────────────────────

  Search: [ Name or Email                🔍 ]

  Role: [ All ▾ ]   Status: [ All ▾ ]   [ Apply ]

  ──────────────────────────────────────────────────────────────────────────────
  Name               Email                    Role         Status     Joined        Action
  ──────────────────────────────────────────────────────────────────────────────
  Santos, Maria      maria@email.com          Resident     Active     Jan 1, 2026   [View] [Deactivate]
  Cruz, Jose         jose@email.com           Resident     Inactive   Dec 15, 2025  [View] [Reactivate]
  Lim, Anna          anna.lim@mun.gov.ph      Staff        Active     Nov 1, 2025   [View] [Deactivate]
  Admin, System      admin@mun.gov.ph         Admin        Active     Jan 1, 2025   [View]
  ──────────────────────────────────────────────────────────────────────────────

  [← Prev]  Page 1 of 11  [Next →]
```

**Role badges:**
- Resident: `badge bg-secondary`
- Staff: `badge bg-primary`
- Administrator: `badge` with Government Gold background `#c8922a`, dark text

**Status badges:**
- Active: `badge bg-success`
- Inactive: `badge bg-danger`

**Action constraints:**
- Deactivate: hidden/disabled on the currently logged-in Administrator's own row (Req 11.6)
- Deactivate/Reactivate triggers confirmation modal
- No Delete button (Req 11.9)
- Administrator rows show only [View] — no Deactivate (protected per Req 11.10)

**Create Staff Account modal (triggered by "+ Create Staff Account"):**
```
  Create New Staff Account
  ────────────────────────
  First Name *       Last Name *
  [           ]      [           ]

  Email Address *
  [                              ]

  Temporary Password *
  [                              ]

  Role: Staff (fixed — cannot change)

       [ Cancel ]    [ Create Account ]
```

---

### 10.3 Document Types Management (`/admin/document-types`)

```
  Document Types                              [ + Add Document Type ]
  ──────────────────────────────────────────────────────────────────

  ─────────────────────────────────────────────────────────────────────────────
  Document Type         Description          Fee      Status     Requirements  Action
  ─────────────────────────────────────────────────────────────────────────────
  Barangay Clearance    For general...       ₱50.00   ● Active   3 active      [Edit] [Requirements] [Deactivate]
  Business Permit       For business...      ₱200.00  ● Active   5 active      [Edit] [Requirements] [Deactivate]
  Birth Certificate     Copy of birth...     ₱150.00  ○ Inactive 2 active      [Edit] [Requirements] [Activate]
  ─────────────────────────────────────────────────────────────────────────────
```

**Status indicators:**
- Active: `● Active` badge, green background
- Inactive: `○ Inactive` badge, gray background

**Add / Edit Document Type — slide-in panel or modal (large):**
```
  Add Document Type
  ─────────────────

  Document Type Name *
  [                                          ]

  Description *
  [                                          ]
  [                                          ]
  [                                          ]

  Processing Fee (₱) *
  [         ]

  Status:  ● Active  ○ Inactive

       [ Cancel ]    [ Save Document Type ]
```

**[Requirements] button** navigates to the Requirements Management page filtered to this document type.

---

### 10.4 Requirements Management (`/admin/requirements`)

```
  Requirements Management
  ────────────────────────

  Document Type: [ Barangay Clearance ▾ ]         [ + Add Requirement ]

  ──────────────────────────────────────────────────────────────────────
  Requirement Name         Description              Status     Action
  ──────────────────────────────────────────────────────────────────────
  Valid Government ID      Any valid gov-issued...  ● Active   [Edit] [Deactivate]
  Proof of Residency       Utility bill or lease..  ● Active   [Edit] [Deactivate]
  Community Tax Cert.      For residents above...   ○ Inactive [Edit] [Activate]
  ──────────────────────────────────────────────────────────────────────
```

**Add / Edit Requirement — modal:**
```
  Add Requirement
  ───────────────

  Document Type: Barangay Clearance (read-only if accessed from doc type page)

  Requirement Name *
  [                                          ]

  Description
  [                                          ]
  [                                          ]

  Status:  ● Active  ○ Inactive

       [ Cancel ]    [ Save Requirement ]
```

---

### 10.5 Reports Page (`/admin/reports`)

```
  Reports
  ────────

  ┌─── Filters ───────────────────────────────────────────┐
  │  Date Range:   [ Jan 1, 2026 ] to [ Jan 31, 2026 ]    │
  │  Document Type: [ All ▾ ]                              │
  │  Status:        [ All ▾ ]                              │
  │                                                        │
  │                           [ Generate Report ]          │
  └───────────────────────────────────────────────────────┘

  ─── Summary ────────────────────────────────────────────
  Period:        January 1–31, 2026
  Total Requests: 42
  Approved:       28     Rejected: 3     Pending: 11

  ─── Detail Table ───────────────────────────────────────
  Ref. No.        Document Type       Resident        Submitted     Status        Completed
  ──────────────────────────────────────────────────────────────────────────────────────────
  EMU-2026-000042 Barangay Clearance  Santos, Maria   Jan 28, 2026  ✓ COMPLETED   Jan 30, 2026
  ...

  [← Prev]  Page 1 of 3  [Next →]

  [ Print / Export ]
```

- "Print / Export" button triggers browser print dialog (`window.print()`)
- Print stylesheet: hides sidebar, header, filter panel; shows only summary + table
- No PDF export library required at this phase — browser print to PDF is sufficient
- Charts: optional summary bar chart above the table if Chart.js is available

---

### 10.6 Audit Logs (`/admin/audit-logs`)

```
  Audit Logs                                        Read-only — cannot be modified
  ─────────────────────────────────────────────────────────────────────────────────

  User: [ Search by name/email  ] Action: [ All ▾ ] Date: [ From ] [ To ] [ Apply ]

  ─────────────────────────────────────────────────────────────────────────────────
  Timestamp             User                Role    Action                    Record         IP Address
  ─────────────────────────────────────────────────────────────────────────────────
  Jan 18, 2026 10:32    Santos, Maria       R       Request Submitted         EMU-2026-0014  192.168.1.10
  Jan 18, 2026 10:15    Lim, Anna           S       Status Changed → Verif.   EMU-2026-0013  192.168.1.5
  Jan 18, 2026 09:50    Admin               A       Doc. Type Activated       Barangay Cl.   127.0.0.1
  Jan 18, 2026 09:30    [System]            —       Registration Email Sent   santos@...     —
  ─────────────────────────────────────────────────────────────────────────────────

  [← Prev]  Page 1 of 45  [Next →]
```

**Read-only notice:** Persistent `alert-info` banner at top: "Audit logs are read-only and cannot be modified or deleted."

**Filters:**
- User search: free text by name or email
- Action type dropdown: populated from distinct action types in the log
- Date range: from/to date inputs

**Role column abbreviations:** R = Resident, S = Staff, A = Administrator, — = System

---

### 10.7 System Settings (`/admin/settings`)

```
  System Settings
  ────────────────

  ┌─── Municipal Information ─────────────────────────────┐
  │  Office Name *                                         │
  │  [  Municipal Government of Lingayen               ]  │
  │                                                        │
  │  Office Address *                                      │
  │  [  Lingayen, Pangasinan                           ]  │
  └───────────────────────────────────────────────────────┘

  ┌─── Contact Information ───────────────────────────────┐
  │  Contact Number *                                      │
  │  [  (075) XXX-XXXX                                 ]  │
  │                                                        │
  │  Office Email Address *                                │
  │  [  municipal@lingayen.gov.ph                      ]  │
  └───────────────────────────────────────────────────────┘

  ┌─── System Configuration ──────────────────────────────┐
  │  Maximum File Upload Size (MB) *                       │
  │  [  5  ]   (1–20 MB)                                  │
  │                                                        │
  │  Session Timeout (minutes) *                           │
  │  [  30  ]   (5–120 minutes)                           │
  │                                                        │
  │  Records Per Page *                                    │
  │  [  20  ]   (10, 20, or 50)                           │
  └───────────────────────────────────────────────────────┘

  ┌─── Email Configuration ───────────────────────────────┐
  │  ℹ  Email notifications are configured via             │
  │     environment variables on the server.               │
  │     Credentials are not displayed here.                │
  └───────────────────────────────────────────────────────┘

                              [ Save Settings ]
```

**Critical:** Mailtrap credentials are never displayed (Req 10.7). Email section is informational only.

Each section is a separate `<fieldset>`. Save triggers server-side validation per Req 20.3.

Success alert shown at top after save: "Settings saved successfully."

---

### 10.8 Admin Profile Page (`/admin/profile`)

Same layout as Resident Profile (Section 8.7):
- Personal Information panel (editable: first name, last name, contact number)
- Change Password panel

Email shown read-only. Administrator cannot deactivate own account through this page.

---

## 11. Request Form UI — Detailed Specification

This section expands the multi-step request form (Section 8.2) with full interaction detail.

### 11.1 Step Indicator Component

```
  ① Document  ──  ② Details  ──  ③ Upload  ──  ④ Review  ──  ⑤ Confirm
```

**States:**
- Completed step: filled circle with checkmark icon, Government Blue, step label below
- Active step: filled circle with step number, Government Blue + bold label
- Upcoming step: hollow circle, gray, muted label

**Mobile adaptation:**
- Shows only: `Step 2 of 5 — Request Details`
- No step circles on < 576px

**Accessibility:**
```html
<nav aria-label="Request submission progress">
  <ol>
    <li aria-current="step">Step 2: Request Details</li>
  </ol>
</nav>
```

---

### 11.2 Step 1 — Document Type Selection

**Interaction flow:**

1. Resident lands on page — sees grid of active document type cards.
2. Resident clicks a card — card gets selected state (blue border + bg), a checkmark appears top-right of card.
3. Requirements list expands below the grid (smooth `collapse` show, no jarring jump).
4. "Continue" button becomes enabled.
5. Resident clicks "Continue" — step advances.

**Card selected state:**
```
┌──────────────────────────────┐
│                           ✓  │  ← checkmark badge top-right, green fill
│   📄 Barangay Clearance      │
│   For general purposes and   │
│   employment requirements.   │
└──────────────────────────────┘
Border: 2px solid #1a3a5c; background: #e8f0fb
```

**Requirements reveal:**
```
  Requirements for Barangay Clearance:
  ────────────────────────────────────
  ✓  Valid Government-Issued ID
  ✓  Proof of Residency
  ✓  Barangay Indigency (if applicable)

  Please prepare these documents before proceeding.
```

- Shown in `alert-info` style panel below the cards
- List uses `bi-check2` icon per item
- "Continue" button: enabled only when a document type is selected; disabled + `aria-disabled="true"` otherwise

---

### 11.3 Step 2 — Request Details

**Fields:**

```
  Purpose of Request *
  ────────────────────
  [                                                  ]
  [                                                  ]
  [                                                  ]
  e.g., "For employment application at ABC Company."
  (3-row textarea, max 500 characters, character counter below)

  Number of Copies *
  ──────────────────
  [ 1 ]  ▲▼
  Must be a whole number, 1 or more.
```

- Textarea: `form-control`, 3 rows, character counter `<small class="form-text">0/500</small>` updates live via JS
- Copies: `<input type="number" min="1" step="1">`, increment/decrement arrows
- Server-side validation enforces positive integer (Req 6.14)
- Dynamic additional fields: if the selected document type has admin-defined extra fields, they render here between Purpose and Copies

**Navigation:**
```
  [ ← Back to Document Selection ]       [ Continue to Upload → ]
```

---

### 11.4 Step 3 — Upload Supporting Documents

**Reference list:**
```
  Required Documents for Barangay Clearance
  ──────────────────────────────────────────
  • Valid Government-Issued ID
  • Proof of Residency
```

Shown as an `alert-info` panel above the upload zone. Helps residents know what to upload before interacting with the drop zone.

**File upload zone:**
```
┌──────────────────────────────────────────────────────────┐
│                                                          │
│    📎  Drag files here, or click to browse               │
│                                                          │
│    Accepted: PDF, JPG, PNG  ·  Max: 5 MB per file        │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

**Uploaded file list (builds dynamically):**
```
  📄  valid_id.pdf          1.2 MB    ████████████████ 100%   [ × ]
  🖼  proof_of_res.jpg       840 KB   ████████████████ 100%   [ × ]
  📄  invalid_doc.exe        25 KB    [Error: File type not allowed.]   [ × ]
```

- Each file row: type icon, original filename (truncated at 40 chars), file size, progress bar (Bootstrap `progress`), remove button
- Error files: row background `#fee2e2`, error message in red below filename
- Progress bar: shown during upload, replaced by checkmark on completion
- "Continue" button disabled until at least one valid file is present and no error files remain

**Navigation:**
```
  [ ← Back to Details ]       [ Continue to Review → ]
```

---

### 11.5 Step 4 — Review Request

**Layout:** Read-only summary. Two columns desktop, stacked mobile.

```
  Review Your Request
  ────────────────────────────────────────────────────────────

  ┌─── Document ─────────────────────────────────────────────┐
  │  Document Type:    Barangay Clearance                     │
  │  Purpose:          For employment application             │
  │  Copies Requested: 2                                      │
  └──────────────────────────────────────────────────────────┘

  ┌─── Uploaded Files ───────────────────────────────────────┐
  │  📄 valid_id.pdf         1.2 MB                          │
  │  🖼 proof_of_res.jpg      840 KB                         │
  └──────────────────────────────────────────────────────────┘

  By submitting, you confirm that all information and documents
  provided are true and correct.

  [ ← Back to Upload ]        [ Submit Request → ]
```

- Submit button: `btn btn-primary btn-lg`
- Submission triggers a loading state on the button (Section 6.1 loading state)
- CSRF token is included as a hidden field in the form
- No editing from this step — user must go Back

---

### 11.6 Step 5 — Submission Confirmation

**Layout:** Centered success panel.

```
  ┌──────────────────────────────────────────────────────────┐
  │                                                          │
  │  ✓  Your Request Has Been Submitted                      │
  │                                                          │
  │  Reference Number:                                       │
  │  EMU-2026-000014                                         │
  │  (shown large, monospace)                                │
  │                                                          │
  │  Document:    Barangay Clearance                         │
  │  Submitted:   January 18, 2026, 10:32 AM                │
  │  Status:      ● PENDING                                  │
  │                                                          │
  │  A confirmation email has been sent to your              │
  │  registered email address.                               │
  │                                                          │
  │  [ View Request Details ]   [ Back to Dashboard ]        │
  │                                                          │
  └──────────────────────────────────────────────────────────┘
```

- Panel: white card, Level 2 shadow, green top border `4px solid #1a7a4a`
- Success icon: `bi-check-circle-fill`, 3rem, `#1a7a4a`
- Reference number: `.text-reference`, font-size 1.75rem, Government Blue, bold
- "New Request" option not shown — resident navigated deliberately

---

## 12. Request Tracking UI — Detailed Specification

### 12.1 Tracking Page Entry States

**State 1: No reference number entered (page load)**
```
  Track Your Request
  ──────────────────
  Enter your Reference Number to see your request status.

  [ EMU-2026-______           ]  [ Track ]

  ─────────────────────────────────────────────────────
         🔍
    Enter a reference number above to begin tracking.
```

**State 2: Reference number not found**
```
  [ EMU-2026-000999           ]  [ Track ]

  ─────────────────────────────────────────────────────
  ⚠  No request found with reference number EMU-2026-000999.
     Please check the number and try again.
```

**State 3: Valid reference number — result displayed**
(See Section 12.2)

**State 4: Loading**
```
  [ EMU-2026-000014           ]  [ ◌ Searching… ]
```

---

### 12.2 Tracking Result — Full Display

```
  EMU-2026-000014
  ──────────────────────────────────────────────────────

  Document:       Barangay Clearance
  Submitted:      January 18, 2026
  Current Status: ● PENDING

  ──────────────── Progress ──────────────────────────

    ●  PENDING                  Jan 18, 2026  9:14 AM
    |     Request submitted and awaiting staff review.
    |
    ○  UNDER VERIFICATION             (not yet reached)
    |
    ○  APPROVED                       (not yet reached)
    |
    ○  READY FOR CLAIMING             (not yet reached)
    |
    ○  COMPLETED                      (not yet reached)

  ────────────────────────────────────────────────────

  [ View Full Details ]
```

---

### 12.3 Timeline — Branching States

**When ADDITIONAL INFORMATION REQUIRED is active:**

```
    ●  PENDING                  Jan 18, 2026  9:14 AM
    |
    ●  UNDER VERIFICATION       Jan 18, 2026  2:32 PM
    |
    ●  ADD. INFO REQUIRED       Jan 19, 2026 10:05 AM
    |   ⚠  Staff remark: "Please upload a clearer copy
    |       of your valid ID."
    |   [ Upload Additional Documents ]
    |
    ○  APPROVED                       (awaiting)
    ...
```

- Remark shown inline below the status step
- Upload link shown only for authenticated resident owners of the request
- Remark text is visually offset with a left border `3px solid #c8922a`

**When REJECTED:**

```
    ●  PENDING                  Jan 18, 2026  9:14 AM
    |
    ●  UNDER VERIFICATION       Jan 18, 2026  2:32 PM
    |
    ✕  REJECTED                 Jan 19, 2026 11:00 AM
         Reason: "Submitted documents are incomplete.
                  Valid ID is expired."
```

- Terminal step shown with `✕` icon, red color `#b91c1c`
- Rejection reason shown below the step
- No further steps rendered after terminal state

**When CANCELLED:**

```
    ●  PENDING                  Jan 18, 2026  9:14 AM
    |
    ⊘  CANCELLED                Jan 18, 2026 11:30 AM
         Cancelled by: Resident
         Reason: (not provided)
```

- Terminal step, `⊘` icon, dark gray color `#4b5563`
- Shows who cancelled (Resident or Staff role label — not personal name)

---

### 12.4 Status Color and Icon Reference (Timeline)

| Status                          | Icon                  | Color       |
|---------------------------------|-----------------------|-------------|
| PENDING                         | `bi-clock`            | `#9ca3af`   |
| UNDER VERIFICATION              | `bi-search`           | `#1d4ed8`   |
| ADDITIONAL INFORMATION REQUIRED | `bi-exclamation-circle`| `#92400e`  |
| APPROVED                        | `bi-check-circle`     | `#065f46`   |
| REJECTED                        | `bi-x-circle-fill`    | `#991b1b`   |
| READY FOR CLAIMING              | `bi-building`         | `#0f766e`   |
| COMPLETED                       | `bi-check-circle-fill`| `#14532d`   |
| CANCELLED                       | `bi-slash-circle`     | `#1f2937`   |

Completed/current step node: filled circle with icon, full color.
Future step node: hollow circle, icon outlined, muted gray `#9ca3af`.
Connecting line: `2px solid #e5e7eb`.

---

### 12.5 Mobile Timeline Adaptation

On screens < 576px:
- Timeline is fully vertical (same as desktop — already vertical)
- Step labels wrap to two lines if needed
- Remark text is collapsible: "Show remark ▼" / "Hide remark ▲" toggle
- "View Full Details" button is full-width `btn-block`
- Touch target for each step node: minimum 44×44px tap area

---

## 13. Notification UI — Detailed Specification

### 13.1 Notification Trigger Events

Per `requirements.md` Requirement 10 and Requirement 16, notifications are created for the following events:

| Event                           | In-System Notification | Email Notification |
|---------------------------------|------------------------|--------------------|
| Registration                    | —                      | ✓                  |
| Request Submitted               | ✓                      | ✓                  |
| Under Verification              | ✓                      | ✓                  |
| Additional Information Required | ✓                      | ✓                  |
| Approved                        | ✓                      | ✓                  |
| Rejected                        | ✓                      | ✓                  |
| Ready for Claiming              | ✓                      | ✓                  |
| Completed                       | ✓                      | ✓                  |
| Cancelled                       | ✓                      | ✓                  |

In-system notifications are for Residents only (Req 16.1). Registration does not produce an in-system notification since the resident is not yet logged in at that point.

---

### 13.2 Notification Bell — Header Component

**Inactive (zero unread):**
```
  🔔
```
- `bi-bell` icon, Secondary Text color `#4b5563`
- No badge rendered
- `aria-label="Notifications, no unread messages"`

**Active (unread notifications exist):**
```
  🔔 ³
     └── small red circle badge
```
- `bi-bell-fill` icon, Government Blue `#1a3a5c`
- Badge: `position: absolute`, top-right of icon, Danger Red `#b91c1c` background, white text
- Badge text: count up to 99; shows "99+" for counts above 99
- `aria-label="Notifications, 3 unread messages"`
- Badge is `aria-hidden="true"` — the count is in the `aria-label` of the button

**Interaction:** Click opens notification dropdown (Section 13.3). Keyboard: Enter or Space opens dropdown.

---

### 13.3 Notification Dropdown

Triggered by clicking the bell icon. Shows the 5 most recent notifications.

```
┌─────────────────────────────────────────────────────────┐
│  Notifications                     [ Mark all as read ] │
├─────────────────────────────────────────────────────────┤
│ ▌ ● Your request EMU-2026-000014 is                     │  ← unread
│    Ready for Claiming. Visit the office                 │
│    to claim your document.                              │
│    2 hours ago                                          │
├─────────────────────────────────────────────────────────┤
│   ● Your request EMU-2026-000013 has                    │  ← read
│    been Approved.                                       │
│    Yesterday, 3:15 PM                                   │
├─────────────────────────────────────────────────────────┤
│   ● Your request EMU-2026-000012 requires               │  ← read
│    additional information.                              │
│    Jan 16, 2026                                         │
├─────────────────────────────────────────────────────────┤
│                  View All Notifications →               │
└─────────────────────────────────────────────────────────┘
```

**Unread item styling:**
- Left border: `3px solid #1a3a5c`
- Background: `#f0f4ff`
- Message text: Inter 600 (bold)
- Unread dot: `●` inline before message, Government Blue

**Read item styling:**
- No left border
- Background: white
- Message text: Inter 400 (normal)

**Non-color unread indicator:** Left border + bold text (not color alone — WCAG 1.4.1).

**Dropdown specifications:**
- Width: 360px desktop, full-width on mobile
- Max height: 400px, overflow-y auto
- Appears below bell icon, right-aligned
- Closes on outside click or Escape key
- `role="menu"`, each item `role="menuitem"`
- "Mark all as read": `btn btn-link btn-sm`, top right of header row

**On item click:**
1. Marks notification as read (API call)
2. Navigates to relevant request detail page

**Empty state:**
```
  Notifications
  ─────────────
        🔔
   No notifications yet.
   You'll be notified when your
   request status changes.
```

---

### 13.4 Notifications Full Page (`/resident/notifications`)

**Layout:** Authenticated page within resident sidebar.

```
  Notifications                          [ Mark All as Read ]
  ──────────────────────────────────────────────────────────

  Filter: [ All ▾ ]  (All / Unread / Read)

  ──────────────────────────────────────────────────────────
  ▌ ●  Ready for Claiming  ·  EMU-2026-000014
       Your document is ready for pick-up at the
       Municipal Civil Registrar's Office.
       January 18, 2026, 10:32 AM              [ View Request ]

  ──────────────────────────────────────────────────────────
     ●  Approved  ·  EMU-2026-000013
       Your request for Business Permit has been approved.
       January 17, 2026, 3:15 PM               [ View Request ]

  ──────────────────────────────────────────────────────────
     ●  Additional Info Required  ·  EMU-2026-000012
       Please upload a clearer copy of your valid ID.
       January 16, 2026, 11:05 AM              [ View Request ]
  ──────────────────────────────────────────────────────────

  [← Prev]  Page 1 of 2  [Next →]
```

**Columns per row (full page list):**
- Left indicator strip (unread: colored, read: none)
- Notification type label (status name) — bold, Government Blue for unread
- Reference number — `.text-reference` monospace
- Message body — body sm
- Timestamp — muted, right-aligned
- [View Request] button — `btn btn-sm btn-outline-primary`

**Unread vs Read visual distinction (non-color):**
- Unread: left border `3px solid #1a3a5c` + bold type label + background `#f0f4ff`
- Read: no border, normal weight, white background
- Filter dropdown allows viewing only unread items

**Mark All as Read:** `btn btn-outline-secondary btn-sm`, top right. Triggers confirmation: "Mark all notifications as read?" with [Cancel] [Confirm].

**Empty state — no notifications:**
```
        🔔
   No notifications.
   You'll be notified here when your request status changes.
```

**Empty state — no unread (filter = Unread):**
```
        ✓
   All caught up!
   You have no unread notifications.
```

---

### 13.5 Notification Message Templates

Standard message text shown in in-system notifications:

| Event                           | Message Template                                                              |
|---------------------------------|-------------------------------------------------------------------------------|
| Request Submitted               | "Your request for [Document Type] has been submitted. Reference: [Ref No.]"  |
| Under Verification              | "Your request [Ref No.] is now under verification by our staff."             |
| Additional Info Required        | "Your request [Ref No.] requires additional information. Please check the details." |
| Approved                        | "Your request [Ref No.] has been approved and is being processed."           |
| Rejected                        | "Your request [Ref No.] has been rejected. Please check the rejection reason."|
| Ready for Claiming              | "Your document for [Ref No.] is ready for claiming at our office."           |
| Completed                       | "Your request [Ref No.] has been completed. Thank you."                      |
| Cancelled                       | "Your request [Ref No.] has been cancelled."                                 |

---

## 14. Responsive Design Behavior

### 14.1 Breakpoint Reference

Using Bootstrap 5 default breakpoints:

| Breakpoint | Label  | Min Width | Target Devices                        |
|------------|--------|-----------|---------------------------------------|
| xs         | (none) | 0px       | Small phones (320px–374px)            |
| sm         | sm     | 576px     | Standard phones (375px–575px range)   |
| md         | md     | 768px     | Large phones, small tablets           |
| lg         | lg     | 992px     | Tablets, small laptops                |
| xl         | xl     | 1200px    | Laptops, desktops                     |
| xxl        | xxl    | 1400px    | Large desktops, wide monitors         |

Minimum supported viewport: **320px wide**.

---

### 14.2 Layout Behavior by Breakpoint

#### Public Pages

| Element              | xs / sm (< 768px)             | md (768px–991px)             | lg+ (≥ 992px)                  |
|----------------------|-------------------------------|------------------------------|--------------------------------|
| Navbar               | Collapsed, hamburger menu     | Collapsed, hamburger menu    | Horizontal, fully expanded     |
| Hero section         | Single column, smaller H1     | Single column, medium H1     | Single column, full H1         |
| Services grid        | 1 column                      | 2 columns                    | 3 columns                      |
| How It Works steps   | 1 column stacked              | 2 columns                    | 4 columns inline               |
| Benefits             | 1 column stacked              | 3 columns                    | 3 columns                      |
| Footer columns       | 1 column stacked              | 2 columns                    | 3 columns                      |
| Login / Register     | Full-width card (no shadow)   | Centered card, max 480px     | Centered card, max 560px       |

#### Authenticated Layout (Resident / Staff / Admin)

| Element              | xs / sm / md (< 992px)        | lg+ (≥ 992px)                              |
|----------------------|-------------------------------|--------------------------------------------|
| Sidebar              | Hidden, off-canvas overlay    | Fixed left, 240px wide, always visible     |
| Main content         | Full width                    | `calc(100% - 240px)`, offset right         |
| Header               | Full width, hamburger visible | Full width, hamburger hidden               |
| Metric card grid     | 2 columns                     | 3–4 columns                                |
| Request tables       | `table-responsive` wrapper, horizontal scroll | Full table visible         |
| Staff action panel   | Below request content         | Fixed right column, 300px wide             |
| Form fields          | 1 column (stacked)            | Up to 2 columns for paired short fields    |
| Step indicator       | Step N of M text only         | Full step circles with labels              |

---

### 14.3 Navigation — Mobile Behavior Detail

**Public navbar (< 992px):**
- Hamburger `bi-list` icon, white, 28px touch target minimum 44×44px
- Clicking hamburger toggles `<div class="navbar-collapse">`
- Collapsed menu: stacked vertical links, full width, 16px padding per item
- CTA buttons (Login, Register) appear at bottom of collapsed menu
- Close: clicking hamburger again, clicking outside, or pressing Escape

**Authenticated sidebar (< 992px):**
- Sidebar uses Bootstrap Offcanvas (`<div class="offcanvas offcanvas-start">`)
- Triggered by hamburger in fixed header
- Overlay backdrop covers main content when sidebar is open
- Sidebar items same as desktop
- Close: × button inside offcanvas, backdrop click, or Escape key
- Focus trap active while offcanvas is open (Bootstrap handles this natively)

---

### 14.4 Responsive Tables

All data tables are wrapped in `<div class="table-responsive">`.

On mobile (< 768px), tables scroll horizontally within their container. The container does not cause page-level horizontal overflow.

**Priority columns on mobile (visible without scrolling):**
- Requests table: Reference No. + Status + Action (others scroll)
- Users table: Name + Role + Action (others scroll)
- Audit log: Timestamp + Action (others scroll)

Alternative for very small screens: consider a card-list layout instead of table rows for the resident "My Requests" list on < 576px viewports. Each card shows the key fields stacked vertically.

**Card layout (mobile alternative for My Requests):**
```
┌────────────────────────────────────────┐
│  EMU-2026-000014                       │
│  Barangay Clearance                    │
│  Jan 18, 2026          ● PENDING       │
│                  [View]  [Track]       │
└────────────────────────────────────────┘
```

---

### 14.5 Form Responsive Behavior

| Form element         | Mobile (< 768px)                  | Desktop (≥ 768px)                      |
|----------------------|-----------------------------------|----------------------------------------|
| Label + input pairs  | Full width, stacked               | Up to 2 columns for short paired inputs|
| Textarea             | Full width                        | Full width                             |
| Submit button        | Full width (`d-grid`)             | Auto width, right-aligned              |
| Step indicator       | "Step N of M" text                | Full circles + labels                  |
| File upload zone     | Full width, reduced height        | Full width                             |
| Modal                | Full-screen on xs (`modal-fullscreen-sm-down`) | Centered, max-width 500px |

---

### 14.6 Touch Targets

All interactive elements on mobile meet the WCAG 2.1 SC 2.5.5 minimum touch target size of **44×44 CSS pixels**:

- Navigation items: `min-height: 44px`, `padding: 12px 20px`
- Buttons: `min-height: 44px` (Bootstrap `btn` default satisfies this)
- Table action buttons: `btn btn-sm` + explicit `min-height: 44px` override on mobile
- File remove (×) buttons: `min-width: 44px; min-height: 44px`
- Notification items in dropdown: `min-height: 56px`
- Checkbox / radio: 44×44px clickable area via label association

---

### 14.7 Typography Scaling

| Element    | Mobile (< 768px) | Desktop (≥ 768px) |
|------------|------------------|-------------------|
| H1 hero    | 1.75rem          | 2.5rem            |
| H1 page    | 1.375rem         | 1.75rem           |
| H2         | 1.25rem          | 1.5rem            |
| Body       | 1rem             | 1rem              |
| Metric value | 1.5rem         | 2rem              |
| Reference No. | 1.1rem        | 1.5rem (confirm page) |

No text below 0.75rem (12px) on any viewport.

---

### 14.8 Image and Icon Handling

- Municipal seal: `max-width: 100%; height: auto` — scales correctly at all sizes
- Bootstrap Icons: SVG font-based, inherits `font-size` — scales with text
- No decorative images that could cause layout shift on slow connections
- All `<img>` elements have explicit `width` and `height` attributes to prevent Cumulative Layout Shift (CLS)

---

## 15. Accessibility Considerations

### 15.1 Standards Reference

The system targets **WCAG 2.1 Level AA** compliance. Full compliance requires manual testing with assistive technologies beyond what is specified here.

Key standards applied:
- **1.1.1** Non-text Content — all images have descriptive `alt` text
- **1.3.1** Info and Relationships — semantic HTML (headings, lists, tables, fieldsets)
- **1.3.3** Sensory Characteristics — status not communicated by color alone
- **1.4.1** Use of Color — icons + text labels accompany all color indicators
- **1.4.3** Contrast (Minimum) — 4.5:1 for normal text, 3:1 for large text
- **1.4.4** Resize Text — layout intact at 200% zoom
- **2.1.1** Keyboard — all interactive elements keyboard accessible
- **2.1.2** No Keyboard Trap — focus not trapped except in modals (intentional)
- **2.4.1** Bypass Blocks — skip navigation link provided
- **2.4.2** Page Titled — every page has a unique, descriptive `<title>`
- **2.4.3** Focus Order — logical focus order matching visual layout
- **2.4.6** Headings and Labels — descriptive headings and form labels
- **2.4.7** Focus Visible — focus indicators always visible
- **3.3.1** Error Identification — errors identified and described in text
- **3.3.2** Labels or Instructions — all form inputs labelled
- **4.1.2** Name, Role, Value — ARIA used correctly where needed

---

### 15.2 Semantic HTML Structure

Every page uses correct semantic HTML landmarks:

```html
<header>         <!-- Fixed top navigation / authenticated header -->
<nav>            <!-- Primary navigation (public navbar or sidebar) -->
<main>           <!-- Main content area, one per page -->
<aside>          <!-- Sidebar when present -->
<footer>         <!-- Public page footer -->
<section>        <!-- Major content sections with headings -->
<article>        <!-- Self-contained content (notification items, request cards) -->
<form>           <!-- All form elements wrapped in form -->
<fieldset>       <!-- Grouped form fields with legend -->
<table>          <!-- Tabular data only, never for layout -->
<nav aria-label="Breadcrumb"> <!-- Breadcrumb navigation -->
<nav aria-label="Pagination"> <!-- Pagination navigation -->
```

**One `<h1>` per page.** Heading levels are never skipped (h1 → h2 → h3, not h1 → h3).

---

### 15.3 Skip Navigation

A skip-to-content link is the first focusable element in every page:

```html
<a href="#main-content" class="visually-hidden-focusable skip-link">
  Skip to main content
</a>
```

- Visually hidden until focused (Bootstrap `visually-hidden-focusable`)
- On focus: becomes visible, positioned top-left, high contrast
- `<main id="main-content">` is the target

---

### 15.4 Keyboard Navigation

**Tab order follows the visual reading order** (left to right, top to bottom):

1. Skip link
2. Header (logo, notification bell, user menu)
3. Sidebar navigation items (top to bottom)
4. Breadcrumb
5. Page title / main content
6. Form fields in order
7. Submit / action buttons
8. Pagination

**Focus indicator:**
- All focusable elements show a visible focus ring: `outline: 3px solid #2563a8; outline-offset: 2px`
- Never `outline: none` without a custom visible alternative
- Buttons on colored backgrounds use `outline: 3px solid #ffffff` with `box-shadow: 0 0 0 5px #1a3a5c`

**Keyboard interactions for custom components:**

| Component            | Key               | Behavior                                    |
|----------------------|-------------------|---------------------------------------------|
| Notification dropdown| Enter / Space     | Open dropdown                               |
| Notification dropdown| Escape            | Close dropdown, return focus to bell button |
| Notification dropdown| Tab               | Move through notification items             |
| Modal                | Escape            | Close modal, return focus to trigger        |
| Modal                | Tab               | Cycle through modal's focusable elements    |
| Accordion (FAQ)      | Enter / Space     | Expand / collapse panel                     |
| File upload zone     | Enter / Space     | Open file browser                           |
| Select (dropdown)    | Arrow keys        | Navigate options                            |
| Offcanvas sidebar    | Escape            | Close sidebar                               |

---

### 15.5 Form Accessibility

**Labels:**
- Every `<input>`, `<select>`, and `<textarea>` has an associated `<label>` using `for`/`id` pairing.
- Placeholder text is supplementary only — never a substitute for a label.

**Required fields:**
```html
<label for="purpose">
  Purpose of Request
  <span class="text-danger" aria-hidden="true">*</span>
  <span class="visually-hidden">(required)</span>
</label>
```

**Error messages:**
```html
<input id="email" aria-describedby="email-error" aria-invalid="true" ...>
<div id="email-error" class="invalid-feedback" role="alert">
  Please enter a valid email address.
</div>
```

- `aria-invalid="true"` set on the input when invalid
- `aria-describedby` links input to its error message
- Errors use `role="alert"` when injected dynamically via JS so screen readers announce them

**Grouped inputs (radio / checkbox):**
```html
<fieldset>
  <legend>Filter by Status</legend>
  <div class="form-check">
    <input type="radio" id="status-all" name="status">
    <label for="status-all">All</label>
  </div>
  ...
</fieldset>
```

---

### 15.6 Status Communication — Non-Color

Status is **never communicated by color alone** (WCAG 1.4.1). Every status indicator uses a combination of:

1. **Icon** (Bootstrap Icon, unique per status)
2. **Text label** (full status name)
3. **Color** (as a supplementary visual cue only)

Example badge markup:
```html
<span class="badge status-pending" aria-label="Status: Pending">
  <i class="bi bi-clock" aria-hidden="true"></i>
  PENDING
</span>
```

`aria-label` on the badge provides the full accessible name for screen readers.

---

### 15.7 Images and Icons

**Decorative icons** (purely visual, described by adjacent text):
```html
<i class="bi bi-person" aria-hidden="true"></i>
My Profile
```
`aria-hidden="true"` prevents screen readers from announcing the icon separately.

**Meaningful icons** (icon alone conveys meaning — e.g., action buttons with no text):
```html
<button type="button" aria-label="Remove valid_id.pdf">
  <i class="bi bi-x-circle" aria-hidden="true"></i>
</button>
```

**Municipal seal:**
```html
<img src="/assets/images/municipal-seal.png"
     alt="Municipal Seal of Lingayen, Pangasinan"
     width="36" height="36">
```

---

### 15.8 Dynamic Content (JavaScript)

- Dynamically injected alerts use `role="alert"` or `aria-live="polite"` so screen readers announce changes without page reload.
- Loading states announce to screen readers: visually-hidden `<span aria-live="polite">Loading...</span>` injected when a request starts.
- Notification count updates in the bell button update the `aria-label` attribute dynamically.
- After form submission that replaces page content (e.g., step wizard advancing), focus is moved to the new step heading programmatically.

---

### 15.9 Modal Accessibility

- On open: focus moves to the modal's first focusable element (close button or first input).
- Focus is trapped within the modal while open (Bootstrap 5 native behavior).
- On close: focus returns to the element that triggered the modal.
- `aria-modal="true"` and `role="dialog"` on the modal container.
- `aria-labelledby` references the modal title `<h5>`.

---

### 15.10 Color Contrast Verification Checklist

| Element                              | Foreground    | Background    | Ratio  | Level |
|--------------------------------------|---------------|---------------|--------|-------|
| Body text                            | `#1a202c`     | `#ffffff`     | 16.1:1 | AAA   |
| Header nav links                     | `#ffffff`     | `#1a3a5c`     | 9.8:1  | AAA   |
| Sidebar active item                  | `#1a3a5c`     | `#e8f0fb`     | 5.4:1  | AA    |
| PENDING badge text                   | `#374151`     | `#e5e7eb`     | 6.1:1  | AA    |
| APPROVED badge text                  | `#065f46`     | `#d1fae5`     | 5.5:1  | AA    |
| REJECTED badge text                  | `#991b1b`     | `#fee2e2`     | 5.2:1  | AA    |
| READY FOR CLAIMING badge text        | `#0f766e`     | `#ccfbf1`     | 4.7:1  | AA    |
| Muted helper text                    | `#6b7280`     | `#ffffff`     | 4.6:1  | AA    |
| Link text                            | `#2563a8`     | `#ffffff`     | 5.1:1  | AA    |
| Warning button text                  | `#1a202c`     | `#f0b429`     | 9.2:1  | AAA   |

---

## 16. User Flows

### 16.1 Resident Registration Flow

```
[Public: Home Page]
        │
        ▼
[Click "Register" in navbar]
        │
        ▼
[Register Page]
  Fill: First Name, Last Name, Contact No., Address, Email, Password, Confirm Password
        │
        ├──► [Validation fails] → Inline error messages → User corrects → Re-submit
        │
        ▼
[System creates account, sends registration email via Mailtrap]
        │
        ▼
[Redirect to Login Page]
  Success alert: "Account created successfully. Please sign in."
```

---

### 16.2 Resident Login Flow

```
[Login Page]
  Enter: Email, Password
        │
        ├──► [Invalid credentials] → Generic error alert → Retry
        ├──► [Account inactive] → "Account is deactivated" error → Cannot proceed
        ├──► [5+ failed attempts, lockout active] → "Account locked" error → Wait 15 minutes
        │
        ▼
[Session created]
        │
        ▼
[Redirect to Resident Dashboard]
```

---

### 16.3 Document Request Submission Flow

```
[Resident Dashboard]
  Click "+ New Request"
        │
        ▼
[Step 1: Select Document Type]
  Click a document type card
  Requirements list reveals below
  Click "Continue"
        │
        ▼
[Step 2: Request Details]
  Enter: Purpose, Number of Copies
  (+ any document-specific fields)
  Click "Continue"
        │
        ├──► [Validation fails] → Inline errors → User corrects → Re-submit
        │
        ▼
[Step 3: Upload Supporting Documents]
  Drop or browse files
  Each file validated client-side (type + size preview) and server-side on upload
        │
        ├──► [File rejected: wrong type or too large] → Per-file error → User removes and re-uploads
        │
  Click "Continue"
        │
        ▼
[Step 4: Review Request]
  Read-only summary of all entries
  Click "Submit Request"
        │
        ▼
[System: Creates request record in DB transaction]
[System: Generates reference number EMU-YYYY-NNNNNN]
[System: Sets status to PENDING, writes status_history]
[System: Creates in-system notification]
[System: Sends confirmation email via Mailtrap]
        │
        ▼
[Step 5: Submission Confirmation]
  Shows: Reference Number, Document Type, Status: PENDING
  Options: "View Request Details" or "Back to Dashboard"
```

---

### 16.4 Staff Request Processing Flow

```
[Staff Dashboard]
  View requests by status (click a metric card or go to Requests)
        │
        ▼
[Staff Request List]
  Filter/search for target request
  Click [Review]
        │
        ▼
[Staff Request Review Page]
  Review: Resident info, Request info, Uploaded files, Status history
        │
        ├──► [Status: PENDING]
        │         Click "Begin Verification"
        │         → Confirm (no modal needed) → Status: UNDER VERIFICATION
        │         → Email + in-system notification sent to Resident
        │
        ├──► [Status: UNDER VERIFICATION]
        │         ├──► Click "Approve"
        │         │         → Confirm modal → Status: APPROVED
        │         │         → Email + notification sent
        │         │
        │         ├──► Click "Reject"
        │         │         → Reject modal with required reason field
        │         │         → Status: REJECTED
        │         │         → Email + notification sent
        │         │
        │         └──► Click "Request Additional Info"
        │                   → Modal with required remark field
        │                   → Status: ADDITIONAL INFORMATION REQUIRED
        │                   → Email + notification sent
        │
        ├──► [Status: ADDITIONAL INFORMATION REQUIRED]
        │         Resident uploads additional docs
        │         Staff reviews updated request
        │         ├──► Approve → Status: APPROVED
        │         └──► Reject → Status: REJECTED
        │
        ├──► [Status: APPROVED]
        │         Click "Mark Ready for Claiming"
        │         → Status: READY FOR CLAIMING
        │         → Email + notification sent
        │
        └──► [Status: READY FOR CLAIMING]
                  Click "Mark Completed"
                  → Status: COMPLETED
                  → Email + notification sent
```

---

### 16.5 Request Cancellation Flow

**By Resident (PENDING or ADDITIONAL INFO REQUIRED):**
```
[Resident: My Requests or Request Details]
  Click "Cancel Request"
        │
        ▼
[Cancellation confirmation modal]
  Optional reason field
  Click "Confirm Cancel"
        │
        ▼
[Status: CANCELLED]
[In-system notification created]
[Email sent to Resident]
[Redirect to My Requests with success alert]
```

**By Staff (PENDING or ADDITIONAL INFO REQUIRED):**
```
[Staff Request Review Page]
  Click "Cancel Request" in action panel
        │
        ▼
[Cancellation confirmation modal]
  Optional reason field
  Click "Confirm Cancel"
        │
        ▼
[Status: CANCELLED]
[In-system notification created]
[Email sent to Resident]
[Page refreshes, action panel shows: "No further actions — request is cancelled"]
```

---

### 16.6 Request Tracking Flow

**From Resident Dashboard (authenticated):**
```
[Resident Dashboard]
  Click [Track] on a request row
        │
        ▼
[Request Tracking Page]
  Reference number pre-filled from URL parameter
  Timeline displays current status and history
```

**Direct tracking (unauthenticated or standalone):**
```
[Public: Any page with "Track Your Request" link]
  OR
[Navigate to /resident/track]
        │
        ▼
[Tracking Page — empty search state]
  Enter reference number manually
  Click "Track"
        │
        ├──► [Not found] → Error message → User re-enters
        │
        ▼
[Timeline displayed for the matching request]
  (Only requests belonging to the logged-in resident are accessible;
   unauthenticated users are redirected to login per Req 8.8)
```

---

### 16.7 Administrator User Deactivation Flow

```
[Admin: User Management]
  Locate user in list
  Click [Deactivate]
        │
        ▼
[Confirmation modal]
  "Deactivate [User Name]? They will no longer be able to log in."
  Click "Confirm Deactivate"
        │
        ├──► [User is the current Admin] → Action blocked by UI (button disabled/hidden)
        │
        ▼
[User account status = Inactive]
[Audit log entry created]
[User list refreshes: user now shows "Inactive" badge]
[User's active sessions invalidated on next request]
```

---

### 16.8 Administrator Document Type Management Flow

**Add new document type:**
```
[Admin: Document Types]
  Click "+ Add Document Type"
        │
        ▼
[Add Document Type modal]
  Enter: Name, Description, Processing Fee, Status
  Click "Save Document Type"
        │
        ├──► [Validation fails] → Inline errors in modal → User corrects
        │
        ▼
[Document type created in database]
[Audit log entry created]
[Document type appears in list as Active]
[Available immediately on Resident request form]
```

**Deactivate document type:**
```
[Admin: Document Types]
  Click [Deactivate] on an active document type
        │
        ▼
[Confirmation modal]
  "Deactivate [Document Type]? Residents will no longer be able to request this document.
   Existing requests in progress will not be affected."
  Click "Confirm Deactivate"
        │
        ▼
[Document type status = Inactive]
[Audit log entry created]
[Removed from Resident request form immediately]
[Existing in-progress requests unaffected]
```

---

### 16.9 Notification Interaction Flow

```
[Any Authenticated Resident Page]
  Status-change event occurs (server-side)
  In-system notification record created in DB
        │
        ▼
[Bell icon updates: badge count increments]
  (On next page load or via JS polling)
        │
        ▼
Resident clicks bell icon
        │
        ▼
[Notification dropdown opens]
  Resident clicks a notification item
        │
        ▼
[Notification marked as read (API call)]
[Redirect to relevant Request Details page]
        │
  OR
        ▼
Resident clicks "View All Notifications"
        │
        ▼
[Notifications full page]
  Resident clicks [View Request] on any notification
        │
        ▼
[Notification marked as read]
[Redirect to Request Details page]
```

---

### 16.10 UI State Summary per Page

| Page                    | Empty State                        | Error State                           | Loading State          | Success State                     |
|-------------------------|------------------------------------|---------------------------------------|------------------------|-----------------------------------|
| Resident Dashboard      | "No requests yet. Start a new request." | "Unable to load requests. Try again." | Skeleton cards + table | N/A (dashboard is display-only)   |
| My Requests             | "No requests found."               | "Unable to load. Try again."          | Skeleton table rows    | N/A                               |
| Request Details         | N/A (always has data)              | "Request not found." (404)            | Skeleton layout        | After cancel: success alert       |
| Request Tracking        | "Enter a reference number."        | "No request found for this number."   | Button spinner         | Timeline displayed                |
| Notifications           | "No notifications yet."            | "Unable to load notifications."       | Skeleton list rows     | After mark-all-read: info alert   |
| Staff Request List      | "No requests match your filters."  | "Unable to load requests."            | Skeleton table rows    | After status update: success alert|
| Staff Request Review    | N/A                                | "Request not found." (404)            | Skeleton layout        | Status change: success alert      |
| Admin User Management   | "No users found."                  | "Unable to load users."               | Skeleton table rows    | After create/deactivate: alert    |
| Admin Document Types    | "No document types yet."           | "Unable to load."                     | Skeleton table rows    | After save: success alert         |
| Admin Reports           | "No data for selected filters."    | "Report generation failed."           | Button spinner + overlay| Table renders with data          |
| Admin Audit Logs        | "No log entries for this filter."  | "Unable to load logs."                | Skeleton table rows    | N/A (read-only)                   |
| System Settings         | N/A                                | Inline validation errors per field    | Button spinner         | "Settings saved successfully."    |

---

*End of UI/UX Design Specification — Phase 1*

*No backend logic, database schema, API implementation, or authentication code is defined in this document.*
*All functional requirements are sourced from `requirements.md`.*
*Implementation begins in Phase 2 per the development sequence in `requirements.md` Requirement 22.7.*
