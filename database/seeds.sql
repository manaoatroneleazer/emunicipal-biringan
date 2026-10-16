-- ============================================================
-- eMunicipal — Seed Data
-- Biringan City Administration (Academic Project)
--
-- Source of truth: database.md §17 (Seed Data)
--
-- HOW TO APPLY:
--   Run this file AFTER schema.sql on a freshly created database.
--   Uses INSERT IGNORE so re-running is safe (idempotent).
--
-- NOTE ON ROLES:
--   The `roles` table no longer exists. Each user's role is stored
--   directly in users.role as ENUM('resident','staff','admin').
--   There is no separate roles seed section.
--
-- IMPORTANT — ADMINISTRATOR PASSWORD:
--   The admin password hash below is a placeholder.
--   Replace it before running in any environment:
--
--   In PHP, generate a hash with:
--     php -r "echo password_hash('YourStrongPassword1', PASSWORD_DEFAULT);"
--
--   Then replace the placeholder hash in the INSERT below.
--   NEVER commit a real password or real hash to version control.
--
-- FICTIONAL NOTICE:
--   All organization names, addresses, document types, departments,
--   and records in this file are entirely fictional and created for
--   academic demonstration purposes only.
-- ============================================================

USE `emunicipal`;

-- ============================================================
-- 1. Initial Administrator Account (database.md §17.2)
--
-- Role is stored directly in users.role (no separate roles table).
--
-- REPLACE THE HASH BELOW with the output of:
--   php -r "echo password_hash('YourStrongPassword1', PASSWORD_DEFAULT);"
--
-- Default placeholder hash is for: Admin@1234
-- CHANGE THIS BEFORE DEPLOYING.
-- ============================================================
INSERT IGNORE INTO `users` (
    `id`,
    `role`,
    `first_name`,
    `last_name`,
    `email`,
    `password_hash`,
    `contact_number`,
    `address`,
    `is_active`,
    `failed_login_attempts`
) VALUES (
    1,
    'admin',                    -- role: admin
    'System',
    'Administrator',
    'admin@emunicipal.local',   -- Change to real admin email
    -- Hash for: Admin@1234  (REPLACE THIS IN PRODUCTION)
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NULL,
    NULL,
    1,
    0
);

-- ============================================================
-- 2. System Settings (database.md §17.3, §4.11)
-- All 8 required settings seeded with fictional defaults.
-- No credentials stored here.
-- ============================================================
INSERT IGNORE INTO `system_settings` (`key`, `value`, `label`, `description`, `group`) VALUES
    (
        'office_name',
        'Biringan City Administration',
        'Office Name',
        'Name of the fictional administration displayed throughout the system.',
        'municipal_info'
    ),
    (
        'office_address',
        'Biringan City Civic Center, Central District, Biringan City, Province of Elaria',
        'Office Address',
        'Fictional address of the administration office.',
        'municipal_info'
    ),
    (
        'office_contact_number',
        '(000) 000-0000',
        'Office Contact Number',
        'Fictional contact number displayed on public pages and email templates.',
        'contact'
    ),
    (
        'office_email',
        'emunicipal.portal@gmail.com',
        'Office Email',
        'Contact email for the fictional administration office.',
        'contact'
    ),
    (
        'max_upload_size_mb',
        '5',
        'Maximum Upload Size (MB)',
        'Maximum allowed file size per uploaded document in megabytes. Range: 1–20.',
        'system'
    ),
    (
        'session_timeout_minutes',
        '30',
        'Session Timeout (Minutes)',
        'Duration of inactivity before a user session is automatically invalidated. Range: 5–120.',
        'system'
    ),
    (
        'records_per_page',
        '20',
        'Records Per Page',
        'Default number of records shown per page in all paginated list views. Allowed: 10, 20, or 50.',
        'system'
    ),
    (
        'email_service_status',
        'configured_via_env',
        'Email Service Status',
        'Informational indicator of email service configuration. Credentials are stored in .env only — never here.',
        'email_status'
    );

-- ============================================================
-- 3. Document Types — 8 Fictional Biringan City Documents
--
-- FICTIONAL NOTICE: All document types, departments, descriptions,
-- and fees listed here are entirely fictional and created solely
-- for this academic demonstration project.
-- They are NOT derived from any real government Citizen's Charter.
--
-- Department prefix format: [DEPT] used for office filter in UI.
-- INSERT IGNORE — safe to re-run.
-- ============================================================

-- Biringan Civil Records Office (BCRO)
INSERT IGNORE INTO `document_types` (`name`, `description`, `processing_fee`, `is_active`, `created_by`) VALUES
(
    'Biringan City Birth Certificate',
    '[BCRO] A fictional sample certificate confirming the registered birth of a community member in Biringan City. Issued by the Biringan Civil Records Office for academic demonstration purposes.',
    50.00, 1, 1
);

-- Community Records Office (CRO)
INSERT IGNORE INTO `document_types` (`name`, `description`, `processing_fee`, `is_active`, `created_by`) VALUES
(
    'Biringan City Residency Certificate',
    '[CRO] A fictional sample certificate confirming that the holder is a registered resident of Biringan City. Issued by the Community Records Office for academic demonstration purposes.',
    50.00, 1, 1
),
(
    'Biringan City Community Clearance',
    '[CRO] A fictional sample clearance document confirming that the holder has no adverse records within the Biringan City community registry. Issued by the Community Records Office.',
    50.00, 1, 1
),
(
    'Certificate of Community Membership',
    '[CRO] A fictional sample certificate confirming active membership in the Biringan City community. Issued by the Community Records Office for academic demonstration purposes.',
    0.00, 1, 1
);

-- Community Assistance Office (CAO)
INSERT IGNORE INTO `document_types` (`name`, `description`, `processing_fee`, `is_active`, `created_by`) VALUES
(
    'Certificate of Indigency',
    '[CAO] A fictional sample certificate issued by the Community Assistance Office confirming that the holder qualifies for community financial assistance programs. For academic demonstration only.',
    0.00, 1, 1
);

-- Business Services Office (BSO)
INSERT IGNORE INTO `document_types` (`name`, `description`, `processing_fee`, `is_active`, `created_by`) VALUES
(
    'Local Business Registration Certificate',
    '[BSO] A fictional sample certificate confirming the registration of a business entity within Biringan City. Issued by the Business Services Office for academic demonstration purposes.',
    100.00, 1, 1
),
(
    'Business Clearance Certificate',
    '[BSO] A fictional sample clearance confirming that a registered business in Biringan City has no pending compliance issues. Issued by the Business Services Office.',
    100.00, 1, 1
);

-- Community Health Office (CHO)
INSERT IGNORE INTO `document_types` (`name`, `description`, `processing_fee`, `is_active`, `created_by`) VALUES
(
    'Community Health Certificate',
    '[CHO] A fictional sample health certificate issued by the Biringan City Community Health Office confirming the general health status of the applicant. For academic demonstration only.',
    50.00, 1, 1
);

-- ============================================================
-- 4. Document Requirements
-- Fictional sample requirements for each document type above.
-- Requirements are database-driven; no real Citizen's Charter
-- content is reproduced here.
-- ============================================================

-- Helper: requirements are inserted using document type names as a lookup
-- so that the seed remains portable across fresh installs.

-- 1. Biringan City Birth Certificate
INSERT IGNORE INTO `document_requirements` (`document_type_id`, `name`, `description`, `is_required`, `sort_order`, `is_active`)
SELECT dt.id, req.name, req.description, req.is_required, req.sort_order, 1
FROM `document_types` dt
JOIN (
    SELECT 'Completed Request Form'          AS name, 'Duly accomplished online request form.'                    AS description, 1 AS is_required, 1 AS sort_order UNION ALL
    SELECT 'Fictional Resident Identification',        'Any valid fictional community ID issued by Biringan City.', 1,              2               UNION ALL
    SELECT 'Birth Information Declaration',            'Accomplished birth information declaration slip.',          1,              3
) req ON dt.name = 'Biringan City Birth Certificate'
WHERE dt.name = 'Biringan City Birth Certificate';

-- 2. Biringan City Residency Certificate
INSERT IGNORE INTO `document_requirements` (`document_type_id`, `name`, `description`, `is_required`, `sort_order`, `is_active`)
SELECT dt.id, req.name, req.description, req.is_required, req.sort_order, 1
FROM `document_types` dt
JOIN (
    SELECT 'Completed Request Form'          AS name, 'Duly accomplished online request form.'                    AS description, 1 AS is_required, 1 AS sort_order UNION ALL
    SELECT 'Fictional Resident Identification',        'Any valid fictional community ID issued by Biringan City.', 1,              2               UNION ALL
    SELECT 'Proof of Residence',                       'Utility bill or similar document showing current address.',  1,              3
) req ON dt.name = 'Biringan City Residency Certificate'
WHERE dt.name = 'Biringan City Residency Certificate';

-- 3. Biringan City Community Clearance
INSERT IGNORE INTO `document_requirements` (`document_type_id`, `name`, `description`, `is_required`, `sort_order`, `is_active`)
SELECT dt.id, req.name, req.description, req.is_required, req.sort_order, 1
FROM `document_types` dt
JOIN (
    SELECT 'Completed Request Form'          AS name, 'Duly accomplished online request form.'                    AS description, 1 AS is_required, 1 AS sort_order UNION ALL
    SELECT 'Fictional Resident Identification',        'Any valid fictional community ID issued by Biringan City.', 1,              2               UNION ALL
    SELECT 'Proof of Residence',                       'Utility bill or similar document showing current address.',  1,              3
) req ON dt.name = 'Biringan City Community Clearance'
WHERE dt.name = 'Biringan City Community Clearance';

-- 4. Certificate of Community Membership
INSERT IGNORE INTO `document_requirements` (`document_type_id`, `name`, `description`, `is_required`, `sort_order`, `is_active`)
SELECT dt.id, req.name, req.description, req.is_required, req.sort_order, 1
FROM `document_types` dt
JOIN (
    SELECT 'Completed Request Form'          AS name, 'Duly accomplished online request form.'                    AS description, 1 AS is_required, 1 AS sort_order UNION ALL
    SELECT 'Fictional Resident Identification',        'Any valid fictional community ID issued by Biringan City.', 1,              2               UNION ALL
    SELECT 'Community Membership Information',         'Proof of community group affiliation or enrollment.',       1,              3
) req ON dt.name = 'Certificate of Community Membership'
WHERE dt.name = 'Certificate of Community Membership';

-- 5. Certificate of Indigency
INSERT IGNORE INTO `document_requirements` (`document_type_id`, `name`, `description`, `is_required`, `sort_order`, `is_active`)
SELECT dt.id, req.name, req.description, req.is_required, req.sort_order, 1
FROM `document_types` dt
JOIN (
    SELECT 'Completed Request Form'          AS name, 'Duly accomplished online request form.'                    AS description, 1 AS is_required, 1 AS sort_order UNION ALL
    SELECT 'Fictional Resident Identification',        'Any valid fictional community ID issued by Biringan City.', 1,              2               UNION ALL
    SELECT 'Proof of Residence',                       'Utility bill or similar document showing current address.',  1,              3               UNION ALL
    SELECT 'Supporting Declaration',                   'Accomplished declaration form for community assistance.',    1,              4
) req ON dt.name = 'Certificate of Indigency'
WHERE dt.name = 'Certificate of Indigency';

-- 6. Local Business Registration Certificate
INSERT IGNORE INTO `document_requirements` (`document_type_id`, `name`, `description`, `is_required`, `sort_order`, `is_active`)
SELECT dt.id, req.name, req.description, req.is_required, req.sort_order, 1
FROM `document_types` dt
JOIN (
    SELECT 'Completed Business Application'  AS name, 'Accomplished business registration application form.'       AS description, 1 AS is_required, 1 AS sort_order UNION ALL
    SELECT 'Fictional Owner Identification',           'Any valid fictional community ID of the business owner.',   1,              2               UNION ALL
    SELECT 'Business Information',                     'Brief description of the nature and scope of the business.',1,              3               UNION ALL
    SELECT 'Proof of Business Location',               'Document confirming the business address within Biringan City.', 1,         4
) req ON dt.name = 'Local Business Registration Certificate'
WHERE dt.name = 'Local Business Registration Certificate';

-- 7. Business Clearance Certificate
INSERT IGNORE INTO `document_requirements` (`document_type_id`, `name`, `description`, `is_required`, `sort_order`, `is_active`)
SELECT dt.id, req.name, req.description, req.is_required, req.sort_order, 1
FROM `document_types` dt
JOIN (
    SELECT 'Completed Business Request Form' AS name, 'Accomplished business clearance request form.'              AS description, 1 AS is_required, 1 AS sort_order UNION ALL
    SELECT 'Fictional Owner Identification',           'Any valid fictional community ID of the business owner.',   1,              2               UNION ALL
    SELECT 'Business Registration Information',        'Copy of existing business registration document.',          1,              3               UNION ALL
    SELECT 'Proof of Business Location',               'Document confirming the business address within Biringan City.', 1,         4
) req ON dt.name = 'Business Clearance Certificate'
WHERE dt.name = 'Business Clearance Certificate';

-- 8. Community Health Certificate
INSERT IGNORE INTO `document_requirements` (`document_type_id`, `name`, `description`, `is_required`, `sort_order`, `is_active`)
SELECT dt.id, req.name, req.description, req.is_required, req.sort_order, 1
FROM `document_types` dt
JOIN (
    SELECT 'Completed Request Form'          AS name, 'Duly accomplished online request form.'                    AS description, 1 AS is_required, 1 AS sort_order UNION ALL
    SELECT 'Fictional Resident Identification',        'Any valid fictional community ID issued by Biringan City.', 1,              2               UNION ALL
    SELECT 'Health Declaration',                       'Accomplished health declaration form.',                     1,              3
) req ON dt.name = 'Community Health Certificate'
WHERE dt.name = 'Community Health Certificate';

-- ============================================================
-- End of seeds.sql
-- ============================================================
