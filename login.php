<?php
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}
if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

$error = '';
$success = '';

if (isset($_GET['error']) && $_GET['error'] === 'suspended') {
    $error = 'Your account has been suspended by an administrator.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limit input length and trim
    $login_input = substr(trim($_POST['username_email'] ?? ''), 0, 255);
    $password = $_POST['password'] ?? '';

    if ($login_input === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDBConnection();
        try {
            // 1. Check admins table first
            $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$login_input, $login_input]);
            $admin = $stmt->fetch();

            if ($admin && isset($admin['password']) && password_verify($password, $admin['password'])) {
                // Regenerate session id to prevent fixation
                session_regenerate_id(true);
                // Set Admin Sessions
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['user_role'] = 'admin';

                // Flash success for next page
                $_SESSION['flash_success'] = 'Admin login successful!';
                header("Location: admin/dashboard.php");
                exit;
            }

            // 2. Check regular users table
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch();

            if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
                if (isset($user['status']) && $user['status'] === 'suspended') {
                    $error = 'Your account has been suspended by an administrator.';
                } else {
                    session_regenerate_id(true);
                    // Set User Sessions
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = 'user';

                    $_SESSION['flash_success'] = 'Login successful!';
                    header("Location: index.php?page=dashboard");
                    exit;
                }
            } else {
                $error = 'Invalid username/email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card glass-panel animate-on-scroll">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p style="color: var(--text-muted);">Log in to meet your matches</p>
        </div>

        <?php if (!empty($error)): ?>
            <div
                style="background: rgba(244, 67, 54, 0.1); border-left: 4px solid #f44336; color: #d32f2f; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div
                style="background: rgba(76, 175, 80, 0.1); border-left: 4px solid #4caf50; color: #2e7d32; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="input-group">
                <label class="input-label" for="username_email">Username or Email</label>
                <input type="text" name="username_email" id="username_email" class="input-control"
                    placeholder="eg:suraj01" required>
            </div>

            <div class="input-group" style="position: relative;">
                <label class="input-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="input-control" placeholder="••••••••"
                    required>
                <i class="fas fa-eye password-toggle" onclick="togglePasswordVisibility('password', this);"
                    style="top: 43px;"></i>
            </div>

            <div style="text-align: right; margin-bottom: 20px;">
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 14px;">Log In</button>
        </form>

        <p style="text-align: center; margin-top: 25px; font-size: 14px;">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>