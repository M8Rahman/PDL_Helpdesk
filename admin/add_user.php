<?php
require_once("admin_guard.php");

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $role = $_POST["role"];

    if ($username && $password && $role) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())"
        );
        $stmt->bind_param("sss", $username, $hashed, $role);

        if ($stmt->execute()) {
            $message = "User created successfully!";
            $messageType = "success";
        } else {
            $message = "Username already exists";
            $messageType = "error";
        }
    } else {
        $message = "All fields are required";
        $messageType = "error";
    }
}

$pageTitle = "Add User - Admin";
$headerTitle = "➕ Add New User";
$basePath = "../";
include("../includes/header.php");
?>

<?php if ($message): ?>
    <div class="message message-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="section">
    <form method="post">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter username" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>

        <div class="form-group">
            <label>Role</label>
            <select name="role" required>
                <option value="">-- Select Role --</option>
                <option value="user">Normal User</option>
                <option value="it">IT Support</option>
                <option value="mis">MIS Support</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <button type="submit" class="btn-success">Create User</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include("../includes/footer.php"); ?>