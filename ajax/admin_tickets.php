<?php
require_once("../config/db.php");
require_once("../helpers/time.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    exit("Unauthorized");
}

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

<?php if ($result->num_rows === 0): ?>
    <div class="message message-info">No tickets found</div>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>Ticket No</th>
            <th>Team</th>
            <th>Requester</th>
            <th>Problem</th>
            <th>Status</th>
            <th>Time Taken</th>
            <th>Solved By</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($r = $result->fetch_assoc()): ?>
        <tr>
            <td><strong><?= $r["ticket_no"] ?></strong></td>
            <td><span class="team-badge team-<?= $r["team"] ?>"><?= strtoupper($r["team"]) ?></span></td>
            <td><?= htmlspecialchars($r["requester"]) ?></td>
            <td><?= htmlspecialchars($r["problem"]) ?></td>
            <td><span class="status-badge status-<?= strtolower($r["status"]) ?>"><?= $r["status"] ?></span></td>
            <td><?= timeTaken($r["created_at"], $r["solved_at"]) ?></td>
            <td><?= htmlspecialchars($r["solver"] ?? '—') ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php endif; ?>