<?php
session_start();
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE["remember_user"])) {
    setcookie("remember_user", "", time() - 3600, "/");
}

header("Location: login.php");
exit;