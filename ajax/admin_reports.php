<?php
session_start();
require_once "../config/db.php";

/* Security */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    exit("Unauthorized");
}

$type = $_GET['type'] ?? 'all';

$where = "";
if ($type === 'pending') {
    $where = "WHERE status='pending'";
}
elseif ($type === 'solved') {
    $where = "WHERE status='solved'";
}
elseif ($type === 'it') {
    $where = "WHERE assigned_team='it'";
}
elseif ($type === 'mis') {
    $where = "WHERE assigned_team='mis'";
}

$sql = "
SELECT 
    ticket_number,
    status,
    assigned_team,
    updated_by,
    TIMESTAMPDIFF(MINUTE, created_at, updated_at) AS time_taken
FROM tickets
$where
ORDER BY created_at DESC
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
    <td><?= $row['ticket_number'] ?></td>
    <td><?= $row['status'] ?></td>
    <td><?= strtoupper($row['assigned_team']) ?></td>
    <td><?= $row['status'] === 'solved' ? $row['time_taken'] : '-' ?></td>
    <td><?= $row['updated_by'] ?></td>
</tr>
<?php endwhile; ?>
</table>
