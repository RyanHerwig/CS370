<?php
session_start();

// Destroy the session and unset session variables
session_destroy();
$_SESSION = array();

header('Content-Type: application/json');

// Construct the redirect URL with proper relative path
$redirectUrl = dirname($_SERVER['PHP_SELF']) . '/../index.html';

echo json_encode([
    'success' => true,
    'redirect' => $redirectUrl
]);
?>
