<?php
// Database configuration
$conn = new mysqli("localhost", "root", "", "pdl_helpdesk");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}