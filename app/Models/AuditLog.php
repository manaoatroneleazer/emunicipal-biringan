<?php

namespace Emunicipal\Models;

/**
 * AuditLog model — maps to `audit_logs` (database.md Section 4.10).
 *
 * Append-only tamper-evident log of all significant system actions (per Req 13).
 * Columns: id, user_id, user_role, action, module, record_table, record_id,
 *          old_value, new_value, description, ip_address, user_agent, created_at
 */
class AuditLog extends BaseModel
{
    protected string $table = 'audit_logs';

    /** Action constants — match database.md Section 4.10 audited events table */
    public const ACTION_USER_LOGIN          = 'user_login';
    public const ACTION_USER_LOGOUT         = 'user_logout';
    public const ACTION_LOGIN_FAILED        = 'login_failed';
    public const ACTION_ACCOUNT_LOCKED      = 'account_locked';
    public const ACTION_ACCOUNT_CREATED     = 'account_created';
    public const ACTION_ACCOUNT_DEACTIVATED = 'account_deactivated';
    public const ACTION_ACCOUNT_REACTIVATED = 'account_reactivated';
    public const ACTION_PASSWORD_CHANGED    = 'password_changed';
    public const ACTION_DOC_TYPE_CREATED    = 'document_type_created';
    public const ACTION_DOC_TYPE_MODIFIED   = 'document_type_modified';
    public const ACTION_REQ_CREATED         = 'requirement_created';
    public const ACTION_REQ_MODIFIED        = 'requirement_modified';
    public const ACTION_REQUEST_SUBMITTED   = 'request_submitted';
    public const ACTION_STATUS_CHANGED      = 'request_status_changed';
    public const ACTION_FILE_UPLOADED       = 'file_uploaded';
    public const ACTION_FILE_DOWNLOADED     = 'file_downloaded';
    public const ACTION_REPORT_GENERATED    = 'report_generated';
    public const ACTION_SETTING_CHANGED     = 'setting_changed';
    public const ACTION_PAYMENT_RECORDED   = 'payment_recorded';

    /**
     * Insert an audit log entry.
     * Called by AuditLogService — never called directly from controllers.
     */
    public function log(
        ?int    $userId,
        ?string $userRole,
        string  $action,
        string  $module,
        ?string $recordTable = null,
        ?int    $recordId    = null,
        mixed   $oldValue    = null,
        mixed   $newValue    = null,
        ?string $description = null,
        ?string $ipAddress   = null,
        ?string $userAgent   = null
    ): int {
        return $this->insert([
            'user_id'      => $userId,
            'user_role'    => $userRole,
            'action'       => $action,
            'module'       => $module,
            'record_table' => $recordTable,
            'record_id'    => $recordId,
            'old_value'    => $oldValue !== null ? json_encode($oldValue) : null,
            'new_value'    => $newValue !== null ? json_encode($newValue) : null,
            'description'  => $description,
            'ip_address'   => $ipAddress,
            'user_agent'   => $userAgent,
        ]);
    }
}
