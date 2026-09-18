<?php
session_start();

$databaseHost = getenv('DB_HOST') ?: 'localhost';
$databaseUser = getenv('DB_USER') ?: 'root';
$databasePassword = getenv('DB_PASSWORD') ?: '';
$databaseName = getenv('DB_NAME') ?: 'pirati_gasum';

$conn = mysqli_connect(
    $databaseHost,
    $databaseUser,
    $databasePassword,
    $databaseName
);

if (!$conn) {
    http_response_code(500);
    die('Unable to connect to the database.');
}

mysqli_set_charset($conn, 'utf8mb4');

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email address and password.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1'
        );

        if (!$stmt) {
            http_response_code(500);
            die('Unable to process the login request.');
        }

        mysqli_stmt_bind_param($stmt, 's', $email);

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            http_response_code(500);
            die('Unable to process the login request.');
        }

        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) === 1) {
            mysqli_stmt_bind_result($stmt, $userId, $userName, $userEmail, $passwordHash);
            mysqli_stmt_fetch($stmt);
        }

        mysqli_stmt_close($stmt);

        if (
            isset($passwordHash) &&
            password_verify($password, $passwordHash)
        ) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['name'] = $userName;
            $_SESSION['email'] = $userEmail;

            header('Location: ../index.php?page=dashboard');
            exit;
        }

        $error = 'Invalid email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Pirati Gasum</title>
</head>
<body>
    <h2>User Login</h2>

    <?php if ($error !== ''): ?>
        <p role="alert" style="color: red;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
        <label for="email">Email:</label><br>
        <input
            type="email"
            id="email"
            name="email"
            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="email"
            required
        >
        <br><br>

        <label for="password">Password:</label><br>
        
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        >
        <br><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>
