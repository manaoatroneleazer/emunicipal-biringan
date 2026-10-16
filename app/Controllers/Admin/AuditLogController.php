<?php

namespace Emunicipal\Controllers\Admin;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Models\AuditLog;
use Emunicipal\Core\Database;
use Emunicipal\Core\Logger;

/**
 * AuditLogController — Admin Audit Log Viewer.
 *
 * Implements Req 12.4, 12.6, 13:
 *  12.4 — Audit log viewing with filtering by user, action type, and date range
 *  12.5 — Default page size 20 records per page
 *  12.6 — Each entry displays: timestamp, acting user, action type,
 *          affected record identifier, and IP address
 *  13.3 — No user role (including admin) may delete or modify audit log entries
 *         through the application interface
 *
 * Route:  GET /admin/audit-logs
 *
 * Security:
 *  - Admin-only (AuthMiddleware::requireRole('admin'))
 *  - All filter inputs validated/sanitised before use in SQL
 *  - All SQL uses PDO prepared statements (Req 14.2)
 *  - Read-only: no POST/PUT/PATCH/DELETE endpoints exist for audit logs
 *  - No sensitive fields (password_hash, etc.) are selected or exposed
 */
class AuditLogController extends BaseController
{
    private const DEFAULT_PER_PAGE = 20;

    /** All valid action values drawn from AuditLog constants — used for filter whitelist. */
    private const VALID_ACTIONS = [
        AuditLog::ACTION_USER_LOGIN,
        AuditLog::ACTION_USER_LOGOUT,
        AuditLog::ACTION_LOGIN_FAILED,
        AuditLog::ACTION_ACCOUNT_LOCKED,
        AuditLog::ACTION_ACCOUNT_CREATED,
        AuditLog::ACTION_ACCOUNT_DEACTIVATED,
        AuditLog::ACTION_ACCOUNT_REACTIVATED,
        AuditLog::ACTION_PASSWORD_CHANGED,
        AuditLog::ACTION_DOC_TYPE_CREATED,
        AuditLog::ACTION_DOC_TYPE_MODIFIED,
        AuditLog::ACTION_REQ_CREATED,
        AuditLog::ACTION_REQ_MODIFIED,
        AuditLog::ACTION_REQUEST_SUBMITTED,
        AuditLog::ACTION_STATUS_CHANGED,
        AuditLog::ACTION_FILE_UPLOADED,
        AuditLog::ACTION_FILE_DOWNLOADED,
        AuditLog::ACTION_REPORT_GENERATED,
        AuditLog::ACTION_SETTING_CHANGED,
    ];

    // -------------------------------------------------------------------------
    // GET /admin/audit-logs
    // -------------------------------------------------------------------------

    public function index(): void
    {
        AuthMiddleware::requireRole('admin');

        $filters = $this->collectFilters();
        $page    = max(1, (int)($filters['page'] ?? 1));
        // Use configured records_per_page (Req 20) — fall back to constant default
        $perPage = \Emunicipal\Services\SettingsService::recordsPerPage() ?: self::DEFAULT_PER_PAGE;

        $logs       = [];
        $totalRows  = 0;
        $totalPages = 1;

        try {
            [$logs, $totalRows] = $this->fetchLogs($filters, $page, $perPage);
            $totalPages = $perPage > 0 ? (int) ceil($totalRows / $perPage) : 1;
        } catch (\Throwable $e) {
            Logger::error('AuditLogController::index query failed: ' . $e->getMessage());
        }

        // Load user list for the actor filter dropdown (id + name, active & inactive)
        $userList = [];
        try {
            $db   = Database::getInstance();
            $stmt = $db->query(
                "SELECT u.id, u.first_name, u.last_name, u.role AS role_name
                 FROM users u
                 ORDER BY u.last_name ASC, u.first_name ASC"
            );
            $userList = $stmt->fetchAll();
        } catch (\Throwable $e) {
            Logger::error('AuditLogController: user list load failed: ' . $e->getMessage());
        }

        $this->renderWithLayout('admin/audit-logs/index', [
            'title'       => 'Audit Logs — eMunicipal',
            'activePage'  => 'audit-logs',
            'filters'     => $filters,
            'logs'        => $logs,
            'totalRows'   => $totalRows,
            'totalPages'  => $totalPages,
            'currentPage' => $page,
            'perPage'     => $perPage,
            'userList'    => $userList,
            'validActions' => self::VALID_ACTIONS,
        ], 'layouts/auth');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Read and sanitise filter values from $_GET.
     * Returns a flat array; no raw user input is passed through untouched.
     */
    private function collectFilters(): array
    {
        // Actor: must be a positive integer (user ID) or 0 for "all"
        $userId   = max(0, (int)($_GET['user_id']  ?? 0));

        // Action: must be in the whitelist of known AuditLog constants
        $action   = trim($_GET['action'] ?? '');
        if (!in_array($action, self::VALID_ACTIONS, true)) {
            $action = '';
        }

        // Dates: YYYY-MM-DD format, validated by checkdate
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo   = trim($_GET['date_to']   ?? '');
        if ($dateFrom !== '' && !$this->isValidDate($dateFrom)) $dateFrom = '';
        if ($dateTo   !== '' && !$this->isValidDate($dateTo))   $dateTo   = '';
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $page = max(1, (int)($_GET['page'] ?? 1));

        return [
            'user_id'   => $userId,
            'action'    => $action,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'page'      => $page,
        ];
    }

    /**
     * Execute the paginated audit log query.
     * Returns [rows, total].
     */
    private function fetchLogs(array $filters, int $page, int $perPage): array
    {
        $db     = Database::getInstance();
        $where  = ['1=1'];
        $params = [];

        if ($filters['user_id'] > 0) {
            $where[]          = 'al.user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }
        if ($filters['action'] !== '') {
            $where[]          = 'al.action = :action';
            $params[':action'] = $filters['action'];
        }
        if ($filters['date_from'] !== '') {
            $where[]              = 'DATE(al.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if ($filters['date_to'] !== '') {
            $where[]            = 'DATE(al.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $whereStr = implode(' AND ', $where);

        // Total count
        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM audit_logs al WHERE {$whereStr}"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Paginated rows — JOIN users to show actor name (Req 12.6)
        $offset = ($page - 1) * $perPage;
        $params[':lim'] = $perPage;
        $params[':off'] = $offset;

        $stmt = $db->prepare(
            "SELECT
                 al.id,
                 al.user_id,
                 al.user_role,
                 al.action,
                 al.module,
                 al.record_table,
                 al.record_id,
                 al.old_value,
                 al.new_value,
                 al.description,
                 al.ip_address,
                 al.created_at,
                 u.first_name,
                 u.last_name
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE {$whereStr}
             ORDER BY al.created_at DESC, al.id DESC
             LIMIT :lim OFFSET :off"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(
                $key, $value,
                in_array($key, [':lim', ':off'], true) ? \PDO::PARAM_INT : \PDO::PARAM_STR
            );
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return [$rows, $total];
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
        [$y, $m, $d] = explode('-', $date);
        return checkdate((int)$m, (int)$d, (int)$y);
    }
}
