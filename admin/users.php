<?php
require_once("admin_guard.php");

$message = "";
$messageType = "";
$isSuperAdmin = ($_SESSION["role"] === "super_admin");

// Handle user status toggle
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["toggle_status"])) {
    $user_id = $_POST["user_id"];
    $current_status = $_POST["current_status"];
    $new_status = ($current_status === "active") ? "inactive" : "active";
    $action = ($new_status === "active") ? "Activated" : "Deactivated";
    $remarks = "$action by " . $_SESSION["username"] . " on " . date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("UPDATE users SET status = ?, remarks = ? WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $remarks, $user_id);
    
    if ($stmt->execute()) {
        $message = "User $action successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to update user status";
        $messageType = "error";
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {
    $user_id = $_POST["user_id"];
    $new_password = $_POST["new_password"];
    $user_role = $_POST["user_role"];
    
    // Check if trying to change another admin's password (only super_admin can)
    if ($user_role === "admin" && !$isSuperAdmin) {
        $message = "Only Super Admin can change other admin passwords";
        $messageType = "error";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        
        if ($stmt->execute()) {
            $message = "Password changed successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to change password";
            $messageType = "error";
        }
    }
}

// Handle username change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_username"])) {
    $user_id = $_POST["user_id"];
    $new_username = trim($_POST["new_username"]);
    $user_role = $_POST["user_role"];
    
    // Check if trying to change another admin's username (only super_admin can)
    if ($user_role === "admin" && !$isSuperAdmin) {
        $message = "Only Super Admin can change other admin usernames";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $new_username, $user_id);
        
        if ($stmt->execute()) {
            $message = "Username changed successfully!";
            $messageType = "success";
        } else {
            $message = "Username already exists";
            $messageType = "error";
        }
    }
}

$result = $conn->query("SELECT * FROM users ORDER BY id DESC");

$pageTitle = "Manage Users - Admin";
$headerTitle = "👥 Manage Users";
$basePath = "../";
include("../includes/header.php");
?>

<style>
    .user-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid #3498db;
    }
    
    .user-card.inactive {
        opacity: 0.6;
        border-left-color: #e74c3c;
    }
    
    .user-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .user-info h4 {
        margin: 0 0 5px 0;
        color: #2c3e50;
        font-size: 18px;
    }
    
    .user-meta {
        color: #7f8c8d;
        font-size: 13px;
    }
    
    .role-badge {
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .role-super_admin {
        background: #e74c3c;
        color: white;
    }
    
    .role-admin {
        background: #e67e22;
        color: white;
    }
    
    .role-it {
        background: #3498db;
        color: white;
    }
    
    .role-mis {
        background: #9b59b6;
        color: white;
    }
    
    .role-user {
        background: #27ae60;
        color: white;
    }
    
    .user-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .action-form {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }
    
    .action-form input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    
    .action-form button {
        white-space: nowrap;
    }
    
    .remarks-text {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        font-size: 12px;
        color: #555;
        margin-top: 10px;
    }
</style>

<?php if ($message): ?>
    <div class="message message-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div style="margin-bottom: 20px; display: flex; gap: 10px;">
    <a href="add_user.php" class="btn">➕ Add New User</a>
    <a href="dashboard.php" class="btn btn-secondary">⬅ Back to Dashboard</a>
</div>

<?php while ($user = $result->fetch_assoc()): ?>
    <div class="user-card <?= $user['status'] ?? 'active' ?>">
        <div class="user-header">
            <div class="user-info">
                <h4>
                    <?= htmlspecialchars($user['username']) ?>
                    <span class="role-badge role-<?= $user['role'] ?>">
                        <?= str_replace('_', ' ', $user['role']) ?>
                    </span>
                    <?php if (isset($user['status'])): ?>
                        <span class="status-badge status-<?= $user['status'] ?>">
                            <?= strtoupper($user['status']) ?>
                        </span>
                    <?php endif; ?>
                </h4>
                <div class="user-meta">
                    ID: <?= $user['id'] ?> | 
                    Created: <?= date('M d, Y', strtotime($user['created_at'])) ?>
                </div>
            </div>
        </div>
        
        <?php 
        $canEdit = true;
        // Super admin can edit anyone, regular admin cannot edit other admins
        if ($user['role'] === 'admin' && !$isSuperAdmin && $user['id'] !== $_SESSION['user_id']) {
            $canEdit = false;
        }
        if ($user['role'] === 'super_admin' && $user['id'] !== $_SESSION['user_id']) {
            $canEdit = false;
        }
        ?>
        
        <?php if ($canEdit): ?>
        <div class="user-actions">
            <!-- Change Username -->
            <form method="post" class="action-form">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <input type="hidden" name="user_role" value="<?= $user['role'] ?>">
                <div style="flex: 1;">
                    <label style="font-size: 12px; color: #7f8c8d; display: block; margin-bottom: 5px;">
                        Change Username
                    </label>
                    <input type="text" name="new_username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <button type="submit" name="change_username" class="btn btn-sm">Update</button>
            </form>
            
            <!-- Change Password -->
            <form method="post" class="action-form">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <input type="hidden" name="user_role" value="<?= $user['role'] ?>">
                <div style="flex: 1;">
                    <label style="font-size: 12px; color: #7f8c8d; display: block; margin-bottom: 5px;">
                        Change Password
                    </label>
                    <input type="text" name="new_password" placeholder="New password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-sm btn-secondary">Change</button>
            </form>
            
            <!-- Toggle Status -->
            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
            <form method="post" class="action-form" style="align-items: center;" 
                  onsubmit="return confirm('<?= ($user['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate' ?> this user?');">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <input type="hidden" name="current_status" value="<?= $user['status'] ?? 'active' ?>">
                <button type="submit" name="toggle_status" class="btn btn-sm <?= ($user['status'] ?? 'active') === 'active' ? 'btn-danger' : 'btn-success' ?>" style="width: 100%;">
                    <?= ($user['status'] ?? 'active') === 'active' ? '🚫 Deactivate' : '✅ Activate' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php else: ?>
            <div class="message message-info" style="margin: 0;">
                🔒 Only Super Admin can modify this account
            </div>
        <?php endif; ?>
        
        <?php if (!empty($user['remarks'])): ?>
            <div class="remarks-text">
                <strong>Remarks:</strong> <?= htmlspecialchars($user['remarks']) ?>
            </div>
        <?php endif; ?>
    </div>
<?php endwhile; ?>

<?php include("../includes/footer.php"); ?>