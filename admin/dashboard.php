<?php
require_once("admin_guard.php");

/* Summary */
$summary = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Solved') AS solved,
        SUM(status = 'Pending') AS pending,
        SUM(team = 'it') AS it_total,
        SUM(team = 'mis') AS mis_total
    FROM tickets
")->fetch_assoc();

/* Avg resolution time (minutes) */
$avgTime = $conn->query("
    SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, solved_at)) AS avg_min
    FROM tickets
    WHERE solved_at IS NOT NULL
")->fetch_assoc()["avg_min"];

/* Leaderboard */
$leaders = $conn->query("
    SELECT u.username, COUNT(*) AS solved_count
    FROM tickets t
    JOIN users u ON u.id = t.solved_by
    WHERE t.status = 'Solved'
    GROUP BY t.solved_by
    ORDER BY solved_count DESC
    LIMIT 10
");

/* Password Reset Requests Count */
$resetCount = $conn->query("
    SELECT COUNT(*) as count FROM password_reset_requests WHERE status = 'pending'
")->fetch_assoc()["count"];

$pageTitle = "Admin Dashboard - PDL Helpdesk";
$headerTitle = "👑 Admin Dashboard";
$basePath = "../";
include("../includes/header.php");
?>

<style>
    .admin-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 30px;
        position: relative;
    }
    
    .left-sidebar {
        position: sticky;
        top: 20px;
        height: fit-content;
    }
    
    .right-content {
        min-height: 100vh;
    }
    
    /* Enhanced Cards */
    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .card {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        position: relative;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.3);
    }
    
    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
        transform: translateX(-100%);
        transition: transform 0.6s;
    }
    
    .card:hover::before {
        transform: translateX(100%);
    }
    
    .card.navy {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    }
    
    .card.steel {
        background: linear-gradient(135deg, #283c86 0%, #45a247 100%);
    }
    
    .card.charcoal {
        background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
    }
    
    .card.slate {
        background: linear-gradient(135deg, #434343 0%, #000000 100%);
    }
    
    .card.oxford {
        background: linear-gradient(135deg, #314755 0%, #26a0da 100%);
    }
    
    .card h3 {
        font-size: 14px;
        margin-bottom: 10px;
        opacity: 0.9;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .card .number {
        font-size: 42px;
        font-weight: bold;
        margin: 15px 0;
    }
    
    .card button {
        margin-top: 15px;
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.3);
        backdrop-filter: blur(10px);
        padding: 8px 16px;
        font-size: 13px;
    }
    
    .card button:hover {
        background: rgba(255,255,255,0.25);
    }
    
    /* Sidebar Sections */
    .sidebar-section {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .sidebar-section h3 {
        font-size: 16px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .performance-metric {
        text-align: center;
        padding: 20px 0;
    }
    
    .performance-metric .value {
        font-size: 36px;
        font-weight: bold;
        color: #3498db;
    }
    
    .performance-metric .label {
        font-size: 13px;
        opacity: 0.8;
        margin-top: 5px;
    }
    
    .leaderboard-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .leaderboard-list li {
        padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .leaderboard-list li:last-child {
        border-bottom: none;
    }
    
    .leaderboard-list .rank {
        background: rgba(255,255,255,0.2);
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
        margin-right: 10px;
    }
    
    .leaderboard-list .name {
        flex: 1;
        font-weight: 600;
    }
    
    .leaderboard-list .count {
        background: rgba(52, 152, 219, 0.3);
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .quick-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .quick-actions .btn {
        width: 100%;
        text-align: center;
        padding: 12px;
        background: rgba(52, 152, 219, 0.2);
        border: 1px solid rgba(52, 152, 219, 0.4);
    }
    
    .quick-actions .btn:hover {
        background: rgba(52, 152, 219, 0.3);
    }
    
    .reset-requests-badge {
        background: #e74c3c;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        margin-left: 8px;
        font-weight: bold;
    }
    
    @media (max-width: 1024px) {
        .admin-layout {
            grid-template-columns: 1fr;
        }
        
        .left-sidebar {
            position: relative;
            top: 0;
        }
    }
</style>

<div class="admin-layout">
    <!-- Left Sidebar -->
    <div class="left-sidebar">
        <!-- Performance -->
        <div class="sidebar-section">
            <h3>📊 Performance</h3>
            <div class="performance-metric">
                <div class="value"><?= $avgTime ? round($avgTime) : '0' ?> <span style="font-size: 20px;">min</span></div>
                <div class="label">Average Resolution Time</div>
            </div>
        </div>
        
        <!-- Top Performers -->
        <div class="sidebar-section">
            <h3>🏆 Top Performers</h3>
            <ul class="leaderboard-list">
                <?php 
                $rank = 1;
                while ($l = $leaders->fetch_assoc()): 
                ?>
                    <li>
                        <span class="rank"><?= $rank ?></span>
                        <span class="name"><?= htmlspecialchars($l["username"]) ?></span>
                        <span class="count"><?= $l["solved_count"] ?></span>
                    </li>
                <?php 
                $rank++;
                endwhile; 
                ?>
                <?php if ($rank === 1): ?>
                    <li style="text-align: center; opacity: 0.6; padding: 20px;">No data yet</li>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Quick Actions -->
        <div class="sidebar-section">
            <h3>⚙️ Quick Actions</h3>
            <div class="quick-actions">
                <a href="add_user.php" class="btn">➕ Add New User</a>
                <a href="users.php" class="btn">👥 Manage Users</a>
                <a href="password_reset_requests.php" class="btn">
                    🔑 Password Resets
                    <?php if ($resetCount > 0): ?>
                        <span class="reset-requests-badge"><?= $resetCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Right Content -->
    <div class="right-content">
        <!-- Summary Cards -->
        <div class="cards">
            <div class="card navy">
                <h3>Total Tickets</h3>
                <div class="number"><?= $summary["total"] ?? 0 ?></div>
                <button onclick="loadAdminTickets('all')">View All</button>
            </div>

            <div class="card steel">
                <h3>Solved</h3>
                <div class="number"><?= $summary["solved"] ?? 0 ?></div>
                <button onclick="loadAdminTickets('solved')">View Details</button>
            </div>

            <div class="card charcoal">
                <h3>Pending</h3>
                <div class="number"><?= $summary["pending"] ?? 0 ?></div>
                <button onclick="loadAdminTickets('pending')">View Details</button>
            </div>

            <div class="card slate">
                <h3>IT Team</h3>
                <div class="number"><?= $summary["it_total"] ?? 0 ?></div>
                <button onclick="loadAdminTickets('it')">View Details</button>
            </div>

            <div class="card oxford">
                <h3>MIS Team</h3>
                <div class="number"><?= $summary["mis_total"] ?? 0 ?></div>
                <button onclick="loadAdminTickets('mis')">View Details</button>
            </div>
        </div>

        <hr class="section-divider">

        <!-- Filters -->
        <div class="section">
            <h3>🔍 Filter Tickets</h3>
            
            <form id="adminFilterForm" class="form-inline" onsubmit="event.preventDefault(); loadAdminTickets('filter');">
                <div class="form-group">
                    <label>Ticket No</label>
                    <input type="text" name="ticket_no" placeholder="PDL-IT-000001">
                </div>

                <div class="form-group">
                    <label>Team</label>
                    <select name="team">
                        <option value="">All</option>
                        <option value="it">IT</option>
                        <option value="mis">MIS</option>
                    </select>
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
        <div id="adminTable">
            <div class="loading">Loading tickets</div>
        </div>
    </div>
</div>

<script>
function loadAdminTickets(filter) {
    let url = "../ajax/admin_tickets.php";

    if (filter === 'filter') {
        const form = document.getElementById("adminFilterForm");
        const params = new URLSearchParams(new FormData(form)).toString();
        url += "?" + params;
    } else if (filter === 'pending') {
        url += "?status=Pending";
    } else if (filter === 'solved') {
        url += "?status=Solved";
    } else if (filter === 'it') {
        url += "?team=it";
    } else if (filter === 'mis') {
        url += "?team=mis";
    }

    fetch(url)
        .then(res => res.text())
        .then(html => {
            document.getElementById("adminTable").innerHTML = html;
            
            // Auto scroll to table
            document.getElementById("adminTable").scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        })
        .catch(err => {
            document.getElementById("adminTable").innerHTML = 
                '<div class="message message-error">Failed to load tickets</div>';
        });
}

// Load all tickets on page load
loadAdminTickets('all');
</script>

<?php include("../includes/footer.php"); ?>