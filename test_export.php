<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'core/Auth.php';
require_once 'core/RBAC.php';
require_once 'core/Model.php';
require_once 'core/Controller.php';
require_once 'modules/tickets/models/TicketModel.php';

Auth::startSession();

// Simulate a logged-in admin user (temporarily)
if (!Auth::isLoggedIn()) {
    // You might need to log in first through the browser
    die('Please log in first');
}

require_once 'modules/tickets/controllers/TicketExportController.php';

$controller = new TicketExportController();
$controller->export();

echo "Test completed";