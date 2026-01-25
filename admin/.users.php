<?php
require_once("admin_guard.php");

$result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id DESC");

$pageTitle = "Manage Users - Admin";
$headerTitle = "👥 Manage Users";
$basePath = "../";
include("../includes/header.php");
?>

<div style="margin-bottom: 20px;">
    <a href="add_user.php" class="btn">➕ Add New User</a>
    <a href="dashboard.php" class="btn btn-secondary">⬅ Back to Dashboard</a>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row["id"] ?></td>
            <td><?= htmlspecialchars($row["username"]) ?></td>
            <td>
                <?php
                $roleColors = [
                    'admin' => 'background: #dc3545; color: white; padding: 4px 10px; border-radius: 4px;',
                    'it' => 'background: #17a2b8; color: white; padding: 4px 10px; border-radius: 4px;',
                    'mis' => 'background: #ffc107; color: black; padding: 4px 10px; border-radius: 4px;',
                    'user' => 'background: #28a745; color: white; padding: 4px 10px; border-radius: 4px;'
                ];
                ?>
                <span style="<?= $roleColors[$row['role']] ?? '' ?>">
                    <?= strtoupper($row["role"]) ?>
                </span>
            </td>
            <td><?= date('M d, Y h:i A', strtotime($row["created_at"])) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include("../includes/footer.php"); ?>