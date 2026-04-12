<?php
/**
 * PDL_Helpdesk — Router
 *
 * Maps ?page= URL parameters to controller actions.
 * GET and POST routes are now explicitly separated to prevent
 * the POST-suffix logic from interfering with GET-only pages
 * like Reports and Audit which were returning 404.
 */

class Router
{
    /**
     * GET-only routes — always resolved regardless of request method.
     */
    private static array $getRoutes = [
        // Auth
        'auth/login'            => ['modules/auth/controllers/LoginController.php',              'LoginController',      'showLogin'],
        'auth/logout'           => ['modules/auth/controllers/LoginController.php',              'LoginController',      'logout'],

        // Dashboard
        'dashboard'             => ['modules/dashboard/controllers/DashboardController.php',     'DashboardController',  'index'],

        // Tickets — list/view/forms
        'tickets'               => ['modules/tickets/controllers/TicketController.php',          'TicketController',     'index'],
        'tickets/create'        => ['modules/tickets/controllers/TicketController.php',          'TicketController',     'create'],
        'tickets/view'          => ['modules/tickets/controllers/TicketController.php',          'TicketController',     'view'],
        'tickets/edit'          => ['modules/tickets/controllers/TicketController.php',          'TicketController',     'edit'],

        // Users — list/forms
        'users'                 => ['modules/users/controllers/UserController.php',              'UserController',       'index'],
        'users/create'          => ['modules/users/controllers/UserController.php',              'UserController',       'create'],
        'users/edit'            => ['modules/users/controllers/UserController.php',              'UserController',       'edit'],

        // Reports — GET always
        'reports'               => ['modules/reports/controllers/ReportController.php',          'ReportController',     'index'],
        'reports/export'        => ['modules/reports/controllers/ReportController.php',          'ReportController',     'export'],

        // Audit — GET always
        'audit'                 => ['modules/audit/controllers/AuditController.php',             'AuditController',      'index'],

        // Notifications — AJAX GET
        'notifications/fetch'   => ['modules/dashboard/controllers/NotificationController.php', 'NotificationController','fetch'],
    ];

    /**
     * POST-only routes — only matched when REQUEST_METHOD === POST.
     */
    private static array $postRoutes = [
        // Auth
        'auth/login'            => ['modules/auth/controllers/LoginController.php',              'LoginController',      'handleLogin'],

        // Tickets — mutations
        'tickets/store'         => ['modules/tickets/controllers/TicketController.php',          'TicketController',     'store'],
        'tickets/update'        => ['modules/tickets/controllers/TicketController.php',          'TicketController',     'update'],
        'tickets/status'        => ['modules/tickets/controllers/TicketController.php',          'TicketController',     'changeStatus'],
        'tickets/transfer'      => ['modules/tickets/controllers/TicketController.php',          'TicketController',     'transfer'],
        'tickets/comment'       => ['modules/tickets/controllers/TicketController.php',          'TicketController',     'addComment'],
        'tickets/upload'        => ['modules/tickets/controllers/TicketController.php',          'TicketController',     'uploadAttachment'],

        // Users — mutations
        'users/store'           => ['modules/users/controllers/UserController.php',              'UserController',       'store'],
        'users/update'          => ['modules/users/controllers/UserController.php',              'UserController',       'update'],
        'users/toggle'          => ['modules/users/controllers/UserController.php',              'UserController',       'toggleActive'],
        'users/reset-password'  => ['modules/users/controllers/UserController.php',              'UserController',       'resetPassword'],

        // Notifications — AJAX POST
        'notifications/read'    => ['modules/dashboard/controllers/NotificationController.php', 'NotificationController','markRead'],
        'notifications/read-all'=> ['modules/dashboard/controllers/NotificationController.php', 'NotificationController','markAllRead'],
    ];

    /**
     * Resolve the current request and dispatch to the correct controller.
     */
    public static function dispatch(): void
    {
        Auth::startSession();

        $page   = trim($_GET['page'] ?? 'auth/login', '/');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Redirect logged-in users away from login page
        if ($page === 'auth/login' && Auth::isLoggedIn()) {
            header('Location: ' . BASE_URL . '?page=dashboard');
            exit;
        }

        // Resolve route based on request method
        if ($method === 'POST') {
            // POST: check post routes first, then fall back to get routes
            $route = self::$postRoutes[$page] ?? self::$getRoutes[$page] ?? null;
        } else {
            // GET (or any other method): only get routes
            $route = self::$getRoutes[$page] ?? null;
        }

        if ($route === null) {
            self::render404();
            return;
        }

        [$file, $class, $action] = $route;
        $fullPath = ROOT_PATH . $file;

        if (!file_exists($fullPath)) {
            error_log("[PDL_Helpdesk] Controller file not found: {$fullPath}");
            self::render404();
            return;
        }

        require_once $fullPath;

        if (!class_exists($class)) {
            error_log("[PDL_Helpdesk] Controller class not found: {$class}");
            self::render404();
            return;
        }

        if (!method_exists($class, $action)) {
            error_log("[PDL_Helpdesk] Controller method not found: {$class}::{$action}");
            self::render404();
            return;
        }

        $controller = new $class();
        $controller->$action();
    }

    private static function render404(): void
    {
        http_response_code(404);
        echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>404 — PDL Helpdesk</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f1f5f9;color:#1e293b}
  .box{text-align:center;padding:48px 32px}
  .code{font-size:7rem;font-weight:800;color:#e2e8f0;line-height:1}
  h2{font-size:1.25rem;font-weight:600;margin:12px 0 6px}
  p{color:#64748b;font-size:.9rem;margin-bottom:24px}
  a{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#0d9488;color:#fff;border-radius:10px;text-decoration:none;font-weight:500;font-size:.875rem;transition:background .15s}
  a:hover{background:#0f766e}
</style>
</head>
<body>
<div class="box">
  <div class="code">404</div>
  <h2>Page Not Found</h2>
  <p>The page you requested does not exist or has been moved.</p>
  <a href="' . BASE_URL . '">← Back to Dashboard</a>
</div>
</body>
</html>';
    }
}
