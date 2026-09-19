<?php

session_start();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parameters = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $parameters['path'], $parameters['domain'], $parameters['secure'], $parameters['httponly']);
}

session_destroy();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="2;url=login.php">
    <title>Logged Out | Pirati Gasum</title>
    <link rel="stylesheet" href="Frontend/assets/css/auth.css">
</head>
<body>
    <main class="logout-card">
        <h1>Logged out</h1>
        <p>You have been logged out safely. Redirecting to the login page…</p>
        <p><a href="login.php">Return to login now</a></p>
    </main>
</body>
</html>
