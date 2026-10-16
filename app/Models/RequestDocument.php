<?php

namespace Emunicipal\Models;

/**
 * RequestDocument model — maps to `request_documents` (database.md Section 4.7).
 *
 * Stores file metadata only — no binary content in MySQL (per Req 18, database.md Section 8).
 * Columns: id, request_id, uploaded_by, original_filename, stored_filename,
 *          storage_path, mime_type, file_size_bytes, created_at
 */
class RequestDocument extends BaseModel
{
    protected string $table = 'request_documents';

    /** Allowed MIME types per Req 18.1 */
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    /** Maximum file size in bytes: 5 MB per Req 18.3 */
    public const MAX_FILE_SIZE_BYTES = 5_242_880;

    /**
     * Return all uploaded documents for a request.
     */
    public function forRequest(int $requestId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM request_documents
             WHERE request_id = :request_id
             ORDER BY created_at ASC"
        );
        $stmt->execute([':request_id' => $requestId]);
        return $stmt->fetchAll();
    }
}
