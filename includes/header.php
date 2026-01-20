<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'PDL Helpdesk' ?></title>
    <link rel="stylesheet" href="<?= $basePath ?? '../' ?>assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><?= $headerTitle ?? 'PDL Helpdesk' ?></h2>
            <div class="user-info">
                <span class="username">👤 <?= htmlspecialchars($_SESSION["username"]) ?></span>
                <a href="<?= $basePath ?? '../' ?>auth/logout.php" class="btn btn-secondary btn-sm">Logout</a>
            </div>
        </div>