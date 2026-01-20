<?php
require_once("support_guard.php");

$team = $_SESSION["role"]; // it or mis

/* Summary */
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Solved') AS solved,
        SUM(status = 'Pending') AS pending
    FROM tickets
    WHERE team = ?
");
$stmt->bind_param("s", $team);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

/* Leaderboard */
$leader = $conn->prepare("
    SELECT u.username, COUNT(*) AS solved_count
    FROM tickets t
    JOIN users u ON t.solved_by = u.id
    WHERE t.team = ? AND t.status = 'Solved'
    GROUP BY solved_by
    ORDER BY solved_count DESC
");
$leader->bind_param("s", $team);
$leader->execute();
$leaders = $leader->get_result();

$pageTitle = strtoupper($team) . " Support Dashboard";
$headerTitle = strtoupper($team) . " Team Dashboard";
$basePath = "../";
include("../includes/header.php");
?>

<!-- Summary Cards -->
<div class="cards">
    <div class="card blue">
        <h3>Total Tickets</h3>
        <div class="number"><?= $summary["total"] ?? 0 ?></div>
        <button onclick="loadTickets('all')">View All</button>
    </div>

    <div class="card green">
        <h3>Solved</h3>
        <div class="number"><?= $summary["solved"] ?? 0 ?></div>
        <button onclick="loadTickets('solved')">View Details</button>
    </div>

    <div class="card orange">
        <h3>Pending</h3>
        <div class="number"><?= $summary["pending"] ?? 0 ?></div>
        <button onclick="loadTickets('pending')">View Details</button>
    </div>
</div>

<!-- Leaderboard -->
<?php if ($leaders->num_rows > 0): ?>
<div class="section">
    <h3>🏆 Top Performers</h3>
    <ul>
        <?php while ($l = $leaders->fetch_assoc()): ?>
            <li><strong><?= htmlspecialchars($l["username"]) ?></strong> — <?= $l["solved_count"] ?> tickets solved</li>
        <?php endwhile; ?>
    </ul>
</div>
<?php endif; ?>

<hr class="section-divider">

<!-- Solve Panel -->
<div class="section">
    <h3>✅ Solve Ticket</h3>
    
    <form id="solveForm" onsubmit="event.preventDefault(); solveTicket();">
        <div class="form-group">
            <label>Ticket ID</label>
            <input type="text" id="ticket_id" readonly>
        </div>

        <div class="form-group">
            <label>Problem</label>
            <textarea id="problem" readonly></textarea>
        </div>

        <div class="form-group">
            <label>Solution</label>
            <textarea id="solution" placeholder="Enter solution..." disabled></textarea>
        </div>

        <button type="submit" id="solveBtn" class="btn-success" disabled>Mark as Solved</button>
    </form>
</div>

<hr class="section-divider">

<!-- Filters -->
<div class="section">
    <h3>🔍 Filter Tickets</h3>
    
    <form id="supportFilterForm" class="form-inline" onsubmit="event.preventDefault(); loadTickets('filter');">
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
let currentTicketId = null;

function loadTickets(filter) {
    let url = "../ajax/support_tickets.php";

    if (filter === 'filter') {
        const form = document.getElementById("supportFilterForm");
        const params = new URLSearchParams(new FormData(form)).toString();
        url += "?" + params;
    } else if (filter === 'pending') {
        url += "?status=Pending";
    } else if (filter === 'solved') {
        url += "?status=Solved";
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

function selectTicket(id, problem) {
    currentTicketId = id;
    document.getElementById("ticket_id").value = "Ticket #" + id;
    document.getElementById("problem").value = problem;
    document.getElementById("solution").disabled = false;
    document.getElementById("solveBtn").disabled = false;
    document.getElementById("solution").focus();
}

function solveTicket() {
    const solution = document.getElementById("solution").value.trim();

    if (!solution || !currentTicketId) {
        alert("Please enter a solution");
        return;
    }

    fetch("../ajax/solve_ticket.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "ticket_id=" + currentTicketId + "&solution=" + encodeURIComponent(solution)
    })
    .then(res => res.text())
    .then(msg => {
        alert(msg);
        loadTickets('pending');
        document.getElementById("solveForm").reset();
        document.getElementById("solution").disabled = true;
        document.getElementById("solveBtn").disabled = true;
        currentTicketId = null;
    })
    .catch(err => {
        alert("Failed to solve ticket");
    });
}

// Load pending tickets on page load
loadTickets('pending');
</script>

<?php include("../includes/footer.php"); ?>