<?php
/**
 * PDL_Helpdesk — Auth User Model
 * Handles authentication-related database queries.
 */

require_once ROOT_PATH . 'core/Model.php';

class AuthUserModel extends Model
{
    /**
     * Find a user by username or email for login.
     */
    public function findByUsernameOrEmail(string $identifier): ?array
    {
        return $this->selectOne(
            'SELECT user_id, full_name, username, email, password_hash,
                    role, department, is_active, avatar
             FROM users
             WHERE (username = ? OR email = ?) AND is_active = 1
             LIMIT 1',
            [$identifier, $identifier]
        );
    }

    /**
     * Update the last_login_at timestamp after successful login.
     */
    public function updateLastLogin(int $userId): void
    {
        $this->execute(
            'UPDATE users SET last_login_at = NOW() WHERE user_id = ?',
            [$userId]
        );
    }

    /**
     * Verify a plain password against a stored bcrypt hash.
     */
    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
