<?php
// File: c:\xampp\htdocs\CS370\php_scripts\get_showcase_data.php

header('Content-Type: application/json');
require 'db.php'; // Adjust path if needed

$output = [];
$error = null;

try {
    // Ensure $pdo variable exists
    if (!isset($pdo)) {
        throw new Exception("Database connection is not available.");
    }

    // Fetch showcase config JOINED with art details and artist name
    // Use LEFT JOINs to ensure showcase slots appear even if the linked art/artist is deleted
    $sql = "SELECT
                sc.slot_id,
                sc.art_id,
                sc.custom_description,
                a.title,
                CONCAT(ar.first_name, ' ', ar.last_name) AS artist_name
                -- No default description column in your 'art' table
            FROM showcase_config sc
            LEFT JOIN art a ON sc.art_id = a.art_id
            LEFT JOIN artist ar ON a.artist_id = ar.artist_id
            ORDER BY sc.slot_id"; // Optional: order for consistency

    $stmt = $pdo->query($sql);

    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Handle potential NULL artist name if an artist record is missing but art exists
            if ($row['art_id'] !== null && empty(trim($row['artist_name']))) {
                 $row['artist_name'] = 'Unknown Artist';
            }
            // Use slot_id as the key in the output array
            $output[$row['slot_id']] = $row;
        }
    } else {
        throw new Exception("Database query failed.");
        // Log detailed error: error_log("PDO Error in get_showcase_data: " . print_r($pdo->errorInfo(), true));
    }

} catch (Exception $e) {
    error_log("Error in get_showcase_data.php: " . $e->getMessage());
    $error = "Failed to retrieve showcase configuration.";
    http_response_code(500);
}


if ($error) {
    echo json_encode(['error' => $error]);
} else {
    echo json_encode($output);
}
?>