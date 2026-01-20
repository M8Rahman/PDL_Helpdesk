<?php
require_once("../config/db.php");

if (
    !isset($_SESSION["user_id"]) ||
    !in_array($_SESSION["role"], ["it", "mis"])
) {
    header("Location: ../auth/login.php");
    exit;
}

$team = $_SESSION["role"]; // IT or MIS
