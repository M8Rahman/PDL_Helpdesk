<?php
// require_once("../config/db.php");

// if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin" && $_SESSION["role"] !== "super_admin") {
//     header("Location: ../auth/login.php");
//     exit;
// }

require_once("../config/db.php");

if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ["admin", "super_admin"])) {
    header("Location: ../auth/login.php");
    exit;
}
