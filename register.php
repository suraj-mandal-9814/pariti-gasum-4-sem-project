<?php
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $country = trim($_POST['country'] ?? '');
    $city = trim($_POST['city'] ?? '');

    // Server-side validations
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($gender) || empty($dob) || empty($country) || empty($city)) {
        $error = 'All fields are required.';
    } elseif ($age < 18) {
        $error = 'You must be at least 18 years old to join.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if username/email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or Email is already registered.';
            } else {
                $db->beginTransaction();
                
                // Hash Password
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // New accounts are ready to use immediately; no OTP is required.
                $stmt = $db->prepare("INSERT INTO users (username, email, password, role, status, email_verified, verification_code, verification_code_expires_at) VALUES (?, ?, ?, 'user', 'approved', 1, NULL, NULL)");
                $stmt->execute([$username, $email, $password_hash]);
                $user_id = $db->lastInsertId();

                // Insert Profile
                $stmt = $db->prepare("INSERT INTO profiles (user_id, full_name, age, gender, dob, country, city, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?, 'default.png')");
                $stmt->execute([$user_id, $full_name, $age, $gender, $dob, $country, $city]);

                $db->commit();

                $success = 'Registration successful! You can log in now.';
            }
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card glass-panel animate-on-scroll">

            <div class="auth-header">
                <h2>Start Your Journey</h2>
                <p style="color: var(--text-muted);">Find your perfect match today</p>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background: rgba(244, 67, 54, 0.1); border-left: 4px solid #f44336; color: #d32f2f; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div style="background: rgba(76, 175, 80, 0.1); border-left: 4px solid #4caf50; color: #2e7d32; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <a href="login.php" style="color: #2e7d32; font-weight: bold;">Log in now</a>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="registerForm" onsubmit="return validateRegisterForm(event);">
                <div class="input-group">
                    <label class="input-label" for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="input-control" placeholder="Your Name" required>
                </div>

                <div class="input-group">
                    <label class="input-label" for="username">Username</label>
                    <input type="text" name="username" id="username" class="input-control" placeholder="eg: suraj01" required>
                </div>

                <div class="input-group">
                    <label class="input-label" for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="input-control" placeholder="suraj@gmail.com" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="input-group">
                        <label class="input-label" for="gender">Gender</label>
                        <select name="gender" id="gender" class="input-control" required>
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="age">Age</label>
                        <input type="number" name="age" id="age" class="input-control" min="18" placeholder="20" required>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="dob">Date of Birth</label>
                    <input type="date" name="dob" id="dob" class="input-control" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="input-group">
                        <label class="input-label" for="country">Country</label>
                        <input type="text" name="country" id="country" class="input-control" placeholder="Nepal" required>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="city">City</label>
                        <input type="text" name="city" id="city" class="input-control" placeholder="Kathmandu" required>
                    </div>
                </div>

                <div class="input-group" style="position: relative;">
                    <label class="input-label" for="password">Password</label>
                    <input type="password" name="password" id="password" class="input-control" placeholder="••••••••" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePasswordVisibility('password', this);"></i>
                    <div class="password-strength">
                        <div id="password-strength-bar" class="password-strength-bar"></div>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px; padding: 14px;">Sign Up</button>
            </form>

            <p style="text-align: center; margin-top: 25px; font-size: 14px;">
                Already have an account? <a href="login.php">Login here</a>
            </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Enable Password strength meter
    updatePasswordStrengthUI('password', 'password-strength-bar');
});

function validateRegisterForm(e) {
    const age = parseInt(document.getElementById('age').value);
    const password = document.getElementById('password').value;
    const email = document.getElementById('email').value;

    if (age < 18) {
        showToast('You must be 18 or older to register.', 'error');
        e.preventDefault();
        return false;
    }

    if (password.length < 6) {
        showToast('Password must be at least 6 characters long.', 'error');
        e.preventDefault();
        return false;
    }

    return true;
}
</script>

<?php require_once 'includes/footer.php'; ?>
