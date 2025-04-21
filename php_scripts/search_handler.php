<?php
session_start();
// search_handler.php

require 'db.php';

header('Content-Type: application/json');

$title = $_GET['title'] ?? '';
$artistSearchTerm = $_GET['artist'] ?? '';
$genre = $_GET['genre'] ?? '';
$type = $_GET['type'] ?? '';
$dateStart = $_GET['date-start'] ?? '';
$dateEnd = $_GET['date-end'] ?? '';

$sql = "SELECT art.art_id, art.title, art.date_created, art.genre, art.type,
               CONCAT(artist.first_name, ' ', artist.last_name) AS artist_name
        FROM art
        JOIN artist ON art.artist_id = artist.artist_id
        WHERE 1=1";

$params = [];

if (!empty($title)) {
    $sql .= " AND art.title LIKE :title";
    $params[':title'] = '%' . $title . '%';
}

if (!empty($artistSearchTerm)) {
    $sql .= " AND CONCAT(artist.first_name, ' ', artist.last_name) LIKE :artist_name";
    $params[':artist_name'] = '%' . $artistSearchTerm . '%';
}

if (!empty($genre)) {
    $sql .= " AND art.genre = :genre";
    $params[':genre'] = $genre;
}

if (!empty($type)) {
    $sql .= " AND art.type = :type";
    $params[':type'] = $type;
}

if (!empty($dateStart)) {
    $sql .= " AND art.date_created >= :date_start";
    $params[':date_start'] = $dateStart;
}
if (!empty($dateEnd)) {
    $sql .= " AND art.date_created <= :date_end";
    $params[':date_end'] = $dateEnd;
}

$sql .= " ORDER BY art.title ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while searching: ' . $e->getMessage()]);
}

?>
