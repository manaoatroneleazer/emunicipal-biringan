-- ============================================================
-- eMunicipal — Migration: Phase 3 Simulated Online Payment
--
-- Extends the `payments` table created in Phase 2.
-- Safe to inspect before applying; do NOT auto-execute.
--
-- Compatibility:
--   - All three statements are non-destructive.
--   - MODIFY COLUMN on an ENUM only adds new values; existing
--     'cash' rows remain valid and unchanged.
--   - ADD COLUMN appends a nullable column; existing rows
--     will have transaction_ref = NULL (correct for cash).
--   - No FK, index, or constraint is removed.
--   - Existing payment records are fully preserved.
--
-- Apply with:
--   mysql -h 127.0.0.1 -P 3306 -u root emunicipal
--     < database/migration_phase3_online_payment.sql
--
-- Rollback (only if no online payment rows exist yet):
--   ALTER TABLE `payments`
--     MODIFY COLUMN `payment_method`  ENUM('cash') NOT NULL DEFAULT 'cash',
--     MODIFY COLUMN `payment_status`  ENUM('pending','paid') NOT NULL DEFAULT 'pending',
--     DROP COLUMN `transaction_ref`;
-- ============================================================

SET NAMES utf8mb4;

-- 1. Add 'online' to payment_method ENUM
--    Existing 'cash' rows remain valid; 'cash' stays the default.
ALTER TABLE `payments`
  MODIFY COLUMN `payment_method`
    ENUM('cash','online') NOT NULL DEFAULT 'cash'
    COMMENT 'cash = Phase 2 in-office; online = Phase 3 simulated demo payment.';

-- 2. Add 'online_pending' to payment_status ENUM
--    'online_pending' = resident initiated demo online payment, not yet confirmed.
--    'pending'        = cash selected (Phase 2 behaviour, unchanged).
--    'paid'           = confirmed by Staff (cash) or auto-confirmed (online demo).
--    Existing 'pending' and 'paid' rows are unaffected.
ALTER TABLE `payments`
  MODIFY COLUMN `payment_status`
    ENUM('pending','paid','online_pending') NOT NULL DEFAULT 'pending'
    COMMENT 'pending=cash selected; online_pending=online demo initiated; paid=confirmed.';

-- 3. Add transaction_ref column for server-generated demo transaction references.
--    NULL for cash payments (existing and future).
--    Populated by the server (never accepted from client input).
ALTER TABLE `payments`
  ADD COLUMN `transaction_ref` VARCHAR(100) NULL DEFAULT NULL
    COMMENT 'Server-generated demo transaction reference (Phase 3). NULL for cash payments.'
    AFTER `notes`;
