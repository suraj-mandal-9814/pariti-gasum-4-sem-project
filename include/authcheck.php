<?php
// Load the session configuration before starting the session.
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Simple check if user is suspended
// Simple check if user is suspended
try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $status = $stmt->fetchColumn();
    
    if ($status === 'suspended') {
        // Destroy session and redirect
        session_unset();
        session_destroy();
        header("Location: login.php?error=suspended");
        exit();
    }
} catch (PDOException $e) {
    // If DB is offline during verify, proceed with local session checks
}
?>
