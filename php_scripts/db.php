<?php

  // (Localhost Database Variables)
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "gallery_db";

  // Establish database connection using PDO
  try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8mb4");
  } catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
  }

?>
