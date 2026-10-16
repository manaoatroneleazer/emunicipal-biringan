<?php

namespace Emunicipal\Models;

use PDO;

/**
 * Request model — maps to the `requests` table (database.md Section 4.5).
 *
 * Columns: id, reference_number, user_id, document_type_id, purpose,
 *          copies_requested, status, assigned_staff_id, rejection_reason,
 *          cancellation_reason, cancelled_by, created_at, updated_at
 *
 * Status values (ENUM — per database.md Section 6.1):
 *   pending | under_verification | additional_info_required |
 *   approved | rejected | ready_for_claiming | completed | cancelled
 */
class Request extends BaseModel
{
    protected string $table = 'requests';

    /** Valid status values — must match the ENUM in database.md */
    public const STATUS_PENDING                  = 'pending';
    public const STATUS_UNDER_VERIFICATION       = 'under_verification';
    public const STATUS_ADDITIONAL_INFO_REQUIRED = 'additional_info_required';
    public const STATUS_APPROVED                 = 'approved';
    public const STATUS_REJECTED                 = 'rejected';
    public const STATUS_READY_FOR_CLAIMING       = 'ready_for_claiming';
    public const STATUS_COMPLETED                = 'completed';
    public const STATUS_CANCELLED               = 'cancelled';

    /**
     * Terminal statuses — no further status changes allowed (per Req 7.8).
     */
    public const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Statuses a Resident may cancel (per Req 7.1 table).
     */
    public const RESIDENT_CANCELLABLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ADDITIONAL_INFO_REQUIRED,
    ];

    /**
     * Find a request by its reference number.
     */
    public function findByReferenceNumber(string $referenceNumber): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.first_name, u.last_name, u.email,
                    dt.name AS document_type_name
             FROM requests r
             JOIN users u ON r.user_id = u.id
             JOIN document_types dt ON r.document_type_id = dt.id
             WHERE r.reference_number = :ref
             LIMIT 1"
        );
        $stmt->execute([':ref' => $referenceNumber]);
        return $stmt->fetch();
    }

    /**
     * Public-safe request lookup by reference number.
     * Returns only fields safe for unauthenticated public display.
     * Does NOT join users table — no resident PII returned.
     * The internal `id` is included solely to retrieve status history
     * and must never be rendered in the view.
     */
    public function findPublicByReferenceNumber(string $referenceNumber): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT r.id,
                    r.reference_number,
                    r.status,
                    r.created_at,
                    r.updated_at,
                    dt.name AS document_type_name
             FROM requests r
             JOIN document_types dt ON r.document_type_id = dt.id
             WHERE r.reference_number = :ref
             LIMIT 1"
        );
        $stmt->execute([':ref' => $referenceNumber]);
        return $stmt->fetch();
    }

    /**
     * Find all requests submitted by a specific resident (per Req 8.1).
     */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, dt.name AS document_type_name
             FROM requests r
             JOIN document_types dt ON r.document_type_id = dt.id
             WHERE r.user_id = :user_id
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /** Total count from last findAllForStaff() call — populated by pagination logic. */
    public int $lastTotalCount = 0;

    /**
     * Find all requests for Staff/Admin list view (Req 9.1–9.2, Req 24).
     * Joins users and document_types so the list can show resident name and document name.
     * Optional filters: status, document_type_id, date_from, date_to, search (ref, name,
     *   email, or contact number — Req 24.1), page, per_page.
     */
    public function findAllForStaff(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]           = 'r.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['document_type_id'])) {
            $where[]          = 'r.document_type_id = :dt_id';
            $params[':dt_id'] = (int)$filters['document_type_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[]              = 'DATE(r.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]            = 'DATE(r.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            // Distinct PDO named parameters — PDO requires unique parameter names (Req 24.7)
            $searchVal = '%' . $filters['search'] . '%';
            $where[] = '(r.reference_number                              LIKE :search_ref
                         OR u.first_name                                 LIKE :search_first
                         OR u.last_name                                  LIKE :search_last
                         OR CONCAT(u.first_name, \' \', u.last_name)    LIKE :search_full
                         OR u.email                                      LIKE :search_email
                         OR u.contact_number                             LIKE :search_contact)';
            $params[':search_ref']     = $searchVal;
            $params[':search_first']   = $searchVal;
            $params[':search_last']    = $searchVal;
            $params[':search_full']    = $searchVal;
            $params[':search_email']   = $searchVal;
            $params[':search_contact'] = $searchVal;
        }

        // Pagination (Req 24.6)
        $page    = max(1, (int)($filters['page']     ?? 1));
        $perPage = max(1, (int)($filters['per_page'] ?? 20));
        $offset  = ($page - 1) * $perPage;

        // Total count (preserves filter params, excludes LIMIT/OFFSET)
        $countSql = "SELECT COUNT(*)
                     FROM requests r
                     JOIN users u           ON r.user_id          = u.id
                     JOIN document_types dt ON r.document_type_id = dt.id
                     WHERE " . implode(' AND ', $where);
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $this->lastTotalCount = (int) $countStmt->fetchColumn();

        $sql = "SELECT r.*,
                       u.first_name, u.last_name, u.email,
                       dt.name AS document_type_name,
                       s.first_name AS staff_first_name,
                       s.last_name  AS staff_last_name
                FROM requests r
                JOIN users u           ON r.user_id           = u.id
                JOIN document_types dt ON r.document_type_id  = dt.id
                LEFT JOIN users s      ON r.assigned_staff_id = s.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";

        $params[':limit']  = $perPage;
        $params[':offset'] = $offset;

        $stmt = $this->db->prepare($sql);
        // Bind LIMIT/OFFSET as integers — required by MySQL strict mode (Req 14.2)
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, \PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Find a single request by ID for Staff/Admin detail view (Req 9.3).
     * Joins resident info and document type name.
     * Returns false if not found.
     */
    public function findByIdForStaff(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT r.*,
                    u.first_name, u.last_name, u.email,
                    u.contact_number, u.address,
                    dt.name AS document_type_name
             FROM requests r
             JOIN users u  ON r.user_id          = u.id
             JOIN document_types dt ON r.document_type_id = dt.id
             WHERE r.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Allowed status transitions for Staff (Req 7.1 table).
     * Returns the list of statuses that are valid next states from $currentStatus.
     */
    public static function allowedTransitions(string $currentStatus): array
    {
        return match($currentStatus) {
            self::STATUS_PENDING                  => [self::STATUS_UNDER_VERIFICATION, self::STATUS_CANCELLED],
            self::STATUS_UNDER_VERIFICATION       => [
                self::STATUS_APPROVED,
                self::STATUS_ADDITIONAL_INFO_REQUIRED,
                self::STATUS_REJECTED,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_ADDITIONAL_INFO_REQUIRED => [
                self::STATUS_APPROVED,
                self::STATUS_REJECTED,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_APPROVED                 => [self::STATUS_READY_FOR_CLAIMING],
            self::STATUS_READY_FOR_CLAIMING       => [self::STATUS_COMPLETED],
            default                               => [],   // terminal: completed, rejected, cancelled
        };
    }

    /**
     * Generate the next reference number for the current year and insert the request,
     *
     * Format: EMU-YYYY-NNNNNN  (e.g. EMU-2026-000001)
     * Per database.md §6.2 and Req 6.9.
     *
     * @param array $data Request data (without reference_number or status — these are set here)
     * @param array $historyData Data for the initial request_status_history row
     * @return array{id: int, reference_number: string}
     * @throws \RuntimeException on failure (caller should catch and roll back outer context if needed)
     */
    public function insertWithReferenceNumber(array $data, array $historyData): array
    {
        $year   = date('Y');
        $prefix = "EMU-{$year}-";

        $this->db->beginTransaction();
        try {
            // Lock the latest row for this year to prevent concurrent duplicates (database.md §6.2)
            $lockStmt = $this->db->prepare(
                "SELECT reference_number
                 FROM requests
                 WHERE reference_number LIKE :pattern
                 ORDER BY reference_number DESC
                 LIMIT 1
                 FOR UPDATE"
            );
            $lockStmt->execute([':pattern' => $prefix . '%']);
            $latest = $lockStmt->fetchColumn();

            if ($latest) {
                // Extract the 6-digit sequence and increment
                $seq = (int) substr($latest, strlen($prefix)) + 1;
            } else {
                $seq = 1;
            }

            $referenceNumber = $prefix . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);

            // Insert the request
            $data['reference_number'] = $referenceNumber;
            $data['status']           = self::STATUS_PENDING;

            $columns      = array_keys($data);
            $placeholders = array_map(fn($c) => ":{$c}", $columns);
            $sql = sprintf(
                "INSERT INTO `requests` (%s) VALUES (%s)",
                implode(', ', array_map(fn($c) => "`{$c}`", $columns)),
                implode(', ', $placeholders)
            );
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            $requestId = (int) $this->db->lastInsertId();

            // Insert initial PENDING status history row (changed_by = NULL — system)
            $historyData['request_id']  = $requestId;
            $historyData['new_status']  = self::STATUS_PENDING;
            // previous_status is NULL for the initial entry
            $hCols  = array_keys($historyData);
            $hPhs   = array_map(fn($c) => ":{$c}", $hCols);
            $hSql   = sprintf(
                "INSERT INTO `request_status_history` (%s) VALUES (%s)",
                implode(', ', array_map(fn($c) => "`{$c}`", $hCols)),
                implode(', ', $hPhs)
            );
            $hStmt = $this->db->prepare($hSql);
            $hStmt->execute($historyData);

            $this->db->commit();

            return ['id' => $requestId, 'reference_number' => $referenceNumber];

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
