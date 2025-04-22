<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['userid'])) {
    header("Location: ../login.html");
    exit;
}

define('UPLOAD_DIR', dirname(__DIR__) . '/images/');
define('UPLOAD_URL_PATH', '../images/');

$message = '';
$message_type = '';
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message']['text'] ?? 'An unknown status occurred.';
    $message_type = $_SESSION['status_message']['type'] ?? 'error';
    unset($_SESSION['status_message']);
}

if (isset($_REQUEST['action'])) {
    header('Content-Type: application/json');
    $action = $_REQUEST['action'];
    $response = ['success' => false, 'error' => 'Invalid action'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            if ($action === 'get_list') {
                $type = $_GET['type'] ?? null;
                if ($type === 'artist') {
                    $stmt = $pdo->query("SELECT artist_id, first_name, last_name, dob FROM artist ORDER BY last_name, first_name");
                    $response = ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
                } elseif ($type === 'art') {
                    $stmt = $pdo->query("SELECT a.art_id, a.title, CONCAT(ar.first_name, ' ', ar.last_name) AS artist_name
                                         FROM art a
                                         LEFT JOIN artist ar ON a.artist_id = ar.artist_id
                                         ORDER BY a.title");
                    $response = ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
                } else {
                     $response['error'] = 'Invalid type specified for list.';
                }
            }
            elseif ($action === 'get_details') {
                $type = $_GET['type'] ?? null;
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

                if ($id && $type) {
                    if ($type === 'artist') {
                        $stmt = $pdo->prepare("SELECT artist_id, first_name, last_name, dob, description FROM artist WHERE artist_id = :id");
                        $stmt->execute([':id' => $id]);
                        $data = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($data) {
                            $response = ['success' => true, 'data' => $data];
                        } else {
                            $response['error'] = 'Artist not found.';
                        }
                    } elseif ($type === 'art') {
                        $stmt = $pdo->prepare("SELECT a.*, CONCAT(ar.first_name, ' ', ar.last_name) AS artist_name
                                               FROM art a
                                               LEFT JOIN artist ar ON a.artist_id = ar.artist_id
                                               WHERE a.art_id = :id");
                        $stmt->execute([':id' => $id]);
                        $data = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($data) {
                            $artistStmt = $pdo->query("SELECT artist_id, first_name, last_name FROM artist ORDER BY last_name, first_name");
                            $artists = $artistStmt->fetchAll(PDO::FETCH_ASSOC);
                            $response = ['success' => true, 'data' => $data, 'artists' => $artists];
                        } else {
                            $response['error'] = 'Artwork not found.';
                        }
                    } else {
                         $response['error'] = 'Invalid type specified for details.';
                    }
                } else {
                     $response['error'] = 'Missing or invalid ID or type for details.';
                }
            } else {
                 $response['error'] = 'Invalid GET action specified.';
            }

        } catch (PDOException $e) {
            error_log("Database Error in edit_art.php (GET AJAX): " . $e->getMessage());
            $response = ['success' => false, 'error' => 'Database error occurred. ' . $e->getMessage()];
            http_response_code(500);
        } catch (Exception $e) {
            error_log("General Error in edit_art.php (GET AJAX): " . $e->getMessage());
            $response = ['success' => false, 'error' => 'An error occurred. ' . $e->getMessage()];
            http_response_code(500);
        }
        echo json_encode($response);
        exit;
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
         try {
             $type = $_POST['type'] ?? null;
             $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

             if (!$id || !$type) {
                  throw new Exception("Missing or invalid ID or type for delete action.");
             }

             $pdo->beginTransaction();

             if ($type === 'artist') {
                 $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM art WHERE artist_id = :id");
                 $stmtCheck->execute([':id' => $id]);
                 if ($stmtCheck->fetchColumn() > 0) {
                     throw new Exception("Cannot delete artist: They have associated artwork.");
                 }

                 $portrait_to_delete = null;
                 $extensions_to_check = ['jpg', 'png', 'jpeg'];
                 foreach ($extensions_to_check as $ext) {
                     $potential_path = UPLOAD_DIR . 'artist-' . $id . '.' . $ext;
                     if (file_exists($potential_path)) {
                         $portrait_to_delete = $potential_path;
                         break;
                     }
                 }

                 $stmt = $pdo->prepare("DELETE FROM artist WHERE artist_id = :id");
                 $success = $stmt->execute([':id' => $id]);

                 if ($success && $portrait_to_delete) {
                     if (!unlink($portrait_to_delete)) {
                         error_log("Warning: Could not delete artist portrait file: " . $portrait_to_delete);
                     }
                 }

             } elseif ($type === 'art') {
                 $image_to_delete = null;
                 $extensions_to_check = ['jpg', 'png', 'jpeg'];
                 foreach ($extensions_to_check as $ext) {
                     $potential_path = UPLOAD_DIR . $id . '.' . $ext;
                     if (file_exists($potential_path)) {
                         $image_to_delete = $potential_path;
                         break;
                     }
                 }

                 $stmt = $pdo->prepare("DELETE FROM art WHERE art_id = :id");
                 $success = $stmt->execute([':id' => $id]);

                 if ($success && $image_to_delete) {
                     if (!unlink($image_to_delete)) {
                         error_log("Warning: Could not delete art image file: " . $image_to_delete);
                     }
                 }
             } else {
                 throw new Exception("Invalid type for deletion.");
             }

             if ($success) {
                 $pdo->commit();
                 $response = ['success' => true];
             } else {
                 $pdo->rollBack();
                 throw new Exception("Database deletion failed.");
             }

         } catch (PDOException $e) {
             if ($pdo->inTransaction()) $pdo->rollBack();
             error_log("Database Error in edit_art.php (DELETE AJAX): " . $e->getMessage());
             $response = ['success' => false, 'error' => 'Database error occurred: ' . $e->getMessage()];
             http_response_code(500);
         } catch (Exception $e) {
             if ($pdo->inTransaction()) $pdo->rollBack();
             error_log("General Error in edit_art.php (DELETE AJAX): " . $e->getMessage());
             $response = ['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()];
             http_response_code(400);
         }
         echo json_encode($response);
         exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
     $type = $_POST['type'] ?? null;
     $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
     $redirect_url = "edit_art.php";
     $status_message = ['type' => 'error', 'text' => 'An unknown error occurred during update.'];

     if ($type) {
         $redirect_url .= "?last_type=" . urlencode($type);
     }

     try {
         if (!$id || !$type) {
              throw new Exception("Missing or invalid ID or type for update action.");
         }

         $pdo->beginTransaction();
         $success = false;

         if ($type === 'artist') {
             $first_name = trim($_POST['artist_first_name'] ?? '');
             $last_name = trim($_POST['artist_last_name'] ?? '');
             $dob = !empty($_POST['artist_dob']) ? $_POST['artist_dob'] : null;
             $description = isset($_POST['artist_description']) ? trim($_POST['artist_description']) : null;
             if ($description === '') $description = null;

             if (empty($first_name) || empty($last_name)) {
                 throw new Exception("First name and last name are required.");
             }

             $sql = "UPDATE artist SET first_name = :first_name, last_name = :last_name, dob = :dob, description = :description WHERE artist_id = :id";
             $stmt = $pdo->prepare($sql);
             $success = $stmt->execute([
                 ':first_name' => $first_name,
                 ':last_name' => $last_name,
                 ':dob' => $dob,
                 ':description' => $description,
                 ':id' => $id
             ]);

             if ($success && isset($_FILES['artist_portrait_edit']) && $_FILES['artist_portrait_edit']['error'] === UPLOAD_ERR_OK) {
                 $file_tmp_path = $_FILES['artist_portrait_edit']['tmp_name'];
                 $file_name = $_FILES['artist_portrait_edit']['name'];
                 $file_size = $_FILES['artist_portrait_edit']['size'];
                 $file_ext_lower = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                 $allowed_extensions = ['jpg', 'jpeg', 'png'];
                 $allowed_mime_types = ['image/jpeg', 'image/png'];
                 $max_file_size = 2 * 1024 * 1024;

                 if (in_array($file_ext_lower, $allowed_extensions) && in_array(mime_content_type($file_tmp_path), $allowed_mime_types)) {
                     if ($file_size <= $max_file_size) {
                         $extensions_to_check = ['jpg', 'png', 'jpeg'];
                         foreach ($extensions_to_check as $ext) {
                             $old_file = UPLOAD_DIR . 'artist-' . $id . '.' . $ext;
                             if (file_exists($old_file)) { @unlink($old_file); }
                         }
                         $new_filename = 'artist-' . $id . '.' . $file_ext_lower;
                         $destination_path = UPLOAD_DIR . $new_filename;
                         if (!move_uploaded_file($file_tmp_path, $destination_path)) {
                             error_log("Warning: Failed to move replacement artist portrait for ID $id to $destination_path");
                         }
                     } else { error_log("Warning: Replacement portrait for artist ID $id exceeds size limit."); }
                 } else { error_log("Warning: Invalid replacement portrait file type for artist ID $id."); }
             }

         } elseif ($type === 'art') {
             $title = trim($_POST['art_title'] ?? 'Unknown Title');
             $artist_id = filter_input(INPUT_POST, 'art_artist_id', FILTER_VALIDATE_INT);
             $date_created = !empty($_POST['art_date_created']) ? $_POST['art_date_created'] : null;
             $genre = trim($_POST['art_genre'] ?? 'Undefined');
             $type_field = trim($_POST['art_type'] ?? 'Undefined');

             if (empty($title)) $title = 'Unknown Title';
             if ($artist_id === false || $artist_id <= 0) throw new Exception("A valid artist must be selected.");
             if (empty($genre)) $genre = 'Undefined';
             if (empty($type_field)) $type_field = 'Undefined';

             $new_image_uploaded = false;
             $new_image_path = null;
             $new_image_ext = null;

             if (isset($_FILES['art_image_edit']) && $_FILES['art_image_edit']['error'] === UPLOAD_ERR_OK) {
                 $file_tmp_path = $_FILES['art_image_edit']['tmp_name'];
                 $file_name = $_FILES['art_image_edit']['name'];
                 $file_size = $_FILES['art_image_edit']['size'];
                 $file_ext_lower = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                 $allowed_extensions = ['jpg', 'jpeg', 'png'];
                 $allowed_mime_types = ['image/jpeg', 'image/png'];
                 $max_file_size = 10 * 1024 * 1024;

                 if (!in_array($file_ext_lower, $allowed_extensions) || !in_array(mime_content_type($file_tmp_path), $allowed_mime_types)) {
                     throw new Exception("Invalid replacement file type. Only JPG, JPEG, PNG allowed.");
                 }
                 if ($file_size > $max_file_size) {
                     throw new Exception("Replacement file size exceeds 10MB limit.");
                 }
                 $new_image_uploaded = true;
                 $new_image_ext = $file_ext_lower;
                 $new_image_path = $file_tmp_path;
             } elseif (isset($_FILES['art_image_edit']) && $_FILES['art_image_edit']['error'] !== UPLOAD_ERR_NO_FILE) {
                 throw new Exception("Error uploading replacement file. Code: " . $_FILES['art_image_edit']['error']);
             }

             $sql = "UPDATE art SET title = :title, artist_id = :artist_id, date_created = :date_created, genre = :genre, type = :type WHERE art_id = :id";
             $stmt = $pdo->prepare($sql);
             $success = $stmt->execute([
                 ':title' => $title,
                 ':artist_id' => $artist_id,
                 ':date_created' => $date_created,
                 ':genre' => $genre,
                 ':type' => $type_field,
                 ':id' => $id
             ]);

             if ($success && $new_image_uploaded) {
                 $extensions_to_check = ['jpg', 'png', 'jpeg'];
                 foreach ($extensions_to_check as $ext) {
                     $old_file = UPLOAD_DIR . $id . '.' . $ext;
                     if (file_exists($old_file)) { @unlink($old_file); }
                 }
                 $destination_path = UPLOAD_DIR . $id . '.' . $new_image_ext;
                 if (!move_uploaded_file($new_image_path, $destination_path)) {
                     throw new Exception("Failed to move uploaded replacement file. Database changes rolled back.");
                 }
             }

         } else {
             throw new Exception("Invalid type for update.");
         }

         if ($success) {
             $pdo->commit();
             $status_message = ['type' => 'success', 'text' => ucfirst($type) . ' updated successfully!'];
         } else {
             $pdo->rollBack();
             throw new Exception("Database update failed.");
         }

     } catch (PDOException $e) {
         if ($pdo->inTransaction()) $pdo->rollBack();
         error_log("Database Error in edit_art.php (POST Update): " . $e->getMessage());
         $status_message = ['type' => 'error', 'text' => 'Database error occurred: ' . $e->getMessage()];
     } catch (Exception $e) {
         if ($pdo->inTransaction()) $pdo->rollBack();
         error_log("General Error in edit_art.php (POST Update): " . $e->getMessage());
         $status_message = ['type' => 'error', 'text' => 'An error occurred: ' . $e->getMessage()];
     }

     $_SESSION['status_message'] = $status_message;
     header("Location: " . $redirect_url);
     exit;
}

$last_type_selected = $_GET['last_type'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit/Delete Art & Artists</title>
    <link rel="stylesheet" href="../assets/css/main.css" />
    <noscript><link rel="stylesheet" href="../assets/css/noscript.css" /></noscript>
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        #navbar { overflow: hidden; background-color: #333; padding: 10px 0; text-align: center; width: 100%; }
        #navbar a { display: inline-block; color: #f2f2f2; text-align: center; padding: 14px 16px; text-decoration: none; font-size: 17px; margin: 0 5px; border-radius: 5px; }
        #navbar a:hover { background-color: #ddd; color: black; }
        #navbar a.active { background-color: #555; color: white; }

        .container {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 20px auto;
        }
        h1, h2 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="file"] {
             padding: 10px;
             margin-bottom: 15px;
             border: 1px solid #ccc;
             border-radius: 4px;
             display: block;
             width: 100%;
             box-sizing: border-box;
        }
        button, .button-link {
            background-color: #5cb85c;
            color: white !important;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
            vertical-align: middle;
        }
        button[type="submit"] {
             width: auto;
             display: inline-block;
        }
        button:hover, .button-link:hover {
            background-color: #4cae4c;
        }
        .button-link.delete {
             background-color: #d9534f;
        }
         .button-link.delete:hover {
             background-color: #c9302c;
         }
         .button-link.cancel {
             background-color: #f0ad4e;
         }
         .button-link.cancel:hover {
             background-color: #ec971f;
         }

        .hidden { display: none; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; text-align: center; }
        .message.success { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .message.error { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        .required-star { color: red; margin-left: 2px; }

        #list-container ul { list-style: none; padding: 0; }
        #list-container li {
            background: #f9f9f9;
            border: 1px solid #eee;
            padding: 10px 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #list-container li span { flex-grow: 1; margin-right: 15px; }
        #list-container li .actions { white-space: nowrap; }

        #edit-form-container { border: 1px solid #ddd; padding: 20px; margin-top: 20px; border-radius: 5px; background: #fefefe; }
        #edit-form-container h2 { margin-top: 0; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; display: inline-block; margin-left: 10px; vertical-align: middle; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .loading-indicator { text-align: center; padding: 20px; font-style: italic; color: #666; }
        .current-image-preview { margin-bottom: 15px; }
        .current-image-preview img { max-width: 150px; max-height: 100px; border: 1px solid #ccc; vertical-align: middle; margin-right: 10px; }
        .current-image-preview span { font-style: italic; color: #555; }

    </style>
</head>
<body>

<div id="navbar">
    <a href="../index.html">Home</a>
    <a href="../search.html">Search</a>
    <a href="../about.html">About</a>
</div>
<script src="../script.js"></script>

<div class="container">
    <h1>Edit/Delete Entries</h1>

    <?php if (!empty($message)): ?>
        <div id="global-message" class="message <?= htmlspecialchars($message_type) ?>">
             <?= htmlspecialchars($message) ?>
        </div>
    <?php else: ?>
         <div id="global-message" class="message hidden"></div>
    <?php endif; ?>


    <div>
        <label for="edit_type">Select what to manage:<span class="required-star">*</span></label>
        <select name="edit_type" id="edit_type" required>
            <option value="">-- Select Type --</option>
            <option value="art" <?= ($last_type_selected === 'art' ? 'selected' : '') ?>>Manage Art</option>
            <option value="artist" <?= ($last_type_selected === 'artist' ? 'selected' : '') ?>>Manage Artists</option>
        </select>
    </div>
    <div id="list-container" style="margin-top: 20px;">
        <div class="loading-indicator hidden">Loading... <div class="loader"></div></div>
    </div>
    <div id="edit-form-container" class="hidden">
         <div class="loading-indicator hidden">Loading details... <div class="loader"></div></div>
    </div>

</div>

<script>
    const editTypeSelect = document.getElementById('edit_type');
    const listContainer = document.getElementById('list-container');
    const editFormContainer = document.getElementById('edit-form-container');
    const globalMessageDiv = document.getElementById('global-message');
    const listLoadingIndicator = listContainer.querySelector('.loading-indicator');
    const formLoadingIndicator = editFormContainer.querySelector('.loading-indicator');

    function showMessage(msg, type = 'error') {
        globalMessageDiv.textContent = msg;
        globalMessageDiv.className = `message ${type}`;
        globalMessageDiv.classList.remove('hidden');
        setTimeout(() => {
            if (globalMessageDiv.textContent === msg) {
               globalMessageDiv.classList.add('hidden');
            }
        }, 5000);
    }

    function showListLoading(show) {
        listLoadingIndicator.classList.toggle('hidden', !show);
        if (show) listContainer.innerHTML = '';
    }
     function showFormLoading(show) {
        formLoadingIndicator.classList.toggle('hidden', !show);
        if (show) editFormContainer.innerHTML = '';
    }

    async function loadList(type) {
        if (!type) {
            listContainer.innerHTML = '<p>Please select a type to manage.</p>';
            editFormContainer.classList.add('hidden');
            return;
        }
        showListLoading(true);
        editFormContainer.classList.add('hidden');
        listContainer.classList.remove('hidden');

        try {
            const response = await fetch(`edit_art.php?action=get_list&type=${type}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.success && result.data) {
                renderList(type, result.data);
            } else {
                throw new Error(result.error || 'Failed to load list data.');
            }
        } catch (error) {
            console.error('Error loading list:', error);
            showMessage(`Error loading ${type} list: ${error.message}`, 'error');
            listContainer.innerHTML = `<p style="color: red;">Could not load ${type} list.</p>`;
        } finally {
            showListLoading(false);
        }
    }

    function renderList(type, data) {
        let listHtml = '<ul>';
        if (data.length === 0) {
            listHtml = `<p>No ${type} entries found.</p>`;
        } else {
            data.forEach(item => {
                const id = item.artist_id || item.art_id;
                let displayText = '';
                if (type === 'artist') {
                    displayText = `${item.first_name} ${item.last_name} (Artist_ID: ${id})`;
                } else {
                    displayText = `${item.title} (Artist: ${item.artist_name || 'Unknown'}, ID: ${id})`;
                }

                listHtml += `
                    <li data-id="${id}" data-type="${type}">
                        <span>${escapeHtml(displayText)}</span>
                        <div class="actions">
                            <button class="button-link edit-button" data-id="${id}" data-type="${type}">Edit</button>
                            <button class="button-link delete delete-button" data-id="${id}" data-type="${type}">Delete</button>
                        </div>
                    </li>
                `;
            });
            listHtml += '</ul>';
        }
        listContainer.innerHTML = listHtml;
    }

    async function loadEditForm(type, id) {
        showFormLoading(true);
        listContainer.classList.add('hidden');
        editFormContainer.classList.remove('hidden');

        try {
            const response = await fetch(`edit_art.php?action=get_details&type=${type}&id=${id}`);
             if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.success && result.data) {
                renderEditForm(type, id, result.data, result.artists || []);
            } else {
                 throw new Error(result.error || `Failed to load details for ${type} ID ${id}.`);
            }
        } catch (error) {
            console.error('Error loading edit form:', error);
            showMessage(`Error loading details: ${error.message}`, 'error');
            editFormContainer.innerHTML = `<p style="color: red;">Could not load details.</p><button type="button" class="button-link cancel cancel-edit">Back to List</button>`;
        } finally {
             showFormLoading(false);
        }
    }

    function renderEditForm(type, id, data, artists = []) {
        let formHtml = `<form id="editItemForm" action="edit_art.php" method="post" enctype="multipart/form-data">`;
        formHtml += `<input type="hidden" name="action" value="update">`;
        formHtml += `<input type="hidden" name="type" value="${type}">`;
        formHtml += `<input type="hidden" name="id" value="${id}">`;

        if (type === 'artist') {
            formHtml += `<h2>Edit Artist (ID: ${id})</h2>`;

            const portraitBaseUrl = `../images/artist-${id}`;
            const portraitJpg = `${portraitBaseUrl}.jpg`;
            const portraitPng = `${portraitBaseUrl}.png`;
            const portraitJpeg = `${portraitBaseUrl}.jpeg`;

            formHtml += `<div class="current-image-preview"><strong>Current Portrait:</strong><br>`;
            formHtml += `<img src="${portraitJpg}" alt="Current Portrait" style="max-width: 100px; max-height: 100px; border-radius: 50%; margin-right: 5px;"
                           onerror="this.onerror=null; this.src='${portraitPng}'; this.onerror=()=>{this.onerror=null; this.src='${portraitJpeg}'; this.onerror=()=>{this.style.display='none'; this.nextSibling.textContent='No portrait found.';};};">`;
            formHtml += `<span></span></div>`;

            formHtml += `
                <div>
                    <label for="artist_portrait_edit">Replace Portrait (Optional, JPG/PNG)</label>
                    <input type="file" id="artist_portrait_edit" name="artist_portrait_edit" accept=".jpg, .jpeg, .png">
                </div>
                <hr>
                <div>
                    <label for="artist_first_name">First Name<span class="required-star">*</span></label>
                    <input type="text" id="artist_first_name" name="artist_first_name" value="${escapeHtml(data.first_name || '')}" required>
                </div>
                <div>
                    <label for="artist_last_name">Last Name<span class="required-star">*</span></label>
                    <input type="text" id="artist_last_name" name="artist_last_name" value="${escapeHtml(data.last_name || '')}" required>
                </div>
                <div>
                    <label for="artist_dob">Date of Birth</label>
                    <input type="date" id="artist_dob" name="artist_dob" value="${escapeHtml(data.dob || '')}">
                </div>
                <div>
                    <label for="artist_description">Description</label>
                    <textarea id="artist_description" name="artist_description" rows="4" placeholder="Enter a brief description...">${escapeHtml(data.description || '')}</textarea>
                </div>
            `;
        } else { // art
             formHtml += `<h2>Edit Art (ID: ${id})</h2>`;
             const imageUrlBase = `../images/${id}`;
             const imageUrlJpg = `${imageUrlBase}.jpg`;
             const imageUrlPng = `${imageUrlBase}.png`;
             const imageUrlJpeg = `${imageUrlBase}.jpeg`;

             formHtml += `<div class="current-image-preview"><strong>Current Image:</strong><br>`;
             formHtml += `<img src="${imageUrlJpg}" alt="Current Image" style="margin-right: 5px;"
                            onerror="this.onerror=null; this.src='${imageUrlPng}'; this.onerror=()=>{this.onerror=null; this.src='${imageUrlJpeg}'; this.onerror=()=>{this.style.display='none'; this.nextSibling.textContent='No image found.';};};">`;
             formHtml += `<span></span></div>`;

             formHtml += `
                <div>
                    <label for="art_title">Title</label>
                    <input type="text" id="art_title" name="art_title" value="${escapeHtml(data.title || 'Unknown Title')}">
                </div>
                <div>
                    <label for="art_artist_id">Artist<span class="required-star">*</span></label>
                    <select id="art_artist_id" name="art_artist_id" required>
                        <option value="">-- Select Artist --</option>
                        ${artists.map(artist =>
                            `<option value="${artist.artist_id}" ${artist.artist_id == data.artist_id ? 'selected' : ''}>
                                ${escapeHtml(artist.first_name + ' ' + artist.last_name)}
                            </option>`
                        ).join('')}
                        ${artists.length === 0 ? '<option value="" disabled>No artists available</option>' : ''}
                    </select>
                </div>
                 <div>
                    <label for="art_date_created">Date Created</label>
                    <input type="date" id="art_date_created" name="art_date_created" value="${escapeHtml(data.date_created || '')}">
                </div>
                 <div>
                    <label for="art_genre">Genre</label>
                    <input type="text" id="art_genre" name="art_genre" value="${escapeHtml(data.genre || 'Undefined')}">
                </div>
                 <div>
                    <label for="art_type">Type</label>
                    <input type="text" id="art_type" name="art_type" value="${escapeHtml(data.type || 'Undefined')}">
                </div>
                 <div>
                    <label for="art_image_edit">Replace Image (Optional, JPG/PNG, max 10MB)</label>
                    <input type="file" id="art_image_edit" name="art_image_edit" accept=".jpg, .jpeg, .png">
                </div>
            `;
        }

        formHtml += `
            <button type="submit">Save Changes</button>
            <button type="button" class="button-link cancel cancel-edit">Cancel</button>
        </form>`;

        editFormContainer.innerHTML = formHtml;
    }

    async function handleDelete(type, id) {
        if (!confirm(`Are you sure you want to delete this ${type} (ID: ${id})? This action cannot be undone.`)) {
            return;
        }

        showListLoading(true);

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('type', type);
            formData.append('id', id);

            const response = await fetch('edit_art.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showMessage(`${type} (ID: ${id}) deleted successfully.`, 'success');
                const listItem = listContainer.querySelector(`li[data-id="${id}"][data-type="${type}"]`);
                if (listItem) {
                    listItem.remove();
                    if (!listContainer.querySelector('li')) {
                         listContainer.innerHTML = `<p>No ${type} entries found.</p>`;
                    }
                } else {
                    loadList(type);
                }
            } else {
                 throw new Error(result.error || 'Deletion failed. Unknown error.');
            }

        } catch (error) {
            console.error('Error deleting item:', error);
            showMessage(`Error deleting: ${error.message}`, 'error');
        } finally {
            showListLoading(false);
        }
    }

    editTypeSelect.addEventListener('change', (event) => {
        const selectedType = event.target.value;
        loadList(selectedType);
    });

    document.body.addEventListener('click', (event) => {
        if (event.target.classList.contains('edit-button')) {
            const button = event.target;
            const type = button.dataset.type;
            const id = button.dataset.id;
            loadEditForm(type, id);
        }
        else if (event.target.classList.contains('delete-button')) {
             const button = event.target;
             const type = button.dataset.type;
             const id = button.dataset.id;
             handleDelete(type, id);
        }
        else if (event.target.classList.contains('cancel-edit')) {
             editFormContainer.classList.add('hidden');
             const currentListType = editTypeSelect.value;
             loadList(currentListType);
        }
    });

    function escapeHtml(unsafe) {
        if (unsafe === null || typeof unsafe === 'undefined') return '';
        return unsafe
             .toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    editTypeSelect.dispatchEvent(new Event('change'));

</script>
</body>
</html>