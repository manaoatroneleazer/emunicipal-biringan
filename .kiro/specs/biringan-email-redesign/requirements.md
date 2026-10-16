# Requirements Document

## Introduction

This requirements document specifies the visual redesign of all HTML email templates in the eMunicipal system to match the Biringan City design system. The redesign applies to 10 email templates (9 resident notification emails and 1 contact form owner email) while preserving all existing functionality, SMTP configuration, notification triggers, email logging, and data structures.

The Biringan City design system features deep purple, dark burgundy, rose/pink, gold, and cream colors, white content cards, elegant modern typography, and subtle Philippine folklore-inspired visual character appropriate for a professional civic-service portal.

This is a visual-only redesign. No changes will be made to EmailService logic, NotificationService, Mailtrap integration, database schema, request workflow, authentication, authorization, or any other system functionality beyond email template presentation.

## Glossary

- **Email_Template_System**: The layout.php file and inline HTML generation in EmailService that produces HTML email output
- **EmailService**: The PHP class (app/Services/EmailService.php) responsible for sending emails via PHPMailer
- **Resident**: A user with the resident role who submits document requests
- **Staff**: A user with the staff role who processes document requests
- **Administrator**: A user with the administrator role who manages system configuration
- **Request**: A document request submitted by a Resident
- **Reference_Number**: A unique identifier assigned to each Request (format: YYYYMMDD-NNNN)
- **Notification**: An email message sent to a user when specific system events occur
- **Email_Layout_Template**: The shared HTML structure file (views/emails/layout.php) that wraps email body content
- **Email_Body_Content**: The HTML string generated in EmailService methods and injected into Email_Layout_Template
- **Biringan_Color_Palette**: The color scheme defined in app.css CSS variables (deep purple #4A1F46, dark burgundy #2B173D, rose #D85C86, gold #D8B06A, gold-light #F0D9A0, cream #F8F6F8)
- **Email_Client**: Software used to read email (Gmail, Outlook, Apple Mail, Yahoo Mail, mobile clients)
- **Mobile_Viewport**: Screen width between 320px and 767px
- **Desktop_Viewport**: Screen width 768px and above
- **Status_Badge**: A visual indicator displaying the current status of a Request
- **CTA_Button**: Call-to-action button that links to portal pages (track request, upload documents, etc.)
- **Request_Info_Card**: A styled container displaying Request details (reference number, document type, date, status)
- **Remark_Box**: A highlighted container displaying Staff remarks or rejection reasons
- **Email_Header**: The top section of the email displaying eMunicipal and Biringan City branding
- **Email_Footer**: The bottom section of the email containing disclaimers and contact information
- **Academic_Disclaimer**: A notice stating that Biringan City is fictional and created for academic demonstration
- **Mailtrap**: The SMTP email service used for development and testing
- **Email_Logs_Table**: The database table (email_logs) that records all email send attempts
- **PHPMailer**: The PHP library used to send SMTP emails (version 6.12.0)
- **Inline_CSS**: CSS styles written directly in HTML style attributes (required for email client compatibility)
- **Table_Based_Layout**: HTML layout structure using &lt;table&gt; elements (required for email client compatibility)

---

## Requirements

### Requirement 1: Biringan City Email Design System

**User Story:** As a Resident, I want to receive emails that visually match the Biringan City portal design, so that I recognize the emails as consistent communications from the fictional eMunicipal academic demonstration system and experience a consistent brand identity.

#### Acceptance Criteria

1. THE Email_Template_System SHALL use the Biringan_Color_Palette as the primary color scheme with deep purple (#4A1F46) for primary brand elements, dark burgundy (#2B173D) for darker accents, rose (#D85C86) for interactive elements, gold (#D8B06A) for highlights, gold-light (#F0D9A0) for subtle accents, and cream (#F8F6F8) for light backgrounds.

2. THE Email_Template_System SHALL implement a consistent Email_Header displaying the eMunicipal wordmark, Biringan City Administration name, and visual branding using Biringan_Color_Palette colors.

3. THE Email_Template_System SHALL implement a consistent Email_Footer containing the text "This is an automated message from the eMunicipal Online Document Request System", the organization name "Biringan City Administration", a link to the portal homepage, and the Academic_Disclaimer text "Biringan City and all organizations, services, records, and characters presented are fictional and created for academic demonstration".

4. THE Email_Template_System SHALL use a white background (#FFFFFF) for the main email content card container with the cream color (#F8F6F8) as the outer page background.

5. THE Email_Template_System SHALL use web-safe font stacks for email client compatibility: 'Arial, Helvetica, sans-serif' for body text and 'Arial, Helvetica, sans-serif' for headings, ensuring reliable rendering across all email clients without requiring external web font loading.

6. THE Email_Template_System SHALL use a maximum content width of 600 pixels to ensure readability across Email_Clients.

7. THE Email_Template_System SHALL apply border-radius values of 6-12 pixels to create rounded corners on cards, buttons, and containers matching the Biringan City design aesthetic.

8. THE Email_Template_System SHALL use box-shadows with purple-tinted rgba values (e.g., rgba(74,31,70,0.10)) to create depth and elevation effects consistent with the Biringan City design system.

---

### Requirement 2: Email Client Compatibility and Responsiveness

**User Story:** As a Resident, I want to receive properly formatted emails that display correctly on my device and email application, so that I can read and interact with email content regardless of how I access my email.

#### Acceptance Criteria

1. THE Email_Template_System SHALL use Table_Based_Layout for all structural elements to ensure compatibility with Email_Clients that have limited CSS support.

2. THE Email_Template_System SHALL use Inline_CSS for all styling to ensure styles are applied correctly across all Email_Clients.

3. THE Email_Template_System SHALL implement responsive design using CSS media queries with a breakpoint at 600 pixels to optimize layout for Mobile_Viewport and Desktop_Viewport.

4. WHEN rendered in a Mobile_Viewport, THE Email_Template_System SHALL adjust font sizes, padding, and spacing to maintain readability on small screens.

5. THE Email_Template_System SHALL use explicit color values in both light and dark color schemes to prevent text visibility issues in Email_Clients with dark mode enabled.

6. THE Email_Template_System SHALL avoid using external resources (external CSS files, web fonts, JavaScript) that are commonly blocked by Email_Clients.

7. THE Email_Template_System SHALL use web-safe font stacks that fall back to system fonts when primary fonts are unavailable.

8. THE Email_Template_System SHALL use HTML entities for special characters where necessary (e.g., &#8369; for peso sign) to ensure proper rendering across Email_Clients.

9. THE Email_Template_System SHALL verify email-safe HTML and CSS practices. Testing in external Email_Clients (Gmail, Outlook, Apple Mail, Yahoo Mail) is recommended when available but not required.

---

### Requirement 3: Registration Welcome Email Redesign

**User Story:** As a new Resident, I want to receive a welcoming and professionally designed confirmation email after registering, so that I feel confident my account was created successfully and understand how to access the portal.

#### Acceptance Criteria

1. WHEN a Resident completes registration, THE Email_Template_System SHALL send a registration welcome email containing: a welcoming headline, the Resident's full name, a confirmation message stating the account was successfully created, a CTA_Button linking to the login page with the text "Sign In to eMunicipal", and a security notice stating "If you did not create this account, please contact the Biringan City Administration office immediately".

2. THE Email_Template_System SHALL use the subject line format "Welcome to eMunicipal — Your Account Has Been Created" for registration emails.

3. THE Email_Template_System SHALL style the registration email using Biringan_Color_Palette with a welcoming visual hierarchy emphasizing the welcome message.

4. THE Email_Template_System SHALL include the CTA_Button styled with the deep purple background (#4A1F46), white text, rounded corners, and adequate padding for touch targets on mobile devices.

---

### Requirement 4: Request Submitted Email Redesign

**User Story:** As a Resident, I want to receive a clear and informative confirmation email immediately after submitting a document request, so that I have a record of my submission and know how to track my request.

#### Acceptance Criteria

1. WHEN a Resident submits a Request, THE Email_Template_System SHALL send a request submitted email containing: the Reference_Number prominently displayed, the document type name, the submission date formatted as "Month DD, YYYY at HH:MM AM/PM", the current status "Pending", a message explaining next steps, and a CTA_Button linking to the request tracking page.

2. THE Email_Template_System SHALL use the subject line format "[REFERENCE_NUMBER] Your Document Request Has Been Submitted" for request submitted emails.

3. THE Email_Template_System SHALL display the Reference_Number in a Request_Info_Card with a label "Reference Number" styled with Biringan_Color_Palette colors using a cream or light purple background (#EEF2F7), a deep purple left border (4px solid #4A1F46), monospace font for the reference number, and increased font weight for emphasis.

4. THE Email_Template_System SHALL display request details (Document Type, Date Submitted, Status) in a table format with alternating row styling for readability.

5. THE Email_Template_System SHALL style the status value "Pending" with a Status_Badge using a neutral background color (#E5E7EB) and dark text color (#374151).

---

### Requirement 5: Under Verification Email Redesign

**User Story:** As a Resident, I want to receive a reassuring status update email when my request enters verification, so that I know Staff are actively reviewing my request and I understand the next steps.

#### Acceptance Criteria

1. WHEN a Staff member changes a Request status to "under_verification", THE Email_Template_System SHALL send an under verification email containing: the Reference_Number in a Request_Info_Card, the document type name, a message stating "Our staff is now reviewing your request", an estimated processing note, and a CTA_Button linking to the request tracking page.

2. THE Email_Template_System SHALL use the subject line format "[REFERENCE_NUMBER] Your Request Is Under Verification" for under verification emails.

3. THE Email_Template_System SHALL style the Status_Badge for "Under Verification" status with a blue background (#DBEAFE) and blue text (#1D4ED8).

4. THE Email_Template_System SHALL use a calm and reassuring tone in the message content to reduce user anxiety about processing times.

---

### Requirement 6: Additional Information Required Email Redesign

**User Story:** As a Resident, I want to receive a clear and actionable email when Staff request additional information, so that I understand exactly what is needed and can quickly provide the required documents.

#### Acceptance Criteria

1. WHEN a Staff member changes a Request status to "additional_info_required" and provides a remark, THE Email_Template_System SHALL send an additional information required email containing: the Reference_Number in a Request_Info_Card, the document type name, the Staff remark displayed in a Remark_Box, a clear instruction to upload requested documents, and a CTA_Button linking to the resident requests page with the text "Upload Documents".

2. THE Email_Template_System SHALL use the subject line format "[REFERENCE_NUMBER] Additional Information Required for Your Request" for additional information required emails.

3. THE Email_Template_System SHALL display the Staff remark in a Remark_Box styled with a yellow/amber background (#FEF3C7), an amber border (1px solid #F59E0B), rounded corners, adequate padding, and a bold label "Staff Note:" above the remark text.

4. THE Email_Template_System SHALL style the Status_Badge for "Additional Info Required" status with an amber background (#FEF3C7) and dark amber text (#92400E).

5. THE Email_Template_System SHALL emphasize the CTA_Button with high visual prominence to encourage immediate action.

---

### Requirement 7: Approved Email Redesign

**User Story:** As a Resident, I want to receive a celebratory and clear confirmation email when my request is approved, so that I feel positive about the outcome and understand the next steps in the process.

#### Acceptance Criteria

1. WHEN a Staff member changes a Request status to "approved", THE Email_Template_System SHALL send an approved email containing: a positive headline "Request Approved", the Reference_Number in a Request_Info_Card, the document type name, a congratulatory message, a statement that the document is being prepared, a notice that the Resident will be notified when the document is ready for claiming, and a CTA_Button linking to the request tracking page.

2. THE Email_Template_System SHALL use the subject line format "[REFERENCE_NUMBER] Your Request Has Been Approved" for approved emails.

3. THE Email_Template_System SHALL style the Status_Badge for "Approved" status with a green background (#D1FAE5) and dark green text (#065F46).

4. THE Email_Template_System SHALL use positive and encouraging language in the message content to create a satisfying user experience.

---

### Requirement 8: Rejected Email Redesign

**User Story:** As a Resident, I want to receive a clear and respectful email when my request is rejected, so that I understand the reason for rejection and know what options I have to resolve the issue.

#### Acceptance Criteria

1. WHEN a Staff member changes a Request status to "rejected" and provides a remark, THE Email_Template_System SHALL send a rejected email containing: a clear headline "Request Rejected", the Reference_Number in a Request_Info_Card, the document type name, the rejection reason displayed in a Remark_Box, guidance on next steps (contacting the office or submitting a new request with correct documents), and a CTA_Button linking to the request details page.

2. THE Email_Template_System SHALL use the subject line format "[REFERENCE_NUMBER] Your Request Has Been Rejected" for rejected emails.

3. THE Email_Template_System SHALL display the rejection reason in a Remark_Box styled with a red/pink background (#FEE2E2), a red border (1px solid #B91C1C), rounded corners, adequate padding, and a bold label "Rejection Reason:" above the reason text.

4. THE Email_Template_System SHALL style the Status_Badge for "Rejected" status with a red background (#FEE2E2) and dark red text (#991B1B).

5. THE Email_Template_System SHALL use respectful and empathetic language in the message content to maintain a positive relationship with the Resident despite the rejection.

---

### Requirement 9: Ready for Claiming Email Redesign

**User Story:** As a Resident, I want to receive a complete and actionable email when my document is ready for pick-up, so that I have all the information I need to claim my document at the municipal office.

#### Acceptance Criteria

1. WHEN a Staff member changes a Request status to "ready_for_claiming", THE Email_Template_System SHALL send a ready for claiming email containing: a headline "Document Ready for Claiming", the Reference_Number in a Request_Info_Card, the document type name, a confirmation that the document is ready for pick-up, office location details (office name, address, office hours), an instruction to bring valid community identification, and optionally a CTA_Button linking to office location or contact information.

2. THE Email_Template_System SHALL use the subject line format "[REFERENCE_NUMBER] Your Document Is Ready for Claiming" for ready for claiming emails.

3. THE Email_Template_System SHALL display office information in a table format with clear labels (Office, Address, Office Hours) and styled with adequate spacing and visual hierarchy.

4. THE Email_Template_System SHALL style the Status_Badge for "Ready for Claiming" status with a teal/cyan background (#CCFBF1) and dark teal text (#0F766E).

5. THE Email_Template_System SHALL include the office hours "Monday–Friday, 8:00 AM – 5:00 PM" and office address "Biringan City Civic Center, Central District, Biringan City" as specified in the existing email content.

---

### Requirement 10: Completed Email Redesign

**User Story:** As a Resident, I want to receive a closing confirmation email when my request is marked as completed, so that I have final confirmation that the transaction is finished and I feel appreciated as a user of the system.

#### Acceptance Criteria

1. WHEN a Staff member changes a Request status to "completed", THE Email_Template_System SHALL send a completed email containing: a headline "Request Completed", the Reference_Number in a Request_Info_Card, the document type name, a thank-you message, an expression of appreciation for using the eMunicipal system, and a CTA_Button linking to the portal homepage with the text "Back to eMunicipal".

2. THE Email_Template_System SHALL use the subject line format "[REFERENCE_NUMBER] Your Request Has Been Completed" for completed emails.

3. THE Email_Template_System SHALL style the Status_Badge for "Completed" status with a bright green background (#BBF7D0) and very dark green text (#14532D).

4. THE Email_Template_System SHALL use warm and appreciative language in the message content to create a positive closing experience.

---

### Requirement 11: Cancelled Email Redesign

**User Story:** As a Resident, I want to receive a clear confirmation email when a request is cancelled, so that I understand who initiated the cancellation, why it was cancelled, and what options I have to submit a new request if needed.

#### Acceptance Criteria

1. WHEN a Request status is changed to "cancelled", THE Email_Template_System SHALL send a cancelled email containing: a headline "Request Cancelled", the Reference_Number in a Request_Info_Card, the document type name, a statement indicating who cancelled the request (Resident or Staff), optionally the cancellation reason in a Remark_Box if a remark was provided, a message stating the Resident may submit a new request at any time, and a CTA_Button linking to the new request page with the text "Submit New Request".

2. THE Email_Template_System SHALL use the subject line format "[REFERENCE_NUMBER] Your Request Has Been Cancelled" for cancelled emails.

3. IF a cancellation remark was provided, THEN THE Email_Template_System SHALL display it in a Remark_Box styled with a neutral yellow background (#FEF3C7), an amber border (1px solid #F59E0B), rounded corners, adequate padding, and a bold label "Reason:" above the remark text.

4. THE Email_Template_System SHALL style the Status_Badge for "Cancelled" status with a gray background (#D1D5DB) and dark gray text (#1F2937).

5. THE Email_Template_System SHALL use neutral and non-judgmental language in the message content regardless of who initiated the cancellation.

---

### Requirement 12: Contact Form Owner Email Redesign

**User Story:** As the website Administrator, I want to receive contact form submissions in a well-formatted email, so that I can easily read the visitor's message and reply directly to their email address.

#### Acceptance Criteria

1. WHEN a visitor submits the contact form on the public website, THE Email_Template_System SHALL send a contact form email to the configured owner email address containing: the subject line "[Website Contact] SUBMITTED_SUBJECT", the visitor's name, the visitor's email address, the submitted subject, the submission timestamp formatted as "Month DD, YYYY at HH:MM AM/PM", and the message content with line breaks preserved.

2. THE Email_Template_System SHALL display contact form details in a table format with clear labels (Name, Email, Subject, Submitted) and styled with adequate spacing.

3. THE Email_Template_System SHALL display the message content in a Remark_Box styled with Biringan_Color_Palette colors.

4. THE Email_Template_System SHALL configure the email Reply-To header to the visitor's email address so the Administrator can reply directly to the visitor.

5. THE Email_Template_System SHALL include a note at the bottom of the email stating "Reply directly to this email to respond to the visitor" to guide the Administrator.

6. THE Email_Template_System SHALL use the same Email_Header and Email_Footer design as resident notification emails to maintain consistent branding.

---

### Requirement 13: Status Badge Visual Design

**User Story:** As a Resident, I want to see color-coded status indicators in my emails that match the status colors I see on the website, so that I can quickly recognize the current state of my request.

#### Acceptance Criteria

1. THE Email_Template_System SHALL implement Status_Badge styling as an inline-block element with padding, border-radius, font-size 13-14 pixels, and font-weight 600.

2. THE Email_Template_System SHALL use the following background and text color combinations for each status: Pending (background #E5E7EB, text #374151), Under Verification (background #DBEAFE, text #1D4ED8), Additional Info Required (background #FEF3C7, text #92400E), Approved (background #D1FAE5, text #065F46), Rejected (background #FEE2E2, text #991B1B), Ready for Claiming (background #CCFBF1, text #0F766E), Completed (background #BBF7D0, text #14532D), Cancelled (background #D1D5DB, text #1F2937).

3. THE Email_Template_System SHALL ensure Status_Badge color combinations meet WCAG 2.1 Level AA contrast requirements (minimum 4.5:1 ratio for normal text).

4. THE Email_Template_System SHALL display status text in title case (e.g., "Under Verification", "Ready for Claiming") with underscores replaced by spaces for readability.

---

### Requirement 14: Call-to-Action Button Visual Design

**User Story:** As a Resident, I want CTA buttons in my emails to be visually prominent and match the Biringan City design style, so that I can easily identify and click on important actions.

#### Acceptance Criteria

1. THE Email_Template_System SHALL style CTA_Button elements with a deep purple background color (#4A1F46), white text color (#FFFFFF), font-size 14 pixels, font-weight 600, padding of 10-12 pixels vertical and 24-28 pixels horizontal, border-radius of 6 pixels, and text-decoration none.

2. WHEN rendered in Email_Clients that support hover states, THE Email_Template_System SHALL apply a darker purple background (#2B173D) on hover to provide visual feedback.

3. THE Email_Template_System SHALL ensure CTA_Button elements have a minimum touch target size of 44x44 pixels to meet mobile accessibility guidelines.

4. THE Email_Template_System SHALL include appropriate icon elements (using Unicode or HTML entities) before button text where contextually appropriate (e.g., &#128279; for links, &#128194; for documents).

5. THE Email_Template_System SHALL ensure CTA_Button text is descriptive and action-oriented (e.g., "Track My Request", "Upload Documents", "Sign In to eMunicipal").

---

### Requirement 15: Request Information Card Visual Design

**User Story:** As a Resident, I want my reference number and request details to be displayed prominently in a visually distinct card, so that I can quickly identify the important information about my request.

#### Acceptance Criteria

1. THE Email_Template_System SHALL style Request_Info_Card elements with a light purple/cream background (#EEF2F7), a deep purple left border (4 pixels solid #4A1F46), border-radius of 6 pixels, padding of 12-16 pixels, and margin of 20 pixels vertical.

2. THE Email_Template_System SHALL display the Reference_Number in Request_Info_Card with a label "Reference Number" in uppercase, font-size 12 pixels, font-weight 600, letter-spacing 0.05em, color #4B5563, followed by the reference number in monospace font family ("Courier New", Courier, monospace), font-size 1.2em, font-weight 700, and deep purple color (#4A1F46).

3. THE Email_Template_System SHALL ensure the Reference_Number is easily selectable for copy-paste operations by users.

4. THE Email_Template_System SHALL use adequate spacing between the label and reference number to create clear visual hierarchy.

---

### Requirement 16: Remark Box Visual Design

**User Story:** As a Resident, I want Staff remarks and rejection reasons to be displayed in a visually distinct highlighted box, so that I can immediately identify important messages from Staff.

#### Acceptance Criteria

1. THE Email_Template_System SHALL style Remark_Box elements for staff notes with a yellow/amber background (#FEF3C7), an amber border (1 pixel solid #F59E0B), border-radius of 6 pixels, padding of 12-16 pixels, margin of 16 pixels vertical, and font-size 14 pixels.

2. THE Email_Template_System SHALL style Remark_Box elements for rejection reasons with a red/pink background (#FEE2E2), a red border (1 pixel solid #B91C1C), border-radius of 6 pixels, padding of 12-16 pixels, margin of 16 pixels vertical, and font-size 14 pixels.

3. THE Email_Template_System SHALL display a bold label ("Staff Note:", "Rejection Reason:", or "Reason:") above the remark text within the Remark_Box.

4. THE Email_Template_System SHALL preserve line breaks in remark text using br elements or white-space CSS properties to maintain Staff-formatted content.

---

### Requirement 17: Email Header Visual Design

**User Story:** As a Resident, I want every email to have a consistent professional header that identifies the sender as Biringan City Administration, so that I can recognize it as communication from the fictional eMunicipal academic demonstration system and experience consistent branding.

#### Acceptance Criteria

1. THE Email_Template_System SHALL display an Email_Header with a deep purple background color (#4A1F46), padding of 24-32 pixels, and white text.

2. THE Email_Template_System SHALL include the eMunicipal wordmark in the Email_Header with font-size 18 pixels, font-weight 700, and white color (#FFFFFF), using text-based branding compatible with email clients.

3. THE Email_Template_System SHALL include the text "Biringan City Administration" in the Email_Header with font-size 13 pixels and white color with 80% opacity (rgba(255,255,255,0.80)).

4. THE Email_Template_System SHALL use web-safe font stacks 'Arial, Helvetica, sans-serif' for Email_Header text to ensure reliable rendering across all email clients.

---

### Requirement 18: Email Footer Visual Design

**User Story:** As a Resident, I want every email to have a consistent footer with contact information and a disclaimer, so that I understand how to access the portal and know that Biringan City is a fictional demonstration project.

#### Acceptance Criteria

1. THE Email_Template_System SHALL display an Email_Footer with a light gray background (#F1F3F5), padding of 20-32 pixels, centered text alignment, font-size 12 pixels, and medium gray text color (#6B7280).

2. THE Email_Template_System SHALL include the text "This is an automated message from the eMunicipal Online Document Request System." in the Email_Footer.

3. THE Email_Template_System SHALL include the organization name "Biringan City Administration" in the Email_Footer followed by a vertical bar separator and a hyperlink to the portal homepage with the text "eMunicipal Portal".

4. THE Email_Template_System SHALL style Email_Footer hyperlinks with a blue color (#2563A8) and text-decoration none.

5. THE Email_Template_System SHALL include the Academic_Disclaimer text in the Email_Footer: "Biringan City and all organizations, services, records, and characters presented are fictional and created for academic demonstration."

6. THE Email_Template_System SHALL include the text "Please do not reply to this email." in the Email_Footer to set appropriate expectations for Residents.

7. THE Email_Footer Academic_Disclaimer SHALL be displayed in a slightly smaller font size (11-12 pixels) and lighter color to visually distinguish it from primary footer content while maintaining readability.

---

### Requirement 19: Email Template Data Preservation

**User Story:** As a Developer, I want the email redesign to preserve all existing template variables and data sources, so that no changes are required to EmailService method signatures or calling code.

#### Acceptance Criteria

1. THE Email_Template_System SHALL continue to accept all existing template variables from EmailService methods including: $name, $ref, $type, $docType, $date, $appUrl, $trackUrl, $remark, $cancelledBy, $ownerEmail, $visitorName, $visitorEmail, $subject, $message, $submitted.

2. THE Email_Template_System SHALL continue to receive Email_Body_Content from EmailService via the renderTemplate method without requiring changes to the EmailService API.

3. THE Email_Template_System SHALL continue to expose $subject and $body variables to the Email_Layout_Template file as it does currently.

4. THE Email_Template_System SHALL continue to use the layout.php template file path (views/emails/layout.php) without requiring directory structure changes.

5. THE Email_Template_System SHALL continue to sanitize all user-provided content using htmlspecialchars with ENT_QUOTES and UTF-8 encoding to prevent XSS vulnerabilities.

---

### Requirement 20: Email Functionality Preservation

**User Story:** As a System Administrator, I want the email redesign to maintain all existing email sending functionality, so that SMTP configuration, email logging, notification triggers, and error handling continue to work without modification.

#### Acceptance Criteria

1. THE Email_Template_System SHALL NOT modify any EmailService method signatures, PHPMailer configuration, or SMTP connection logic.

2. THE Email_Template_System SHALL NOT modify email_logs table insertion logic or the EmailLog model.

3. THE Email_Template_System SHALL NOT modify notification trigger logic in NotificationService or request status change handlers.

4. THE Email_Template_System SHALL NOT modify Mailtrap SMTP credential loading from environment variables.

5. THE Email_Template_System SHALL continue to wrap Email_Body_Content in the Email_Layout_Template using the same PHP output buffering pattern currently implemented in renderTemplate method.

6. THE Email_Template_System SHALL continue to generate plaintext alternative content (AltBody) using strip_tags and string replacement as currently implemented.

7. THE Email_Template_System SHALL continue to use PHPMailer methods (setFrom, addAddress, addReplyTo, isHTML, send) without modification.

8. THE Email_Template_System SHALL continue to catch and log email send failures without throwing exceptions to calling code (except for contact form emails which intentionally throw on failure).

---

### Requirement 21: Reference Number in Subject Lines

**User Story:** As a Resident, I want the reference number to appear in the subject line of all request-related emails, so that I can easily filter, search, and organize my emails by request.

#### Acceptance Criteria

1. THE Email_Template_System SHALL include the Reference_Number in square brackets at the beginning of the subject line for all request-related notification emails (Request Submitted, Under Verification, Additional Info Required, Approved, Rejected, Ready for Claiming, Completed, Cancelled).

2. THE Email_Template_System SHALL format subject lines as "[REFERENCE_NUMBER] Message Text" where REFERENCE_NUMBER follows the format YYYYMMDD-NNNN.

3. THE Email_Template_System SHALL NOT include reference numbers in subject lines for non-request emails (Registration, Contact Form).

---

### Requirement 22: Mobile Email Responsiveness

**User Story:** As a Resident accessing email on a mobile device, I want emails to be optimized for small screens, so that I can read all content and tap buttons easily without zooming or horizontal scrolling.

#### Acceptance Criteria

1. WHEN an email is rendered in a Mobile_Viewport (320-600 pixels width), THE Email_Template_System SHALL apply responsive CSS using media queries to adjust font sizes, padding, and spacing.

2. WHEN an email is rendered in a Mobile_Viewport, THE Email_Template_System SHALL reduce heading font sizes by 10-20% to prevent text overflow.

3. WHEN an email is rendered in a Mobile_Viewport, THE Email_Template_System SHALL reduce padding values in Email_Header, Email_Footer, and Request_Info_Card by 20-30% to maximize content space.

4. WHEN an email is rendered in a Mobile_Viewport, THE Email_Template_System SHALL ensure CTA_Button elements span full width or are centered to provide easy touch targets.

5. WHEN an email is rendered in a Mobile_Viewport, THE Email_Template_System SHALL stack table columns vertically where appropriate to prevent horizontal scrolling.

6. THE Email_Template_System SHALL implement responsive CSS for mobile viewports. Testing in external mobile email clients (Gmail, Apple Mail iOS, Outlook mobile) is recommended when available but not required.

---

### Requirement 23: Dark Mode Email Compatibility

**User Story:** As a Resident using an email client with dark mode enabled, I want emails to remain readable with clear contrast, so that I can comfortably read email content in my preferred viewing mode.

#### Acceptance Criteria

1. THE Email_Template_System SHALL use explicit color values for all text and background elements to prevent Email_Clients from applying unwanted dark mode color transformations.

2. THE Email_Template_System SHALL ensure text color and background color combinations meet WCAG 2.1 Level AA contrast requirements (minimum 4.5:1 ratio for normal text, 3:1 for large text).

3. THE Email_Template_System SHALL use light text colors on dark backgrounds (e.g., white text on deep purple header) and dark text colors on light backgrounds (e.g., dark text on white content card).

4. THE Email_Template_System SHALL avoid pure white (#FFFFFF) or pure black (#000000) as text colors where possible, instead using off-white (rgba(255,255,255,0.95)) and near-black (#1A202C) for better dark mode compatibility.

5. THE Email_Template_System SHALL use explicit colors and adequate contrast ratios. Testing in external email clients with dark mode (Gmail, Apple Mail, Outlook) is recommended when available but not required.

---

### Requirement 24: Email Testing and Validation

**User Story:** As a Developer, I want to verify that redesigned emails render correctly across major email clients and devices, so that I can ensure a consistent user experience before deploying to production.

#### Acceptance Criteria

1. THE Email_Template_System SHALL verify HTML structure, inline CSS validity, and email-safe coding practices. Testing in external Email_Clients (Gmail, Outlook, Apple Mail, Yahoo Mail) or preview tools (Litmus, Email on Acid) is recommended when available but not required.

2. THE Email_Template_System SHALL be tested at the following viewport widths: 320px, 375px, 414px (mobile), 600px (tablet), 768px, and 1024px (desktop).

3. THE Email_Template_System SHALL verify that all CTA_Button links point to correct portal URLs.

4. THE Email_Template_System SHALL verify that all Status_Badge color combinations meet WCAG 2.1 Level AA contrast requirements using a contrast checker tool.

5. THE Email_Template_System SHALL verify that HTML validates against email-specific HTML standards (avoiding unsupported elements and attributes).

6. THE Email_Template_System SHALL verify that Inline_CSS styles apply correctly without external stylesheet dependencies.

7. THE Email_Template_System SHALL verify that plaintext alternative content (AltBody) provides a reasonable text-only experience for Email_Clients that do not support HTML.

---

### Requirement 25: Academic Disclaimer Visibility

**User Story:** As a System Administrator, I want every email to clearly indicate that Biringan City is fictional and created for academic demonstration, so that email recipients understand the nature of the project and do not confuse it with real government communication.

#### Acceptance Criteria

1. THE Email_Template_System SHALL display the Academic_Disclaimer text in the Email_Footer of every email template including registration, request status updates, and contact form emails.

2. THE Academic_Disclaimer text SHALL read: "Biringan City and all organizations, services, records, and characters presented are fictional and created for academic demonstration."

3. THE Academic_Disclaimer SHALL be visually distinct from other footer content using a slightly smaller font size (11-12 pixels) and lighter gray color (#9CA3AF or similar).

4. THE Academic_Disclaimer SHALL remain readable and accessible, meeting WCAG 2.1 Level AA contrast requirements against the Email_Footer background color.

5. THE Academic_Disclaimer SHALL NOT be hidden, truncated, or require user interaction to view.

---

### Requirement 26: Implementation Scope Boundaries

**User Story:** As a Developer, I want clear boundaries on what is in scope and out of scope for this redesign, so that I avoid making unintended changes to system functionality.

#### Acceptance Criteria

1. THE Email_Template_System redesign SHALL modify only the following files: views/emails/layout.php and the inline HTML string generation in app/Services/EmailService.php methods (sendRegistrationConfirmation, sendRequestSubmitted, sendStatusUpdate, sendContactMessage, sendContactRaw).

2. THE Email_Template_System redesign SHALL NOT modify database schema files (database/schema.sql, database/seeds.sql).

3. THE Email_Template_System redesign SHALL NOT modify the EmailLog model (app/Models/EmailLog.php).

4. THE Email_Template_System redesign SHALL NOT modify NotificationService (app/Services/NotificationService.php).

5. THE Email_Template_System redesign SHALL NOT modify request status workflow logic in controllers (Admin/AdminRequestController.php, Staff/StaffRequestController.php, Resident/RequestController.php).

6. THE Email_Template_System redesign SHALL NOT modify Mailtrap SMTP configuration loading from config/mail.php or environment variables.

7. THE Email_Template_System redesign SHALL NOT modify authentication, authorization, or RBAC logic.

8. THE Email_Template_System redesign SHALL NOT modify website/dashboard UI views (layouts/auth.php, layouts/public.php, layouts/minimal.php, home/index.php, or any dashboard views).

9. THE Email_Template_System redesign SHALL NOT modify API endpoints, routing configuration, or middleware.

10. THE Email_Template_System redesign SHALL NOT add new database tables, columns, or indexes.

12. THE Email_Template_System redesign SHALL NOT add new functionality, features, data fields, status types, notification types, or system behaviors beyond what currently exists in the implemented EmailService and email template system.

---

### Requirement 27: Existing Content Preservation

**User Story:** As a Developer, I want all existing email content, office information, contact details, URLs, and instructions to be preserved exactly as implemented, so that the redesign only changes visual presentation without altering factual information or system behavior.

#### Acceptance Criteria

1. THE Email_Template_System redesign SHALL preserve all existing email content including: message text, office names, office addresses, office hours, contact information, processing time estimates, instructions, and guidance text currently implemented in EmailService methods.

2. THE Email_Template_System redesign SHALL preserve all existing URL generation logic and route references (login URLs, tracking URLs, request URLs, profile URLs) without modification.

3. THE Email_Template_System redesign SHALL preserve all existing dynamic data sources including: user names, email addresses, reference numbers, document type names, submission dates, status values, staff remarks, rejection reasons, and cancellation information.

4. THE Email_Template_System redesign SHALL NOT invent new addresses, office hours, processing times, contact information, requirements, routes, instructions, or business logic that is not already present in the existing implementation.

5. THE Email_Template_System redesign SHALL NOT remove required content elements specified in the original Requirement 10 (Email Notification System) from the existing system.

6. IF the existing implementation contains placeholder or fictional information appropriate to the Biringan City academic demonstration context (e.g., "Biringan City Civic Center, Central District, Biringan City"), THEN the redesign SHALL preserve that exact information without substitution.

---
