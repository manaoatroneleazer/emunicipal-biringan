<?php

namespace Emunicipal\Models;

/**
 * DocumentType model — maps to `document_types` table (database.md Section 4.3).
 *
 * Columns: id, name, description, processing_fee, is_active,
 *          created_by, updated_by, created_at, updated_at
 */
class DocumentType extends BaseModel
{
    protected string $table = 'document_types';

    /**
     * Return only active document types for display on the resident request form.
     * (per Req 5.9, Req 6.1)
     */
    public function allActive(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM document_types WHERE is_active = 1 ORDER BY name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Return all document types (active + inactive) for admin list.
     * Includes active requirement count per type.
     */
    public function allWithRequirementCount(): array
    {
        $stmt = $this->db->prepare(
            "SELECT dt.*,
                    u.first_name AS creator_first_name,
                    u.last_name  AS creator_last_name,
                    (
                        SELECT COUNT(*)
                        FROM document_requirements dr
                        WHERE dr.document_type_id = dt.id AND dr.is_active = 1
                    ) AS active_requirement_count
             FROM document_types dt
             LEFT JOIN users u ON dt.created_by = u.id
             ORDER BY dt.name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Find a document type by its name (for uniqueness check).
     * @param int|null $excludeId — exclude this ID when checking (used for edits)
     */
    public function findByName(string $name, ?int $excludeId = null): array|false
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare(
                "SELECT id FROM document_types WHERE name = :name AND id != :exclude LIMIT 1"
            );
            $stmt->execute([':name' => $name, ':exclude' => $excludeId]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT id FROM document_types WHERE name = :name LIMIT 1"
            );
            $stmt->execute([':name' => $name]);
        }
        return $stmt->fetch();
    }
}
