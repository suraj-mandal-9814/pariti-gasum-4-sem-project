<?php
require_once __DIR__ . '/include/authcheck.php';

$userId = (int) $_SESSION['user_id'];
$error = '';
$success = '';
$db = getDBConnection();

$userStatement = $db->prepare(
    'SELECT u.username, u.email, p.full_name, p.age, p.gender, p.dob, p.country,
            p.city, p.religion, p.bio
     FROM users u
     LEFT JOIN profiles p ON p.user_id = u.id
     WHERE u.id = ?'
);
$userStatement->execute([$userId]);
$profile = $userStatement->fetch();

if (!$profile) {
    http_response_code(404);
    exit('Profile not found.');
}

$form = [
    'username' => $profile['username'] ?? '',
    'email' => $profile['email'] ?? '',
    'full_name' => $profile['full_name'] ?? '',
    'age' => $profile['age'] ?? '',
    'gender' => $profile['gender'] ?? '',
    'dob' => $profile['dob'] ?? '',
    'country' => $profile['country'] ?? '',
    'city' => $profile['city'] ?? '',
    'religion' => $profile['religion'] ?? '',
    'bio' => $profile['bio'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $field => $value) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $password = (string) ($_POST['password'] ?? '');
    $age = filter_var($form['age'], FILTER_VALIDATE_INT);

    if (
        $form['username'] === '' || $form['email'] === '' || $form['full_name'] === '' ||
        $form['gender'] === '' || $form['dob'] === '' || $form['country'] === '' ||
        $form['city'] === ''
    ) {
        $error = 'Please complete all required fields.';
    } elseif ($age === false || $age < 18) {
        $error = 'You must be at least 18 years old.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($form['gender'], ['male', 'female', 'other'], true)) {
        $error = 'Please select a valid gender.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'A new password must be at least 6 characters.';
    } else {
        try {
            $duplicateStatement = $db->prepare(
                'SELECT id FROM users
                 WHERE (username = ? OR email = ?) AND id <> ?
                 LIMIT 1'
            );
            $duplicateStatement->execute([$form['username'], $form['email'], $userId]);

            if ($duplicateStatement->fetch()) {
                $error = 'That username or email is already in use.';
            } else {
                $db->beginTransaction();

                if ($password !== '') {
                    $userUpdate = $db->prepare(
                        'UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?'
                    );
                    $userUpdate->execute([
                        $form['username'],
                        $form['email'],
                        password_hash($password, PASSWORD_DEFAULT),
                        $userId,
                    ]);
                } else {
                    $userUpdate = $db->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
                    $userUpdate->execute([$form['username'], $form['email'], $userId]);
                }

                $profileExists = $db->prepare('SELECT user_id FROM profiles WHERE user_id = ?');
                $profileExists->execute([$userId]);

                if ($profileExists->fetch()) {
                    $profileUpdate = $db->prepare(
                        'UPDATE profiles
                         SET full_name = ?, age = ?, gender = ?, dob = ?, country = ?, city = ?,
                             religion = ?, bio = ?
                         WHERE user_id = ?'
                    );
                    $profileUpdate->execute([
                        $form['full_name'],
                        $age,
                        $form['gender'],
                        $form['dob'],
                        $form['country'],
                        $form['city'],
                        $form['religion'] !== '' ? $form['religion'] : null,
                        $form['bio'] !== '' ? $form['bio'] : null,
                        $userId,
                    ]);
                } else {
                    $profileInsert = $db->prepare(
                        'INSERT INTO profiles
                         (user_id, full_name, age, gender, dob, country, city, religion, bio, profile_pic)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $profileInsert->execute([
                        $userId,
                        $form['full_name'],
                        $age,
                        $form['gender'],
                        $form['dob'],
                        $form['country'],
                        $form['city'],
                        $form['religion'] !== '' ? $form['religion'] : null,
                        $form['bio'] !== '' ? $form['bio'] : null,
                        'default.png',
                    ]);
                }

                $db->commit();
                $_SESSION['username'] = $form['username'];
                $success = 'Your profile has been updated.';
            }
        } catch (PDOException $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = 'Unable to update your profile right now.';
        }
    }
}

function editEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<style>
    .edit-profile-card {
        max-width: 820px;
        padding: 28px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 6px 30px rgba(69, 23, 45, 0.08);
    }

    .edit-profile-card h1 {
        margin-top: 0;
    }

    .edit-profile-form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .edit-profile-form .full-width {
        grid-column: 1 / -1;
    }

    .edit-profile-form label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
    }

    .edit-profile-form input,
    .edit-profile-form select,
    .edit-profile-form textarea {
        width: 100%;
        padding: 11px 12px;
        border: 1px solid #e4d7dc;
        border-radius: 8px;
        font: inherit;
    }

    .edit-profile-form textarea {
        min-height: 110px;
        resize: vertical;
    }

    .edit-profile-form button {
        border: 0;
        cursor: pointer;
    }

    .edit-message {
        padding: 12px;
        margin-bottom: 18px;
        border-radius: 8px;
    }

    .edit-error {
        color: #9b1c1c;
        background: #fff0f0;
    }

    .edit-success {
        color: #176b35;
        background: #eefbf1;
    }

    @media (max-width: 650px) {
        .edit-profile-form {
            grid-template-columns: 1fr;
        }

        .edit-profile-form .full-width {
            grid-column: auto;
        }
    }
</style>

<div class="edit-profile-card">
    <h1>Edit your profile</h1>
    <p>Update your account and profile information below.</p>

    <?php if ($error !== ''): ?>
        <div class="edit-message edit-error"><?= editEscape($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="edit-message edit-success"><?= editEscape($success) ?></div>
    <?php endif; ?>

    <form class="edit-profile-form" method="post" action="index.php?page=edit">
        <div>
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" value="<?= editEscape($form['full_name']) ?>" required>
        </div>

        <div>
            <label for="username">Username</label>
            <input id="username" name="username" value="<?= editEscape($form['username']) ?>" required>
        </div>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= editEscape($form['email']) ?>" required>
        </div>

        <div>
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $form['gender'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="age">Age</label>
            <input type="number" id="age" name="age" min="18" value="<?= editEscape((string) $form['age']) ?>" required>
        </div>

        <div>
            <label for="dob">Date of birth</label>
            <input type="date" id="dob" name="dob" value="<?= editEscape($form['dob']) ?>" required>
        </div>

        <div>
            <label for="country">Country</label>
            <input id="country" name="country" value="<?= editEscape($form['country']) ?>" required>
        </div>

        <div>
            <label for="city">City</label>
            <input id="city" name="city" value="<?= editEscape($form['city']) ?>" required>
        </div>

        <div>
            <label for="religion">Religion</label>
            <input id="religion" name="religion" value="<?= editEscape($form['religion']) ?>">
        </div>

        <div>
            <label for="password">New password</label>
            <input type="password" id="password" name="password" minlength="6"
                placeholder="Leave blank to keep your current password">
        </div>

        <div class="full-width">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" maxlength="1000"><?= editEscape($form['bio']) ?></textarea>
        </div>

        <div class="full-width">
            <button type="submit" class="btn-primary">Save changes</button>
        </div>
    </form>
</div>