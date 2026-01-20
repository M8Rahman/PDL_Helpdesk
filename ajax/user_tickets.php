<?php
require_once("../config/db.php");
require_once("../helpers/time.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    exit("Unauthorized");
}

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
    t.ticket_no,
    t.team,
    t.problem,
    t.status,
    t.solution,
    t.created_at,
    t.solved_at,
    u.username AS solved_by
FROM tickets t
LEFT JOIN users u ON u.id = t.solved_by
WHERE " . implode(" AND ", $where) . "
ORDER BY t.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
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
            <th>Problem</th>
            <th>Status</th>
            <th>Time Taken</th>
            <th>Solution</th>
            <th>Solved By</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($r = $result->fetch_assoc()): ?>
        <tr>
            <td><strong><?= $r["ticket_no"] ?></strong></td>
            <td><span class="team-badge team-<?= $r["team"] ?>"><?= strtoupper($r["team"]) ?></span></td>
            <td><?= htmlspecialchars($r["problem"]) ?></td>
            <td><span class="status-badge status-<?= strtolower($r["status"]) ?>"><?= $r["status"] ?></span></td>
            <td><?= timeTaken($r["created_at"], $r["solved_at"]) ?></td>
            <td><?= htmlspecialchars($r["solution"] ?? '—') ?></td>
            <td><?= htmlspecialchars($r["solved_by"] ?? '—') ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php endif; ?>