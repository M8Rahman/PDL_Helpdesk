<?php
/**
 * PDL_Helpdesk — Audit Log Service
 *
 * Centralized audit logging.
 * All state-changing actions call AuditLog::record() after completing.
 */

class AuditLog
{
    private static ?PDO $db = null;

    private static function db(): PDO
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    /**
     * Record an audit event.
     *
     * @param string   $action      Dot-notation action key e.g. 'ticket.status_changed'
     * @param string   $description Human-readable description
     * @param int|null $ticketId    Associated ticket (optional)
     * @param mixed    $oldValue    Previous value (serialized if array)
     * @param mixed    $newValue    New value (serialized if array)
     */
    public static function record(
        string $action,
        string $description,
        ?int   $ticketId = null,
        mixed  $oldValue = null,
        mixed  $newValue = null
    ): void {
        $userId    = Auth::id();
        $ipAddress = self::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $oldStr = is_array($oldValue) ? json_encode($oldValue) : (string) ($oldValue ?? '');
        $newStr = is_array($newValue) ? json_encode($newValue) : (string) ($newValue ?? '');

        try {
            $stmt = self::db()->prepare(
                'INSERT INTO audit_logs
                 (user_id, ticket_id, action, description, old_value, new_value, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $ticketId,
                $action,
                $description,
                $oldStr ?: null,
                $newStr ?: null,
                $ipAddress,
                $userAgent ? substr($userAgent, 0, 500) : null,
            ]);
        } catch (PDOException $e) {
            // Never let an audit failure crash the app — just log it
            error_log('[PDL_Helpdesk] AuditLog::record failed: ' . $e->getMessage());
        }
    }

    /**
     * Convenience: log a user login event.
     */
    public static function login(int $userId, string $username): void
    {
        self::record(
            'auth.login',
            "User '{$username}' logged in.",
        );
    }

    /**
     * Convenience: log a ticket status change.
     */
    public static function ticketStatusChanged(int $ticketId, string $oldStatus, string $newStatus): void
    {
        self::record(
            'ticket.status_changed',
            "Ticket #{$ticketId} status changed from '{$oldStatus}' to '{$newStatus}'.",
            $ticketId,
            $oldStatus,
            $newStatus
        );
    }

    /**
     * Convenience: log a ticket transfer.
     */
    public static function ticketTransferred(int $ticketId, string $fromDept, string $toDept): void
    {
        self::record(
            'ticket.transferred',
            "Ticket #{$ticketId} transferred from {$fromDept} to {$toDept}.",
            $ticketId,
            $fromDept,
            $toDept
        );
    }

    /**
     * Paginated fetch of audit logs for the admin UI.
     */
    public static function getPaginated(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[]  = 'al.user_id = ?';
            $params[] = (int) $filters['user_id'];
        }
        if (!empty($filters['ticket_id'])) {
            $where[]  = 'al.ticket_id = ?';
            $params[] = (int) $filters['ticket_id'];
        }
        if (!empty($filters['action'])) {
            $where[]  = 'al.action LIKE ?';
            $params[] = '%' . $filters['action'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'al.created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'al.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        // Total count
        $countStmt = self::db()->prepare(
            "SELECT COUNT(*) FROM audit_logs al WHERE {$whereStr}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Data query
        $dataParams   = array_merge($params, [$perPage, $offset]);
        $dataStmt     = self::db()->prepare(
            "SELECT al.*, u.full_name, u.username
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.user_id
             WHERE {$whereStr}
             ORDER BY al.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $dataStmt->execute($dataParams);
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        return ['rows' => $rows, 'total' => $total];
    }

    // ── Helpers ───────────────────────────────────────────────

    private static function getClientIp(): ?string
    {
        // Handle proxies (internal network context)
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return null;
    }
}
