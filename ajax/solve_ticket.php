<?php
require_once("../config/db.php");

if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ["it", "mis"])) {
    exit("Unauthorized");
}

$ticket_id = $_POST["ticket_id"] ?? null;
$solution = trim($_POST["solution"] ?? '');
$user_id = $_SESSION["user_id"];

if (!$ticket_id || !$solution) {
    exit("Invalid input");
}

// Get ticket info
$ticketStmt = $conn->prepare("SELECT user_id, ticket_no FROM tickets WHERE id = ?");
$ticketStmt->bind_param("i", $ticket_id);
$ticketStmt->execute();
$ticketInfo = $ticketStmt->get_result()->fetch_assoc();

// Update ticket
$stmt = $conn->prepare("
    UPDATE tickets
    SET
        solution = ?,
        status = 'Solved',
        solved_by = ?,
        solved_at = NOW(),
        updated_at = NOW(),
        updated_by = ?
    WHERE id = ?
");
$stmt->bind_param("siii", $solution, $user_id, $user_id, $ticket_id);

if ($stmt->execute()) {
    // Create notification for the ticket creator
    $message = "Your ticket has been solved! Solution: " . substr($solution, 0, 100) . (strlen($solution) > 100 ? '...' : '');
    
    $notifStmt = $conn->prepare("
        INSERT INTO notifications (user_id, ticket_id, message, type, created_at)
        VALUES (?, ?, ?, 'solved', NOW())
    ");
    $notifStmt->bind_param("iis", $ticketInfo['user_id'], $ticket_id, $message);
    $notifStmt->execute();
    
    echo "Ticket solved successfully!";
} else {
    echo "Failed to solve ticket";
}