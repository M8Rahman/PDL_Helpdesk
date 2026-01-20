<?php
require_once("admin_guard.php");

/* Ticket summary */
$summary = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Solved') AS solved,
        SUM(status = 'Open') AS pending,
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

/* Solved by leaderboard */
$leaders = $conn->query("
    SELECT u.username, COUNT(*) AS solved_count
    FROM tickets t
    JOIN users u ON u.id = t.solved_by
    WHERE t.status = 'Solved'
    GROUP BY t.solved_by
    ORDER BY solved_count DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - PDL Helpdesk</title>

    <script>
        function loadAdminTickets() {
            const form = document.getElementById("adminFilterForm");
            const data = new URLSearchParams(new FormData(form)).toString();

            fetch("../ajax/admin_tickets.php?" + data)
                .then(res => res.text())
                .then(html => {
                    document.getElementById("adminTable").innerHTML = html;
                });
        }
    </script>

</head>
<body>

<h2>👑 Super Admin Dashboard</h2>

<!-- SUMMARY -->
<h3>System Summary</h3>
<ul>
    <li>Total Tickets: <b><?= $summary["total"] ?></b></li>
    <li>Solved: <b><?= $summary["solved"] ?></b></li>
    <li>Pending: <b><?= $summary["pending"] ?></b></li>
    <li>IT Team Tickets: <b><?= $summary["it_total"] ?></b></li>
    <li>MIS Team Tickets: <b><?= $summary["mis_total"] ?></b></li>
</ul>

<h3>Performance</h3>
<ul>
    <li>Average Resolution Time: 
        <b><?= round($avgTime) ?> min</b>
    </li>
</ul>

<h3>Solved By</h3>
<ul>
<?php while ($l = $leaders->fetch_assoc()): ?>
    <li><?= $l["username"] ?> — <?= $l["solved_count"] ?></li>
<?php endwhile; ?>
</ul>


<h3>Filters</h3>

<form id="adminFilterForm" onsubmit="event.preventDefault(); loadAdminTickets();">
    Ticket No:
    <input type="text" name="ticket_no">

    Team:
    <select name="team">
        <option value="">All</option>
        <option value="it">IT</option>
        <option value="mis">MIS</option>
    </select>

    Status:
    <select name="status">
        <option value="">All</option>
        <option value="Open">Open</option>
        <option value="Solved">Solved</option>
    </select>

    From:
    <input type="date" name="from_date">

    To:
    <input type="date" name="to_date">

    <button type="submit">Apply</button>
</form>

<button onclick="loadAdminTickets()">View All Tickets</button>

<hr>

<div id="adminTable"></div>

<br>
<a href="../auth/logout.php">Logout</a>

</body>
</html>

