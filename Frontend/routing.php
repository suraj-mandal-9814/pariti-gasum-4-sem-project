<?php

$page = $_GET['page'] ?? 'dashboard';

$routes = [
    'dashboard'    => 'page/dashboard.php',
    'edit'         => 'page/edit.php',
    'suggestion'   => 'page/suggestion.php',
    'search'       => 'page/search.php',
    'messages'     => 'page/messages.php',
    'notification' => 'page/notification.php',
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
require 'layout/userdashboard.php';
