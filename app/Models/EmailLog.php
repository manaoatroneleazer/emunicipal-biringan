<?php

namespace Emunicipal\Models;

/**
 * EmailLog model — maps to `email_logs` (database.md Section 4.9).
 *
 * Records every outbound email attempt for audit (per Req 10.3, 10.4).
 * Append-only — no credentials are ever stored here.
 * Columns: id, recipient_email, notification_type, reference_number,
 *          subject, status, error_message, created_at
 */
class EmailLog extends BaseModel
{
    protected string $table = 'email_logs';

    public const STATUS_SENT   = 'sent';
    public const STATUS_FAILED = 'failed';
}
