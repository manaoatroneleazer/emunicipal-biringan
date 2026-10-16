<?php

namespace Emunicipal\Controllers\Api;

use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Validators\Validator;
use Emunicipal\Models\User;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Core\Database;
use Emunicipal\Core\Logger;

/**
 * UserApiController — User resource API.
 *
 * GET    /api/users           — Admin: list all users (paginated, filterable)
 * GET    /api/users/{id}      — Admin or self: get user by ID
 * PUT    /api/users/{id}      — Admin or self: update profile
 * PATCH  /api/users/{id}/status — Admin: toggle active/inactive
 *
 * Per Req 15.1, Req 11 (user management).
 * Per Req 14.4  — password_hash / failed_login_attempts / locked_until never exposed.
 */
class UserApiController extends BaseApiController
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/users
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): void
    {
        $this->requireRole('admin');

        [$page, $perPage] = $this->pagination(20);
        $search = $this->query('search', '');
        $role   = $this->query('role', '');

        try {
            $db    = Database::getInstance();
            $where = ['1=1'];
            $params = [];

            if ($search !== '') {
                $where[]           = "(u.first_name LIKE :s OR u.last_name LIKE :s2 OR u.email LIKE :s3 OR CONCAT(u.first_name,' ',u.last_name) LIKE :s4)";
                $sv                = '%' . $search . '%';
                $params[':s']      = $sv;
                $params[':s2']     = $sv;
                $params[':s3']     = $sv;
                $params[':s4']     = $sv;
            }
            if ($role !== '') {
                $where[]        = 'u.role = :role';
                $params[':role'] = $role;
            }

            $whereStr = implode(' AND ', $where);

            $countStmt = $db->prepare(
                "SELECT COUNT(*) FROM users u WHERE {$whereStr}"
            );
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $offset = ($page - 1) * $perPage;
            $params[':lim'] = $perPage;
            $params[':off'] = $offset;

            $stmt = $db->prepare(
                "SELECT u.id, u.first_name, u.last_name, u.email, u.contact_number,
                        u.address, u.is_active, u.last_login_at, u.created_at,
                        u.role AS role_name
                 FROM users u
                 WHERE {$whereStr}
                 ORDER BY u.id ASC
                 LIMIT :lim OFFSET :off"
            );
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v,
                    in_array($k, [':lim',':off'], true) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
            }
            $stmt->execute();
            $users = $stmt->fetchAll();

            $this->json($this->paginated($users, $total, $page, $perPage));

        } catch (\Throwable $e) {
            Logger::error('UserApiController::index: ' . $e->getMessage());
            $this->error('Failed to load users.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/users/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(string $id): void
    {
        $authId   = $this->requireAuth();
        $authRole = AuthMiddleware::getRole();
        $targetId = (int)$id;

        // Admin can view anyone; others only themselves
        if ($authRole !== 'admin' && $authId !== $targetId) {
            $this->error('Access denied.', 403);
        }

        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT u.id, u.first_name, u.last_name, u.email, u.contact_number,
                        u.address, u.is_active, u.last_login_at, u.created_at,
                        u.role AS role_name
                 FROM users u
                 WHERE u.id = :id LIMIT 1"
            );
            $stmt->execute([':id' => $targetId]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->error('User not found.', 404);
            }

            $this->json($user);

        } catch (\Throwable $e) {
            Logger::error('UserApiController::show: ' . $e->getMessage());
            $this->error('Failed to load user.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/users/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function update(string $id): void
    {
        $authId   = $this->requireAuth();
        $authRole = AuthMiddleware::getRole();
        $targetId = (int)$id;

        if ($authRole !== 'admin' && $authId !== $targetId) {
            $this->error('Access denied.', 403);
        }

        $data = $this->body();
        $v    = new Validator($data);
        $v->required(['first_name','last_name'])
          ->maxLength('first_name', 100)
          ->maxLength('last_name', 100);

        // contact_number and address are optional
        if (!empty($data['contact_number'])) {
            $v->philippinePhone('contact_number');
        }

        if ($v->fails()) {
            $this->error('Validation failed.', 422, $v->errors());
        }

        try {
            $userModel = new User();
            $existing  = $userModel->find($targetId);
            if (!$existing) {
                $this->error('User not found.', 404);
            }

            $updateData = [
                'first_name' => $v->get('first_name'),
                'last_name'  => $v->get('last_name'),
            ];
            if (array_key_exists('contact_number', $data)) {
                $updateData['contact_number'] = $v->get('contact_number') ?: null;
            }
            if (array_key_exists('address', $data)) {
                $updateData['address'] = $v->get('address') ?: null;
            }

            $userModel->update($targetId, $updateData);

            AuditLogService::log(
                $authId, $authRole, AuditLog::ACTION_PASSWORD_CHANGED, 'users',
                'users', $targetId, null, array_keys($updateData),
                "User ID {$targetId} profile updated via API by user ID {$authId}"
            );

            // Refresh from DB — never expose sensitive fields
            $db   = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT u.id, u.first_name, u.last_name, u.email, u.contact_number,
                        u.address, u.is_active, u.last_login_at, u.created_at,
                        u.role AS role_name
                 FROM users u
                 WHERE u.id = :id LIMIT 1"
            );
            $stmt->execute([':id' => $targetId]);
            $updated = $stmt->fetch();

            $this->json($updated, 'Profile updated.');

        } catch (\Throwable $e) {
            Logger::error('UserApiController::update: ' . $e->getMessage());
            $this->error('Failed to update user.', 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PATCH /api/users/{id}/status
    // ─────────────────────────────────────────────────────────────────────────

    public function toggleStatus(string $id): void
    {
        $adminId  = $this->requireRole('admin');
        $targetId = (int)$id;

        // Admin cannot deactivate themselves (Req 11.6)
        if ($adminId === $targetId) {
            $this->error('You cannot change the status of your own account.', 403);
        }

        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT u.id, u.first_name, u.last_name, u.is_active, u.role AS role_name
                 FROM users u
                 WHERE u.id = :id LIMIT 1"
            );
            $stmt->execute([':id' => $targetId]);
            $target = $stmt->fetch();

            if (!$target) {
                $this->error('User not found.', 404);
            }

            // Protect the seeded admin (id=1) from deactivation (Req 11.10)
            if ($target['role_name'] === 'admin' && $targetId === 1) {
                $this->error('The primary Administrator account cannot be deactivated.', 403);
            }

            $newActive = $target['is_active'] ? 0 : 1;
            $verb      = $newActive ? 'reactivated' : 'deactivated';

            $db->prepare("UPDATE users SET is_active = :a WHERE id = :id")
               ->execute([':a' => $newActive, ':id' => $targetId]);

            $action = $newActive ? AuditLog::ACTION_ACCOUNT_REACTIVATED : AuditLog::ACTION_ACCOUNT_DEACTIVATED;
            AuditLogService::log(
                $adminId, 'admin', $action, 'users',
                'users', $targetId,
                ['is_active' => (int)$target['is_active']],
                ['is_active' => $newActive],
                "User ID {$targetId} {$verb} via API by admin ID {$adminId}"
            );

            $this->json(['id' => $targetId, 'is_active' => $newActive],
                "Account {$verb} successfully.");

        } catch (\Throwable $e) {
            Logger::error('UserApiController::toggleStatus: ' . $e->getMessage());
            $this->error('Failed to update account status.', 500);
        }
    }
}
