<?php
require_once("../config/db.php");

// Check remember me cookie
if (!isset($_SESSION["user_id"]) && isset($_COOKIE["remember_user"])) {
    $token = $_COOKIE["remember_user"];
    $stmt = $conn->prepare("SELECT * FROM users WHERE MD5(CONCAT(id, username)) = ? AND status = 'active'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["role"] = $user["role"];
        $_SESSION["username"] = $user["username"];
        
        $redirect = match($user["role"]) {
            "admin", "super_admin" => "../admin/dashboard.php",
            "user" => "../user/dashboard.php",
            default => "../support/dashboard.php"
        };
        header("Location: $redirect");
        exit;
    }
}

// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    $role = $_SESSION["role"];
    $redirect = match($role) {
        "admin", "super_admin" => "../admin/dashboard.php",
        "user" => "../user/dashboard.php",
        default => "../support/dashboard.php"
    };
    header("Location: $redirect");
    exit;
}

$error = "";
$success = "";

// Handle Login
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login"])) {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $remember = isset($_POST["remember"]);

    if (!$username || !$password) {
        $error = "Please enter both username and password";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Check if account is active
            if (isset($user["status"]) && $user["status"] === "inactive") {
                $error = "Your account has been deactivated. Please contact admin.";
            } elseif (password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["username"] = $user["username"];

                // Set remember me cookie
                if ($remember) {
                    $token = md5($user["id"] . $user["username"]);
                    setcookie("remember_user", $token, time() + (86400 * 30), "/"); // 30 days
                }

                $redirect = match($user["role"]) {
                    "admin", "super_admin" => "../admin/dashboard.php",
                    "user" => "../user/dashboard.php",
                    default => "../support/dashboard.php"
                };
                header("Location: $redirect");
                exit;
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
    }
}

// Handle Forget Password
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["forgot_password"])) {
    $username = trim($_POST["forgot_username"]);
    
    if (!$username) {
        $error = "Please enter your username";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Insert password reset request
            $stmt = $conn->prepare("INSERT INTO password_reset_requests (user_id, username, status, requested_at) VALUES (?, ?, 'pending', NOW())");
            $stmt->bind_param("is", $user["id"], $username);
            $stmt->execute();
            
            $success = "Password reset request sent to admin successfully!";
        } else {
            $error = "Username not found";
        }
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
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        #particles-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 1;
        }
        
        .login-wrapper {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 800px;
            height: 500px;
            display: flex;
            overflow: hidden;
            position: relative;
        }
        
        .login-section, .forgot-section {
            position: absolute;
            height: 100%;
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px;
        }
        
        .login-section {
            left: 0;
            width: 70%;
            background: white;
        }
        
        .forgot-section {
            right: 0;
            width: 30%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            align-items: center;
            justify-content: center;
        }
        
        /* When forgot password is active */
        .login-card.forgot-active .login-section {
            width: 30%;
            left: 0;
        }
        
        .login-card.forgot-active .forgot-section {
            width: 70%;
            right: 0;
        }
        
        .login-section h2 {
            color: #007bff;
            margin-bottom: 10px;
            font-size: 32px;
        }
        
        .login-section p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .remember-me input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }
        
        .remember-me label {
            margin: 0;
            font-weight: normal;
            color: #666;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .forgot-section .content {
            text-align: center;
            transition: opacity 0.3s;
        }
        
        .forgot-section h3 {
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .forgot-section .btn-forgot {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .forgot-section .btn-forgot:hover {
            background: white;
            color: #667eea;
        }
        
        /* Compact mode styles */
        .login-section.compact {
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .login-section.compact h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .login-section.compact .form-container {
            display: none;
        }
        
        .forgot-section.expanded .forgot-form {
            display: block;
            width: 100%;
        }
        
        .forgot-form {
            display: none;
            width: 100%;
        }
        
        .forgot-form input {
            width: 100%;
            padding: 12px;
            border: 2px solid white;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            margin-bottom: 20px;
        }
        
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .btn-back:hover {
            background: white;
            color: #667eea;
        }
    </style>
</head>
<body>
    <canvas id="particles-canvas"></canvas>
    
    <div class="login-wrapper">
        <div class="login-card" id="loginCard">
            <!-- Login Section -->
            <div class="login-section" id="loginSection">
                <div class="form-container">
                    <h2>🔐 Welcome Back </h2>
                    <p>Sign in to PDL Helpdesk</p>

                    <?php if ($error && !isset($_POST["forgot_password"])): ?>
                        <div class="message message-error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" placeholder="Enter your username" required autofocus>
                        </div>

                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Enter your password" required>
                        </div>

                        <div class="remember-me">
                            <input type="checkbox" name="remember" id="remember">
                            <label for="remember">Remember me for 30 days</label>
                        </div>

                        <button type="submit" name="login" class="btn-login">Sign In</button>
                    </form>
                </div>
                
                <!-- Compact mode (when forgot is active) -->
                <div style="display: none;" id="backToLogin">
                    <h2>Login</h2>
                    <button class="btn-login" onclick="toggleForgotPassword()">Back to Login</button>
                </div>
            </div>

            <!-- Forgot Password Section -->
            <div class="forgot-section" id="forgotSection">
                <div class="content" id="forgotButton">
                    <h3>Forgot Password?</h3>
                    <p>Don't worry, we'll help you reset it</p>
                    <button class="btn-forgot" onclick="toggleForgotPassword()">Reset Password</button>
                </div>
                
                <div class="forgot-form" id="forgotForm">
                    <h2 style="margin-bottom: 20px;">Reset Password</h2>
                    
                    <?php if ($error && isset($_POST["forgot_password"])): ?>
                        <div class="message message-error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="message message-success"><?= htmlspecialchars($success) ?></div>
                        <script>
                            setTimeout(function() {
                                toggleForgotPassword();
                            }, 2000);
                        </script>
                    <?php endif; ?>
                    
                    <form method="post">
                        <input type="text" name="forgot_username" placeholder="Enter your username" required>
                        <button type="submit" name="forgot_password" class="btn-login">Submit Request</button>
                        <button type="button" class="btn-back" onclick="toggleForgotPassword()">Back to Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Particle System
        const canvas = document.getElementById('particles-canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        
        const particles = [];
        const particleCount = 100;
        
        class Particle {
            constructor(x, y) {
                this.x = x || Math.random() * canvas.width;
                this.y = y || Math.random() * canvas.height;
                this.size = Math.random() * 3 + 1;
                this.speedX = Math.random() * 2 - 1;
                this.speedY = Math.random() * 2 - 1;
                this.opacity = Math.random() * 0.5 + 0.2;
                this.shape = Math.floor(Math.random() * 3); // 0: circle, 1: square, 2: triangle
            }
            
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                
                if (this.x > canvas.width) this.x = 0;
                if (this.x < 0) this.x = canvas.width;
                if (this.y > canvas.height) this.y = 0;
                if (this.y < 0) this.y = canvas.height;
            }
            
            draw() {
                ctx.fillStyle = `rgba(200, 200, 200, ${this.opacity})`;
                ctx.strokeStyle = `rgba(255, 255, 255, ${this.opacity})`;
                ctx.lineWidth = 1;
                
                if (this.shape === 0) {
                    // Circle
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                } else if (this.shape === 1) {
                    // Square
                    ctx.fillRect(this.x, this.y, this.size * 2, this.size * 2);
                } else {
                    // Triangle
                    ctx.beginPath();
                    ctx.moveTo(this.x, this.y - this.size);
                    ctx.lineTo(this.x - this.size, this.y + this.size);
                    ctx.lineTo(this.x + this.size, this.y + this.size);
                    ctx.closePath();
                    ctx.fill();
                }
            }
        }
        
        function init() {
            for (let i = 0; i < particleCount; i++) {
                particles.push(new Particle());
            }
        }
        
        function animate() {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });
            
            requestAnimationFrame(animate);
        }
        
        // Add particles on click
        canvas.addEventListener('click', (e) => {
            for (let i = 0; i < 10; i++) {
                particles.push(new Particle(e.clientX, e.clientY));
            }
        });
        
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
        
        init();
        animate();
        
        // Toggle Forgot Password
        function toggleForgotPassword() {
            const card = document.getElementById('loginCard');
            const loginSection = document.getElementById('loginSection');
            const forgotButton = document.getElementById('forgotButton');
            const forgotForm = document.getElementById('forgotForm');
            const formContainer = loginSection.querySelector('.form-container');
            const backToLogin = document.getElementById('backToLogin');
            
            card.classList.toggle('forgot-active');
            
            if (card.classList.contains('forgot-active')) {
                // Show forgot password form
                forgotButton.style.display = 'none';
                forgotForm.style.display = 'block';
                formContainer.style.display = 'none';
                backToLogin.style.display = 'block';
                loginSection.classList.add('compact');
            } else {
                // Show login form
                forgotButton.style.display = 'block';
                forgotForm.style.display = 'none';
                formContainer.style.display = 'block';
                backToLogin.style.display = 'none';
                loginSection.classList.remove('compact');
            }
        }
    </script>
</body>
</html>