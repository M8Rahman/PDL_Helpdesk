<?php
/**
 * PDL_Helpdesk — Dashboard Model
 * All dashboard data queries are here, separated by role context.
 */

require_once ROOT_PATH . 'core/Model.php';

class DashboardModel extends Model
{
    // ── Normal User Stats ─────────────────────────────────────

    public function getUserStats(int $userId): array
    {
        return [
            'total'       => $this->count('SELECT COUNT(*) FROM tickets WHERE created_by = ?', [$userId]),
            'open'        => $this->count("SELECT COUNT(*) FROM tickets WHERE created_by = ? AND status = 'open'", [$userId]),
            'in_progress' => $this->count("SELECT COUNT(*) FROM tickets WHERE created_by = ? AND status = 'in_progress'", [$userId]),
            'solved'      => $this->count("SELECT COUNT(*) FROM tickets WHERE created_by = ? AND status = 'solved'", [$userId]),
            'closed'      => $this->count("SELECT COUNT(*) FROM tickets WHERE created_by = ? AND status = 'closed'", [$userId]),
        ];
    }

    public function getUserRecentTickets(int $userId, int $limit = 5): array
    {
        return $this->select(
            "SELECT ticket_id, ticket_code, title, status, priority, created_at
             FROM tickets
             WHERE created_by = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    // ── IT / MIS Department Stats ──────────────────────────────

    public function getDepartmentStats(string $department): array
    {
        return [
            'total'       => $this->count('SELECT COUNT(*) FROM tickets WHERE assigned_department = ?', [$department]),
            'open'        => $this->count("SELECT COUNT(*) FROM tickets WHERE assigned_department = ? AND status = 'open'", [$department]),
            'in_progress' => $this->count("SELECT COUNT(*) FROM tickets WHERE assigned_department = ? AND status = 'in_progress'", [$department]),
            'solved_today' => $this->count(
                "SELECT COUNT(*) FROM tickets WHERE assigned_department = ? AND status IN ('solved','closed') AND DATE(resolved_at) = CURDATE()",
                [$department]
            ),
            'avg_resolution_hours' => $this->getAvgResolutionHours($department),
        ];
    }

    public function getDepartmentQueue(string $department, int $limit = 8): array
    {
        return $this->select(
            "SELECT t.ticket_id, t.ticket_code, t.title, t.status, t.priority, t.created_at,
                    u.full_name AS creator_name
             FROM tickets t
             JOIN users u ON t.created_by = u.user_id
             WHERE t.assigned_department = ? AND t.status IN ('open','in_progress')
             ORDER BY
                FIELD(t.priority,'critical','high','medium','low'),
                t.created_at ASC
             LIMIT ?",
            [$department, $limit]
        );
    }

    public function getDepartmentWeeklyTrend(string $department): array
    {
        return $this->select(
            "SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM tickets
             WHERE assigned_department = ?
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$department]
        );
    }

    private function getAvgResolutionHours(string $department): float
    {
        $result = $this->selectOne(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) AS avg_hours
             FROM tickets
             WHERE assigned_department = ?
               AND resolved_at IS NOT NULL
               AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            [$department]
        );
        return round((float)($result['avg_hours'] ?? 0), 1);
    }

    // ── Admin Stats ───────────────────────────────────────────

    public function getAdminStats(): array
    {
        return [
            'total_tickets'   => $this->count('SELECT COUNT(*) FROM tickets'),
            'open'            => $this->count("SELECT COUNT(*) FROM tickets WHERE status = 'open'"),
            'in_progress'     => $this->count("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress'"),
            'solved'          => $this->count("SELECT COUNT(*) FROM tickets WHERE status IN ('solved','closed')"),
            'total_users'     => $this->count("SELECT COUNT(*) FROM users WHERE is_active = 1"),
            'tickets_today'   => $this->count("SELECT COUNT(*) FROM tickets WHERE DATE(created_at) = CURDATE()"),
            'solved_today'    => $this->count("SELECT COUNT(*) FROM tickets WHERE DATE(resolved_at) = CURDATE()"),
            'avg_resolution'  => $this->getGlobalAvgResolution(),
        ];
    }

    public function getTicketsByDepartment(): array
    {
        return $this->select(
            "SELECT assigned_department AS department, COUNT(*) AS total,
                    SUM(status IN ('solved','closed')) AS resolved,
                    SUM(status IN ('open','in_progress')) AS active
             FROM tickets
             GROUP BY assigned_department"
        );
    }

    public function getStatusBreakdown(): array
    {
        return $this->select(
            "SELECT status, COUNT(*) AS total FROM tickets GROUP BY status"
        );
    }

    /**
     * Tickets created per day for the past N days (for bar chart).
     */
    public function getDailyTicketTrend(int $days = 14): array
    {
        return $this->select(
            "SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM tickets
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$days]
        );
    }

    /**
     * Top 5 performers by tickets resolved in the last 30 days.
     */
    public function getTopPerformers(int $limit = 5): array
    {
        return $this->select(
            "SELECT u.full_name, u.role, u.department,
                    COUNT(t.ticket_id) AS resolved_count,
                    AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)) AS avg_hours
             FROM tickets t
             JOIN users u ON t.resolved_by = u.user_id
             WHERE t.resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY t.resolved_by
             ORDER BY resolved_count DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function getRecentAllTickets(int $limit = 8): array
    {
        return $this->select(
            "SELECT t.ticket_id, t.ticket_code, t.title, t.status, t.priority,
                    t.assigned_department, t.created_at, u.full_name AS creator_name
             FROM tickets t
             JOIN users u ON t.created_by = u.user_id
             ORDER BY t.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    private function getGlobalAvgResolution(): float
    {
        $result = $this->selectOne(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) AS avg_hours
             FROM tickets
             WHERE resolved_at IS NOT NULL
               AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        return round((float)($result['avg_hours'] ?? 0), 1);
    }
}
