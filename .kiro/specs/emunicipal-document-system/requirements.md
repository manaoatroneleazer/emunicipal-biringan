# Requirements Document

## Introduction

eMunicipal is an Online Municipal Document Request and Management System for the Municipal Government Office of Lingayen, Pangasinan. The system enables residents to submit document requests online, tracks those requests through a defined workflow, and provides municipal staff and administrators with tools to verify, approve, reject, and manage requests — all within a monolithic PHP/MySQL application served over the web.

This document covers Phase 0: planning and requirements only. No implementation is prescribed here. All subsequent design, implementation, and testing phases will build directly from these requirements.

The system replaces or supplements manual over-the-counter document request processes, reducing the need for in-person visits, improving traceability, and providing a permanent audit trail for all government document transactions.

---

## Glossary

- **System**: The eMunicipal Online Municipal Document Request and Management System
- **Resident**: A registered member of the public who submits document requests through the System
- **Staff**: A municipal government employee who processes, verifies, approves, or rejects document requests
- **Administrator**: A privileged user who manages system configuration, users, document types, and audit data
- **Request**: A formal submission by a Resident for a specific document type, assigned a unique Reference Number
- **Reference Number**: A unique, human-readable identifier in the format EMU-YYYY-NNNNNN (e.g., EMU-2026-000001) assigned by the System upon request submission
- **Document Type**: A category of municipal document (e.g., Barangay Clearance, Business Permit) managed by the Administrator in the database
- **Requirement**: A supporting document or piece of information that a Resident must provide for a specific Document Type
- **Request Status**: The current stage of a Request in the processing workflow
- **Notification**: An in-system or email message sent to a Resident or Staff member when a Request Status changes
- **Audit Log**: A tamper-evident record of all significant actions performed within the System
- **PDO**: PHP Data Objects — the database abstraction layer used for all MySQL queries
- **CSRF Token**: A Cross-Site Request Forgery token used to validate that form submissions originate from the System
- **MIME Type**: The media type of an uploaded file, validated server-side to prevent malicious uploads
- **Mailtrap**: The email testing and development service used for all outbound email during development
- **Environment Variable**: A configuration value stored outside the codebase (e.g., in a `.env` file) to keep credentials out of version control
- **Bootstrap 5**: The CSS/JS framework used for all frontend UI components
- **REST API**: Representational State Transfer Application Programming Interface — the API style used for structured data exchange within the System
- **Role**: A defined set of permissions assigned to a user account (Resident, Staff, or Administrator)
- **Session**: A server-side PHP session used to maintain authenticated user state
- **Status History**: A chronological record of all status changes for a given Request

---


## Requirements

### Requirement 1: User Registration

**User Story:** As a Resident, I want to register for an account, so that I can submit and track document requests online without visiting the municipal office in person.

#### Acceptance Criteria

1. THE System SHALL provide a public registration page accessible without authentication.
2. WHEN a visitor submits the registration form, THE System SHALL validate all required fields — first name, last name, email address, password, password confirmation, contact number, and home address — server-side before creating an account.
3. WHEN a visitor submits a registration form with an email address already associated with an existing account, THE System SHALL return a descriptive error message and SHALL NOT create a duplicate account.
4. WHEN a visitor submits a valid registration form, THE System SHALL hash the password using PHP's `password_hash()` function before storing it and SHALL NOT store the plain-text password anywhere in the database.
5. WHEN a new Resident account is successfully created, THE System SHALL assign the Resident role to the account automatically.
6. WHEN a new Resident account is successfully created, THE System SHALL send a registration confirmation email to the Resident's registered email address via the Mailtrap email service.
7. IF the Mailtrap email service is unavailable during registration, THEN THE System SHALL still complete the account creation and SHALL log the email failure in the email_logs table.
8. THE System SHALL validate that the password field contains at least 8 characters, at least one uppercase letter, at least one lowercase letter, and at least one number before accepting registration.
9. THE System SHALL validate that the contact number field contains a valid Philippine mobile or landline number format before accepting registration.
10. WHEN a visitor submits the registration form with a password confirmation that does not match the password field, THE System SHALL return a descriptive error message and SHALL NOT create an account.

---

### Requirement 2: User Authentication

**User Story:** As a registered user (Resident, Staff, or Administrator), I want to log in to the System securely, so that I can access functionality appropriate to my role.

#### Acceptance Criteria

1. THE System SHALL provide a login page accessible without authentication.
2. WHEN a user submits login credentials, THE System SHALL validate the email and password server-side using `password_verify()` against the stored hashed password.
3. WHEN login credentials are valid, THE System SHALL create a server-side PHP session and redirect the user to the dashboard appropriate for their assigned role.
4. WHEN login credentials are invalid, THE System SHALL return a generic error message that does not reveal whether the email or the password was incorrect, and SHALL NOT create a session.
5. WHEN a user account is inactive or deactivated, THE System SHALL reject login attempts for that account and SHALL return an appropriate error message.
6. WHEN a user successfully logs in, THE System SHALL record the login event in the audit_logs table including the user ID, IP address, and timestamp.
7. THE System SHALL implement CSRF token validation on all login form submissions.
8. WHEN a logged-in user requests logout, THE System SHALL destroy the PHP session, clear session cookies, and redirect the user to the public home page.
9. WHILE a user session is active, THE System SHALL enforce role-based access control on every page request, returning HTTP 403 if the authenticated user's role does not permit access to the requested resource.
10. WHEN a user session expires due to inactivity exceeding 30 minutes, THE System SHALL invalidate the session and redirect the user to the login page upon their next request.

---

### Requirement 3: Role-Based Access Control

**User Story:** As an Administrator, I want the System to enforce strict role boundaries, so that Residents cannot access staff or administrator functionality, and Staff cannot access administrator-only functionality.

#### Acceptance Criteria

1. THE System SHALL define exactly three roles: Resident, Staff, and Administrator, each stored in the roles table.
2. THE System SHALL enforce the following permission matrix on every server-side request:

   | Functionality                         | Resident | Staff | Administrator |
   |---------------------------------------|----------|-------|---------------|
   | Public pages (Home, About, etc.)      | ✓        | ✓     | ✓             |
   | Register / Login                      | ✓        | —     | —             |
   | Resident Dashboard                    | ✓        | —     | —             |
   | Submit Document Request               | ✓        | —     | —             |
   | View Own Requests                     | ✓        | —     | —             |
   | Track Own Request                     | ✓        | —     | —             |
   | Upload Supporting Documents           | ✓        | —     | —             |
   | Manage Own Profile                    | ✓        | ✓     | ✓             |
   | Receive Notifications                 | ✓        | ✓     | —             |
   | Staff Dashboard                       | —        | ✓     | —             |
   | View All Requests                     | —        | ✓     | ✓             |
   | Set Status: Under Verification        | —        | ✓     | —             |
   | Set Status: Additional Info Required  | —        | ✓     | —             |
   | Set Status: Approved                  | —        | ✓     | —             |
   | Set Status: Rejected                  | —        | ✓     | —             |
   | Set Status: Ready for Claiming        | —        | ✓     | —             |
   | Set Status: Completed                 | —        | ✓     | —             |
   | Admin Dashboard                       | —        | —     | ✓             |
   | Manage Users (Residents)              | —        | —     | ✓             |
   | Manage Staff Accounts                 | —        | —     | ✓             |
   | Manage Document Types                 | —        | —     | ✓             |
   | Manage Requirements per Document Type | —        | —     | ✓             |
   | View Reports                          | —        | —     | ✓             |
   | View Audit Logs                       | —        | —     | ✓             |
   | Manage System Settings                | —        | —     | ✓             |

3. WHEN an authenticated user attempts to access a resource not permitted by their role, THE System SHALL return HTTP 403 and SHALL display an access-denied page without exposing system internals.
4. THE System SHALL never rely solely on client-side checks for access control enforcement; all authorization checks SHALL be performed server-side on every request.
5. WHEN an unauthenticated user attempts to access any protected page, THE System SHALL redirect the user to the login page.

---

### Requirement 4: Resident Profile Management

**User Story:** As a Resident, I want to manage my profile information, so that my contact details and address remain current for communication and document processing.

#### Acceptance Criteria

1. WHILE authenticated as a Resident, THE System SHALL provide a profile management page where the Resident can update their first name, last name, contact number, and home address.
2. WHEN a Resident submits a profile update, THE System SHALL validate all fields server-side before applying the update.
3. WHEN a Resident requests a password change, THE System SHALL require the current password, a new password, and a new password confirmation before accepting the change.
4. WHEN a Resident submits a valid password change request, THE System SHALL hash the new password using `password_hash()` and update the stored hash, and SHALL NOT store the plain-text password.
5. WHEN a Resident submits a password change request with an incorrect current password, THE System SHALL reject the request and return a descriptive error message.
6. THE System SHALL NOT allow a Resident to change their email address through the profile management page without Administrator involvement.
7. WHEN a profile update is successfully saved, THE System SHALL record the update event in the audit_logs table.

---

### Requirement 5: Document Type Management

**User Story:** As an Administrator, I want to manage document types and their requirements in the database, so that new document types can be added or modified without changing application code.

#### Acceptance Criteria

1. THE System SHALL store all document types in the document_types table and SHALL NOT hard-code any document type in application source files.
2. WHILE authenticated as an Administrator, THE System SHALL provide a document type management interface where document types can be created, edited, activated, and deactivated.
3. WHEN an Administrator creates a document type, THE System SHALL require at minimum: a document type name, a description, and the standard processing fee amount.
4. WHEN an Administrator deactivates a document type, THE System SHALL prevent new Requests for that document type from being submitted, but SHALL NOT affect existing Requests already in progress.
5. WHEN an Administrator activates a previously deactivated document type, THE System SHALL make that document type available for selection by Residents on the request form.
6. THE System SHALL store all requirements for each document type in the document_requirements table, linked to the document type by foreign key.
7. WHILE authenticated as an Administrator, THE System SHALL allow adding, editing, activating, and deactivating individual requirements for each document type independently.
8. WHEN an Administrator deactivates a requirement, THE System SHALL remove it from the requirements checklist shown to Residents for new requests of that document type, but SHALL retain historical requirement records for existing requests.
9. THE System SHALL display only active document types to Residents on the request form.
10. WHEN an Administrator saves a document type change, THE System SHALL record the change in the audit_logs table, including the field changed, old value, and new value.

---

### Requirement 6: Document Request Submission

**User Story:** As a Resident, I want to submit a document request online with all required supporting documents, so that my request is formally received and tracked without requiring an in-person visit.

#### Acceptance Criteria

1. WHILE authenticated as a Resident, THE System SHALL provide a new request page listing all active document types available for selection.
2. WHEN a Resident selects a document type, THE System SHALL display the complete list of active requirements for that document type before the Resident proceeds to the request form.
3. THE System SHALL provide a request form collecting at minimum: purpose of request, number of copies requested, and any document-type-specific fields defined by the Administrator.
4. THE System SHALL allow the Resident to upload supporting documents as file attachments to the request, accepting only files with MIME types of application/pdf, image/jpeg, or image/png.
5. WHEN a file upload is submitted, THE System SHALL validate the MIME type server-side using PHP's `finfo` extension and SHALL reject files whose MIME type does not match the allowed list regardless of the file extension declared by the client.
6. WHEN a file upload is submitted, THE System SHALL enforce a maximum file size of 5 MB per file and SHALL reject files exceeding this limit with a descriptive error message.
7. WHEN a file is accepted, THE System SHALL generate a secure filename (not using the original client-provided filename) and store the file in a directory that is not directly accessible via the web server.
8. WHEN a Resident submits a completed request form, THE System SHALL validate all required fields server-side before creating the Request record.
9. WHEN a valid Request is submitted, THE System SHALL generate a unique Reference Number in the format EMU-YYYY-NNNNNN where YYYY is the four-digit submission year and NNNNNN is a zero-padded sequential number restarting at 000001 each calendar year.
10. WHEN a valid Request is submitted, THE System SHALL set the initial Request Status to PENDING and record the status change in the request_status_history table with a timestamp.
11. WHEN a valid Request is submitted, THE System SHALL send a request submission confirmation email to the Resident containing the Reference Number and a summary of the request via the Mailtrap email service.
12. WHEN a valid Request is submitted, THE System SHALL redirect the Resident to the request detail page showing the Reference Number and current status.
13. THE System SHALL implement CSRF token validation on all request submission form submissions.
14. THE System SHALL validate that the number of copies requested is a positive integer greater than zero.

---

### Requirement 7: Request Status Workflow

**User Story:** As a Staff member, I want to move requests through a defined status workflow, so that each request is processed consistently and Residents are kept informed at every stage.

#### Acceptance Criteria

1. THE System SHALL enforce the following Request Status definitions, transitions, and responsibilities:

   | Status                          | Meaning                                                          | Who Can Set     | Allowed Previous Statuses                   | Required Input               |
   |---------------------------------|------------------------------------------------------------------|-----------------|---------------------------------------------|------------------------------|
   | PENDING                         | Request submitted, awaiting staff review                        | System only     | (initial)                                   | None                         |
   | UNDER VERIFICATION              | Staff is actively reviewing the request and uploads             | Staff           | PENDING                                     | None                         |
   | ADDITIONAL INFORMATION REQUIRED | Staff needs more information or documents from the Resident     | Staff           | UNDER VERIFICATION                          | Remark (required)            |
   | APPROVED                        | Request verified and approved for processing                    | Staff           | UNDER VERIFICATION                          | None                         |
   | REJECTED                        | Request cannot be fulfilled                                     | Staff           | UNDER VERIFICATION, ADDITIONAL INFO REQUIRED | Rejection reason (required)  |
   | READY FOR CLAIMING              | Document is prepared and ready for the Resident to pick up      | Staff           | APPROVED                                    | None                         |
   | COMPLETED                       | Resident has claimed the document                               | Staff           | READY FOR CLAIMING                          | None                         |
   | CANCELLED                       | Request was cancelled                                           | Resident, Staff | PENDING, ADDITIONAL INFO REQUIRED           | Cancellation reason (optional)|

2. WHEN a Staff member attempts a status transition not permitted by the table above, THE System SHALL reject the action and return a descriptive error message without changing the status.
3. WHEN a Staff member sets a Request to REJECTED, THE System SHALL require a non-empty rejection reason before saving the status change.
4. WHEN a Staff member sets a Request to ADDITIONAL INFORMATION REQUIRED, THE System SHALL require a non-empty remark explaining what information is needed before saving the status change.
5. WHEN any status change occurs, THE System SHALL record the new status, the previous status, the acting user ID, the timestamp, and any accompanying remarks or reasons in the request_status_history table.
6. WHEN any status change occurs, THE System SHALL send the appropriate notification email to the Resident via the Mailtrap email service as defined in Requirement 10.
7. WHEN a Resident cancels a PENDING request, THE System SHALL set the status to CANCELLED and record the event in request_status_history.
8. WHILE a Request has a status of COMPLETED or REJECTED or CANCELLED, THE System SHALL prevent any further status changes to that Request.

---

### Requirement 8: Request Tracking and History

**User Story:** As a Resident, I want to track the current status of my requests and view their full history, so that I always know where my request stands in the process.

#### Acceptance Criteria

1. WHILE authenticated as a Resident, THE System SHALL provide a request list page showing all Requests submitted by that Resident, displaying at minimum: Reference Number, document type, submission date, and current status.
2. WHEN a Resident selects a Request from the list, THE System SHALL display a request detail page showing: Reference Number, document type, purpose, number of copies, current status, submission date, all uploaded files, and the complete status history in chronological order.
3. THE System SHALL display the status history with each entry showing: status label, timestamp, acting user role (not the personal name of staff), and any accompanying remark or rejection reason.
4. THE System SHALL provide a request tracking page accessible via the Reference Number that displays the current status and status history without requiring the Resident to navigate to the request list first.
5. THE System SHALL allow a Resident to search for their own requests by Reference Number from the resident dashboard.
6. WHEN a Request has the status ADDITIONAL INFORMATION REQUIRED, THE System SHALL display the staff remark prominently on the request detail page and SHALL provide the Resident with an upload interface to submit additional documents.
7. WHEN a Resident uploads additional documents in response to an ADDITIONAL INFORMATION REQUIRED status, THE System SHALL apply the same file validation rules defined in Requirement 6 (MIME type, size, secure filename, safe storage).
8. THE System SHALL never display requests belonging to other Residents to the requesting Resident regardless of any query parameter manipulation.

---

### Requirement 9: Staff Request Management

**User Story:** As a Staff member, I want to view and manage all submitted requests, so that I can process them efficiently and maintain up-to-date status information.

#### Acceptance Criteria

1. WHILE authenticated as a Staff member, THE System SHALL provide a request list page showing all Requests across all Residents, with filtering by status, document type, and date range.
2. THE System SHALL display the request list with at minimum: Reference Number, Resident name, document type, submission date, and current status.
3. WHEN a Staff member selects a Request, THE System SHALL display the full request detail including all uploaded files, status history, and Resident contact information.
4. WHILE authenticated as a Staff member, THE System SHALL allow downloading any uploaded supporting document file associated with a Request.
5. WHEN a Staff member views uploaded files, THE System SHALL serve files from the secure storage location through a PHP-controlled download handler and SHALL NOT expose the server file path in URLs.
6. WHEN a Staff member updates a Request status, THE System SHALL enforce all transition rules defined in Requirement 7.
7. THE System SHALL display a count of Requests in each status on the Staff dashboard to provide an at-a-glance workload overview.
8. WHEN a Staff member performs any status change action, THE System SHALL record the action in the audit_logs table including the Staff user ID, Request Reference Number, action taken, and timestamp.

---

### Requirement 10: Email Notification System

**User Story:** As a Resident, I want to receive email notifications when my request status changes, so that I can respond promptly and stay informed without checking the system constantly.

#### Acceptance Criteria

1. THE System SHALL send email notifications for the following events using the Mailtrap email service:

   | Event                           | Recipient  | Email Content Required                                                      |
   |---------------------------------|------------|-----------------------------------------------------------------------------|
   | Registration                    | Resident   | Welcome message, account details summary                                    |
   | Request Submitted               | Resident   | Reference Number, document type, submission date, next steps                |
   | Under Verification              | Resident   | Reference Number, status update, estimated processing note                  |
   | Additional Information Required | Resident   | Reference Number, staff remark explaining what is needed, upload link       |
   | Approved                        | Resident   | Reference Number, approval confirmation, next steps                         |
   | Rejected                        | Resident   | Reference Number, rejection reason                                          |
   | Ready for Claiming              | Resident   | Reference Number, document ready notice, office location and hours          |
   | Completed                       | Resident   | Reference Number, completion confirmation, thank-you message                |
   | Cancelled                       | Resident   | Reference Number, cancellation confirmation, and who initiated the cancellation |

2. THE System SHALL store Mailtrap SMTP credentials (host, port, username, password) exclusively in environment variables and SHALL NOT hard-code these values anywhere in source files or commit them to version control.
3. WHEN an email notification is sent, THE System SHALL log the event in the email_logs table, recording: recipient email, notification type, Request Reference Number (where applicable), send timestamp, and success or failure status.
4. IF an email send attempt fails, THEN THE System SHALL log the failure in email_logs and SHALL continue processing the status change without rolling back the status update.
5. THE System SHALL use a consistent, professionally formatted HTML email template appropriate for a government office context.
6. THE System SHALL include the Reference Number in the subject line of all request-related email notifications.
7. THE System SHALL never expose Mailtrap credentials in HTTP responses, logs visible to users, or client-side code.

---

### Requirement 11: Administrator User Management

**User Story:** As an Administrator, I want to manage Resident and Staff user accounts, so that I can onboard new staff, deactivate inactive accounts, and maintain an accurate user registry.

#### Acceptance Criteria

1. WHILE authenticated as an Administrator, THE System SHALL provide a user management interface listing all user accounts with their role, status (active/inactive), and registration date.
2. WHILE authenticated as an Administrator, THE System SHALL allow creating new Staff accounts by providing: first name, last name, email, temporary password, and assigned role (Staff).
3. WHEN an Administrator creates a new Staff account, THE System SHALL hash the temporary password using `password_hash()` before storing it and SHALL NOT store the plain-text password.
4. WHILE authenticated as an Administrator, THE System SHALL allow deactivating any Resident or Staff account, which prevents the account from logging in but retains all historical data.
5. WHILE authenticated as an Administrator, THE System SHALL allow reactivating a previously deactivated account.
6. THE System SHALL prevent an Administrator from deactivating their own account through the user management interface.
7. WHEN a user account is created, deactivated, or reactivated by an Administrator, THE System SHALL record the action in the audit_logs table including the acting Administrator's user ID and timestamp.
8. THE System SHALL allow an Administrator to search users by name or email address.
9. THE System SHALL prevent deletion of user accounts that have associated Requests in order to preserve historical data integrity; deactivation SHALL be used instead of deletion.
10. THE System SHALL ensure that at least one Administrator account always exists; the initial Administrator account SHALL be seeded directly in the database during system setup and SHALL NOT be deletable or deactivatable through the user management interface.

---

### Requirement 12: Admin Dashboard and Reports

**User Story:** As an Administrator, I want a dashboard showing key metrics and access to reports, so that I can monitor system activity and generate summaries for management.

#### Acceptance Criteria

1. WHILE authenticated as an Administrator, THE System SHALL display a dashboard showing at minimum: total requests by status, total registered Residents, total Staff accounts, and requests submitted in the current calendar month.
2. WHILE authenticated as an Administrator, THE System SHALL provide a reports section allowing generation of request summary reports filterable by date range, document type, and status.
3. WHEN an Administrator generates a report, THE System SHALL display the results in a paginated table within the browser.
4. THE System SHALL provide audit log viewing with filtering by user, action type, and date range.
5. THE System SHALL paginate all list views (users, requests, audit logs, reports) with a default page size of 20 records per page.
6. WHEN an Administrator accesses the audit log, THE System SHALL display each entry with: timestamp, acting user, action type, affected record identifier, and IP address.

---

### Requirement 13: Audit Logging

**User Story:** As an Administrator, I want every significant system action to be recorded in an audit log, so that there is a complete, tamper-evident trail of all activity for accountability and compliance.

#### Acceptance Criteria

1. THE System SHALL record an audit log entry in the audit_logs table for each of the following events: user login, user logout, failed login attempt, account creation, account deactivation, account reactivation, password change, document type created or modified, requirement created or modified, request submitted, request status changed, file uploaded, file downloaded, report generated, system setting changed.
2. EACH audit log entry SHALL contain at minimum: entry ID, timestamp, user ID of the acting user (or null for system-generated events), user role at time of action, action type, affected table name, affected record ID, IP address, and a JSON-serialized description of the change.
3. THE System SHALL NOT allow any user role to delete or modify audit log entries through the application interface.
4. WHEN an audit log entry is created, THE System SHALL write the entry directly to the database without passing through any user-editable code path that could suppress or alter the record.

---

### Requirement 14: Security Controls

**User Story:** As an Administrator, I want the System to apply comprehensive security controls, so that resident data and government records are protected from unauthorized access and common web vulnerabilities.

#### Acceptance Criteria

1. THE System SHALL use PHP's `password_hash()` with the PASSWORD_BCRYPT or PASSWORD_DEFAULT algorithm for all password storage and `password_verify()` for all password comparison operations.
2. THE System SHALL use PDO prepared statements with bound parameters for every database query and SHALL NOT concatenate user-supplied input directly into SQL strings.
3. THE System SHALL generate and validate a CSRF token on every HTML form submission and every state-changing API endpoint.
4. THE System SHALL apply output escaping using `htmlspecialchars()` or equivalent before rendering any user-supplied data in HTML responses to prevent XSS attacks.
5. THE System SHALL validate all file uploads server-side for MIME type using PHP's `finfo` extension, file size, and file extension, and SHALL store uploaded files outside the web-accessible document root.
6. THE System SHALL NOT accept or execute uploaded files with extensions associated with server-side executable code (e.g., .php, .phtml, .php3, .phar, .exe, .sh).
7. THE System SHALL sanitize all file names before storage, replacing the client-provided filename with a system-generated secure name to prevent path traversal attacks.
8. THE System SHALL store all credentials (database, Mailtrap, application secrets) in environment variables loaded at runtime and SHALL NOT hard-code any credential in source files.
9. THE System SHALL ensure the `.env` file containing environment variables is listed in `.gitignore` and is never committed to the version control repository.
10. WHEN a REST API endpoint receives a request, THE System SHALL validate the authentication token or session before processing the request and SHALL return HTTP 401 for unauthenticated requests and HTTP 403 for requests from authenticated users lacking sufficient permissions.
11. THE System SHALL set appropriate HTTP security headers on all responses, including: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, and `Content-Security-Policy` restricting script sources to the application's own origin.
12. THE System SHALL enforce a maximum of 5 consecutive failed login attempts per account within a 15-minute window, after which the System SHALL temporarily lock the account for 15 minutes and log the lockout event in the audit_logs table.

---

### Requirement 15: RESTful API

**User Story:** As a developer, I want the System to expose a structured RESTful API, so that frontend JavaScript components and any future integrations can interact with the backend in a consistent, predictable way.

#### Acceptance Criteria

1. THE System SHALL implement RESTful API endpoints using consistent URL patterns, HTTP methods, and JSON response structures as defined in the following API plan:

   **Authentication**
   | Method | Endpoint          | Auth Required | Description                      |
   |--------|-------------------|---------------|----------------------------------|
   | POST   | /api/auth/login   | No            | Authenticate and create session  |
   | POST   | /api/auth/logout  | Yes           | Destroy session                  |
   | POST   | /api/auth/register| No            | Register new Resident account    |

   **Users**
   | Method | Endpoint              | Auth Required | Role          | Description             |
   |--------|-----------------------|---------------|---------------|-------------------------|
   | GET    | /api/users            | Yes           | Admin         | List all users          |
   | GET    | /api/users/{id}       | Yes           | Admin, Self   | Get user by ID          |
   | PUT    | /api/users/{id}       | Yes           | Admin, Self   | Update user profile     |
   | PATCH  | /api/users/{id}/status| Yes           | Admin         | Activate/deactivate user|

   **Document Types**
   | Method | Endpoint                       | Auth Required | Role  | Description                        |
   |--------|--------------------------------|---------------|-------|------------------------------------|
   | GET    | /api/document-types            | Yes           | Any   | List active document types         |
   | GET    | /api/document-types/{id}       | Yes           | Any   | Get document type with requirements|
   | POST   | /api/document-types            | Yes           | Admin | Create document type               |
   | PUT    | /api/document-types/{id}       | Yes           | Admin | Update document type               |
   | PATCH  | /api/document-types/{id}/status| Yes           | Admin | Activate/deactivate document type  |

   **Requirements**
   | Method | Endpoint                           | Auth Required | Role  | Description                    |
   |--------|------------------------------------|---------------|-------|--------------------------------|
   | GET    | /api/document-types/{id}/requirements | Yes        | Any   | List requirements for a type   |
   | POST   | /api/document-types/{id}/requirements | Yes        | Admin | Add requirement to type        |
   | PUT    | /api/requirements/{id}             | Yes           | Admin | Update requirement             |
   | PATCH  | /api/requirements/{id}/status      | Yes           | Admin | Activate/deactivate requirement|

   **Requests**
   | Method | Endpoint                        | Auth Required | Role           | Description                    |
   |--------|---------------------------------|---------------|----------------|--------------------------------|
   | GET    | /api/requests                   | Yes           | Staff, Admin   | List all requests (filterable) |
   | GET    | /api/requests/my                | Yes           | Resident       | List own requests              |
   | GET    | /api/requests/{id}              | Yes           | Resident (own), Staff, Admin | Get request detail |
   | POST   | /api/requests                   | Yes           | Resident       | Submit new request             |
   | POST   | /api/requests/{id}/documents    | Yes           | Resident (own) | Upload document to request     |
   | PATCH  | /api/requests/{id}/cancel       | Yes           | Resident (own) | Cancel a PENDING or ADDITIONAL INFO REQUIRED request |

   **Request Status**
   | Method | Endpoint                        | Auth Required | Role  | Description            |
   |--------|---------------------------------|---------------|-------|------------------------|
   | PATCH  | /api/requests/{id}/status       | Yes           | Staff | Update request status  |
   | GET    | /api/requests/{id}/history      | Yes           | Resident (own), Staff, Admin | Get status history |

   **Notifications**
   | Method | Endpoint                        | Auth Required | Role     | Description                   |
   |--------|---------------------------------|---------------|----------|-------------------------------|
   | GET    | /api/notifications              | Yes           | Resident | List own notifications        |
   | PATCH  | /api/notifications/{id}/read    | Yes           | Resident | Mark notification as read     |

2. WHEN an API endpoint returns a successful response, THE System SHALL use the following JSON structure:
   ```json
   { "success": true, "data": { ... }, "message": "..." }
   ```
3. WHEN an API endpoint returns an error response, THE System SHALL use the following JSON structure:
   ```json
   { "success": false, "error": "...", "message": "..." }
   ```
4. THE System SHALL return appropriate HTTP status codes: 200 for success, 201 for resource creation, 400 for validation errors, 401 for unauthenticated requests, 403 for forbidden requests, 404 for not-found resources, 422 for unprocessable entities, and 500 for server errors.
5. THE System SHALL validate and sanitize all input received through API endpoints server-side before processing, regardless of any client-side validation in place.
6. WHEN an API endpoint receives a file upload, THE System SHALL apply the same MIME type, file size, and filename security rules defined in Requirement 14.

---

### Requirement 16: In-System Notifications

**User Story:** As a Resident, I want to receive in-system notifications when my request status changes, so that I am informed even if I do not check my email.

#### Acceptance Criteria

1. THE System SHALL create a notification record in the notifications table for each status change event affecting a Resident's Request.
2. WHILE authenticated as a Resident, THE System SHALL display unread notification count on the navigation bar.
3. WHEN a Resident views the notifications page, THE System SHALL display all notifications in reverse chronological order, distinguishing read from unread notifications visually.
4. WHEN a Resident clicks on a notification, THE System SHALL mark the notification as read and navigate the Resident to the relevant request detail page.
5. THE System SHALL store each notification with: notification ID, recipient user ID, Request Reference Number, notification type, message body, read status, and creation timestamp.

---

### Requirement 17: Database Design

**User Story:** As a developer, I want the database to be properly normalized and structured, so that data integrity is maintained and queries are efficient throughout the system lifecycle.

#### Acceptance Criteria

1. THE System's database SHALL include at minimum the following tables with the described purpose:

   | Table                   | Purpose                                                                 |
   |-------------------------|-------------------------------------------------------------------------|
   | roles                   | Stores the three user roles and their labels                            |
   | users                   | Stores all user accounts with role FK, hashed password, and status      |
   | document_types          | Stores document type definitions with active/inactive status            |
   | document_requirements   | Stores per-document-type requirements with active/inactive status       |
   | requests                | Stores each Request with FK to user and document type, reference number |
   | request_documents       | Stores uploaded file metadata (not file contents) linked to requests    |
   | request_status_history  | Stores every status change event with timestamps and remarks            |
   | notifications           | Stores in-system notification records per user                          |
   | email_logs              | Stores outbound email attempts with status and type                     |
   | audit_logs              | Stores all significant system events with acting user and timestamp     |
   | system_settings         | Stores key-value system configuration pairs managed by Administrator    |

2. THE System SHALL enforce referential integrity through foreign key constraints on all relationship columns.
3. THE System SHALL index the following columns at minimum for query performance: users.email, requests.reference_number, requests.user_id, requests.document_type_id, request_status_history.request_id, audit_logs.user_id, audit_logs.created_at.
4. THE System SHALL use InnoDB as the MySQL storage engine for all tables to support foreign key constraints and transactions.
5. WHEN a Request record is created, THE System SHALL record it within a database transaction that also creates the initial request_status_history entry, so that no Request can exist without a status history record.
6. THE System SHALL use appropriate MySQL data types: VARCHAR for short strings, TEXT for long strings, TINYINT(1) for boolean flags, DECIMAL for monetary amounts, DATETIME for timestamps, and INT UNSIGNED AUTO_INCREMENT for primary keys.

---

### Requirement 18: File Upload Management

**User Story:** As a Resident, I want to upload supporting documents securely, so that my required files are received by the System without exposing the server to malicious uploads.

#### Acceptance Criteria

1. THE System SHALL accept uploaded files only with the following MIME types: `application/pdf`, `image/jpeg`, `image/png`.
2. THE System SHALL validate uploaded file MIME types using PHP's `finfo_file()` function server-side and SHALL NOT rely on the Content-Type header supplied by the client or the file extension alone.
3. THE System SHALL enforce a maximum upload size of 5 MB per file and SHALL return a descriptive error for files exceeding this limit.
4. THE System SHALL generate a UUID or equivalent cryptographically random identifier as the stored filename for every uploaded file, appending the validated file extension, and SHALL store this generated filename in the request_documents table alongside the original filename for display purposes.
5. THE System SHALL store uploaded files in a directory outside the web server document root, accessible only through the PHP file download handler.
6. THE System SHALL reject any uploaded file whose MIME type corresponds to server-side executable content, including but not limited to: `application/x-php`, `application/x-httpd-php`, `application/x-sh`, `application/x-executable`.
7. WHEN a file is successfully uploaded, THE System SHALL create a record in the request_documents table containing: document ID, request ID, original filename, stored filename, MIME type, file size in bytes, upload timestamp, and uploader user ID.
8. WHEN a Staff member or Resident requests to download an uploaded file, THE System SHALL verify that the requesting user has permission to access the file before serving it through the PHP download handler with appropriate Content-Disposition headers.

---

### Requirement 19: UI/UX and Frontend

**User Story:** As a Resident or Staff member, I want a clean, professional, and responsive interface, so that the System is easy to use on any device and conveys the credibility expected of a government system.

#### Acceptance Criteria

1. THE System SHALL use Bootstrap 5 as the CSS framework for all pages and SHALL NOT introduce a conflicting CSS framework.
2. THE System SHALL implement a responsive layout that is usable on screens with a minimum viewport width of 320px.
3. THE System SHALL use a professional, government-appropriate color palette and typography, avoiding informal or consumer-oriented visual styles.
4. THE System SHALL include the following public-facing pages accessible without authentication: Home, About, Services, How It Works, FAQs, Contact, Login, Registration.
5. THE System SHALL include the following Resident-authenticated pages: Dashboard, New Request, My Requests, Request Details, Request Tracking, Notifications, Profile, Settings.
6. THE System SHALL include the following Staff-authenticated pages: Dashboard, Request List, Request Details, Verification, Approval, Rejection, Additional Information, Ready for Claiming, Completed Requests, Profile.
7. THE System SHALL include the following Administrator-authenticated pages: Dashboard, User Management, Resident Management, Staff Management, Document Types, Requirements, Reports, Audit Logs, System Settings.
8. THE System SHALL use JavaScript for client-side interactivity including form validation feedback, dynamic status updates, and file upload progress indicators, but SHALL NOT depend on JavaScript alone for security or access control.
9. THE System SHALL display clear, actionable error messages adjacent to invalid form fields rather than generic top-level error notices.
10. THE System SHALL display status labels using consistent color coding: PENDING in gray, UNDER VERIFICATION in blue, ADDITIONAL INFO REQUIRED in orange, APPROVED in green, REJECTED in red, READY FOR CLAIMING in teal, COMPLETED in dark green, CANCELLED in dark gray.
11. THE System SHALL include accessible HTML markup including ARIA labels on interactive elements, adequate color contrast ratios (minimum 4.5:1 for normal text per WCAG 2.1 AA), and keyboard navigability for all interactive components.

---

### Requirement 20: System Settings Management

**User Story:** As an Administrator, I want to manage configurable system settings through the admin interface, so that operational parameters can be adjusted without code changes.

#### Acceptance Criteria

1. WHILE authenticated as an Administrator, THE System SHALL provide a system settings page where configurable values can be viewed and updated.
2. THE System SHALL store the following settings in the system_settings table at minimum: office name, office address, office contact number, office email address, maximum file upload size (in MB), session timeout duration (in minutes), and requests per page for list views.
3. WHEN an Administrator updates a system setting, THE System SHALL validate the new value against the expected data type and range for that setting before saving.
4. WHEN an Administrator saves a system setting change, THE System SHALL record the change in the audit_logs table with the old and new values.
5. THE System SHALL apply the session timeout duration setting from the system_settings table as the inactivity threshold for all authenticated sessions.
6. WHEN a database transaction fails during a multi-step operation (e.g., request submission, status change), THE System SHALL roll back all changes within that transaction, return a descriptive error message to the user, and log the failure in the audit_logs table without exposing internal error details.

---

### Requirement 21: Reference Number Generation

**User Story:** As a Resident and Staff member, I want every request to have a unique, human-readable reference number, so that requests can be easily identified, communicated, and searched across all channels.

#### Acceptance Criteria

1. WHEN a new Request is successfully submitted, THE System SHALL generate a Reference Number in the format `EMU-YYYY-NNNNNN` where `YYYY` is the four-digit year of submission and `NNNNNN` is a zero-padded six-digit sequential counter.
2. THE System SHALL reset the sequential counter to `000001` at the start of each calendar year.
3. THE System SHALL guarantee the uniqueness of each Reference Number by generating the number within a database transaction using a locking mechanism that prevents duplicate generation under concurrent submissions.
4. THE System SHALL store the Reference Number in the requests table as a unique-indexed VARCHAR column.
5. THE System SHALL include the Reference Number in all email notifications, in-system notifications, and UI displays related to the Request.
6. THE System SHALL allow searching for a Request by its full Reference Number from both the Staff request list and the Resident tracking page.

---

### Requirement 22: Code Organization and Development Standards

**User Story:** As a developer, I want the codebase to follow consistent organization and development standards, so that the application is maintainable, extensible, and reviewable across development phases.

#### Acceptance Criteria

1. THE System SHALL be built as a monolithic PHP application where all components — routing, business logic, data access, and templating — are deployed as a single application.
2. THE System SHALL NOT use a microservices architecture or require separate service deployments.
3. THE System SHALL separate business logic from presentation by organizing code into distinct layers: routing, controllers, models/services, and view templates, rather than placing all logic in a single PHP file.
4. THE System SHALL use PHP PDO for all database interactions and SHALL NOT use deprecated `mysql_*` functions or direct `mysqli_*` procedural calls without an abstraction layer.
5. THE System SHALL define reusable components (navigation bars, form elements, page layouts) as PHP include files or template partials to avoid duplication across pages.
6. THE System SHALL load all environment variables from a `.env` file using a PHP dotenv library or equivalent, and the `.env` file SHALL be listed in `.gitignore`.
7. THE System SHALL follow an incremental development approach across phases in this order: Phase 0 (Planning), Phase 1 (Database and Core Setup), Phase 2 (Authentication), Phase 3 (Document and Request Management), Phase 4 (Staff Workflow), Phase 5 (Admin Management), Phase 6 (Notifications and Email), Phase 7 (API Layer), Phase 8 (Reporting and Audit), Phase 9 (UI Polish and Testing).
8. THE System SHALL NOT introduce features or modules not specified in this requirements document without prior documentation and review.

---

### Requirement 23: Non-Functional Requirements

**User Story:** As a system stakeholder, I want the System to meet baseline performance, reliability, and maintainability standards, so that it is fit for use in a live government office environment.

#### Acceptance Criteria

1. WHEN a page request is made by an authenticated user under normal load (up to 50 concurrent users), THE System SHALL return a complete HTML response within 3 seconds.
2. WHEN an API endpoint is called under normal load, THE System SHALL return a JSON response within 2 seconds.
3. THE System SHALL be designed to run on an XAMPP development environment and SHALL be deployable to a standard LAMP stack (Linux, Apache, MySQL, PHP) production server without architectural changes.
4. THE System SHALL use MySQL with InnoDB tables to ensure ACID-compliant transactions for all multi-step database operations.
5. THE System SHALL maintain 100% of historical request data — no request records, status history entries, or audit logs SHALL be permanently deleted through any user-facing interface.
6. THE System SHALL be designed such that a new document type can be added by an Administrator through the UI without any code changes.
7. THE System's codebase SHALL be organized such that a developer familiar with PHP and MVC patterns can locate the relevant controller, model, and view for any given feature within 5 minutes of reading the directory structure.

---

## Recommended Implementation Order

The following incremental phase sequence is recommended for implementation after Phase 0 planning is complete:

1. **Phase 1 — Database and Environment Setup**: Create MySQL schema with all tables, foreign keys, indexes. Set up `.env` configuration. Initialize Git repository with `.gitignore`.
2. **Phase 2 — Authentication Module**: Registration, login, logout, session management, password hashing, CSRF protection, role-based middleware.
3. **Phase 3 — Document and Request Foundation**: Document type management (Admin), requirement management (Admin), request submission (Resident), reference number generation, file upload with security validation.
4. **Phase 4 — Staff Workflow**: Request list, request detail view, status transitions, rejection with reason, additional info with remark, file download handler, staff notifications.
5. **Phase 5 — Admin Management**: User management, Staff account creation, account activation/deactivation, audit log viewer, system settings.
6. **Phase 6 — Notifications and Email**: In-system notification records, notification UI, Mailtrap integration, email templates for all events, email_logs.
7. **Phase 7 — RESTful API Layer**: Implement all API endpoints defined in Requirement 15 with authentication, authorization, validation, and consistent JSON responses.
8. **Phase 8 — Reporting and Audit**: Admin dashboard metrics, reports with filters, audit log pagination and search.
9. **Phase 9 — UI Polish, Security Hardening, and Testing**: Final Bootstrap 5 styling, accessibility review, security header implementation, account lockout, end-to-end testing of all workflows.

---

### Requirement 24: Staff/Admin Request Search and Tracking

**User Story:** As a Staff member or Administrator, I want to search for requests using multiple resident identifiers, so that I can quickly locate a specific request when a resident visits the office in person.

#### Acceptance Criteria

1. WHILE authenticated as a Staff member, THE System SHALL provide a request search interface on the Staff request list page that searches across: EMU Reference Number (exact or partial), Resident full name (partial), Resident email (partial), and Resident mobile/contact number (partial).
2. WHILE authenticated as an Administrator, THE System SHALL provide the same request search capability on the Admin request list page.
3. THE System SHALL allow combining a text search with status, document type, and date-range filters simultaneously.
4. THE System SHALL display a document type filter dropdown on both the Staff and Admin request list pages, populated from the active document types in the database.
5. THE System SHALL display date-from and date-to filter inputs on both the Staff and Admin request list pages.
6. WHEN search and filter parameters are active and the user navigates between pages, THE System SHALL preserve all active search and filter parameters in the pagination URLs.
7. THE System SHALL use parameterized SQL queries for all search operations and SHALL NOT concatenate user input into SQL strings.
8. THE System SHALL search against the existing requests table joined to users and document_types — no separate tracking table SHALL be created.

---

### Requirement 25: Staff/Admin In-System Notifications

**User Story:** As a Staff member or Administrator, I want to receive in-system notifications when residents submit new requests or cancel existing ones, so that I can respond promptly without needing to refresh the request list.

#### Acceptance Criteria

1. WHEN a Resident successfully submits a new document request, THE System SHALL create one notification record in the notifications table for each active Staff user and each active Admin user.
2. WHEN a Resident cancels a request, THE System SHALL create one notification record in the notifications table for each active Staff user and each active Admin user.
3. EACH Staff/Admin notification SHALL contain: recipient user ID, request ID, EMU Reference Number, notification type, title, message identifying the resident and document type, unread status (is_read = 0), and creation timestamp.
4. WHILE authenticated as a Staff member, THE System SHALL display a Notifications page showing all notifications for that Staff user in reverse chronological order, distinguishing read from unread.
5. WHILE authenticated as an Administrator, THE System SHALL display a Notifications page showing all notifications for that Admin user in reverse chronological order, distinguishing read from unread.
6. THE System SHALL display an unread notification count badge on the Notifications navigation item for authenticated Staff and Admin users.
7. WHEN a Staff or Admin user clicks "View Request" on a notification, THE System SHALL navigate to the corresponding request detail page appropriate for their role (/staff/requests/{id} for Staff, /admin/requests/{id} for Admin).
8. WHEN a Staff or Admin user marks a notification as read, THE System SHALL update only notifications owned by that authenticated user using the session user ID, never a user ID from request input.
9. THE System SHALL provide a "Mark All as Read" action for Staff and Admin users that marks only the authenticated user's own notifications.
10. A Staff user SHALL only be able to view and manage their own notifications; notifications belonging to Admin users or other Staff users SHALL NOT be accessible.
11. An Admin user SHALL only be able to view and manage their own notifications; notifications belonging to Staff users or other Admin users SHALL NOT be accessible.
12. Residents SHALL NOT be able to access Staff or Admin notification routes.
13. THE Staff/Admin in-system notification system SHALL operate independently of the Resident email notification system; a failure in notification creation SHALL NOT affect request submission or cancellation.
14. IF an "Additional Information Submitted" event exists in the resident upload flow, THEN THE System SHALL also create Staff/Admin notifications for that event; until that upload flow is implemented, this notification type remains pending.
15. THE System SHALL reuse the existing notifications table for Staff/Admin notifications — no separate notification table SHALL be created.
