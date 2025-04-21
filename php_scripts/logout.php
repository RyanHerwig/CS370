<?php
// File: c:\xampp\htdocs\CS370\php_scripts\logout.php
session_start();

// clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    // Set cookie expiration in the past
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

header('Content-Type: application/json');

echo json_encode(['success' => true, 'redirect' => '/cs370/index.html']); // <-- Absolute Path

exit;
?>
