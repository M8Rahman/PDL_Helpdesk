<?php
require_once("support_guard.php");

/* Summary */
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Solved') AS solved,
        SUM(status = 'Open') AS pending
    FROM tickets
    WHERE team = ?
");
$stmt->bind_param("s", $team);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

/* Solved by leaderboard */
$leader = $conn->prepare("
    SELECT u.username, COUNT(*) AS solved_count
    FROM tickets t
    JOIN users u ON t.solved_by = u.id
    WHERE t.team = ? AND t.status = 'Solved'
    GROUP BY solved_by
");
$leader->bind_param("s", $team);
$leader->execute();
$leaders = $leader->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $team ?> Support Dashboard</title>

    <script>
        let currentTicketId = null;

        function loadTickets(filter) {
            let url = "../ajax/support_tickets.php";

            if (filter === 'filter') {
                const form = document.getElementById("supportFilterForm");
                const params = new URLSearchParams(new FormData(form)).toString();
                url += "?" + params;
            } else {
                url += "?filter=" + filter;
            }

            fetch(url)
                .then(res => res.text())
                .then(html => {
                    document.getElementById("ticketTable").innerHTML = html;
                });
        }

        function selectTicket(id, problem) {
            currentTicketId = id;
            document.getElementById("ticket_id").value = id;
            document.getElementById("problem").value = problem;
            document.getElementById("solution").disabled = false;
            document.getElementById("solveBtn").disabled = false;
        }

        function solveTicket() {
            const solution = document.getElementById("solution").value;

            if (!solution || !currentTicketId) return alert("Missing data");

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
            });
        }
    </script>
</head>
<body>

<h2><?= $team ?> Team Dashboard</h2>

<!-- SUMMARY -->
<h3>Summary</h3>
<ul>
    <li>Total: <?= $summary["total"] ?> 
        <button onclick="loadTickets('all')">Details</button>
    </li>
    <li>Solved: <?= $summary["solved"] ?> 
        <button onclick="loadTickets('solved')">Details</button>
    </li>
    <li>Pending: <?= $summary["pending"] ?> 
        <button onclick="loadTickets('pending')">Details</button>
    </li>
</ul>

<h4>Solved By</h4>
<ul>
<?php while ($l = $leaders->fetch_assoc()): ?>
    <li><?= $l["username"] ?> — <?= $l["solved_count"] ?></li>
<?php endwhile; ?>
</ul>

<hr>

<!-- STATIC SOLVE PANEL -->
<h3>Solve Ticket</h3>

<form id="solveForm" onsubmit="event.preventDefault(); solveTicket();">
    <input type="text" id="ticket_id" placeholder="Ticket ID" readonly><br><br>
    <textarea id="problem" readonly></textarea><br><br>
    <textarea id="solution" placeholder="Write solution" disabled></textarea><br><br>
    <button id="solveBtn" disabled>Solve</button>
</form>

<hr>
<h3>Filters</h3>

<form id="supportFilterForm" onsubmit="event.preventDefault(); loadTickets('filter');">
    Ticket No:
    <input type="text" name="ticket_no">

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

<hr>

<!-- TABLE -->
<div id="ticketTable"></div>

<br>
<a href="../auth/logout.php">Logout</a>

</body>
</html>
