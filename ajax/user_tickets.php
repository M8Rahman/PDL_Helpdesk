<?php
require_once("../config/db.php");
require_once("../helpers/time.php");

$user_id = $_SESSION["user_id"];

$where = ["user_id = ?"];
$params = [$user_id];
$types  = "i";

/* Ticket no */
if (!empty($_GET["ticket_no"])) {
    $where[] = "ticket_no LIKE ?";
    $params[] = "%" . $_GET["ticket_no"] . "%";
    $types .= "s";
}

/* Status */
if (!empty($_GET["status"])) {
    $where[] = "status = ?";
    $params[] = $_GET["status"];
    $types .= "s";
}

/* Date range */
if (!empty($_GET["from_date"])) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $_GET["from_date"];
    $types .= "s";
}

if (!empty($_GET["to_date"])) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $_GET["to_date"];
    $types .= "s";
}

$sql = "
SELECT 
    ticket_no,
    team,
    problem,
    status,
    solution,
    created_at,
    solved_at,
    u.username AS solved_by
FROM tickets t
LEFT JOIN users u ON u.id = t.solved_by
WHERE " . implode(" AND ", $where) . "
ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<table border="1" width="100%">
<tr>
    <th>Ticket</th>
    <th>To</th>
    <th>Problem</th>
    <th>Status</th>
    <th>Time Taken</th>
    <th>Solution</th>
    <th>Solved By</th>
</tr>

<?php while ($r = $result->fetch_assoc()): ?>
<tr>
    <td><?= $r["ticket_no"] ?></td>
    <td><?= strtoupper($r["team"]) ?></td>
    <td><?= htmlspecialchars($r["problem"]) ?></td>
    <td><?= $r["status"] ?></td>
    <td><?= timeTaken($r["created_at"], $r["solved_at"]) ?></td>
    <td><?= htmlspecialchars($r["solution"]) ?></td>
    <td><?= $r["solved_by"] ?></td>
</tr>
<?php endwhile; ?>
</table>
