<?php
require_once("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    exit("Unauthorized");
}

$notification_id = $_POST["notification_id"] ?? null;
$user_id = $_SESSION["user_id"];

if ($notification_id) {
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    
    echo "OK";
} else {
    echo "Invalid request";
}