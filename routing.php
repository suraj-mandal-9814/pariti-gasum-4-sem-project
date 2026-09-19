<?php

$page = $_GET['page'] ?? 'dashboard';

$routes = [
    'dashboard'    => 'dashboard.php',
    'edit'         => 'edit.php',
    'suggestion'   => 'suggestion.php',
    'search'       => 'api/search.php',
    'messages'     => 'messages.php',
    'notification' => 'notification.php',
    'chat'         => 'api/chat.php',
];


// Check whether route exists
if (!isset($routes[$page])) {
    http_response_code(404);
    echo "Page not found.";
    exit;
}


// Get requested page
$content = $routes[$page];


// Check whether file exists
if (!file_exists($content)) {
    http_response_code(404);
    echo "Page file not found: " . htmlspecialchars($content);
    exit;
}


// Load user dashboard layout
require 'userdashboard.php';
