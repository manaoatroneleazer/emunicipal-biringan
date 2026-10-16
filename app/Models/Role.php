<?php

namespace Emunicipal\Models;

/**
 * Role model — maps to the `roles` table (database.md Section 4.1).
 *
 * Columns: id, name, label, created_at
 * Seed values: resident | staff | admin
 */
class Role extends BaseModel
{
    protected string $table = 'roles';

    /** Role name constants — match seed values in database.md Section 17.1 */
    public const RESIDENT = 'resident';
    public const STAFF    = 'staff';
    public const ADMIN    = 'admin';

    /**
     * Find a role by its name identifier.
     */
    public function findByName(string $name): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM roles WHERE name = :name LIMIT 1"
        );
        $stmt->execute([':name' => $name]);
        return $stmt->fetch();
    }
}
