<?php
require_once("../config/db.php");
require_once("../helpers/time.php");

if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ["it", "mis"])) {
    exit("Unauthorized");
}

$team = $_SESSION["role"];

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

$sql = "
SELECT 
    t.id,
    t.ticket_no,
    t.problem,
    t.status,
    t.created_at,
    t.solved_at,
    u.username AS requester
FROM tickets t
JOIN users u ON u.id = t.user_id
WHERE " . implode(" AND ", $where) . "
ORDER BY t.status ASC, t.created_at DESC
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
            <th>From</th>
            <th>Problem</th>
            <th>Status</th>
            <th>Time Taken</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($r = $result->fetch_assoc()): ?>
        <tr class="<?= $r['status'] === 'Pending' ? 'clickable' : '' ?>" 
            <?= $r['status'] === 'Pending' ? "onclick=\"selectTicket({$r['id']}, `" . htmlspecialchars($r['problem'], ENT_QUOTES) . "`)\"" : '' ?>>
            <td><strong><?= $r["ticket_no"] ?></strong></td>
            <td><?= htmlspecialchars($r["requester"]) ?></td>
            <td><?= htmlspecialchars($r["problem"]) ?></td>
            <td><span class="status-badge status-<?= strtolower($r["status"]) ?>"><?= $r["status"] ?></span></td>
            <td><?= timeTaken($r["created_at"], $r["solved_at"]) ?></td>
            <td>
                <?php if ($r["status"] === "Pending"): ?>
                    <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); selectTicket(<?= $r['id'] ?>, `<?= htmlspecialchars($r['problem'], ENT_QUOTES) ?>`)">
                        Solve
                    </button>
                <?php else: ?>
                    ✔ Solved
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php endif; ?>