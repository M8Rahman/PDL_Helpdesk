<?php
require_once("../config/db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    exit("Unauthorized");
}

$user_id = $_SESSION["user_id"];

// Get username
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$username = $stmt->get_result()->fetch_assoc()["username"];

// Insert password reset request
$stmt = $conn->prepare("
    INSERT INTO password_reset_requests (user_id, username, status, requested_at) 
    VALUES (?, ?, 'pending', NOW())
");
$stmt->bind_param("is", $user_id, $username);

if ($stmt->execute()) {
    $_SESSION["message"] = "Password reset request sent to admin successfully!";
    $_SESSION["message_type"] = "success";
} else {
    $_SESSION["message"] = "Failed to send request";
    $_SESSION["message_type"] = "error";
}

header("Location: ../user/dashboard.php");
exit;