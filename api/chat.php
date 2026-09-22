<?php
// Start the session if this page is opened directly.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Make an empty chat list the first time the page is opened.
if (!isset($_SESSION['chat_messages'])) {
    $_SESSION['chat_messages'] = [];
}

// Run this code only when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear all saved messages.
    if (isset($_POST['clear'])) {
        $_SESSION['chat_messages'] = [];
    }

    // Save a new message.
    if (isset($_POST['send'])) {
        $message = trim($_POST['message'] ?? '');

        if ($message !== '') {
            $_SESSION['chat_messages'][] = [
                'name' => $_SESSION['username'] ?? 'Student',
                'text' => $message,
                'time' => date('h:i A'),
            ];
        }
    }

    // Prevent the browser from sending the same message on refresh.
    header('Location: index.php?page=chat');
    exit;
}

// Convert special characters before showing a message in HTML.
function chatEscape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>

<style>
    .chat-box { max-width: 650px; margin: 20px auto; padding: 20px; background: white; border-radius: 10px; }
    .chat-message { margin: 10px 0; padding: 10px; background: #f3eef0; border-radius: 8px; }
    .chat-message small { color: #666; }
    .chat-form { display: flex; gap: 10px; margin-top: 15px; }
    .chat-form input { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
    .chat-form button { padding: 10px 16px; border: 0; border-radius: 6px; background: #8d3d58; color: white; cursor: pointer; }
</style>

<section class="chat-box">
    <h1>Simple Chat</h1>
    <p>Write a message and press Send.</p>

    <?php if (empty($_SESSION['chat_messages'])): ?>
        <p>No messages yet.</p>
    <?php endif; ?>

    <?php foreach ($_SESSION['chat_messages'] as $chat): ?>
        <div class="chat-message">
            <strong><?= chatEscape($chat['name']) ?>:</strong>
            <?= chatEscape($chat['text']) ?><br>
            <small><?= chatEscape($chat['time']) ?></small>
        </div>
    <?php endforeach; ?>

    <form method="post" class="chat-form">
        <input type="text" name="message" placeholder="Type your message" maxlength="200" required>
        <button type="submit" name="send">Send</button>
        <button type="submit" name="clear">Clear</button>
    </form>
</section>
