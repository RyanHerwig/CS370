<?php
// File: c:\xampp\htdocs\CS370\db.php  <-- NOTE: This file should be in CS370/, NOT php_scripts/

// (Localhost Database Variables)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gallery_db";

// Establish database connection using PDO
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set attributes AFTER successful connection
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optional: Set default fetch mode if desired
    // $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // $pdo->exec("set names utf8mb4"); // Generally redundant with charset=utf8mb4 in DSN

} catch (PDOException $e) {
    // Log the error for the server admin
    error_log("Database connection failed in db.php: " . $e->getMessage());

    // Option 1: Set $pdo to null. Calling scripts MUST check if ($pdo) {}
    // $pdo = null;

    // Option 2 (Recommended): Rethrow the exception. Calling scripts will catch it.
    throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());

    // --- DO NOT USE die() or echo here ---
    // die("Database connection failed: " . $e->getMessage()); // REMOVE/COMMENT OUT
}

// The $pdo variable is now available to including scripts if connection succeeded.
// If connection failed and Option 2 was used, the exception will halt execution here
// and be caught by the calling script's try...catch block.
?>
