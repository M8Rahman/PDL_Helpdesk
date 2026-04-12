<?php
/**
 * PDL_Helpdesk — Auth
 *
 * Manages session lifecycle: login, logout, current user, guards.
 * All controllers call Auth methods — never touch $_SESSION directly.
 */

class Auth
{
    private const USER_KEY = 'pdl_auth_user';

    // ── Session Bootstrap ─────────────────────────────────────

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => false,   // set true if HTTPS
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    // ── Login / Logout ────────────────────────────────────────

    /**
     * Store authenticated user data in session.
     */
    public static function login(array $user): void
    {
        // Regenerate ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION[self::USER_KEY] = [
            'user_id'    => $user['user_id'],
            'full_name'  => $user['full_name'],
            'username'   => $user['username'],
            'email'      => $user['email'],
            'role'       => $user['role'],
            'department' => $user['department'],
            'avatar'     => $user['avatar'] ?? null,
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    // ── Current User ──────────────────────────────────────────

    /**
     * Returns the current user array or null if not logged in.
     */
    public static function user(): ?array
    {
        return $_SESSION[self::USER_KEY] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION[self::USER_KEY]) ? (int) $_SESSION[self::USER_KEY]['user_id'] : null;
    }

    public static function role(): ?string
    {
        return $_SESSION[self::USER_KEY]['role'] ?? null;
    }

    public static function department(): ?string
    {
        return $_SESSION[self::USER_KEY]['department'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION[self::USER_KEY]);
    }

    // ── Guards ────────────────────────────────────────────────

    /**
     * Redirect to login if not authenticated.
     * Call at the top of any protected controller.
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '?page=auth/login');
            exit;
        }
    }

    /**
     * Require a specific role (or one of many roles).
     * Redirects to dashboard with an error if unauthorized.
     */
    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();

        $roles = (array) $roles;
        if (!in_array(self::role(), $roles, true)) {
            $_SESSION['flash_error'] = 'You do not have permission to access that page.';
            header('Location: ' . BASE_URL . '?page=dashboard');
            exit;
        }
    }

    // ── Flash Messages ────────────────────────────────────────

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    public static function getFlash(string $type): ?string
    {
        $key = 'flash_' . $type;
        if (isset($_SESSION[$key])) {
            $message = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $message;
        }
        return null;
    }

    // ── CSRF ─────────────────────────────────────────────────

    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
