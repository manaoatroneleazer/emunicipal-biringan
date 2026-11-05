# eMunicipal — Payment Method Switching: Production Deployment Guide

> Commit: `9d5a385` — "Add resident payment method switching"
> Branch: `main`

---

## 1. What changed

Residents can now change a pending payment method before it is confirmed:

- **Cash → Online Payment (Demo)**: a "Switch to Online Payment (Demo)" button
  appears under the cash-pending payment card.
- **Online → Cash**: a "Switch to Cash Instead" button appears under the
  online-pending card alongside a "Complete Online Payment" link.
- Switching is blocked once the payment is confirmed (status = `paid`).
- The payment record is updated in place — no duplicate rows are created.

---

## 2. Files deployed by the GitHub → cPanel workflow

The `deploy.yml` workflow (rsync over SSH) deploys automatically on every push
to `main`. The following files were included in commit `9d5a385`:

| File | Change |
|---|---|
| `app/Controllers/Resident/PaymentController.php` | New `switchMethod()` action; updated `simulateOnline()` and `processOnline()` |
| `app/Models/Payment.php` | New `switchMethod()` model method |
| `routes/web.php` | New route: `POST /resident/requests/{id}/payment/switch` |
| `views/resident/requests/show.php` | Switch buttons in pending and online_pending payment card branches |
| `tests/phase12_payment_test.php` | 31 new switch-method test cases |
| `tests/phase13_online_payment_test.php` | 14 new switch-method test cases |
| `.env.example` | Documents Mailtrap Live SMTP settings (no credentials, safe to commit) |

These files are PHP application code and test files only. No schema changes are
bundled in this commit.

---

## 3. Does this feature require a database migration?

**Yes — but only if the production database has not yet received the Phase 3
online payment migration.**

The payment-switching feature (`switchMethod()`) operates on columns added by
`database/migration_phase3_online_payment.sql`:

| Column | Required values |
|---|---|
| `payment_method` ENUM | must include `'online'` (Phase 3 adds this) |
| `payment_status` ENUM | must include `'online_pending'` (Phase 3 adds this) |
| `transaction_ref` | must exist as `VARCHAR(100) NULL` (Phase 3 adds this) |

**If Phase 3 was already applied to production**, no further schema change is
needed. Skip to section 5.

**If Phase 3 has not been applied**, follow section 4 below.

### How to check the current production schema

Run this in phpMyAdmin → SQL on the production database:

```sql
SHOW COLUMNS FROM `payments` LIKE 'payment_method';
SHOW COLUMNS FROM `payments` LIKE 'payment_status';
SHOW COLUMNS FROM `payments` LIKE 'transaction_ref';
```

Expected results after migration is applied:

| Field | Type |
|---|---|
| `payment_method` | `enum('cash','online')` |
| `payment_status` | `enum('pending','paid','online_pending')` |
| `transaction_ref` | `varchar(100)` YES NULL |

If any column shows the pre-migration type (e.g. `enum('cash')` only, or
`transaction_ref` does not exist), the migration must be applied.

---

## 4. Applying the migration on production (if needed)

### Step 1 — Back up the production database first

In cPanel → phpMyAdmin:

1. Select the production database (`emunixyd_biringan` or your actual name).
2. Click **Export** → Quick → Format: SQL → **Go**.
3. Save the `.sql` file to a safe location outside the web root.
4. Verify the download is non-zero in size before continuing.

### Step 2 — Review the migration SQL

The migration is at `database/migration_phase3_online_payment.sql` in the
repository. It contains exactly three `ALTER TABLE` statements:

```sql
-- 1. Add 'online' to payment_method ENUM
ALTER TABLE `payments`
  MODIFY COLUMN `payment_method`
    ENUM('cash','online') NOT NULL DEFAULT 'cash'
    COMMENT 'cash = Phase 2 in-office; online = Phase 3 simulated demo payment.';

-- 2. Add 'online_pending' to payment_status ENUM
ALTER TABLE `payments`
  MODIFY COLUMN `payment_status`
    ENUM('pending','paid','online_pending') NOT NULL DEFAULT 'pending'
    COMMENT 'pending=cash selected; online_pending=online demo initiated; paid=confirmed.';

-- 3. Add transaction_ref column
ALTER TABLE `payments`
  ADD COLUMN `transaction_ref` VARCHAR(100) NULL DEFAULT NULL
    COMMENT 'Server-generated demo transaction reference (Phase 3). NULL for cash payments.'
    AFTER `notes`;
```

**Safety properties:**
- `MODIFY COLUMN` on an ENUM only adds new values — existing `'cash'` rows
  remain valid and unchanged.
- `ADD COLUMN` appends a nullable column — existing rows get `transaction_ref = NULL`,
  which is correct for cash payments.
- No tables are dropped, recreated, or truncated.
- No existing rows are modified or deleted.

### Step 3 — Apply in phpMyAdmin

1. In phpMyAdmin, select the production database.
2. Click the **SQL** tab.
3. Paste and run each statement **one at a time**, verifying the "1 row affected"
   (or "0 rows affected — no error") response before proceeding to the next.
4. Alternatively, copy the full migration file content and run it as a batch.

### Step 4 — Verify after applying

Run the verification query in phpMyAdmin → SQL:

```sql
SHOW COLUMNS FROM `payments` LIKE 'payment_method';
SHOW COLUMNS FROM `payments` LIKE 'payment_status';
SHOW COLUMNS FROM `payments` LIKE 'transaction_ref';
```

Also confirm existing records were not altered:

```sql
SELECT COUNT(*) AS total_payments,
       SUM(payment_status = 'paid')    AS paid_count,
       SUM(payment_status = 'pending') AS pending_count
FROM payments;
```

The counts should match what they were before the migration.

---

## 5. Testing on production

Use a test resident account (not a real user's account).

### 5a. Cash → Online switch

1. Log in as the test resident.
2. Navigate to a request with status **Ready for Claiming** and a non-zero fee.
3. Select **Pay with Cash**. Confirm the payment card shows "Cash — Pending".
4. Click **Switch to Online Payment (Demo)**.
5. Verify the page redirects to the demo payment selection page.
6. Select a provider (GCash, Maya, or Online Banking) and click
   **Simulate Successful Payment**.
7. Confirm the receipt page shows the correct amount and a `TXN-` reference.
8. Return to the request detail page. Confirm the payment card shows "Paid".

### 5b. Online → Cash switch

1. Navigate to a different eligible request (or use one that has no payment yet).
2. Click **Online Payment (Demo)**.
3. On the demo payment page, return to the request detail (do not complete).
   The card should show "Online Payment (Demo) — Pending".
4. Click **Switch to Cash Instead**.
5. Verify the card now shows "Cash — Pending" with the bring-cash instruction.
6. Confirm no duplicate payment rows by checking phpMyAdmin:
   `SELECT COUNT(*) FROM payments WHERE request_id = <your_request_id>;`
   — should return exactly `1`.

### 5c. Paid guard test

1. Find a request with payment status **Paid**.
2. Attempt to navigate directly to:
   `https://emunicipal-biringancity.online/resident/requests/{id}/payment/switch`
   via a manual POST (or simply note there is no switch button in the paid card).
3. Confirm the switch button does not appear on the paid card in the UI.

---

## 6. Verifying existing production records are intact

After deployment, run this read-only query in phpMyAdmin → SQL:

```sql
SELECT
    (SELECT COUNT(*) FROM users)              AS total_users,
    (SELECT COUNT(*) FROM requests)           AS total_requests,
    (SELECT COUNT(*) FROM payments)           AS total_payments,
    (SELECT COUNT(*) FROM notifications)      AS total_notifications,
    (SELECT COUNT(*) FROM audit_logs)         AS total_audit_logs;
```

Compare the counts with your pre-deployment records. None should decrease.

---

## 7. Rollback plan (if migration causes a problem)

The migration is reversible **only while no `online` or `online_pending` rows
exist in production**. If a rollback is needed before any online payments are
recorded, run:

```sql
-- Only safe if no rows with payment_method='online' or payment_status='online_pending' exist
ALTER TABLE `payments`
  MODIFY COLUMN `payment_method` ENUM('cash') NOT NULL DEFAULT 'cash',
  MODIFY COLUMN `payment_status` ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  DROP COLUMN `transaction_ref`;
```

**Do not run the rollback if any online payment rows already exist** — this
would fail due to invalid ENUM values. In that case, revert the PHP files via
the previous GitHub commit instead.

---

## 8. Summary

| Item | Status |
|---|---|
| Commit | `9d5a385` on `main` |
| PHP files to deploy | 4 application files (auto-deployed via `deploy.yml`) |
| DB migration required | Only if Phase 3 not yet applied on production |
| Migration file | `database/migration_phase3_online_payment.sql` |
| Existing records at risk | None — migration is non-destructive |
| Manual production steps | Back up → apply migration → verify → test |
