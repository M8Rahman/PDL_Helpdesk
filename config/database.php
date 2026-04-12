<?php
/**
 * PDL_Helpdesk — Database Connection
 *
 * Provides a singleton PDO instance.
 * All modules use Database::getInstance() — never create raw PDO connections elsewhere.
 */

class Database
{
    // ── Credentials (edit these for your environment) ─────────
    private const DB_HOST    = '127.0.0.1';
    private const DB_PORT    = '3306';
    private const DB_NAME    = 'pdl_helpdesk';
    private const DB_USER    = 'root';        // XAMPP default
    private const DB_PASS    = '';            // XAMPP default (set a password in production)
    private const DB_CHARSET = 'utf8mb4';

    private static ?PDO $instance = null;

    /**
     * Returns the singleton PDO connection.
     * Throws a RuntimeException if the connection fails.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                self::DB_HOST,
                self::DB_PORT,
                self::DB_NAME,
                self::DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,   // use real prepared statements
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            ];

            try {
                self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            } catch (PDOException $e) {
                // Log and display a safe error — never expose credentials
                error_log('[PDL_Helpdesk] Database connection failed: ' . $e->getMessage());
                die(self::connectionErrorPage());
            }
        }

        return self::$instance;
    }

    /**
     * Returns a safe HTML error page if DB is unreachable.
     */
    private static function connectionErrorPage(): string
    {
        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
            <title>Service Unavailable — PDL Helpdesk</title>
            <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;
            height:100vh;background:#f8f9fa;margin:0;}
            .box{text-align:center;padding:40px;background:#fff;border-radius:12px;
            box-shadow:0 4px 20px rgba(0,0,0,.08);}
            h2{color:#1e293b;}p{color:#64748b;}</style></head>
            <body><div class="box">
            <h2>⚠️ Database Unavailable</h2>
            <p>The system database could not be reached.<br>
            Please contact your system administrator.</p>
            </div></body></html>';
    }

    // Prevent instantiation
    private function __construct() {}
    private function __clone() {}
}
