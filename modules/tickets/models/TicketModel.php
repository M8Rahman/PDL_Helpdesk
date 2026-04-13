<?php
/**
 * PDL_Helpdesk — Ticket Model
 * All ticket CRUD and related queries.
 */

require_once ROOT_PATH . 'core/Model.php';

class TicketModel extends Model
{
    // ── Ticket Retrieval ──────────────────────────────────────

    public function getById(int $ticketId): ?array
    {
        return $this->selectOne(
            "SELECT t.*, u.full_name AS creator_name, u.email AS creator_email,
                    r.full_name AS resolver_name
             FROM tickets t
             JOIN users u ON t.created_by = u.user_id
             LEFT JOIN users r ON t.resolved_by = r.user_id
             WHERE t.ticket_id = ?",
            [$ticketId]
        );
    }

    /**
     * Get paginated ticket list with optional filters.
     *
     * @param array $filters  Keys: status, department, priority, search, created_by
     * @param int   $page
     * @param int   $perPage
     */
    // public function getPaginated(array $filters, int $page = 1, int $perPage = TICKETS_PER_PAGE): array
    // {
    //     [$where, $params] = $this->buildWhereClause($filters);
    //     $pag = $this->paginate($page, $perPage);

    //     $total = $this->count(
    //         "SELECT COUNT(*) FROM tickets t WHERE {$where}",
    //         $params
    //     );

    //     $params[] = $pag['limit'];
    //     $params[] = $pag['offset'];

    //     $rows = $this->select(
    //         "SELECT t.ticket_id, t.ticket_code, t.title, t.status, t.priority,
    //                 t.assigned_department, t.created_at, t.updated_at,
    //                 u.full_name AS creator_name
    //          FROM tickets t
    //          JOIN users u ON t.created_by = u.user_id
    //          WHERE {$where}
    //          ORDER BY
    //             FIELD(t.priority,'critical','high','medium','low'),
    //             t.created_at DESC
    //          LIMIT ? OFFSET ?",
    //         $params
    //     );

    //     return ['rows' => $rows, 'total' => $total];
    // }
    public function getPaginated(array $filters, int $page = 1, int $perPage = TICKETS_PER_PAGE): array
    {
        [$where, $params] = $this->buildWhereClause($filters);
        $pag = $this->paginate($page, $perPage);

        $total = $this->count(
            "SELECT COUNT(*) FROM tickets t WHERE {$where}",
            $params
        );

        // Determine sort order from filters
        $sort = $filters['sort'] ?? 'created_desc';
        $orderBy = match($sort) {
            'created_asc'  => 't.created_at ASC',
            'created_desc' => 't.created_at DESC',
            'priority'     => "FIELD(t.priority,'critical','high','medium','low'), t.created_at DESC",
            default        => 't.created_at DESC'
        };

        $params[] = $pag['limit'];
        $params[] = $pag['offset'];

        $rows = $this->select(
            "SELECT t.ticket_id, t.ticket_code, t.title, t.status, t.priority,
                    t.assigned_department, t.created_at, t.updated_at,
                    u.full_name AS creator_name
            FROM tickets t
            JOIN users u ON t.created_by = u.user_id
            WHERE {$where}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?",
            $params
        );

        return ['rows' => $rows, 'total' => $total];
    }

    public function getAllForPdfExport(): array
    {
        return $this->select(
            "SELECT t.ticket_id, t.ticket_code, t.title, t.description,
                    t.status, t.priority, t.department, t.assigned_department,
                    t.created_at, t.updated_at, t.resolved_at,
                    u.full_name AS creator_name, u.email AS creator_email,
                    r.full_name AS resolver_name
             FROM tickets t
             JOIN  users u ON t.created_by  = u.user_id
             LEFT JOIN users r ON t.resolved_by = r.user_id
             ORDER BY t.created_at ASC"
        );
    }

    private function buildWhereClause(array $filters): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['created_by'])) {
            $where[]  = 't.created_by = ?';
            $params[] = (int) $filters['created_by'];
        }
        if (!empty($filters['department'])) {
            $where[]  = 't.assigned_department = ?';
            $params[] = strtoupper($filters['department']);
        }
        if (!empty($filters['status'])) {
            $where[]  = 't.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where[]  = 't.priority = ?';
            $params[] = $filters['priority'];
        }
        if (!empty($filters['search'])) {
            $where[]  = '(t.title LIKE ? OR t.ticket_code LIKE ?)';
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        return [implode(' AND ', $where), $params];
    }

    // ── Ticket Creation ───────────────────────────────────────

    /**
     * Insert a new ticket and return its ID.
     */
    public function create(array $data): int
    {
        $code = $this->generateTicketCode();

        return $this->insert(
            "INSERT INTO tickets
                (ticket_code, title, description, department, assigned_department,
                 priority, created_by, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'open')",
            [
                $code,
                $data['title'],
                $data['description'],
                strtoupper($data['department']),
                strtoupper($data['department']),  // initially same
                $data['priority'] ?? 'medium',
                $data['created_by'],
            ]
        );
    }

    private function generateTicketCode(): string
    {
        // Get the current max ticket_id to generate a readable sequential code
        $result = $this->selectOne('SELECT MAX(ticket_id) AS max_id FROM tickets');
        $nextId = (int)($result['max_id'] ?? 0) + 1;
        return TICKET_PREFIX . '-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }

    // ── Ticket Updates ────────────────────────────────────────

    public function updateStatus(int $ticketId, string $status, ?int $resolvedBy = null): bool
    {
        $resolvedAt = in_array($status, ['solved','closed']) ? 'NOW()' : 'NULL';
        $closedAt   = $status === 'closed' ? 'NOW()' : 'NULL';

        $affected = $this->execute(
            "UPDATE tickets
             SET status = ?, resolved_by = ?, resolved_at = {$resolvedAt}, closed_at = {$closedAt}
             WHERE ticket_id = ?",
            [$status, $resolvedBy, $ticketId]
        );
        return $affected > 0;
    }

    public function updateDetails(int $ticketId, string $title, string $description, string $priority): bool
    {
        $affected = $this->execute(
            'UPDATE tickets SET title = ?, description = ?, priority = ? WHERE ticket_id = ?',
            [$title, $description, $priority, $ticketId]
        );
        return $affected > 0;
    }

    public function transferDepartment(int $ticketId, string $newDepartment): bool
    {
        $affected = $this->execute(
            'UPDATE tickets SET assigned_department = ? WHERE ticket_id = ?',
            [strtoupper($newDepartment), $ticketId]
        );
        return $affected > 0;
    }

    // ── Comments ──────────────────────────────────────────────

    public function getComments(int $ticketId): array
    {
        return $this->select(
            "SELECT c.*, u.full_name, u.role
             FROM ticket_comments c
             JOIN users u ON c.user_id = u.user_id
             WHERE c.ticket_id = ?
             ORDER BY c.created_at ASC",
            [$ticketId]
        );
    }

    public function addComment(int $ticketId, int $userId, string $comment, bool $isInternal = false): int
    {
        return $this->insert(
            'INSERT INTO ticket_comments (ticket_id, user_id, comment, is_internal)
             VALUES (?, ?, ?, ?)',
            [$ticketId, $userId, $comment, (int)$isInternal]
        );
    }

    // ── Attachments ───────────────────────────────────────────

    public function getAttachments(int $ticketId): array
    {
        return $this->select(
            "SELECT a.*, u.full_name AS uploader_name
             FROM ticket_attachments a
             JOIN users u ON a.uploaded_by = u.user_id
             WHERE a.ticket_id = ?
             ORDER BY a.created_at ASC",
            [$ticketId]
        );
    }

    public function addAttachment(array $data): int
    {
        return $this->insert(
            'INSERT INTO ticket_attachments
                (ticket_id, comment_id, uploaded_by, file_name, stored_name, file_size, mime_type, file_path)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['ticket_id'],
                $data['comment_id'] ?? null,
                $data['uploaded_by'],
                $data['file_name'],
                $data['stored_name'],
                $data['file_size'],
                $data['mime_type'],
                $data['file_path'],
            ]
        );
    }

    // ── Transfers ─────────────────────────────────────────────

    public function logTransfer(int $ticketId, int $userId, string $from, string $to, string $reason = ''): int
    {
        return $this->insert(
            'INSERT INTO ticket_transfers (ticket_id, transferred_by, from_department, to_department, reason)
             VALUES (?, ?, ?, ?, ?)',
            [$ticketId, $userId, $from, $to, $reason]
        );
    }

    public function getTransfers(int $ticketId): array
    {
        return $this->select(
            "SELECT tr.*, u.full_name AS transferred_by_name
             FROM ticket_transfers tr
             JOIN users u ON tr.transferred_by = u.user_id
             WHERE tr.ticket_id = ?
             ORDER BY tr.transferred_at ASC",
            [$ticketId]
        );
    }
}
