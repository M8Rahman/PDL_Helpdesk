<?php
/**
 * PDL_Helpdesk — Base Model
 *
 * All module models extend this class.
 * Provides a prepared-statement helper layer over PDO.
 */

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Query Helpers ─────────────────────────────────────────

    /**
     * Execute a SELECT query and return all matching rows.
     *
     * @param  string  $sql    SQL with named or positional placeholders
     * @param  array   $params Bound parameter values
     * @return array
     */
    protected function select(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a SELECT query and return a single row.
     *
     * @return array|null
     */
    protected function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE statement.
     *
     * @return int Number of affected rows
     */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Execute an INSERT and return the last inserted ID.
     */
    protected function insert(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Count rows with a scalar query.
     * e.g. "SELECT COUNT(*) FROM tickets WHERE status = ?"
     */
    protected function count(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Begin a transaction.
     */
    protected function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Commit a transaction.
     */
    protected function commit(): void
    {
        $this->db->commit();
    }

    /**
     * Roll back a transaction.
     */
    protected function rollback(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    /**
     * Build a simple paginated LIMIT / OFFSET clause.
     *
     * @return array ['limit' => int, 'offset' => int]
     */
    protected function paginate(int $page, int $perPage): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;
        return ['limit' => $perPage, 'offset' => $offset];
    }
}
