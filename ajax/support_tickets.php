<?php
require_once("../config/db.php");
require_once("../helpers/time.php");

$team = $_SESSION["role"]; // it or mis

$where = ["team = ?"];
$params = [$team];
$types  = "s";

/* Status */
if (!empty($_GET["status"])) {
    $where[] = "status = ?";
    $params[] = $_GET["status"];
    $types .= "s";
}

/* Ticket no */
if (!empty($_GET["ticket_no"])) {
    $where[] = "ticket_no LIKE ?";
    $params[] = "%" . $_GET["ticket_no"] . "%";
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

/* Legacy quick filter buttons */
if (isset($_GET["filter"])) {
    if ($_GET["filter"] === "pending") {
        $where[] = "status = 'Open'";
    }
    if ($_GET["filter"] === "solved") {
        $where[] = "status = 'Solved'";
    }
}

$sql = "
SELECT id, ticket_no, problem, status, created_at, solved_at
FROM tickets
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
    <th>Problem</th>
    <th>Status</th>
    <th>Time Taken</th>
</tr>

<?php while ($r = $result->fetch_assoc()): ?>
<tr onclick="selectTicket(
    <?= $r['id'] ?>,
    `<?= htmlspecialchars($r['problem'], ENT_QUOTES) ?>`
)">
    <td><?= $r["ticket_no"] ?></td>
    <td><?= htmlspecialchars($r["problem"]) ?></td>
    <td><?= $r["status"] ?></td>
    <td><?= timeTaken($r["created_at"], $r["solved_at"]) ?></td>
</tr>
<?php endwhile; ?>
</table>
