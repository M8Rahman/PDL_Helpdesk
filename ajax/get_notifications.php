<?php
require_once("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    exit("Unauthorized");
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("
    SELECT n.*, t.ticket_no 
    FROM notifications n
    JOIN tickets t ON n.ticket_id = t.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div style="padding: 40px; text-align: center; color: #95a5a6;">
            <p style="font-size: 48px; margin: 0;">🔔</p>
            <p>No notifications yet</p>
          </div>';
    exit;
}

while ($notif = $result->fetch_assoc()):
    $timeAgo = timeAgo($notif['created_at']);
?>
    <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>" 
         onclick="markAsRead(<?= $notif['id'] ?>)">
        <div class="notif-title">
            <?= $notif['type'] === 'solved' ? '✅' : '📋' ?> 
            Ticket <?= htmlspecialchars($notif['ticket_no']) ?>
        </div>
        <div class="notif-message">
            <?= htmlspecialchars($notif['message']) ?>
        </div>
        <div class="notif-time">
            <?= $timeAgo ?>
        </div>
    </div>
<?php endwhile;

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M d, Y h:i A', $time);
}
?>