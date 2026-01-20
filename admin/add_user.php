<?php
require_once("admin_guard.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $role = $_POST["role"];

    if ($username && $password && $role) {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, role) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $username, $hashed, $role);

        if ($stmt->execute()) {
            $message = "User created successfully";
        } else {
            $message = "Username already exists";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User - Admin</title>
</head>
<body>

<h2>Add New User</h2>

<?php if ($message) echo "<p>$message</p>"; ?>

<form method="post">
    <input type="text" name="username" placeholder="Username" required><br><br>

    <input type="password" name="password" placeholder="Password" required><br><br>

    <select name="role" required>
        <option value="">Select Role</option>
        <option value="user">Normal User</option>
        <option value="it">IT Support</option>
        <option value="mis">MIS Support</option>
        <option value="admin">Admin</option>
    </select><br><br>

    <button type="submit">Create User</button>
</form>

<br>
<a href="dashboard.php">⬅ Back to Dashboard</a>

</body>
</html>
