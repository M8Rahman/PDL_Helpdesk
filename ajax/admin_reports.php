<?php
session_start();
require_once "../config/db.php";

/* Security */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit("Unauthorized");
}

$type = $_GET['type'] ?? 'all';

$where = "";
if ($type === 'pending') {
    $where = "WHERE status='Open'";
}
elseif ($type === 'solved') {
    $where = "WHERE status='Solved'";
}
elseif ($type === 'it') {
    $where = "WHERE team='it'";
}
elseif ($type === 'mis') {
    $where = "WHERE team='mis'";
}

$sql = "
SELECT 
    t.ticket_no,
    t.status,
    t.team,
    u.username AS updated_by_name,
    TIMESTAMPDIFF(MINUTE, t.created_at, t.solved_at) AS time_taken
FROM tickets t
LEFT JOIN users u ON u.id = t.updated_by
$where
ORDER BY t.created_at DESC
";

$result = $conn->query($sql);
?>

<h3>Report Details</h3>

<table border="1" cellpadding="5" cellspacing="0">
<tr>
    <th>Ticket</th>
    <th>Status</th>
    <th>Team</th>
    <th>Time Taken (min)</th>
    <th>Updated By</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['ticket_no'] ?></td>
    <td><?= $row['status'] ?></td>
    <td><?= strtoupper($row['team']) ?></td>
    <td><?= $row['status'] === 'Solved' ? $row['time_taken'] : '-' ?></td>
    <td><?= $row['updated_by_name'] ?? '-' ?></td>
</tr>
<?php endwhile; ?>
</table>