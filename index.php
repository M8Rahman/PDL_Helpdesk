<?php
/**
 * PDL_Helpdesk — Front Controller
 * Pantex Dress Ltd.
 *
 * All requests are routed through this file.
 * URL: http://SERVER_IP/PDL_Helpdesk/?page=module/action
 */

// ── Bootstrap ─────────────────────────────────────────────────
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Core classes (loaded in dependency order)
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/RBAC.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Notification.php';
require_once __DIR__ . '/core/AuditLog.php';
require_once __DIR__ . '/core/Router.php';

// ── Dispatch ──────────────────────────────────────────────────
Router::dispatch();
