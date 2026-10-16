<?php

namespace Emunicipal\Controllers\Admin;

use Emunicipal\Controllers\BaseController;
use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Validators\Validator;
use Emunicipal\Core\Database;
use Emunicipal\Core\Logger;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Services\SettingsService;

/**
 * UserController — Admin User Management.
 *
 * Implements Req 11:
 *  11.1  — List all user accounts
 *  11.2  — Create Staff accounts (first name, last name, email, password)
 *  11.3  — Hash password with password_hash()
 *  11.4  — Deactivate Resident or Staff (preserves data)
 *  11.5  — Reactivate previously deactivated account
 *  11.6  — Admin cannot deactivate their own account
 *  11.7  — Audit log for create/deactivate/reactivate
 *  11.8  — Search users by name or email
 *  11.9  — No deletion of users who have requests
 *  11.10 — At least one active Administrator always exists;
 *          deactivating the last active Admin is rejected server-side
 *  12.5  — Default 20 records per page (via SettingsService)
 */
class UserController extends BaseController
{
    // -------------------------------------------------------------------------
    // LIST — with search, role filter, and pagination (Req 11.1, 11.8, 12.5)
    // -------------------------------------------------------------------------

    /** GET /admin/users */
    public function index(): void
    {
        AuthMiddleware::requireRole('admin');

        $search     = trim($_GET['search'] ?? '');
        $roleFilter = trim($_GET['role']   ?? '');
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $perPage    = SettingsService::recordsPerPage();
        $offset     = ($page - 1) * $perPage;

        $users      = [];
        $totalUsers = 0;

        try {
            $db     = Database::getInstance();
            $where  = ['1=1'];
            $params = [];

            if ($search !== '') {
                $sv              = '%' . $search . '%';
                $where[]         = "(u.first_name LIKE :s1 OR u.last_name LIKE :s2 OR u.email LIKE :s3 OR CONCAT(u.first_name,' ',u.last_name) LIKE :s4)";
                $params[':s1']   = $sv;
                $params[':s2']   = $sv;
                $params[':s3']   = $sv;
                $params[':s4']   = $sv;
            }
            if (in_array($roleFilter, ['resident', 'staff', 'admin'], true)) {
                $where[]         = 'u.role = :role';
                $params[':role'] = $roleFilter;
            }

            $whereStr = implode(' AND ', $where);

            $countStmt = $db->prepare(
                "SELECT COUNT(*) FROM users u WHERE {$whereStr}"
            );
            $countStmt->execute($params);
            $totalUsers = (int)$countStmt->fetchColumn();

            $params[':lim'] = $perPage;
            $params[':off'] = $offset;

            $stmt = $db->prepare(
                "SELECT u.id, u.first_name, u.last_name, u.email,
                        u.contact_number, u.is_active, u.created_at, u.last_login_at,
                        u.role AS role_name
                 FROM users u
                 WHERE {$whereStr}
                 ORDER BY u.id ASC
                 LIMIT :lim OFFSET :off"
            );
            foreach ($params as $key => $value) {
                $stmt->bindValue(
                    $key, $value,
                    in_array($key, [':lim', ':off'], true) ? \PDO::PARAM_INT : \PDO::PARAM_STR
                );
            }
            $stmt->execute();
            $users = $stmt->fetchAll();

        } catch (\Throwable $e) {
            Logger::error('UserController index error: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to load users. Please try again.');
        }

        $totalPages = $perPage > 0 ? (int) ceil($totalUsers / $perPage) : 1;

        $this->renderWithLayout('admin/users/index', [
            'title'       => 'Users — eMunicipal',
            'activePage'  => 'users',
            'users'       => $users,
            'search'      => $search,
            'roleFilter'  => $roleFilter,
            'totalUsers'  => $totalUsers,
            'totalPages'  => $totalPages,
            'currentPage' => $page,
            'perPage'     => $perPage,
            'csrfToken'   => SecurityHelper::getCsrfToken(),
        ], 'layouts/auth');
    }

    // -------------------------------------------------------------------------
    // SHOW — read-only detail (Req 11)
    // -------------------------------------------------------------------------

    /** GET /admin/users/{id} */
    public function show(string $id): void
    {
        AuthMiddleware::requireRole('admin');

        $user = null;
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT u.id, u.first_name, u.last_name, u.email, u.contact_number,
                        u.address, u.is_active, u.created_at, u.last_login_at,
                        u.role AS role_name
                 FROM users u
                 WHERE u.id = :id LIMIT 1"
            );
            $stmt->execute([':id' => (int)$id]);
            $user = $stmt->fetch() ?: null;
        } catch (\Throwable $e) {
            Logger::error('UserController show error: ' . $e->getMessage());
        }

        if (!$user) {
            SessionHelper::flash('error', 'User not found.');
            UrlHelper::redirect('/admin/users');
        }

        $this->renderWithLayout('admin/users/show', [
            'title'      => 'User Details — eMunicipal',
            'activePage' => 'users',
            'user'       => $user,
            'csrfToken'  => SecurityHelper::getCsrfToken(),
        ], 'layouts/auth');
    }

    // -------------------------------------------------------------------------
    // TOGGLE ACTIVE (Req 11.4–11.7, 11.10)
    // -------------------------------------------------------------------------

    /** POST /admin/users/{id}/toggle */
    public function toggleActive(string $id): void
    {
        AuthMiddleware::requireRole('admin');

        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('error', 'Invalid security token.');
            UrlHelper::redirect('/admin/users');
        }

        $targetId = (int)$id;
        $adminId  = AuthMiddleware::userId();

        // Prevent admin deactivating their own account (Req 11.6) — server-side
        if ($targetId === $adminId) {
            SessionHelper::flash('error', 'You cannot deactivate your own account.');
            UrlHelper::redirect('/admin/users');
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
                SessionHelper::flash('error', 'User not found.');
                UrlHelper::redirect('/admin/users');
            }

            $newActive = $target['is_active'] ? 0 : 1;

            // Req 11.10: "at least one Administrator must always exist"
            // If we are deactivating an admin, ensure another active admin remains.
            if ($target['role_name'] === 'admin' && $newActive === 0) {
                $activeAdminCount = (int)$db->query(
                    "SELECT COUNT(*) FROM users
                     WHERE role = 'admin' AND is_active = 1"
                )->fetchColumn();

                if ($activeAdminCount <= 1) {
                    SessionHelper::flash('error',
                        'Cannot deactivate this Administrator account — at least one active Administrator must remain.');
                    UrlHelper::redirect('/admin/users');
                }
            }

            $verb = $newActive ? 'reactivated' : 'deactivated';

            $upd = $db->prepare("UPDATE users SET is_active = :active WHERE id = :id");
            $upd->execute([':active' => $newActive, ':id' => $targetId]);

            $action = $newActive ? AuditLog::ACTION_ACCOUNT_REACTIVATED : AuditLog::ACTION_ACCOUNT_DEACTIVATED;
            AuditLogService::log(
                $adminId, 'admin', $action, 'users',
                'users', $targetId,
                ['is_active' => (int)$target['is_active']],
                ['is_active' => $newActive],
                "User '{$target['first_name']} {$target['last_name']}' {$verb} by admin ID {$adminId}"
            );

            $name = htmlspecialchars($target['first_name'] . ' ' . $target['last_name'], ENT_QUOTES, 'UTF-8');
            SessionHelper::flash('success', "Account for {$name} has been {$verb}.");

        } catch (\Throwable $e) {
            Logger::error('UserController toggleActive error: ' . $e->getMessage());
            SessionHelper::flash('error', 'Unable to change account status. Please try again.');
        }

        UrlHelper::redirect('/admin/users');
    }

    // -------------------------------------------------------------------------
    // CREATE STAFF ACCOUNT (Req 11.2–11.3, 11.7)
    // -------------------------------------------------------------------------

    /** GET /admin/users/create */
    public function create(): void
    {
        AuthMiddleware::requireRole('admin');

        $this->renderWithLayout('admin/users/create', [
            'title'      => 'Create Staff Account — eMunicipal',
            'activePage' => 'users',
            'csrfToken'  => SecurityHelper::getCsrfToken(),
            'errors'     => SessionHelper::getFlash('user_errors', []),
            'old'        => SessionHelper::getFlash('user_old', []),
        ], 'layouts/auth');
    }

    /** POST /admin/users */
    public function store(): void
    {
        AuthMiddleware::requireRole('admin');

        // CSRF (Req 14.3)
        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            SessionHelper::flash('user_errors', ['form' => 'Invalid security token. Please try again.']);
            UrlHelper::redirect('/admin/users/create');
        }

        $v = new Validator($_POST);
        $v->required(['first_name', 'last_name', 'email', 'password', 'password_confirmation'])
          ->email('email')
          ->maxLength('first_name', 100)
          ->maxLength('last_name', 100)
          ->maxLength('email', 255)
          ->passwordStrength('password')
          ->matches('password_confirmation', 'password');

        $old = [
            'first_name' => $v->get('first_name', ''),
            'last_name'  => $v->get('last_name', ''),
            'email'      => $v->get('email', ''),
        ];

        if ($v->fails()) {
            SessionHelper::flash('user_errors', $v->errors());
            SessionHelper::flash('user_old', $old);
            UrlHelper::redirect('/admin/users/create');
        }

        try {
            $db = Database::getInstance();

            // Duplicate email check (Req 11 — implied, similar to Req 1.3)
            $emailCheck = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $emailCheck->execute([':email' => $v->get('email')]);
            if ($emailCheck->fetch()) {
                SessionHelper::flash('user_errors', [
                    'email' => 'This email address is already registered.',
                ]);
                SessionHelper::flash('user_old', $old);
                UrlHelper::redirect('/admin/users/create');
            }

            // Server-side role assignment — Staff only; never trust browser role param
            // role is stored directly as 'staff' in the users.role ENUM column

            // Hash password (Req 11.3 — never store plaintext)
            $passwordHash = password_hash($v->get('password'), PASSWORD_DEFAULT);

            $adminId = AuthMiddleware::userId();

            $insert = $db->prepare(
                "INSERT INTO users (role, first_name, last_name, email, password_hash, is_active, failed_login_attempts, created_at, updated_at)
                 VALUES ('staff', :first_name, :last_name, :email, :password_hash, 1, 0, NOW(), NOW())"
            );
            $insert->execute([
                ':first_name'    => $v->get('first_name'),
                ':last_name'     => $v->get('last_name'),
                ':email'         => $v->get('email'),
                ':password_hash' => $passwordHash,
            ]);
            $newUserId = (int)$db->lastInsertId();

            // Audit log (Req 11.7, 13.1) — never log password or hash
            AuditLogService::log(
                $adminId, 'admin',
                AuditLog::ACTION_ACCOUNT_CREATED, 'users',
                'users', $newUserId,
                null,
                ['email' => $v->get('email'), 'role' => 'staff'],
                "Staff account '{$v->get('email')}' created by admin ID {$adminId}"
            );

            $name = htmlspecialchars(
                $v->get('first_name') . ' ' . $v->get('last_name'),
                ENT_QUOTES, 'UTF-8'
            );
            SessionHelper::flash('success', "Staff account for {$name} has been created.");
            UrlHelper::redirect('/admin/users');

        } catch (\Throwable $e) {
            Logger::error('UserController store error: ' . $e->getMessage());
            SessionHelper::flash('user_errors', ['form' => 'Unable to create account. Please try again.']);
            SessionHelper::flash('user_old', $old);
            UrlHelper::redirect('/admin/users/create');
        }
    }
}
