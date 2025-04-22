<?php
session_start();
require_once 'db.php';

$message = '';
$message_type = '';
$artists = [];

try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT artist_id, first_name, last_name FROM artist ORDER BY last_name, first_name");
        $artists = $stmt->fetchAll(PDO::FETCH_ASSOC); // Use FETCH_ASSOC for consistency
    } else {
        throw new Exception("Database connection is not available.");
    }
} catch (PDOException $e) {
    $message = "Error fetching artists: " . $e->getMessage();
    $message_type = 'error';
    error_log("PDOException in add_art.php (fetching artists): " . $e->getMessage());
} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
    $message_type = 'error';
    error_log("Exception in add_art.php (fetching artists): " . $e->getMessage());
}

define('UPLOAD_DIR', dirname(__DIR__) . '/images/');
define('UPLOAD_URL_PATH', '../images/');

// Check/Create Upload Directory only if no error message yet
if (empty($message)) {
    if (!is_dir(UPLOAD_DIR)) {
        // Attempt to create directory recursively
        if (!mkdir(UPLOAD_DIR, 0775, true)) { // Use 0775 for better security than 0777
            $message = "Error: Failed to create upload directory: " . UPLOAD_DIR;
            $message_type = 'error';
            error_log($message); // Log the error
        }
    } elseif (!is_writable(UPLOAD_DIR)) {
         $message = "Error: Upload directory is not writable: " . UPLOAD_DIR;
         $message_type = 'error';
         error_log($message); // Log the error
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_type'])) {
    $add_type = $_POST['add_type'];

    // Ensure DB connection exists before proceeding with POST actions
    if (!isset($pdo)) {
        $message = "Database connection error. Cannot process form.";
        $message_type = 'error';
    }
    // Proceed only if no critical errors so far (like directory issues or DB connection)
    elseif (empty($message)) {
        try {
            if ($add_type === 'artist') {
                $first_name = trim($_POST['artist_first_name'] ?? '');
                $last_name = trim($_POST['artist_last_name'] ?? '');
                $dob = !empty($_POST['artist_dob']) ? $_POST['artist_dob'] : null;
                $description = isset($_POST['artist_description']) ? trim($_POST['artist_description']) : null;
                if ($description === '') $description = null;

                if (empty($first_name) || empty($last_name)) {
                    throw new Exception("First name and last name are required for an artist.");
                }

                $pdo->beginTransaction();

                $sql = "INSERT INTO artist (first_name, last_name, dob, description) VALUES (:first_name, :last_name, :dob, :description)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
                $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
                $stmt->bindParam(':dob', $dob);
                $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                $stmt->execute();

                $last_artist_id = $pdo->lastInsertId();

                $portrait_message = '';
                if ($last_artist_id && isset($_FILES['artist_portrait']) && $_FILES['artist_portrait']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp_path = $_FILES['artist_portrait']['tmp_name'];
                    $file_name = $_FILES['artist_portrait']['name'];
                    $file_size = $_FILES['artist_portrait']['size'];
                    $file_ext_lower = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    $allowed_extensions = ['jpg', 'jpeg', 'png'];
                    $allowed_mime_types = ['image/jpeg', 'image/png'];
                    $max_file_size = 150 * 1024 * 1024; // 150MB

                    // Check MIME type properly
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $file_tmp_path);
                    finfo_close($finfo);

                    if (in_array($file_ext_lower, $allowed_extensions) && in_array($mime_type, $allowed_mime_types)) {
                        if ($file_size <= $max_file_size) {
                            $new_filename = 'artist-' . $last_artist_id . '.' . $file_ext_lower;
                            $destination_path = UPLOAD_DIR . $new_filename;

                            // Delete existing portraits first
                            foreach ($allowed_extensions as $ext_to_del) {
                                $old_file = UPLOAD_DIR . 'artist-' . $last_artist_id . '.' . $ext_to_del;
                                if (file_exists($old_file)) {
                                    @unlink($old_file); // Use @ to suppress warnings if file is not deletable
                                }
                            }

                            if (move_uploaded_file($file_tmp_path, $destination_path)) {
                                $portrait_message = " Portrait uploaded successfully.";
                            } else {
                                error_log("Warning: Failed to move artist portrait for ID $last_artist_id to $destination_path");
                                $portrait_message = " <span style='color:orange;'>Warning: Could not save portrait image.</span>";
                            }
                        } else {
                            $portrait_message = " <span style='color:orange;'>Warning: Portrait file size exceeds 150MB limit.</span>";
                        }
                    } else {
                        $portrait_message = " <span style='color:orange;'>Warning: Invalid portrait file type (JPG, PNG only).</span>";
                    }
                } elseif (isset($_FILES['artist_portrait']) && $_FILES['artist_portrait']['error'] !== UPLOAD_ERR_NO_FILE) {
                     $portrait_message = " <span style='color:orange;'>Warning: Error uploading portrait (Code: " . $_FILES['artist_portrait']['error'] . ").</span>";
                }


                $pdo->commit();

                $message = "Artist '" . htmlspecialchars($first_name . ' ' . $last_name) . "' added successfully!" . $portrait_message;
                $message_type = 'success';

                // Refresh artist list after successful add
                $stmt = $pdo->query("SELECT artist_id, first_name, last_name FROM artist ORDER BY last_name, first_name");
                $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

            }
            elseif ($add_type === 'art') {
                $title = trim($_POST['art_title'] ?? 'Unknown Title');
                $artist_id = filter_input(INPUT_POST, 'art_artist_id', FILTER_VALIDATE_INT);
                $date_created = !empty($_POST['art_date_created']) ? $_POST['art_date_created'] : null;
                $genre = trim($_POST['art_genre'] ?? 'Undefined');
                $type = trim($_POST['art_type'] ?? 'Undefined');

                if (empty($title)) $title = 'Unknown Title';
                if ($artist_id === false || $artist_id <= 0) {
                    throw new Exception("A valid artist must be selected.");
                }
                if (empty($genre)) $genre = 'Undefined';
                if (empty($type)) $type = 'Undefined';

                $uploaded_file_path = null;
                $file_tmp_path = null;
                $file_ext_lower = null;

                if (isset($_FILES['art_image']) && $_FILES['art_image']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp_path = $_FILES['art_image']['tmp_name'];
                    $file_name = $_FILES['art_image']['name'];
                    $file_size = $_FILES['art_image']['size'];
                    $file_ext_lower = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    $allowed_extensions = ['jpg', 'jpeg', 'png'];
                    $allowed_mime_types = ['image/jpeg', 'image/png'];
                    $max_file_size = 150 * 1024 * 1024; // 150MB limit

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $file_tmp_path);
                    finfo_close($finfo);

                    if (!in_array($file_ext_lower, $allowed_extensions) || !in_array($mime_type, $allowed_mime_types)) {
                        throw new Exception("Invalid file type. Only JPG, JPEG, and PNG are allowed.");
                    }

                    if ($file_size > $max_file_size) {
                        throw new Exception("File size exceeds the 150MB limit.");
                    }

                } else if (isset($_FILES['art_image']) && $_FILES['art_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    throw new Exception("Error uploading file. Code: " . $_FILES['art_image']['error']);
                } else {
                    throw new Exception("Art image file is required.");
                }

                $pdo->beginTransaction();

                $sql = "INSERT INTO art (title, artist_id, date_created, genre, type)
                        VALUES (:title, :artist_id, :date_created, :genre, :type)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                $stmt->bindParam(':artist_id', $artist_id, PDO::PARAM_INT);
                $stmt->bindParam(':date_created', $date_created);
                $stmt->bindParam(':genre', $genre, PDO::PARAM_STR);
                $stmt->bindParam(':type', $type, PDO::PARAM_STR);
                $stmt->execute();

                $last_art_id = $pdo->lastInsertId();

                if ($last_art_id && isset($file_tmp_path) && $file_ext_lower) {
                    $new_filename = $last_art_id . '.' . $file_ext_lower;
                    $destination_path = UPLOAD_DIR . $new_filename;

                    // Delete existing art images first
                    foreach ($allowed_extensions as $ext_to_del) {
                        $old_file = UPLOAD_DIR . $last_art_id . '.' . $ext_to_del;
                        if (file_exists($old_file)) {
                            @unlink($old_file);
                        }
                    }

                    if (move_uploaded_file($file_tmp_path, $destination_path)) {
                        $pdo->commit();
                        $message = "Art piece '" . htmlspecialchars($title) . "' added successfully with ID " . $last_art_id . "!";
                        $message_type = 'success';
                    } else {
                        $pdo->rollBack();
                        throw new Exception("Failed to move uploaded file to destination: " . $destination_path);
                    }
                } else {
                    $pdo->rollBack();
                    $error_detail = !$last_art_id ? "Could not get last insert ID." : "File was not uploaded correctly or extension missing.";
                    throw new Exception("Failed to finalize art addition. " . $error_detail);
                }
            } else {
                throw new Exception("Invalid submission type.");
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Database Error: " . $e->getMessage();
            $message_type = 'error';
            error_log("PDOException in add_art.php (POST): " . $e->getMessage());
        } catch (Exception $e) {
             if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Error: " . $e->getMessage();
            $message_type = 'error';
            error_log("Exception in add_art.php (POST): " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Art/Artist</title>
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
        #navbar { overflow: hidden; background-color: #333; padding: 10px 0; text-align: center; width: 100%; margin-bottom: 20px; }
        #navbar a { display: inline-block; color: #f2f2f2; text-align: center; padding: 14px 16px; text-decoration: none; font-size: 17px; margin: 0 5px; border-radius: 5px; }
        #navbar a:hover { background-color: #ddd; color: black; }
        #navbar a.active { background-color: #555; color: white; }

        .container {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 600px;
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
        button[type="submit"] {
            background-color: #5cb85c;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            display: block;
            width: 100%;
            margin-top: 10px;
        }
        button[type="submit"]:hover {
            background-color: #4cae4c;
            justify-content: center;
            align-items: center;
        }
        button[type="submit"]:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .hidden {
            display: none;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
        }
        .message.success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .message.error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        .required-star {
            color: red;
            margin-left: 2px;
        }
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
    <h1>Add New Entry</h1>

    <?php if (!empty($message)): ?>
        <div class="message <?= htmlspecialchars($message_type) ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form action="add_art.php" method="post" enctype="multipart/form-data" id="addForm">

        <div>
            <label for="add_type">What are you adding?<span class="required-star">*</span></label>
            <select name="add_type" id="add_type" required>
                <option value="">-- Select Type --</option>
                <option value="art">Add Art</option>
                <option value="artist">Add Artist</option>
            </select>
        </div>
        <div id="artist_fields" class="hidden">
            <h2>Add New Artist</h2>
            <div>
                <label for="artist_first_name">First Name<span class="required-star">*</span></label>
                <input type="text" id="artist_first_name" name="artist_first_name">
            </div>
            <div>
                <label for="artist_last_name">Last Name<span class="required-star">*</span></label>
                <input type="text" id="artist_last_name" name="artist_last_name">
            </div>
            <div>
                <label for="artist_dob">Date of Birth</label>
                <input type="date" id="artist_dob" name="artist_dob">
            </div>
            <div>
                <label for="artist_description">Description</label>
                <textarea id="artist_description" name="artist_description" rows="4" placeholder="Enter a brief description or notes about the artist..."></textarea>
            </div>
            <div>
                <label for="artist_portrait">Artist Portrait (Optional, JPG/PNG)</label>
                <input type="file" id="artist_portrait" name="artist_portrait" accept=".jpg, .jpeg, .png">
            </div>
        </div>
        <div id="art_fields" class="hidden">
            <h2>Add New Art Piece</h2>
            <div>
                <label for="art_title">Title</label>
                <input type="text" id="art_title" name="art_title" placeholder="Defaults to 'Unknown Title'">
            </div>
            <div>
                <label for="art_artist_id">Artist<span class="required-star">*</span></label>
                <select id="art_artist_id" name="art_artist_id">
                    <option value="">-- Select Artist --</option>
                    <?php if (!empty($artists)): ?>
                        <?php foreach ($artists as $artist): ?>
                            <option value="<?= htmlspecialchars($artist['artist_id']) ?>">
                                <?= htmlspecialchars($artist['first_name'] . ' ' . $artist['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>No artists found. Add an artist first.</option>
                    <?php endif; ?>
                </select>
            </div>
             <div>
                <label for="art_date_created">Date Created</label>
                <input type="date" id="art_date_created" name="art_date_created">
            </div>
             <div>
                <label for="art_genre">Genre</label>
                <input type="text" id="art_genre" name="art_genre" placeholder="Defaults to 'Undefined'">
            </div>
             <div>
                <label for="art_type">Type (e.g., Painting, Sculpture)</label>
                <input type="text" id="art_type" name="art_type" placeholder="Defaults to 'Undefined'">
            </div>
             <div>
                <label for="art_image">Art Image (JPG, PNG only, max 150MB)<span class="required-star">*</span></label>
                <input type="file" id="art_image" name="art_image" accept=".jpg, .jpeg, .png">
            </div>
        </div>

        <button type="submit" id="submitButton" class="hidden">Add Entry</button>

    </form>
</div>

<script>
    const addTypeSelect = document.getElementById('add_type');
    const artistFields = document.getElementById('artist_fields');
    const artFields = document.getElementById('art_fields');
    const submitButton = document.getElementById('submitButton');
    const form = document.getElementById('addForm');
    const artArtistSelect = document.getElementById('art_artist_id');

    function updateRequiredAttributes() {
        const isArtistVisible = !artistFields.classList.contains('hidden');
        const isArtVisible = !artFields.classList.contains('hidden');

        document.getElementById('artist_first_name').required = isArtistVisible;
        document.getElementById('artist_last_name').required = isArtistVisible;

        artArtistSelect.required = isArtVisible;
        document.getElementById('art_image').required = isArtVisible;
    }

    function handleTypeChange() {
        const selectedValue = addTypeSelect.value;

        artistFields.classList.add('hidden');
        artFields.classList.add('hidden');
        submitButton.classList.add('hidden');
        submitButton.disabled = true;
        submitButton.style.backgroundColor = '#ccc';

        if (selectedValue === 'artist') {
            artistFields.classList.remove('hidden');
            submitButton.classList.remove('hidden');
            submitButton.disabled = false;
            submitButton.style.backgroundColor = '';
        } else if (selectedValue === 'art') {
            artFields.classList.remove('hidden');
            submitButton.classList.remove('hidden');

            if (artArtistSelect.options.length <= 1 && artArtistSelect.options[0].value === "") {
                 // Check if only the placeholder exists
                 alert('Please add an artist before adding art.');
                 // Keep button disabled (already set)
            } else {
                 submitButton.disabled = false;
                 submitButton.style.backgroundColor = '';
            }
        }

        updateRequiredAttributes();
    }

    addTypeSelect.addEventListener('change', handleTypeChange);

    // Initial setup on page load
    handleTypeChange();

</script>

</body>
</html>