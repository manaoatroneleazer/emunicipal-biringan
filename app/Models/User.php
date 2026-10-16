<?php

namespace Emunicipal\Models;

/**
 * User model — maps to the `users` table (database.md Section 4.2).
 *
 * Columns: id, role, first_name, last_name, email, password_hash,
 *          contact_number, address, is_active, failed_login_attempts,
 *          locked_until, last_login_at, created_at, updated_at
 *
 * role is ENUM('resident','staff','admin') stored directly on the users table.
 *
 * IMPORTANT: password_hash is NEVER returned in API responses.
 * Use the safe() method when exposing user data externally.
 */
class User extends BaseModel
{
    protected string $table = 'users';

    /**
     * Find a user by email address (used during login).
     * Returns role as $user['role'] (previously $user['role_name']).
     */
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users
             WHERE email = :email
             LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Find an active user by ID.
     * Returns role as $user['role'] (previously $user['role_name']).
     */
    public function findActiveById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users
             WHERE id = :id AND is_active = 1
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Return a user array safe for external use (no password_hash).
     */
    public static function safe(array $user): array
    {
        unset($user['password_hash']);
        return $user;
    }

    /**
     * Check if the account is currently locked (per Req 14.12).
     */
    public function isLocked(array $user): bool
    {
        if (empty($user['locked_until'])) {
            return false;
        }
        return strtotime($user['locked_until']) > time();
    }

    /**
     * Return IDs of all active Staff and Admin users.
     * Used by NotificationService to fan-out operational notifications.
     * (Req 25.1, 25.2)
     *
     * @return int[]
     */
    public function activeStaffAdminIds(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM users
             WHERE role IN ('staff', 'admin')
               AND is_active = 1"
        );
        $stmt->execute();
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'id');
    }
}
