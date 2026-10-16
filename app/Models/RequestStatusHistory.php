<?php

namespace Emunicipal\Models;

/**
 * RequestStatusHistory model — maps to `request_status_history` (database.md Section 4.6).
 *
 * Append-only: records are never updated or deleted.
 * Columns: id, request_id, previous_status, new_status, changed_by, remark, created_at
 */
class RequestStatusHistory extends BaseModel
{
    protected string $table = 'request_status_history';

    /**
     * Return full status history for a request in chronological order (per Req 8.2).
     */
    public function forRequest(int $requestId): array
    {
        $stmt = $this->db->prepare(
            "SELECT rsh.*, u.role AS changed_by_role
             FROM request_status_history rsh
             LEFT JOIN users u ON rsh.changed_by = u.id
             WHERE rsh.request_id = :request_id
             ORDER BY rsh.created_at ASC"
        );
        $stmt->execute([':request_id' => $requestId]);
        return $stmt->fetchAll();
    }

    /**
     * Public-safe status history for a request.
     * Returns only status transition timestamps — no staff identity,
     * no remarks, no internal IDs.
     */
    public function publicForRequest(int $requestId): array
    {
        $stmt = $this->db->prepare(
            "SELECT new_status,
                    previous_status,
                    created_at
             FROM request_status_history
             WHERE request_id = :request_id
             ORDER BY created_at ASC"
        );
        $stmt->execute([':request_id' => $requestId]);
        return $stmt->fetchAll();
    }
}
