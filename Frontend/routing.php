<?php

$page = $_GET['page'] ?? 'dashboard';

$routes = [
    'dashboard'    => 'pages/dashboard.php',
    'edit'         => 'pages/edit.php',
    'suggestion'   => 'pages/suggestion.php',
    'search'       => 'pages/search.php',
    'messages'     => 'pages/messages.php',
    'notification' => 'pages/notification.php',
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
