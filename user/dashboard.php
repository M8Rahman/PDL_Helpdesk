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

$pageTitle = "User Dashboard - PDL Helpdesk";
$headerTitle = "User Dashboard";
$basePath = "../";
include("../includes/header.php");
?>

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

<hr class="section-divider">

<!-- New Ticket Form -->
<div class="section">
    <h3>📝 Submit a New Problem</h3>
    
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
            <textarea name="problem" placeholder="Please provide details about your issue..." required></textarea>
        </div>

        <button type="submit" class="btn btn-success">Submit Ticket</button>
    </form>
</div>

<hr class="section-divider">

<!-- Filters -->
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
        })
        .catch(err => {
            document.getElementById("ticketTable").innerHTML = 
                '<div class="message message-error">Failed to load tickets</div>';
        });
}

// Load tickets on page load
loadUserTickets();
</script>

<?php include("../includes/footer.php"); ?>