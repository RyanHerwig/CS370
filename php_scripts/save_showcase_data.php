<?php
// File: c:\xampp\htdocs\CS370\php_scripts\save_showcase_data.php

header('Content-Type: application/json');
require 'db.php';

$response = ['success' => false, 'error' => null];

// --- IMPORTANT: Add Authentication Check ---
// session_start(); // If using sessions for login
// if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) { // Example check
//     $response['error'] = 'Unauthorized access.';
//     http_response_code(403); // Forbidden
//     echo json_encode($response);
//     exit;
// }
// --- End Authentication Check ---


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Invalid request method.';
    http_response_code(405); // Method Not Allowed
    echo json_encode($response);
    exit;
}

// Ensure $pdo variable exists
if (!isset($pdo)) {
    $response['error'] = 'Database connection is not available.';
    http_response_code(500);
    echo json_encode($response);
    exit;
}


// Define the slots we expect data for
$slots = ['spotlight1', 'spotlight2', 'spotlight3', 'gallery1', 'gallery2', 'gallery3', 'gallery4', 'gallery5'];

// Use prepared statements to prevent SQL injection
$sql = "UPDATE showcase_config SET art_id = :art_id, custom_description = :custom_description WHERE slot_id = :slot_id";

try {
    $pdo->beginTransaction(); // Start transaction for atomicity

    $stmt = $pdo->prepare($sql);

    if (!$stmt) {
        throw new Exception("Database error preparing statement.");
        // Log detailed error: error_log("PDO Prepare failed: " . print_r($pdo->errorInfo(), true));
    }

    foreach ($slots as $slotId) {
        $art_id_key = $slotId . '_art_id';
        $desc_key = $slotId . '_desc';

        // Get art_id: Treat empty string or non-numeric as NULL
        $art_id = isset($_POST[$art_id_key]) && is_numeric($_POST[$art_id_key]) && $_POST[$art_id_key] !== '' ? (int)$_POST[$art_id_key] : null;

        // Get description: Allow empty string, trim whitespace. Store NULL if empty.
        $description = isset($_POST[$desc_key]) ? trim($_POST[$desc_key]) : null;
        if ($description === '') {
            $description = null;
        }

        // Bind parameters using named placeholders
        $params = [
            ':art_id' => $art_id,
            ':custom_description' => $description,
            ':slot_id' => $slotId
        ];

        if (!$stmt->execute($params)) {
            // Log detailed error: error_log("PDO Execute failed for slot $slotId: " . print_r($stmt->errorInfo(), true));
            throw new Exception('Database error updating slot ' . htmlspecialchars($slotId) . '.');
        }
    }

    $pdo->commit(); // Commit transaction if all updates were successful
    $response['success'] = true;

} catch (Exception $e) {
    $pdo->rollBack(); // Roll back changes on error
    error_log("Error in save_showcase_data.php: " . $e->getMessage());
    $response['error'] = $e->getMessage(); // Send specific error back (or a generic one)
    http_response_code(500);
}

// $pdo = null; // Close connection if needed

echo json_encode($response);
?>
