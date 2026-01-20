<?php
require_once("../config/db.php");

$ticket_id = $_POST["ticket_id"];
$solution = $_POST["solution"];
$user_id = $_SESSION["user_id"];

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
$stmt->execute();

echo "Ticket solved successfully";
