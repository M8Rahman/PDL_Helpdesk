<?php
require_once("user_guard.php");

$user_id = $_SESSION["user_id"];

/* Summary data */
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Solved') AS solved,
        SUM(status = 'Open') AS pending
    FROM tickets
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard - PDL Helpdesk</title>

    <script>
        let detailsVisible = false;

        function toggleDetails() {
            const box = document.getElementById("detailsBox");

            if (!detailsVisible) {
                fetch("../ajax/user_tickets.php")
                    .then(res => res.text())
                    .then(html => {
                        box.innerHTML = html;
                        box.style.display = "block";
                        detailsVisible = true;
                    });
            } else {
                box.style.display = "none";
                detailsVisible = false;
            }
        }
    </script>
</head>
<body>

<h2>Welcome, <?= htmlspecialchars($_SESSION["username"]) ?></h2>

<!-- SUMMARY -->
<h3>Ticket Summary</h3>

<ul>
    <li>Total Submitted: <b><?= $summary["total"] ?></b></li>
    <li>Solved: <b><?= $summary["solved"] ?></b></li>
    <li>Pending: <b><?= $summary["pending"] ?></b></li>
</ul>

<button onclick="toggleDetails()">View Details</button>

<hr>

<!-- DETAILS (HIDDEN INITIALLY) -->
<div id="detailsBox" style="display:none"></div>

<hr>

<h3>Submit a New Problem</h3>

<form method="post" action="../actions/create_ticket.php">
    <select name="team" required>
        <option value="">Select Team</option>
        <option value="IT">IT Team</option>
        <option value="MIS">MIS Team</option>
    </select><br><br>

    <textarea name="problem" placeholder="Describe your problem" required></textarea><br><br>

    <button type="submit">Submit</button>
</form>

<br>
<a href="../auth/logout.php">Logout</a>

</body>
</html>
