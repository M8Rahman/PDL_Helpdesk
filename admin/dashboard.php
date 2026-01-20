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

$pageTitle = "Admin Dashboard - PDL Helpdesk";
$headerTitle = "👑 Super Admin Dashboard";
$basePath = "../";
include("../includes/header.php");
?>

<!-- Summary Cards -->
<div class="cards">
    <div class="card blue">
        <h3>Total Tickets</h3>
        <div class="number"><?= $summary["total"] ?? 0 ?></div>
        <button onclick="loadAdminTickets('all')">View All</button>
    </div>

    <div class="card green">
        <h3>Solved</h3>
        <div class="number"><?= $summary["solved"] ?? 0 ?></div>
        <button onclick="loadAdminTickets('solved')">View Details</button>
    </div>

    <div class="card orange">
        <h3>Pending</h3>
        <div class="number"><?= $summary["pending"] ?? 0 ?></div>
        <button onclick="loadAdminTickets('pending')">View Details</button>
    </div>

    <div class="card purple">
        <h3>IT Team</h3>
        <div class="number"><?= $summary["it_total"] ?? 0 ?></div>
        <button onclick="loadAdminTickets('it')">View Details</button>
    </div>

    <div class="card red">
        <h3>MIS Team</h3>
        <div class="number"><?= $summary["mis_total"] ?? 0 ?></div>
        <button onclick="loadAdminTickets('mis')">View Details</button>
    </div>
</div>

<!-- Performance & Leaderboard -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
    <div class="section">
        <h3>📊 Performance</h3>
        <p style="font-size: 24px; font-weight: bold; color: #007bff;">
            <?= $avgTime ? round($avgTime) . ' min' : 'N/A' ?>
        </p>
        <p style="color: #666;">Average Resolution Time</p>
    </div>

    <div class="section">
        <h3>🏆 Top Performers</h3>
        <ul>
            <?php while ($l = $leaders->fetch_assoc()): ?>
                <li><strong><?= htmlspecialchars($l["username"]) ?></strong> — <?= $l["solved_count"] ?> tickets</li>
            <?php endwhile; ?>
        </ul>
    </div>
</div>

<hr class="section-divider">

<!-- Quick Actions -->
<div class="section">
    <h3>⚙️ Quick Actions</h3>
    <div style="display: flex; gap: 10px;">
        <a href="add_user.php" class="btn">➕ Add New User</a>
        <a href="users.php" class="btn btn-secondary">👥 Manage Users</a>
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