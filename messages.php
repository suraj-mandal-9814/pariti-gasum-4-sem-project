<?php
// This page is included by index.php after the user has logged in.
require_once 'config/db.php';

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$selectedUserId = (int) ($_GET['user_id'] ?? $_POST['recipient_id'] ?? 0);
$members = [];
$selectedMember = null;
$chatMessages = [];
$error = '';

function messageEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

try {
    $db = getDBConnection();

    // Create the small table used by this simple chat feature, if needed.
    $db->exec('CREATE TABLE IF NOT EXISTS messages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX sender_receiver (sender_id, receiver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // List every active member except the logged-in user.
    $memberStatement = $db->prepare(
        'SELECT u.id, u.username, p.full_name, p.age, p.gender, p.city, p.country, p.bio
         FROM users u
         LEFT JOIN profiles p ON p.user_id = u.id
         WHERE u.id != ? AND u.role = "user" AND u.status = "approved"
         ORDER BY COALESCE(p.full_name, u.username)'
    );
    $memberStatement->execute([$currentUserId]);
    $members = $memberStatement->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedUserId > 0) {
        $text = trim($_POST['message'] ?? '');

        if ($text === '') {
            $error = 'Please write a message before sending.';
        } elseif (mb_strlen($text) > 1000) {
            $error = 'A message can contain up to 1000 characters.';
        } else {
            // Send only to a valid member shown in the list.
            $validRecipient = false;
            foreach ($members as $member) {
                if ((int) $member['id'] === $selectedUserId) {
                    $validRecipient = true;
                    break;
                }
            }

            if (!$validRecipient) {
                $error = 'Please select a valid member.';
            } else {
                $sendStatement = $db->prepare(
                    'INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)'
                );
                $sendStatement->execute([$currentUserId, $selectedUserId, $text]);
                // Keep the selected conversation open and load the new message below.
            }
        }
    }

    if ($selectedUserId > 0) {
        $profileStatement = $db->prepare(
            'SELECT u.id, u.username, p.full_name, p.age, p.gender, p.city, p.country, p.bio
             FROM users u
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE u.id = ? AND u.id != ? AND u.role = "user" AND u.status = "approved"'
        );
        $profileStatement->execute([$selectedUserId, $currentUserId]);
        $selectedMember = $profileStatement->fetch();

        if ($selectedMember) {
            $historyStatement = $db->prepare(
                'SELECT sender_id, message, created_at
                 FROM messages
                 WHERE (sender_id = ? AND receiver_id = ?)
                    OR (sender_id = ? AND receiver_id = ?)
                 ORDER BY created_at ASC, id ASC'
            );
            $historyStatement->execute([$currentUserId, $selectedUserId, $selectedUserId, $currentUserId]);
            $chatMessages = $historyStatement->fetchAll();
        } else {
            $error = 'That member is not available.';
            $selectedUserId = 0;
        }
    }
} catch (PDOException $exception) {
    $error = 'Messages are temporarily unavailable. Please try again later.';
}
?>

<style>
    .messages-page {
        max-width: 1100px;
        margin: 0 auto;
    }

    .messages-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 20px;
    }

    .member-list,
    .chat-panel {
        background: #fff;
        border: 1px solid #eadfe2;
        border-radius: 12px;
        padding: 18px;
    }

    .member-link {
        display: block;
        padding: 12px;
        margin: 8px 0;
        border-radius: 8px;
        color: #2c2024;
        text-decoration: none;
        background: #fbf7f8;
    }

    .member-link:hover,
    .member-link.active {
        background: #f0dfe5;
    }

    .member-link small {
        color: #75686d;
    }

    .profile-summary {
        padding-bottom: 14px;
        border-bottom: 1px solid #eadfe2;
    }

    .chat-history {
        min-height: 260px;
        max-height: 420px;
        overflow-y: auto;
        padding: 16px 0;
    }

    .message {
        max-width: 75%;
        padding: 10px 13px;
        margin: 8px 0;
        border-radius: 10px;
        background: #f3eef0;
    }

    .message.mine {
        margin-left: auto;
        background: #8d3d58;
        color: #fff;
    }

    .message small {
        display: block;
        margin-top: 5px;
        opacity: .75;
        font-size: .75rem;
    }

    .message-form {
        display: flex;
        gap: 10px;
    }

    .message-form textarea {
        flex: 1;
        min-height: 48px;
        padding: 10px;
        border: 1px solid #cfc1c5;
        border-radius: 8px;
        font: inherit;
    }

    .message-form button {
        border: 0;
        border-radius: 8px;
        padding: 0 18px;
        background: #8d3d58;
        color: #fff;
        font-weight: 700;
        cursor: pointer;
    }

    .notice {
        padding: 12px;
        border-radius: 8px;
        background: #fff3f3;
        color: #9f1d1d;
    }

    @media (max-width: 700px) {
        .messages-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="messages-page">
    <h1>Messages</h1>
    <p>Choose a member, view their profile, and start chatting.</p>

    <?php if ($error !== ''): ?>
        <p class="notice" role="alert"><?= messageEscape($error) ?></p>
    <?php endif; ?>

    <div class="messages-layout">
        <aside class="member-list">
            <h2>Members</h2>
            <?php if (count($members) === 0): ?>
                <p>No other members are available yet.</p>
            <?php endif; ?>

            <?php foreach ($members as $member): ?>
                <a class="member-link <?= (int) $member['id'] === $selectedUserId ? 'active' : '' ?>"
                    href="index.php?page=messages&amp;user_id=<?= (int) $member['id'] ?>">
                    <strong><?= messageEscape($member['full_name'] ?: $member['username']) ?></strong><br>
                    <small><?= messageEscape($member['city'] ?: 'Location not added') ?></small>
                </a>
            <?php endforeach; ?>
        </aside>

        <section class="chat-panel">
            <?php if (!$selectedMember): ?>
                <h2>Select a member</h2>
                <p>Click a member on the left to see their profile and open a conversation.</p>
            <?php else: ?>
                <div class="profile-summary">
                    <h2><?= messageEscape($selectedMember['full_name'] ?: $selectedMember['username']) ?></h2>
                    <p>
                        <?= (int) ($selectedMember['age'] ?? 0) ?> years old
                        &middot; <?= messageEscape($selectedMember['city'] ?: 'Location not added') ?>
                        <?= $selectedMember['country'] ? ', ' . messageEscape($selectedMember['country']) : '' ?>
                    </p>
                    <?php if (!empty($selectedMember['bio'])): ?>
                        <p><?= messageEscape($selectedMember['bio']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="chat-history">
                    <?php if (count($chatMessages) === 0): ?>
                        <p>No messages yet. Say hello!</p>
                    <?php endif; ?>
                    <?php foreach ($chatMessages as $chatMessage): ?>
                        <div class="message <?= (int) $chatMessage['sender_id'] === $currentUserId ? 'mine' : '' ?>">
                            <?= nl2br(messageEscape($chatMessage['message'])) ?>
                            <small><?= messageEscape($chatMessage['created_at']) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form class="message-form" method="post"
                    action="index.php?page=messages&amp;user_id=<?= (int) $selectedUserId ?>">
                    <input type="hidden" name="recipient_id" value="<?= (int) $selectedUserId ?>">
                    <textarea name="message" maxlength="1000" placeholder="Write a message..." required></textarea>
                    <button type="submit">Send</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</main>