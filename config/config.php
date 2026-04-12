<?php
/**
 * PDL_Helpdesk — Application Configuration
 * Pantex Dress Ltd.
 *
 * Adjust these values to match your environment.
 * Never commit sensitive credentials to version control.
 */

// ── Application ──────────────────────────────────────────────
define('APP_NAME',    'PDL Helpdesk');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     'production');   // 'development' | 'production'

// ── Base URL (trailing slash required) ───────────────────────
// Change SERVER_IP to your actual LAN IP or hostname.
define('BASE_URL', 'http://192.168.0.160/PDL_Helpdesk/');

// ── Paths ─────────────────────────────────────────────────────
define('ROOT_PATH',    dirname(__DIR__) . '/');
define('UPLOAD_PATH',  ROOT_PATH . 'uploads/tickets/');
define('LOG_PATH',     ROOT_PATH . 'logs/');
define('STATIC_URL',   BASE_URL  . 'static/');

// ── Session ───────────────────────────────────────────────────
define('SESSION_NAME',     'PDL_HELPDESK_SESSION');
define('SESSION_LIFETIME', 28800);   // 8 hours in seconds

// ── File Uploads ─────────────────────────────────────────────
define('MAX_FILE_SIZE',    10 * 1024 * 1024);   // 10 MB per file
define('MAX_FILES_PER_UPLOAD', 5);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
]);

// ── Pagination ────────────────────────────────────────────────
define('TICKETS_PER_PAGE', 25);
define('USERS_PER_PAGE',   20);
define('LOGS_PER_PAGE',    50);

// ── Ticket Code Prefix ────────────────────────────────────────
define('TICKET_PREFIX', 'PDL');      // generates PDL-000001

// ── Error Reporting ───────────────────────────────────────────
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ── Timezone ──────────────────────────────────────────────────
date_default_timezone_set('Asia/Dhaka');
