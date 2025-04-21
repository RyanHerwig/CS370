<?php

  // (Localhost Database Variables)
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "gallery_db";


  $conn = mysqli_connect($servername, $username, $password, $dbname);

  // Establish database connection using PDO
  try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
  }

?>
