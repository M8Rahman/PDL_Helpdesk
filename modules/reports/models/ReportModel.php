<?php
/**
 * PDL_Helpdesk — Report Model
 * Data queries for the reporting module.
 */

require_once ROOT_PATH . 'core/Model.php';

class ReportModel extends Model
{
    /**
     * Tickets grouped by department with resolution stats.
     */
    public function getByDepartment(string $dateFrom = '', string $dateTo = ''): array
    {
        [$where, $params] = $this->dateRange('created_at', $dateFrom, $dateTo);

        return $this->select(
            "SELECT
                assigned_department AS department,
                COUNT(*) AS total,
                SUM(status IN ('solved','closed')) AS resolved,
                SUM(status IN ('open','in_progress')) AS active,
                ROUND(AVG(CASE WHEN resolved_at IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END), 1) AS avg_hours
             FROM tickets
             WHERE {$where}
             GROUP BY assigned_department
             ORDER BY total DESC",
            $params
        );
    }

    /**
     * Tickets by status in a date range.
     */
    public function getByStatus(string $dateFrom = '', string $dateTo = ''): array
    {
        [$where, $params] = $this->dateRange('created_at', $dateFrom, $dateTo);

        return $this->select(
            "SELECT status, COUNT(*) AS total
             FROM tickets WHERE {$where}
             GROUP BY status",
            $params
        );
    }

    /**
     * Daily ticket volume in a date range (for chart).
     */
    public function getDailyVolume(string $dateFrom, string $dateTo): array
    {
        return $this->select(
            "SELECT DATE(created_at) AS day, COUNT(*) AS created,
                    SUM(DATE(resolved_at) = DATE(created_at)) AS resolved_same_day
             FROM tickets
             WHERE created_at BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']
        );
    }

    /**
     * Employee performance: resolved tickets, avg resolution time.
     */
    public function getEmployeePerformance(string $dateFrom = '', string $dateTo = ''): array
    {
        $where  = "t.resolved_at IS NOT NULL";
        $params = [];

        if ($dateFrom) { $where .= ' AND t.resolved_at >= ?'; $params[] = $dateFrom . ' 00:00:00'; }
        if ($dateTo)   { $where .= ' AND t.resolved_at <= ?'; $params[] = $dateTo   . ' 23:59:59'; }

        return $this->select(
            "SELECT u.user_id, u.full_name, u.role, u.department,
                    COUNT(t.ticket_id) AS resolved_count,
                    ROUND(AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)), 1) AS avg_hours,
                    MIN(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)) AS min_hours,
                    MAX(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)) AS max_hours
             FROM tickets t
             JOIN users u ON t.resolved_by = u.user_id
             WHERE {$where}
             GROUP BY t.resolved_by
             ORDER BY resolved_count DESC",
            $params
        );
    }

    /**
     * Priority distribution in a date range.
     */
    public function getByPriority(string $dateFrom = '', string $dateTo = ''): array
    {
        [$where, $params] = $this->dateRange('created_at', $dateFrom, $dateTo);

        return $this->select(
            "SELECT priority, COUNT(*) AS total
             FROM tickets WHERE {$where}
             GROUP BY priority
             ORDER BY FIELD(priority,'critical','high','medium','low')",
            $params
        );
    }

    /**
     * Full ticket list for export (no pagination).
     */
    public function getAllForExport(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['department'])) {
            $where[]  = 'assigned_department = ?';
            $params[] = $filters['department'];
        }
        if (!empty($filters['status'])) {
            $where[]  = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereStr = implode(' AND ', $where);

        return $this->select(
            "SELECT t.ticket_code, t.title, t.assigned_department, t.status, t.priority,
                    u.full_name AS created_by, r.full_name AS resolved_by,
                    t.created_at, t.resolved_at,
                    TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) AS resolution_hours
             FROM tickets t
             JOIN users u ON t.created_by = u.user_id
             LEFT JOIN users r ON t.resolved_by = r.user_id
             WHERE {$whereStr}
             ORDER BY t.created_at DESC",
            $params
        );
    }

    // ── Helper ────────────────────────────────────────────────

    private function dateRange(string $col, string $from, string $to): array
    {
        $where  = '1=1';
        $params = [];
        if ($from) { $where .= " AND {$col} >= ?"; $params[] = $from . ' 00:00:00'; }
        if ($to)   { $where .= " AND {$col} <= ?"; $params[] = $to   . ' 23:59:59'; }
        return [$where, $params];
    }
}
