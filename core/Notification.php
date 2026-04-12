<?php
/**
 * PDL_Helpdesk — Notification Service
 *
 * Central dispatcher for all in-app notifications.
 * Called from controllers after key events.
 */

class Notification
{
    private static ?PDO $db = null;

    private static function db(): PDO
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    // ── Public Dispatch Methods ───────────────────────────────

    /**
     * Fired when a new ticket is created.
     * Notifies: all users in the assigned department + all admins.
     */
    public static function onTicketCreated(int $ticketId, string $department, string $ticketCode, string $title): void
    {
        $message = "New ticket {$ticketCode}: {$title}";
        $recipients = self::getDepartmentUserIds($department);
        $admins     = self::getAdminUserIds();
        $all        = array_unique(array_merge($recipients, $admins));

        self::insertBulk($all, $ticketId, 'ticket_created', $message);
    }

    /**
     * Fired when a comment is added to a ticket.
     * Notifies: ticket creator + all admins.
     */
    public static function onTicketCommented(int $ticketId, int $creatorId, string $ticketCode, string $commenterName): void
    {
        $message = "{$commenterName} commented on ticket {$ticketCode}";
        $admins  = self::getAdminUserIds();
        $all     = array_unique(array_merge([$creatorId], $admins));

        self::insertBulk($all, $ticketId, 'ticket_commented', $message);
    }

    /**
     * Fired when a ticket is marked Solved.
     * Notifies: ticket creator + all admins.
     */
    public static function onTicketSolved(int $ticketId, int $creatorId, string $ticketCode): void
    {
        $message = "Ticket {$ticketCode} has been solved.";
        $admins  = self::getAdminUserIds();
        $all     = array_unique(array_merge([$creatorId], $admins));

        self::insertBulk($all, $ticketId, 'ticket_solved', $message);
    }

    /**
     * Fired when a ticket is Closed.
     * Notifies: ticket creator + all admins.
     */
    public static function onTicketClosed(int $ticketId, int $creatorId, string $ticketCode): void
    {
        $message = "Ticket {$ticketCode} has been closed.";
        $admins  = self::getAdminUserIds();
        $all     = array_unique(array_merge([$creatorId], $admins));

        self::insertBulk($all, $ticketId, 'ticket_closed', $message);
    }

    /**
     * Fired when a ticket is transferred to another department.
     * Notifies: new department users + admins.
     */
    public static function onTicketTransferred(int $ticketId, string $newDepartment, string $ticketCode): void
    {
        $message    = "Ticket {$ticketCode} has been transferred to {$newDepartment}.";
        $recipients = self::getDepartmentUserIds($newDepartment);
        $admins     = self::getAdminUserIds();
        $all        = array_unique(array_merge($recipients, $admins));

        self::insertBulk($all, $ticketId, 'ticket_transferred', $message);
    }

    // ── Fetch Methods (used by NotificationController) ────────

    /**
     * Get unread notifications for the current user.
     */
    public static function getUnread(int $userId, int $limit = 20): array
    {
        $stmt = self::db()->prepare(
            'SELECT n.*, t.ticket_code
             FROM notifications n
             LEFT JOIN tickets t ON n.ticket_id = t.ticket_id
             WHERE n.user_id = ? AND n.is_read = 0
             ORDER BY n.created_at DESC
             LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count unread notifications for the current user.
     */
    public static function countUnread(int $userId): int
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Mark a specific notification as read.
     */
    public static function markRead(int $notificationId, int $userId): void
    {
        $stmt = self::db()->prepare(
            'UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?'
        );
        $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public static function markAllRead(int $userId): void
    {
        $stmt = self::db()->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
    }

    // ── Private Helpers ───────────────────────────────────────

    private static function insertBulk(array $userIds, int $ticketId, string $type, string $message): void
    {
        if (empty($userIds)) {
            return;
        }

        $stmt = self::db()->prepare(
            'INSERT INTO notifications (user_id, ticket_id, type, message)
             VALUES (?, ?, ?, ?)'
        );

        foreach ($userIds as $userId) {
            $stmt->execute([$userId, $ticketId, $type, $message]);
        }
    }

    /**
     * Get all active user IDs in a given department (IT or MIS roles).
     */
    private static function getDepartmentUserIds(string $department): array
    {
        $role = strtolower($department); // 'it' or 'mis'
        $stmt = self::db()->prepare(
            'SELECT user_id FROM users WHERE role = ? AND is_active = 1'
        );
        $stmt->execute([$role]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id');
    }

    /**
     * Get all admin and super_admin user IDs.
     */
    private static function getAdminUserIds(): array
    {
        $stmt = self::db()->prepare(
            "SELECT user_id FROM users WHERE role IN ('admin','super_admin') AND is_active = 1"
        );
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id');
    }
}
