<?php

session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: adminlogin.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | Pirati Gasum</title>
</head>
<body>
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8') ?>.</p>
    <p><a href="../logout.php">Log out</a></p>
</body>
</html>
