<?php
$servername = "localhost";
$username = "root";
$password = "";
// change this back to gallery_db after done testing
$dbname = "gallery_test";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    error_log("Database connection failed in db.php: " . $e->getMessage());
    throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
}
?>
