<?php
$conn = new mysqli("localhost", "root", "", "pdl_helpdesk");

if ($conn->connect_error) {
    die("Database connection failed");
}

session_start();
