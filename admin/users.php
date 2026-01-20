<?php
require_once("admin_guard.php");

$result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Users - Admin</title>
</head>
<body>

<h2>All Users</h2>

<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Role</th>
        <th>Created At</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row["id"] ?></td>
            <td><?= htmlspecialchars($row["username"]) ?></td>
            <td><?= strtoupper($row["role"]) ?></td>
            <td><?= $row["created_at"] ?></td>
        </tr>
    <?php endwhile; ?>
</table>

<br>
<a href="dashboard.php">⬅ Back to Dashboard</a>

</body>
</html>
