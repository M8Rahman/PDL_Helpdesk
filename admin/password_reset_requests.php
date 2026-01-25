<?php
require_once("admin_guard.php");

$message = "";
$messageType = "";

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reset_password"])) {
    $request_id = $_POST["request_id"];
    $user_id = $_POST["user_id"];
    $new_password = $_POST["new_password"];
    
    if ($request_id && $user_id && $new_password) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update user password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        
        if ($stmt->execute()) {
            // Mark request as done
            $stmt = $conn->prepare("
                UPDATE password_reset_requests 
                SET status = 'done', processed_at = NOW(), processed_by = ? 
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $_SESSION["user_id"], $request_id);
            $stmt->execute();
            
            $message = "Password reset successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to reset password";
            $messageType = "error";
        }
    }
}

// Fetch all requests
$requests = $conn->query("
    SELECT 
        pr.*,
        u.username,
        p.username as processed_by_name
    FROM password_reset_requests pr
    JOIN users u ON pr.user_id = u.id
    LEFT JOIN users p ON pr.processed_by = p.id
    ORDER BY pr.status ASC, pr.requested_at DESC
");

$pageTitle = "Password Reset Requests - Admin";
$headerTitle = "🔑 Password Reset Requests";
$basePath = "../";
include("../includes/header.php");
?>

<style>
    .request-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .request-card.pending {
        border-left: 4px solid #f39c12;
    }
    
    .request-card.done {
        border-left: 4px solid #27ae60;
        opacity: 0.7;
    }
    
    .request-info {
        flex: 1;
    }
    
    .request-info h4 {
        margin: 0 0 10px 0;
        color: #2c3e50;
    }
    
    .request-info p {
        margin: 5px 0;
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .request-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .reset-form {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .reset-form input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        width: 200px;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-done {
        background: #d4edda;
        color: #155724;
    }
</style>

<?php if ($message): ?>
    <div class="message message-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div style="margin-bottom: 20px;">
    <a href="dashboard.php" class="btn btn-secondary">⬅ Back to Dashboard</a>
</div>

<?php if ($requests->num_rows === 0): ?>
    <div class="message message-info">No password reset requests found</div>
<?php else: ?>
    <?php while ($req = $requests->fetch_assoc()): ?>
        <div class="request-card <?= $req['status'] ?>">
            <div class="request-info">
                <h4>
                    <?= htmlspecialchars($req['username']) ?>
                    <span class="status-badge status-<?= $req['status'] ?>">
                        <?= strtoupper($req['status']) ?>
                    </span>
                </h4>
                <p>📅 Requested: <?= date('M d, Y h:i A', strtotime($req['requested_at'])) ?></p>
                <?php if ($req['status'] === 'done'): ?>
                    <p>✅ Processed by: <?= htmlspecialchars($req['processed_by_name']) ?> 
                       on <?= date('M d, Y h:i A', strtotime($req['processed_at'])) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="request-actions">
                <?php if ($req['status'] === 'pending'): ?>
                    <form method="post" class="reset-form" onsubmit="return confirm('Reset password for <?= htmlspecialchars($req['username']) ?>?');">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                        <input type="hidden" name="user_id" value="<?= $req['user_id'] ?>">
                        <input type="text" name="new_password" placeholder="New password" required>
                        <button type="submit" name="reset_password" class="btn btn-success btn-sm">
                            Reset Password
                        </button>
                    </form>
                <?php else: ?>
                    <span style="color: #27ae60; font-weight: bold;">✓ Completed</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<?php include("../includes/footer.php"); ?>