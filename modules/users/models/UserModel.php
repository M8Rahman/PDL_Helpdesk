<?php
/**
 * PDL_Helpdesk — User Model
 * All user management database queries.
 */

require_once ROOT_PATH . 'core/Model.php';

class UserModel extends Model
{
    // ── Retrieval ─────────────────────────────────────────────

    public function getPaginated(array $filters = [], int $page = 1, int $perPage = USERS_PER_PAGE): array
    {
        [$where, $params] = $this->buildWhereClause($filters);
        $pag   = $this->paginate($page, $perPage);
        $total = $this->count("SELECT COUNT(*) FROM users WHERE {$where}", $params);

        $params[] = $pag['limit'];
        $params[] = $pag['offset'];

        $rows = $this->select(
            "SELECT user_id, full_name, username, email, role, department,
                    is_active, last_login_at, created_at
             FROM users
             WHERE {$where}
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );

        return ['rows' => $rows, 'total' => $total];
    }

    private function buildWhereClause(array $filters): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $term     = '%' . $filters['search'] . '%';
            $where[]  = '(full_name LIKE ? OR username LIKE ? OR email LIKE ?)';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if (!empty($filters['role'])) {
            $where[]  = 'role = ?';
            $params[] = $filters['role'];
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[]  = 'is_active = ?';
            $params[] = (int) $filters['is_active'];
        }

        return [implode(' AND ', $where), $params];
    }

    public function getById(int $userId): ?array
    {
        return $this->selectOne(
            'SELECT user_id, full_name, username, email, role, department,
                    is_active, last_login_at, created_at
             FROM users WHERE user_id = ?',
            [$userId]
        );
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM users WHERE username = ?';
        $params = [$username];
        if ($excludeId !== null) {
            $sql    .= ' AND user_id != ?';
            $params[] = $excludeId;
        }
        return $this->count($sql, $params) > 0;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM users WHERE email = ?';
        $params = [$email];
        if ($excludeId !== null) {
            $sql    .= ' AND user_id != ?';
            $params[] = $excludeId;
        }
        return $this->count($sql, $params) > 0;
    }

    // ── Mutations ─────────────────────────────────────────────

    public function create(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        return $this->insert(
            'INSERT INTO users (full_name, username, email, password_hash, role, department, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)',
            [
                $data['full_name'],
                $data['username'],
                $data['email'],
                $hash,
                $data['role'],
                strtoupper($data['department']),
            ]
        );
    }

    public function update(int $userId, array $data): bool
    {
        $affected = $this->execute(
            'UPDATE users SET full_name = ?, email = ?, role = ?, department = ? WHERE user_id = ?',
            [
                $data['full_name'],
                $data['email'],
                $data['role'],
                strtoupper($data['department']),
                $userId,
            ]
        );
        return $affected >= 0;
    }

    public function toggleActive(int $userId, bool $active): bool
    {
        $affected = $this->execute(
            'UPDATE users SET is_active = ? WHERE user_id = ?',
            [(int) $active, $userId]
        );
        return $affected > 0;
    }

    public function resetPassword(int $userId, string $plainPassword): bool
    {
        $hash     = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $affected = $this->execute(
            'UPDATE users SET password_hash = ? WHERE user_id = ?',
            [$hash, $userId]
        );
        return $affected > 0;
    }

    // ── Stats ─────────────────────────────────────────────────

    public function getTicketSummary(int $userId): array
    {
        return $this->selectOne(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'open') AS open,
                SUM(status IN ('solved','closed')) AS resolved
             FROM tickets WHERE created_by = ?",
            [$userId]
        ) ?? ['total' => 0, 'open' => 0, 'resolved' => 0];
    }
}
