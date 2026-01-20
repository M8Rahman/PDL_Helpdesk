<?php
require_once("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["username"] = $user["username"];

            if ($user["role"] == "admin") {
                header("Location: ../admin/dashboard.php");
            } elseif ($user["role"] == "user") {
                header("Location: ../user/dashboard.php");
            } else {
                header("Location: ../support/dashboard.php");
            }
            exit;
        }
    }

    $error = "Invalid login credentials";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PDL Helpdesk Login</title>
</head>
<body>
<h2>PDL Helpdesk Login</h2>

<?php if (!empty($error)) echo "<p style='color:red'>$error</p>"; ?>

<form method="post">
    <input type="text" name="username" placeholder="Username" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
</form>
</body>
</html>
