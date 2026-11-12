<?php

namespace Emunicipal\Controllers\Admin;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Services\SettingsService;
use Emunicipal\Core\Database;
use Emunicipal\Core\Logger;

/**
 * AdminPaymentController — read-only payment history for Admins.
 *
 * Security:
 *  - Admin-only (AuthMiddleware::requireRole('admin'))
 *  - All filter values validated / cast server-side
 *  - All SQL uses PDO prepared statements
 *  - No password_hash, session tokens, or SMTP credentials exposed
 *
 * Routes:
 *  GET /admin/payments
 */
class AdminPaymentController extends BaseController
{
    private const VALID_METHODS  = ['cash', 'online'];
    private const VALID_STATUSES = ['pending', 'paid', 'online_pending'];

    // -------------------------------------------------------------------------
    // GET /admin/payments
    // -------------------------------------------------------------------------

    public function index(): void
    {
        AuthMiddleware::requireRole('admin');

        $filters = $this->collectFilters();
        $page    = max(1, (int)($filters['page'] ?? 1));
        $perPage = SettingsService::recordsPerPage() ?: 20;

        $rows      = [];
        $totalRows = 0;

        try {
            [$rows, $totalRows] = $this->fetchPayments($filters, $page, $perPage);
        } catch (\Throwable $e) {
            Logger::error('AdminPaymentController index: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load payment records. Please try again.');
        }

        $totalPages = $perPage > 0 ? (int) ceil($totalRows / $perPage) : 1;

        $this->renderWithLayout('admin/payments/index', [
            'title'       => 'Payment History — eMunicipal',
            'activePage'  => 'payments',
            'filters'     => $filters,
            'rows'        => $rows,
            'totalRows'   => $totalRows,
            'totalPages'  => $totalPages,
            'currentPage' => $page,
            'perPage'     => $perPage,
        ], 'layouts/auth');
    }

    // -------------------------------------------------------------------------
    // Private — filter collection
    // -------------------------------------------------------------------------

    private function collectFilters(): array
    {
        $search    = trim($_GET['search']    ?? '');
        $method    = trim($_GET['method']    ?? '');
        $status    = trim($_GET['status']    ?? '');
        $dateFrom  = trim($_GET['date_from'] ?? '');
        $dateTo    = trim($_GET['date_to']   ?? '');
        $page      = max(1, (int)($_GET['page'] ?? 1));

        // Whitelist method and status
        if (!in_array($method, self::VALID_METHODS, true))  $method  = '';
        if (!in_array($status, self::VALID_STATUSES, true)) $status  = '';

        // Validate date format YYYY-MM-DD
        if ($dateFrom !== '' && !$this->isValidDate($dateFrom)) $dateFrom = '';
        if ($dateTo   !== '' && !$this->isValidDate($dateTo))   $dateTo   = '';
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // Limit search string to prevent abuse
        if (strlen($search) > 100) $search = substr($search, 0, 100);

        return compact('search', 'method', 'status', 'dateFrom', 'dateTo', 'page');
    }

    // -------------------------------------------------------------------------
    // Private — paginated query
    // -------------------------------------------------------------------------

    /**
     * Returns [rows, totalCount].
     *
     * Joins:
     *  payments p
     *    → requests r         (reference_number, document_type_id)
     *    → users res          (resident first/last name)
     *    → document_types dt  (document type name)
     *    → users conf         (confirmed-by name, nullable)
     *
     * No password_hash or sensitive columns are selected.
     */
    private function fetchPayments(array $f, int $page, int $perPage): array
    {
        $db     = Database::getInstance();
        $where  = ['1=1'];
        $params = [];

        if ($f['search'] !== '') {
            $where[]           = '(p.transaction_ref LIKE :search OR r.reference_number LIKE :search)';
            $params[':search'] = '%' . $f['search'] . '%';
        }
        if ($f['method'] !== '') {
            $where[]           = 'p.payment_method = :method';
            $params[':method'] = $f['method'];
        }
        if ($f['status'] !== '') {
            $where[]           = 'p.payment_status = :status';
            $params[':status'] = $f['status'];
        }
        if ($f['dateFrom'] !== '') {
            $where[]              = 'DATE(p.created_at) >= :date_from';
            $params[':date_from'] = $f['dateFrom'];
        }
        if ($f['dateTo'] !== '') {
            $where[]            = 'DATE(p.created_at) <= :date_to';
            $params[':date_to'] = $f['dateTo'];
        }

        $whereStr = implode(' AND ', $where);

        // COUNT
        $countSql = "SELECT COUNT(*)
                     FROM payments p
                     JOIN requests r       ON p.request_id  = r.id
                     JOIN users res        ON r.user_id      = res.id
                     JOIN document_types dt ON r.document_type_id = dt.id
                     WHERE {$whereStr}";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // DATA
        $offset = ($page - 1) * $perPage;
        $dataSql = "SELECT
                        p.id                AS payment_id,
                        p.request_id,
                        p.payment_method,
                        p.payment_status,
                        p.amount,
                        p.transaction_ref,
                        p.confirmed_at,
                        p.notes,
                        p.created_at        AS payment_date,
                        r.reference_number,
                        res.first_name      AS resident_first,
                        res.last_name       AS resident_last,
                        dt.name             AS document_type_name,
                        conf.first_name     AS confirmed_first,
                        conf.last_name      AS confirmed_last
                    FROM payments p
                    JOIN requests r         ON p.request_id         = r.id
                    JOIN users res          ON r.user_id             = res.id
                    JOIN document_types dt  ON r.document_type_id    = dt.id
                    LEFT JOIN users conf    ON p.confirmed_by        = conf.id
                    WHERE {$whereStr}
                    ORDER BY p.created_at DESC
                    LIMIT :lim OFFSET :off";

        $stmt = $db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, \PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset,  \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [$rows, $total];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
        [$y, $m, $d] = explode('-', $date);
        return checkdate((int)$m, (int)$d, (int)$y);
    }
}
