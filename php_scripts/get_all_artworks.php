<?php
// File: c:\xampp\htdocs\CS370\php_scripts\get_all_artworks.php

header('Content-Type: application/json');
require 'db.php';

$artworks = [];
$error = null;

try {
    // Ensure $pdo variable exists from db.php
    if (!isset($pdo)) {
        throw new Exception("Database connection is not available.");
    }

    // Fetch essential details for the dropdown selector, joining art and artist
    $sql = "SELECT
                a.art_id,
                a.title,
                CONCAT(ar.first_name, ' ', ar.last_name) AS artist_name
            FROM art a
            LEFT JOIN artist ar ON a.artist_id = ar.artist_id -- Use LEFT JOIN in case artist is deleted
            ORDER BY a.title ASC"; // Order alphabetically by title for usability

    $stmt = $pdo->query($sql); // Simple query as no user input is involved

    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
             // Handle cases where artist name might be empty/null
             if (empty(trim($row['artist_name']))) {
                 $row['artist_name'] = 'Unknown Artist';
             }
            $artworks[] = $row;
        }
    } else {
        // Query execution failed
        throw new Exception("Database query failed."); // Keep error generic for client
        // Log detailed error: error_log("PDO Error in get_all_artworks: " . print_r($pdo->errorInfo(), true));
    }

} catch (Exception $e) {
    // Log the detailed error for server admin
    error_log("Error in get_all_artworks.php: " . $e->getMessage());
    // Set a generic error for the client-side
    $error = "Failed to retrieve artwork list.";
    http_response_code(500); // Internal Server Error
}


// Send the response
if ($error) {
    echo json_encode(['error' => $error]);
} else {
    echo json_encode($artworks);
}
?>