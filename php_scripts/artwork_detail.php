<?php

session_start();
require 'db.php';

$art_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$artwork = null;
$error = null;
$imageUrl = 'https://placehold.co/600x400/EEE/31343C?text=Image+Not+Found';

if ($art_id > 0 && isset($pdo)) {
    try {
        $sql = "SELECT a.*, CONCAT(ar.first_name, ' ', ar.last_name) AS artist_name
                FROM art a
                LEFT JOIN artist ar ON a.artist_id = ar.artist_id
                WHERE a.art_id = :art_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':art_id' => $art_id]);
        $artwork = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($artwork) {
            $baseImagePath = '../images/' . $artwork['art_id'];
            $jpgPath = $baseImagePath . '.jpg';
            $pngPath = $baseImagePath . '.png';
            if (file_exists($jpgPath)) {
                $imageUrl = $jpgPath;
            } elseif (file_exists($pngPath)) {
                $imageUrl = $pngPath;
            }
        }

    } catch (Exception $e) {
        error_log("Error fetching artwork detail: " . $e->getMessage());
        $error = "Could not load artwork details.";
    }
} elseif ($art_id <= 0) {
    $error = "Invalid artwork ID specified.";
} elseif (!isset($pdo)) {
    $error = "Database connection is unavailable.";
    error_log("PDO object not available in artwork_detail.php");
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Artwork Details - Art Gallery</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="../assets/css/main.css" />
    <noscript><link rel="stylesheet" href="../assets/css/noscript.css" /></noscript>
    <style>
        body { background: linear-gradient(115deg, white, rgb(210, 210, 210)); padding: 20px; font-family: sans-serif;}
        .detail-container { max-width: 800px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .detail-container img { max-width: 100%; height: auto; display: block; margin: 0 auto 20px auto; background-color: #eee; border: 1px solid #ccc; }
        #navbar { overflow: hidden; background-color: #333; padding: 10px 0; text-align: center; margin-bottom: 20px; }
        #navbar a { display: inline-block; color: #f2f2f2; text-align: center; padding: 14px 16px; text-decoration: none; font-size: 17px; margin: 0 5px; border-radius: 5px; }
        #navbar a:hover { background-color: #ddd; color: black; }
        #navbar a.active { background-color: #555; color: white; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div id="navbar">
        <a href="../index.html">Home</a>
        <a href="../search.html">Search</a>
        <a href="../about.html">About</a>
        <?php if (isset($_SESSION['userid'])): ?>
        <?php else: ?>
            <a href="../login.html" style="float: right; margin-right: 10px;">Login</a>
        <?php endif; ?>
    </div>

    <div class="detail-container">
        <?php if ($artwork): ?>
            <h1><?php echo htmlspecialchars($artwork['title']); ?></h1>
            <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($artwork['title']); ?>"
                 onerror="this.onerror=null; this.src='https://placehold.co/600x400/EEE/31343C?text=Image+Not+Found';">

            <p><strong>Artist:</strong> <?php echo htmlspecialchars($artwork['artist_name'] ?? 'Unknown Artist'); ?></p>
            <p><strong>Date Created:</strong> <?php echo htmlspecialchars($artwork['date_created'] ?? 'N/A'); ?></p>
            <p><strong>Genre:</strong> <?php echo htmlspecialchars($artwork['genre'] ?? 'N/A'); ?></p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($artwork['type'] ?? 'N/A'); ?></p>

        <?php elseif ($error): ?>
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php else: ?>
            <h2>Artwork Not Found</h2>
            <p>The requested artwork (ID: <?php echo htmlspecialchars($art_id); ?>) could not be found.</p>
        <?php endif; ?>
        <p><a href="../search.html" class="button small">Back to Search</a></p>
    </div>

    <script src="../script.js"></script>
    <script>
        const logoutLinkDetail = document.getElementById('logout-link-detail');
        if (logoutLinkDetail && typeof logout === 'function') {
             logoutLinkDetail.addEventListener('click', function(event) {
                 event.preventDefault();
                 logout();
             });
        } else if (logoutLinkDetail) {
            console.error("Logout function not found in script.js, cannot attach listener.");
        }
    </script>

</body>
</html>
