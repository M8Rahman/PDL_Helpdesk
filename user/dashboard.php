<?php
require_once("user_guard.php");

$user_id = $_SESSION["user_id"];

/* Summary data */
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Solved') AS solved,
        SUM(status = 'Pending') AS pending
    FROM tickets
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

/* Get unread notifications count */
$notifStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$notifStmt->bind_param("i", $user_id);
$notifStmt->execute();
$unreadCount = $notifStmt->get_result()->fetch_assoc()["count"];

$pageTitle = "User Dashboard - PDL Helpdesk";
$headerTitle = "User Dashboard";
$basePath = "../";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?= $basePath ?>assets/style.css">
    <style>
        .custom-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #007bff;
        }
        
        .custom-header h2 {
            color: #007bff;
            font-size: 28px;
        }
        
        .header-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .notification-bell {
            position: relative;
            cursor: pointer;
            font-size: 24px;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .notification-bell:hover {
            background: #e9ecef;
            transform: scale(1.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .notification-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 15px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: right 0.3s ease;
            overflow-y: auto;
        }
        
        .notification-panel.active {
            right: 0;
        }
        
        .notification-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-header h3 {
            margin: 0;
        }
        
        .close-notif {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #ecf0f1;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .notification-item .notif-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .notification-item .notif-message {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .notification-item .notif-time {
            color: #95a5a6;
            font-size: 12px;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
        
        /* Compact Summary Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .card {
            padding: 20px;
            border-radius: 10px;
            color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        .card h3 {
            font-size: 13px;
            margin-bottom: 8px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card .number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .card button {
            margin-top: 10px;
            padding: 6px 14px;
            font-size: 12px;
        }
        
        /* Split Layout */
        .split-layout {
            display: grid;
            grid-template-columns: 30% 70%;
            gap: 25px;
        }
        
        .left-panel {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .right-panel {
            min-height: 500px;
        }
        
        .password-reset-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .password-reset-section h4 {
            color: #e74c3c;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        @media (max-width: 1024px) {
            .split-layout {
                grid-template-columns: 1fr;
            }
            
            .left-panel {
                position: relative;
                top: 0;
            }
            
            .notification-panel {
                width: 100%;
                right: -100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="custom-header">
            <h2><?= $headerTitle ?></h2>
            <div class="header-right">
                <div class="notification-bell" onclick="toggleNotifications()">
                    🔔
                    <?php if ($unreadCount > 0): ?>
                        <span class="notification-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </div>
                <span class="username">👤 <?= htmlspecialchars($_SESSION["username"]) ?></span>
                <a href="<?= $basePath ?>auth/logout.php" class="btn btn-secondary btn-sm">Logout</a>
            </div>
        </div>

        <?php if (isset($_SESSION["message"])): ?>
            <div class="message message-<?= $_SESSION["message_type"] ?>">
                <?= htmlspecialchars($_SESSION["message"]) ?>
            </div>
            <?php 
            unset($_SESSION["message"]);
            unset($_SESSION["message_type"]);
            ?>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="cards">
            <div class="card blue">
                <h3>Total Tickets</h3>
                <div class="number"><?= $summary["total"] ?? 0 ?></div>
                <button onclick="loadUserTickets()">View All</button>
            </div>

            <div class="card green">
                <h3>Solved</h3>
                <div class="number"><?= $summary["solved"] ?? 0 ?></div>
                <button onclick="loadUserTickets('solved')">View Details</button>
            </div>

            <div class="card orange">
                <h3>Pending</h3>
                <div class="number"><?= $summary["pending"] ?? 0 ?></div>
                <button onclick="loadUserTickets('pending')">View Details</button>
            </div>
        </div>

        <!-- Split Layout -->
        <div class="split-layout">
            <!-- Left Panel: New Ticket Form -->
            <div class="left-panel">
                <h3 style="color: #007bff; margin-bottom: 20px;">📝 Submit New Problem</h3>
                
                <form method="post" action="../actions/create_ticket.php">
                    <div class="form-group">
                        <label>Select Team</label>
                        <select name="team" required>
                            <option value="">-- Select Team --</option>
                            <option value="it">IT Team</option>
                            <option value="mis">MIS Team</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Describe Your Problem</label>
                        <textarea name="problem" placeholder="Please provide details about your issue..." required style="min-height: 150px;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success" style="width: 100%;">Submit Ticket</button>
                </form>
                
                <!-- Password Reset Request -->
                <div class="password-reset-section">
                    <h4>🔑 Forgot Password?</h4>
                    <form method="post" action="../actions/request_password_reset.php" onsubmit="return confirm('Request password reset? Admin will change your password.');">
                        <input type="hidden" name="user_id" value="<?= $user_id ?>">
                        <button type="submit" class="btn btn-secondary" style="width: 100%;">
                            Request Password Reset
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Panel: Filters & Table -->
            <div class="right-panel">
                <div class="section">
                    <h3>🔍 My Tickets</h3>
                    
                    <form id="userFilterForm" class="form-inline" onsubmit="event.preventDefault(); loadUserTickets();">
                        <div class="form-group">
                            <label>Ticket No</label>
                            <input type="text" name="ticket_no" placeholder="PDL-IT-000001">
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All</option>
                                <option value="Pending">Pending</option>
                                <option value="Solved">Solved</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>From Date</label>
                            <input type="date" name="from_date">
                        </div>

                        <div class="form-group">
                            <label>To Date</label>
                            <input type="date" name="to_date">
                        </div>

                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit">Apply Filters</button>
                        </div>
                    </form>
                </div>

                <!-- Ticket Table -->
                <div id="ticketTable">
                    <div class="loading">Loading tickets</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Panel -->
    <div class="overlay" id="notifOverlay" onclick="toggleNotifications()"></div>
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            <h3>🔔 Notifications</h3>
            <button class="close-notif" onclick="toggleNotifications()">×</button>
        </div>
        <div id="notificationList">
            <div class="loading">Loading notifications...</div>
        </div>
    </div>

    <script>
        function loadUserTickets(quickFilter = null) {
            let url = "../ajax/user_tickets.php";

            if (quickFilter) {
                url += "?status=" + (quickFilter === 'solved' ? 'Solved' : 'Pending');
            } else {
                const form = document.getElementById("userFilterForm");
                const params = new URLSearchParams(new FormData(form)).toString();
                if (params) url += "?" + params;
            }

            fetch(url)
                .then(res => res.text())
                .then(html => {
                    document.getElementById("ticketTable").innerHTML = html;
                    
                    // Auto scroll to table
                    document.getElementById("ticketTable").scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                })
                .catch(err => {
                    document.getElementById("ticketTable").innerHTML = 
                        '<div class="message message-error">Failed to load tickets</div>';
                });
        }

        function toggleNotifications() {
            const panel = document.getElementById('notificationPanel');
            const overlay = document.getElementById('notifOverlay');
            
            panel.classList.toggle('active');
            overlay.classList.toggle('active');
            
            if (panel.classList.contains('active')) {
                loadNotifications();
            }
        }

        function loadNotifications() {
            fetch('../ajax/get_notifications.php')
                .then(res => res.text())
                .then(html => {
                    document.getElementById('notificationList').innerHTML = html;
                })
                .catch(err => {
                    document.getElementById('notificationList').innerHTML = 
                        '<div class="message message-error">Failed to load notifications</div>';
                });
        }

        function markAsRead(notifId) {
            fetch('../ajax/mark_notification_read.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'notification_id=' + notifId
            })
            .then(() => {
                loadNotifications();
                updateNotificationBadge();
                loadUserTickets(); // Refresh tickets
            });
        }

        function updateNotificationBadge() {
            fetch('../ajax/get_unread_count.php')
                .then(res => res.json())
                .then(data => {
                    const bell = document.querySelector('.notification-bell');
                    const existingBadge = bell.querySelector('.notification-badge');
                    
                    if (data.count > 0) {
                        if (existingBadge) {
                            existingBadge.textContent = data.count;
                        } else {
                            bell.innerHTML += `<span class="notification-badge">${data.count}</span>`;
                        }
                    } else {
                        if (existingBadge) {
                            existingBadge.remove();
                        }
                    }
                });
        }

        // Load tickets on page load
        loadUserTickets();
        
        // Check for new notifications every 30 seconds
        setInterval(updateNotificationBadge, 30000);
    </script>
</body>
</html>