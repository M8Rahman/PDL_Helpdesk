<?php
require_once("../config/db.php");

// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    $role = $_SESSION["role"];
    if ($role === "admin") {
        header("Location: ../admin/dashboard.php");
    } elseif ($role === "user") {
        header("Location: ../user/dashboard.php");
    } else {
        header("Location: ../support/dashboard.php");
    }
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (!$username || !$password) {
        $error = "Please enter both username and password";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["username"] = $user["username"];

                if ($user["role"] === "admin") {
                    header("Location: ../admin/dashboard.php");
                } elseif ($user["role"] === "user") {
                    header("Location: ../user/dashboard.php");
                } else {
                    header("Location: ../support/dashboard.php");
                }
                exit;
            }
        }

        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PDL Helpdesk</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-container h2 {
            color: #007bff;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .login-container .form-group {
            margin-bottom: 20px;
        }
        
        .login-container input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .login-container button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>🔐 PDL Helpdesk Login</h2>

        <?php if ($error): ?>
            <div class="message message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required autofocus>
            </div>

            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>