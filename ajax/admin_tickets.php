<?php
require_once("../config/db.php");
require_once("../helpers/time.php");

$where = [];
$params = [];
$types  = "";

/* Filters */
if (!empty($_GET["ticket_no"])) {
    $where[] = "t.ticket_no LIKE ?";
    $params[] = "%" . $_GET["ticket_no"] . "%";
    $types .= "s";
}

if (!empty($_GET["team"])) {
    $where[] = "t.team = ?";
    $params[] = $_GET["team"];
    $types .= "s";
}

if (!empty($_GET["status"])) {
    $where[] = "t.status = ?";
    $params[] = $_GET["status"];
    $types .= "s";
}

if (!empty($_GET["from_date"])) {
    $where[] = "DATE(t.created_at) >= ?";
    $params[] = $_GET["from_date"];
    $types .= "s";
}

if (!empty($_GET["to_date"])) {
    $where[] = "DATE(t.created_at) <= ?";
    $params[] = $_GET["to_date"];
    $types .= "s";
}

$sql = "
SELECT 
    t.ticket_no,
    t.team,
    t.problem,
    t.status,
    t.created_at,
    t.solved_at,
    u1.username AS requester,
    u2.username AS solver
FROM tickets t
JOIN users u1 ON u1.id = t.user_id
LEFT JOIN users u2 ON u2.id = t.solved_by
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<table border="1" width="100%">
<tr>
    <th>Ticket</th>
    <th>Team</th>
    <th>Requester</th>
    <th>Status</th>
    <th>Time Taken</th>
    <th>Solved By</th>
</tr>

<?php while ($r = $result->fetch_assoc()): ?>
<tr>
    <td><?= $r["ticket_no"] ?></td>
    <td><?= strtoupper($r["team"]) ?></td>
    <td><?= $r["requester"] ?></td>
    <td><?= $r["status"] ?></td>
    <td><?= timeTaken($r["created_at"], $r["solved_at"]) ?></td>
    <td><?= $r["solver"] ?></td>
</tr>
<?php endwhile; ?>
</table>
