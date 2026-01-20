<?php
require_once("../config/db.php");

if (!in_array($_SESSION["role"], ["it", "mis"])) {
    exit("Unauthorized");
}

$ticket_id = $_POST["ticket_id"];
$solution = trim($_POST["solution"]);
$user_id = $_SESSION["user_id"];

if (!$ticket_id || !$solution) {
    exit("Invalid input");
}

$stmt = $conn->prepare("
    UPDATE tickets SET
        solution = ?,
        status = 'Solved',
        solved_at = NOW(),
        solved_by = ?,
        updated_at = NOW(),
        updated_by = ?
    WHERE id = ?
");

$stmt->bind_param("siii", $solution, $user_id, $user_id, $ticket_id);
$stmt->execute();

header("Location: ../support/dashboard.php");
