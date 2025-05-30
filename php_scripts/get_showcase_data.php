<?php
header('Content-Type: application/json');
require 'db.php';

$output = [];
$error = null;

try {
    if (!isset($pdo)) {
        throw new Exception("Database connection is not available.");
    }

    $sql = "SELECT
                sc.slot_id,
                sc.art_id,
                sc.custom_description,
                a.title,
                CONCAT(ar.first_name, ' ', ar.last_name) AS artist_name
            FROM showcase_config sc
            LEFT JOIN art a ON sc.art_id = a.art_id
            LEFT JOIN artist ar ON a.artist_id = ar.artist_id
            ORDER BY sc.slot_id";

    $stmt = $pdo->query($sql);

    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['art_id'] !== null && empty(trim($row['artist_name']))) {
                 $row['artist_name'] = 'Unknown Artist';
            }
            $output[$row['slot_id']] = $row;
        }
    } else {
        throw new Exception("Database query failed.");
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