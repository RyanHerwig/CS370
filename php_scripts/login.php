<?php
session_start();
header('Content-Type: application/json');

require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'] ?? '';
    $submittedPassword = $_POST['password'] ?? '';

    if (empty($username) || empty($submittedPassword)) {
        echo json_encode(["success" => false, "error" => "Username and password are required."]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT userid, password, username FROM accounts WHERE username = :username");
        if (!$stmt) {
            error_log("PDO Prepare failed in login.php for username: " . $username);
            echo json_encode(["success" => false, "error" => "An internal error occurred during login preparation."]);
            exit;
        }

        $stmt->bindParam(':username', $username, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            error_log("PDO Execute failed in login.php for username: " . $username . " Error: " . implode(":", $stmt->errorInfo()));
            echo json_encode(["success" => false, "error" => "An internal error occurred during login execution."]);
            exit;
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($submittedPassword, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['userid'] = $user['userid'];
                $_SESSION['username'] = $user['username'];

                echo json_encode(["success" => true]);
                exit;

            } else {
                // Invalid password
                echo json_encode(["success" => false, "error" => "Invalid username or password."]); // Generic error for security
                exit;
            }
        } else {
            // User not found
            echo json_encode(["success" => false, "error" => "Invalid username or password."]); // Generic error for security
            exit;
        }

    } catch (PDOException $e) {
        // Catch any PDO exceptions
        error_log("PDOException in login.php: " . $e->getMessage());
        http_response_code(500); // Internal Server Error
        echo json_encode(["success" => false, "error" => "A database error occurred."]);
        exit;
    }

} else {
    // Invalid request method
    http_response_code(405); // Method not allowed
    echo json_encode(["error" => "Invalid request method."]);
    exit;
}
?>
