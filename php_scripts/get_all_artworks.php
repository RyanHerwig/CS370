<?php

header('Content-Type: application/json');
require 'db.php';

$artworks = [];
$error = null;

try {
    if (!isset($pdo)) {
        throw new Exception("Database connection is not available.");
    }
    $sql = "SELECT
                a.art_id,
                a.title,
                CONCAT(ar.first_name, ' ', ar.last_name) AS artist_name
            FROM art a
            LEFT JOIN artist ar ON a.artist_id = ar.artist_id
            ORDER BY a.title ASC";

    $stmt = $pdo->query($sql);

    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
             if (empty(trim($row['artist_name']))) {
                 $row['artist_name'] = 'Unknown Artist';
             }
            $artworks[] = $row;
        }
    } else {
        throw new Exception("Database query failed.");
    }

} catch (Exception $e) {
    error_log("Error in get_all_artworks.php: " . $e->getMessage());
    $error = "Failed to retrieve artwork list.";
    http_response_code(500);
}
if ($error) {
    echo json_encode(['error' => $error]);
} else {
    echo json_encode($artworks);
}
?>