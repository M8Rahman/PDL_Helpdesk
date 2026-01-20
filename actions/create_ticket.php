<?php
require_once("../config/db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    exit("Unauthorized");
}

$team = strtolower(trim($_POST["team"] ?? ''));
$problem = trim($_POST["problem"] ?? '');
$user_id = $_SESSION["user_id"];

if (!$team || !$problem || !in_array($team, ['it', 'mis'])) {
    exit("Invalid input");
}

/* Get last ticket number for this team */
$stmt = $conn->prepare(
    "SELECT ticket_no FROM tickets WHERE team = ? ORDER BY id DESC LIMIT 1"
);
$stmt->bind_param("s", $team);
$stmt->execute();
$result = $stmt->get_result();

$nextNumber = 1;
if ($row = $result->fetch_assoc()) {
    preg_match('/(\d+)$/', $row["ticket_no"], $m);
    $nextNumber = intval($m[1]) + 1;
}

$ticket_no = "PDL-" . strtoupper($team) . "-" . str_pad($nextNumber, 6, "0", STR_PAD_LEFT);

/* Insert ticket */
$stmt = $conn->prepare(
    "INSERT INTO tickets (ticket_no, user_id, team, problem, status, created_at)
     VALUES (?, ?, ?, ?, 'Pending', NOW())"
);
$stmt->bind_param("siss", $ticket_no, $user_id, $team, $problem);
if ($stmt->execute()) {
    header("Location: ../user/dashboard.php");
} else {
    exit("Failed to create ticket");
}
exit;