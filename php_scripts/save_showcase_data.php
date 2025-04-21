<?php
header('Content-Type: application/json');
require 'db.php';

$response = ['success' => false, 'error' => null];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Invalid request method.';
    http_response_code(405); // Method Not Allowed
    echo json_encode($response);
    exit;
}

if (!isset($pdo)) {
    $response['error'] = 'Database connection is not available.';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

$slots = ['spotlight1', 'spotlight2', 'spotlight3', 'gallery1', 'gallery2', 'gallery3', 'gallery4', 'gallery5'];

$sql = "UPDATE showcase_config SET art_id = :art_id, custom_description = :custom_description WHERE slot_id = :slot_id";

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare($sql);

    if (!$stmt) {
        throw new Exception("Database error preparing statement.");
    }

    foreach ($slots as $slotId) {
        $art_id_key = $slotId . '_art_id';
        $desc_key = $slotId . '_desc';

        $art_id = isset($_POST[$art_id_key]) && is_numeric($_POST[$art_id_key]) && $_POST[$art_id_key] !== '' ? (int)$_POST[$art_id_key] : null;

        $description = isset($_POST[$desc_key]) ? trim($_POST[$desc_key]) : null;
        if ($description === '') {
            $description = null;
        }

        $params = [
            ':art_id' => $art_id,
            ':custom_description' => $description,
            ':slot_id' => $slotId
        ];

        if (!$stmt->execute($params)) {
            throw new Exception('Database error updating slot ' . htmlspecialchars($slotId) . '.');
        }
    }

    $pdo->commit();
    $response['success'] = true;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error in save_showcase_data.php: " . $e->getMessage());
    $response['error'] = $e->getMessage();
    http_response_code(500);
}
echo json_encode($response);
?>
