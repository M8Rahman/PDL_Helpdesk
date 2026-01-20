<?php
require_once("support_guard.php");

$team = strtoupper($_SESSION["role"]); // IT or MIS

$query = "
SELECT 
    t.*, 
    u.username AS requester,
    s.username AS solved_by_name
FROM tickets t
JOIN users u ON t.user_id = u.id
LEFT JOIN users s ON t.solved_by = s.id
WHERE t.team = ?
ORDER BY t.status ASC, t.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $team);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $team ?> Support Dashboard - PDL Helpdesk</title>
</head>
<body>

<h2><?= $team ?> Support Dashboard</h2>

<table border="1" cellpadding="5" width="100%">
<tr>
    <th>Ticket No</th>
    <th>From</th>
    <th>Problem</th>
    <th>Created At</th>
    <th>Status</th>
    <th>Solution</th>
    <th>Solved At</th>
    <th>Solved By</th>
    <th>Action</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row["ticket_no"] ?></td>
    <td><?= htmlspecialchars($row["requester"]) ?></td>
    <td><?= htmlspecialchars($row["problem"]) ?></td>
    <td><?= $row["created_at"] ?></td>
    <td><?= $row["status"] ?></td>
    <td><?= htmlspecialchars($row["solution"]) ?></td>
    <td><?= $row["solved_at"] ?></td>
    <td><?= $row["solved_by_name"] ?></td>

    <td>
    <?php if ($row["status"] === "Open"): ?>
        <form method="post" action="../actions/solve_ticket.php">
            <input type="hidden" name="ticket_id" value="<?= $row["id"] ?>">
            <textarea name="solution" placeholder="Solution" required></textarea><br>
            <button type="submit">Solved</button>
        </form>
    <?php else: ?>
        ✔ Solved
    <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>

<br>
<a href="../auth/logout.php">Logout</a>

</body>
</html>
