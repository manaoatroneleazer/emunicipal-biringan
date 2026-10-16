<?php

namespace Emunicipal\Models;

/**
 * DocumentRequirement model — maps to `document_requirements` table (database.md Section 4.4).
 *
 * Columns: id, document_type_id, name, description, is_required,
 *          sort_order, is_active, created_by, updated_by, created_at, updated_at
 */
class DocumentRequirement extends BaseModel
{
    protected string $table = 'document_requirements';

    /**
     * Return active requirements for a given document type, in display order.
     * Used by resident request form (per Req 5.7, Req 6.2).
     */
    public function activeForDocumentType(int $documentTypeId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM document_requirements
             WHERE document_type_id = :type_id AND is_active = 1
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([':type_id' => $documentTypeId]);
        return $stmt->fetchAll();
    }

    /**
     * Return ALL requirements (active + inactive) for a document type.
     * Used by admin list (Req 5.7).
     */
    public function allForDocumentType(int $documentTypeId): array
    {
        $stmt = $this->db->prepare(
            "SELECT dr.*,
                    u.first_name AS creator_first_name,
                    u.last_name  AS creator_last_name
             FROM document_requirements dr
             LEFT JOIN users u ON dr.created_by = u.id
             WHERE dr.document_type_id = :type_id
             ORDER BY dr.sort_order ASC, dr.id ASC"
        );
        $stmt->execute([':type_id' => $documentTypeId]);
        return $stmt->fetchAll();
    }

    /**
     * Count active requirements for a document type (for display in admin lists).
     */
    public function countActiveForDocumentType(int $documentTypeId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM document_requirements
             WHERE document_type_id = :type_id AND is_active = 1"
        );
        $stmt->execute([':type_id' => $documentTypeId]);
        return (int) $stmt->fetchColumn();
    }
}
