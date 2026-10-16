-- ============================================================
-- eMunicipal — Database Schema
-- Biringan City Administration (Academic Demonstration Project)
--
-- Engine  : InnoDB
-- Charset : utf8mb4
-- Collation: utf8mb4_unicode_ci
--
-- Source of truth: database.md (Phase 2 — Database Design Specification)
--
-- ROLE STORAGE:
--   User roles are stored directly in users.role as
--   ENUM('resident','staff','admin'). There is no separate
--   roles table — role is a plain attribute of the users table.
--
-- HOW TO APPLY:
--   1. Open phpMyAdmin (http://localhost/phpmyadmin) or use MySQL CLI.
--   2. Run this file against the target server.
--   3. Then run database/seeds.sql to insert required seed data.
--
-- SAFETY:
--   This script creates the database and tables only if they do not exist.
--   It does NOT DROP any existing database or tables.
--   Run seeds.sql only on a freshly created database.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Create database (idempotent)
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `emunicipal`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `emunicipal`;

-- ============================================================
-- TABLE: users
-- Purpose: All user accounts across all roles (database.md §4.2)
--
-- Role is stored directly as an ENUM column (resident | staff | admin).
-- There is no separate roles table — role is an attribute of users.
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`                    INT UNSIGNED                        NOT NULL AUTO_INCREMENT,
    `role`                  ENUM('resident','staff','admin')    NOT NULL COMMENT 'User role: resident, staff, or admin',
    `first_name`            VARCHAR(100)                        NOT NULL,
    `last_name`             VARCHAR(100)                        NOT NULL,
    `email`                 VARCHAR(255)                        NOT NULL COMMENT 'Unique login email (Req 1.3)',
    `password_hash`         VARCHAR(255)                        NOT NULL COMMENT 'bcrypt hash via password_hash() — never plain text',
    `contact_number`        VARCHAR(20)                         NULL DEFAULT NULL COMMENT 'Philippine mobile/landline (Req 1.9)',
    `address`               TEXT                                NULL DEFAULT NULL COMMENT 'Home address; required for residents',
    `is_active`             TINYINT(1)                          NOT NULL DEFAULT 1 COMMENT '1=active, 0=deactivated (Req 11.4)',
    `failed_login_attempts` TINYINT UNSIGNED                    NOT NULL DEFAULT 0 COMMENT 'Consecutive failed logins; reset on success',
    `locked_until`          DATETIME                            NULL DEFAULT NULL COMMENT 'Account locked until timestamp; NULL=not locked',
    `last_login_at`         DATETIME                            NULL DEFAULT NULL,
    `created_at`            DATETIME                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME                            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_users_email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='All user accounts (Req 1, 2, 3, 11)';

-- ============================================================
-- TABLE: document_types
-- Purpose: Configurable document categories managed by Admin (Req 5, database.md §4.3)
-- ============================================================
CREATE TABLE IF NOT EXISTS `document_types` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(150)  NOT NULL COMMENT 'e.g. Biringan City Residency Certificate',
    `description`    TEXT          NOT NULL,
    `processing_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Standard fee in PHP pesos',
    `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
    `created_by`     INT UNSIGNED  NOT NULL COMMENT 'FK: Administrator who created',
    `updated_by`     INT UNSIGNED  NULL DEFAULT NULL,
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_document_types_name` (`name`),
    KEY `idx_document_types_is_active` (`is_active`),
    CONSTRAINT `fk_document_types_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_document_types_updated_by`
        FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Municipal document categories (Req 5)';

-- ============================================================
-- TABLE: document_requirements
-- Purpose: Per-document-type supporting document requirements (Req 5.6–5.8, database.md §4.4)
-- ============================================================
CREATE TABLE IF NOT EXISTS `document_requirements` (
    `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `document_type_id` INT UNSIGNED     NOT NULL,
    `name`             VARCHAR(200)     NOT NULL COMMENT 'e.g. Valid Government-Issued ID',
    `description`      TEXT             NULL DEFAULT NULL,
    `is_required`      TINYINT(1)       NOT NULL DEFAULT 1 COMMENT '1=mandatory, 0=optional',
    `sort_order`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`        TINYINT(1)       NOT NULL DEFAULT 1,
    `created_by`       INT UNSIGNED     NOT NULL,
    `updated_by`       INT UNSIGNED     NULL DEFAULT NULL,
    `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_doc_req_document_type_id` (`document_type_id`),
    KEY `idx_doc_req_is_active` (`is_active`),
    CONSTRAINT `fk_doc_req_document_type_id`
        FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_doc_req_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_doc_req_updated_by`
        FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-document-type requirements (Req 5.6)';

-- ============================================================
-- TABLE: requests
-- Purpose: Central entity for a resident document request (Req 6–9, database.md §4.5)
-- ============================================================
CREATE TABLE IF NOT EXISTS `requests` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_number`    VARCHAR(20)  NOT NULL COMMENT 'Format: EMU-YYYY-NNNNNN (Req 6.9)',
    `user_id`             INT UNSIGNED NOT NULL COMMENT 'Resident who submitted',
    `document_type_id`    INT UNSIGNED NOT NULL,
    `purpose`             TEXT         NOT NULL,
    `copies_requested`    TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Must be >= 1 (Req 6.14)',
    `status`              ENUM(
                              'pending',
                              'under_verification',
                              'additional_info_required',
                              'approved',
                              'rejected',
                              'ready_for_claiming',
                              'completed',
                              'cancelled'
                          ) NOT NULL DEFAULT 'pending',
    `assigned_staff_id`   INT UNSIGNED NULL DEFAULT NULL,
    `rejection_reason`    TEXT         NULL DEFAULT NULL,
    `cancellation_reason` TEXT         NULL DEFAULT NULL,
    `cancelled_by`        INT UNSIGNED NULL DEFAULT NULL,
    `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_requests_reference_number` (`reference_number`),
    KEY `idx_requests_user_id` (`user_id`),
    KEY `idx_requests_document_type_id` (`document_type_id`),
    KEY `idx_requests_status` (`status`),
    KEY `idx_requests_created_at` (`created_at`),
    CONSTRAINT `fk_requests_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_requests_document_type_id`
        FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_requests_assigned_staff_id`
        FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_requests_cancelled_by`
        FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Document requests (Req 6, 7, 8, 9)';

-- ============================================================
-- TABLE: request_status_history
-- Purpose: Append-only status transition log per request (Req 7.5, 8.2, database.md §4.6)
-- ============================================================
CREATE TABLE IF NOT EXISTS `request_status_history` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`      INT UNSIGNED NOT NULL,
    `previous_status` ENUM(
                          'pending',
                          'under_verification',
                          'additional_info_required',
                          'approved',
                          'rejected',
                          'ready_for_claiming',
                          'completed',
                          'cancelled'
                      ) NULL DEFAULT NULL COMMENT 'NULL for initial PENDING entry',
    `new_status`      ENUM(
                          'pending',
                          'under_verification',
                          'additional_info_required',
                          'approved',
                          'rejected',
                          'ready_for_claiming',
                          'completed',
                          'cancelled'
                      ) NOT NULL,
    `changed_by`      INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL for system-generated (initial PENDING)',
    `remark`          TEXT         NULL DEFAULT NULL COMMENT 'Staff remark, rejection reason, or cancellation reason',
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rsh_request_id` (`request_id`),
    KEY `idx_rsh_changed_by` (`changed_by`),
    KEY `idx_rsh_created_at` (`created_at`),
    CONSTRAINT `fk_rsh_request_id`
        FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_rsh_changed_by`
        FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Append-only status history (Req 7.5, 8.2)';

-- ============================================================
-- TABLE: request_documents
-- Purpose: File metadata for uploaded supporting documents (Req 6.4–6.8, database.md §4.7)
-- Files are stored on the filesystem; only metadata is in the DB.
-- ============================================================
CREATE TABLE IF NOT EXISTS `request_documents` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`        INT UNSIGNED NOT NULL,
    `uploaded_by`       INT UNSIGNED NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL COMMENT 'Original client filename — display only',
    `stored_filename`   VARCHAR(255) NOT NULL COMMENT 'UUID-based secure filename (Req 6.7)',
    `storage_path`      VARCHAR(500) NOT NULL COMMENT 'Relative path from upload root — never web-accessible',
    `mime_type`         VARCHAR(100) NOT NULL COMMENT 'Validated MIME: application/pdf, image/jpeg, image/png',
    `file_size_bytes`   INT UNSIGNED NOT NULL,
    `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_req_docs_stored_filename` (`stored_filename`),
    KEY `idx_req_docs_request_id` (`request_id`),
    KEY `idx_req_docs_uploaded_by` (`uploaded_by`),
    CONSTRAINT `fk_req_docs_request_id`
        FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_req_docs_uploaded_by`
        FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Uploaded file metadata (Req 6.4–6.8)';

-- ============================================================
-- TABLE: notifications
-- Purpose: In-system notifications for Residents (Req 16, database.md §4.8)
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`          INT UNSIGNED NOT NULL,
    `request_id`       INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL for system-level notifications',
    `reference_number` VARCHAR(20)  NULL DEFAULT NULL COMMENT 'Denormalized for display without JOIN',
    `type`             VARCHAR(50)  NOT NULL COMMENT 'request_submitted, approved, rejected, etc.',
    `title`            VARCHAR(255) NOT NULL,
    `message`          TEXT         NOT NULL,
    `is_read`          TINYINT(1)   NOT NULL DEFAULT 0,
    `read_at`          DATETIME     NULL DEFAULT NULL,
    `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_id` (`user_id`),
    KEY `idx_notifications_user_unread` (`user_id`, `is_read`),
    KEY `idx_notifications_request_id` (`request_id`),
    KEY `idx_notifications_created_at` (`created_at`),
    CONSTRAINT `fk_notifications_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_notifications_request_id`
        FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='In-system notifications for Residents (Req 16)';

-- ============================================================
-- TABLE: email_logs
-- Purpose: Record every outbound email attempt (Req 10.3–10.4, database.md §4.9)
-- Append-only. No credentials stored.
-- ============================================================
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id`                INT UNSIGNED         NOT NULL AUTO_INCREMENT,
    `recipient_email`   VARCHAR(255)         NOT NULL,
    `notification_type` VARCHAR(50)          NOT NULL,
    `reference_number`  VARCHAR(20)          NULL DEFAULT NULL,
    `subject`           VARCHAR(255)         NOT NULL,
    `status`            ENUM('sent','failed') NOT NULL,
    `error_message`     TEXT                 NULL DEFAULT NULL,
    `created_at`        DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email_logs_recipient` (`recipient_email`),
    KEY `idx_email_logs_status` (`status`),
    KEY `idx_email_logs_created_at` (`created_at`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Outbound email log — append-only (Req 10.3)';

-- ============================================================
-- TABLE: audit_logs
-- Purpose: Tamper-evident system action log (Req 13, database.md §4.10)
-- Append-only. Never updated or deleted by application.
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL for system events',
    `user_role`    VARCHAR(20)  NULL DEFAULT NULL COMMENT 'Snapshot of role at time of action',
    `action`       VARCHAR(100) NOT NULL COMMENT 'e.g. user_login, request_submitted',
    `module`       VARCHAR(50)  NOT NULL COMMENT 'e.g. auth, requests, users, documents',
    `record_table` VARCHAR(64)  NULL DEFAULT NULL,
    `record_id`    INT UNSIGNED NULL DEFAULT NULL,
    `old_value`    JSON         NULL DEFAULT NULL,
    `new_value`    JSON         NULL DEFAULT NULL,
    `description`  TEXT         NULL DEFAULT NULL,
    `ip_address`   VARCHAR(45)  NULL DEFAULT NULL COMMENT 'IPv4 or IPv6',
    `user_agent`   VARCHAR(500) NULL DEFAULT NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_user_id` (`user_id`),
    KEY `idx_audit_logs_created_at` (`created_at`),
    KEY `idx_audit_logs_action` (`action`),
    KEY `idx_audit_logs_record` (`record_table`, `record_id`),
    CONSTRAINT `fk_audit_logs_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Append-only tamper-evident audit log (Req 13)';

-- ============================================================
-- TABLE: system_settings
-- Purpose: Admin-configurable key-value settings (Req 20, database.md §4.11)
-- NO credentials stored here — credentials are in .env only.
-- ============================================================
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`         VARCHAR(100) NOT NULL,
    `value`       TEXT         NOT NULL,
    `label`       VARCHAR(200) NOT NULL,
    `description` TEXT         NULL DEFAULT NULL,
    `group`       VARCHAR(50)  NOT NULL COMMENT 'municipal_info, contact, system, email_status',
    `updated_by`  INT UNSIGNED NULL DEFAULT NULL,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_system_settings_key` (`key`),
    KEY `idx_system_settings_group` (`group`),
    CONSTRAINT `fk_system_settings_updated_by`
        FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Admin-configurable system settings (Req 20)';


-- ============================================================
-- TABLE: payments
-- Purpose: Payment records for document requests (Phase 2 cash + Phase 3 online demo)
--
-- One row per request (UNIQUE KEY on request_id).
-- payment_method: 'cash' (Phase 2) or 'online' (Phase 3 simulated demo).
-- amount: snapshotted from document_types.processing_fee at
--         selection time — never re-read from document_types.
-- payment_status transitions:
--   Cash path:   pending → paid (Staff confirms physical receipt)
--   Online path: online_pending → paid (auto-confirmed on successful demo)
-- transaction_ref: server-generated demo reference for online payments;
--                  NULL for cash payments.
-- Transitions enforced at application level via conditional
-- UPDATE (WHERE payment_status = ...) — not a FK.
-- request.status is NOT changed by any payment operation.
--
-- Phase 3 upgrade:  database/migration_phase3_online_payment.sql
--   (required only when upgrading a database created before Phase 3)
-- ============================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id`              INT UNSIGNED                              NOT NULL AUTO_INCREMENT,
    `request_id`      INT UNSIGNED                              NOT NULL
                          COMMENT 'FK to requests(id). UNIQUE: one payment per request.',
    `payment_method`  ENUM('cash','online')                    NOT NULL DEFAULT 'cash'
                          COMMENT 'cash = Phase 2 in-office; online = Phase 3 simulated demo.',
    `amount`          DECIMAL(10,2)                             NOT NULL
                          COMMENT 'Fee snapshotted from document_types.processing_fee at selection.',
    `payment_status`  ENUM('pending','paid','online_pending')  NOT NULL DEFAULT 'pending'
                          COMMENT 'pending=cash selected; online_pending=online demo initiated; paid=confirmed.',
    `selected_by`     INT UNSIGNED                              NOT NULL
                          COMMENT 'Resident user_id who submitted the payment choice.',
    `confirmed_by`    INT UNSIGNED                              NULL DEFAULT NULL
                          COMMENT 'Staff user_id who confirmed cash receipt, or system user_id for online demo.',
    `confirmed_at`    DATETIME                                  NULL DEFAULT NULL
                          COMMENT 'Timestamp of payment confirmation.',
    `notes`           VARCHAR(500)                              NULL DEFAULT NULL
                          COMMENT 'Optional Staff note at confirmation (cash only).',
    `transaction_ref` VARCHAR(100)                              NULL DEFAULT NULL
                          COMMENT 'Server-generated demo transaction reference (Phase 3). NULL for cash payments.',
    `created_at`      DATETIME                                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME                                  NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_payments_request_id` (`request_id`),
    KEY `idx_payments_status` (`payment_status`),
    KEY `idx_payments_confirmed_by` (`confirmed_by`),
    CONSTRAINT `fk_payments_request_id`
        FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_selected_by`
        FOREIGN KEY (`selected_by`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_confirmed_by`
        FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Cash and simulated online payment records for document requests (Phase 2 + Phase 3).';
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- End of schema.sql
-- Run database/seeds.sql next.
-- ============================================================
