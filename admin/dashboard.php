<?php
session_start();
require_once "../config/db.php";

/* 🔒 Security: only super admin */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

/* Summary numbers */
$total = $conn->query("SELECT COUNT(*) c FROM tickets")->fetch_assoc()['c'];
$pending = $conn->query("SELECT COUNT(*) c FROM tickets WHERE status='pending'")->fetch_assoc()['c'];
$solved = $conn->query("SELECT COUNT(*) c FROM tickets WHERE status='solved'")->fetch_assoc()['c'];
$it = $conn->query("SELECT COUNT(*) c FROM tickets WHERE team='it'")->fetch_assoc()['c'];
$mis = $conn->query("SELECT COUNT(*) c FROM tickets WHERE team='mis'")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Super Admin Dashboard</title>
    <style>
        .cards { display:flex; gap:15px; }
        .card { padding:15px; border:1px solid #ccc; width:180px; }
        button { cursor:pointer; }
        #details { margin-top:30px; }
    </style>
</head>
<body>

<h2>Super Admin Dashboard</h2>

<!-- 🔹 TOP SUMMARY -->
<div class="cards">
    <div class="card">
        <b>Total Tickets</b>
        <p><?= $total ?></p>
        <button onclick="loadReport('all')">Details</button>
    </div>

    <div class="card">
        <b>Pending</b>
        <p><?= $pending ?></p>
        <button onclick="loadReport('pending')">Details</button>
    </div>

    <div class="card">
        <b>Solved</b>
        <p><?= $solved ?></p>
        <button onclick="loadReport('solved')">Details</button>
    </div>

    <div class="card">
        <b>IT Team</b>
        <p><?= $it ?></p>
        <button onclick="loadReport('it')">Details</button>
    </div>

    <div class="card">
        <b>MIS Team</b>
        <p><?= $mis ?></p>
        <button onclick="loadReport('mis')">Details</button>
    </div>
</div>

<!-- 🔹 DETAILS (AJAX LOADS HERE) -->
<div id="details"></div>

<script>
function loadReport(type) {
    fetch("../ajax/admin_reports.php?type=" + type)
        .then(res => res.text())
        .then(html => {
            document.getElementById("details").innerHTML = html;
        });
}
</script>

</body>
</html>
