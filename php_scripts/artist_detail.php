<?php
session_start();
require 'db.php';

$artist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$artist = null;
$artworks = [];
$error = null;
$portraitUrl = 'https://placehold.co/400x400/EEE/31343C?text=No+Portrait';

if ($artist_id > 0 && isset($pdo)) {
    try {
        $sql_artist = "SELECT artist_id, first_name, last_name, dob, description
                       FROM artist
                       WHERE artist_id = :artist_id";
        $stmt_artist = $pdo->prepare($sql_artist);
        $stmt_artist->execute([':artist_id' => $artist_id]);
        $artist = $stmt_artist->fetch(PDO::FETCH_ASSOC);

        if ($artist) {
            $baseImagePath = '../images/artist-' . $artist['artist_id'];
            $extensions = ['jpg', 'png', 'jpeg'];
            foreach ($extensions as $ext) {
                $potentialPath = $baseImagePath . '.' . $ext;
                $serverFilePath = dirname(__DIR__) . '/images/artist-' . $artist['artist_id'] . '.' . $ext;
                if (file_exists($serverFilePath)) {
                    $portraitUrl = $potentialPath;
                    break;
                }
            }

            $sql_artworks = "SELECT art_id, title
                             FROM art
                             WHERE artist_id = :artist_id
                             ORDER BY title";
            $stmt_artworks = $pdo->prepare($sql_artworks);
            $stmt_artworks->execute([':artist_id' => $artist_id]);
            $artworks = $stmt_artworks->fetchAll(PDO::FETCH_ASSOC);

        } else {
            $error = "Artist not found.";
        }

    } catch (Exception $e) {
        error_log("Error fetching artist detail or artworks: " . $e->getMessage());
        $error = "Could not load artist details or artworks.";
    }
} elseif ($artist_id <= 0) {
    $error = "Invalid artist ID specified.";
} elseif (!isset($pdo)) {
    $error = "Database connection is unavailable.";
    error_log("PDO object not available in artist_detail.php");
}
?>
<!DOCTYPE HTML>
<html>
    <head>
        <title>Artist Details - Art Gallery</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
        <link rel="stylesheet" href="../assets/css/main.css" />
        <noscript><link rel="stylesheet" href="../assets/css/noscript.css" /></noscript>
        <style>
            body { background: linear-gradient(115deg, white, rgb(210, 210, 210)); padding: 0; margin: 0; font-family: sans-serif;}
            .detail-container { max-width: 800px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .detail-container img.artist-portrait {
                max-width: 250px;
                height: auto;
                display: block;
                margin: 0 auto 25px auto;
                background-color: #eee;
                border: 1px solid #ccc;
                border-radius: 50%;
                object-fit: cover;
                aspect-ratio: 1 / 1;
            }
            .detail-container h1 { text-align: center; margin-bottom: 10px; }
            .detail-container .dob { text-align: center; color: #666; margin-bottom: 25px; font-size: 0.9em; }
            .detail-container .description { margin-top: 20px; line-height: 1.7; text-align: justify; }

            #navbar { overflow: hidden; background-color: #333; padding: 10px 0; text-align: center; width: 100%; margin-bottom: 20px; }
            #navbar a { display: inline-block; color: #f2f2f2; text-align: center; padding: 14px 16px; text-decoration: none; font-size: 17px; margin: 0 5px; border-radius: 5px; }
            #navbar a:hover { background-color: #ddd; color: black; }
            #navbar a.active { background-color: #555; color: white; }
            #navbar a#logout-link-detail { float: right; margin-right: 10px; cursor: pointer; }
            #navbar a#panel-button { float: left; margin-left: 10px; }

            .artist-artworks-section {
                margin-top: 40px;
                padding-top: 30px;
                border-top: 1px solid #eee;
            }
            .artist-artworks-section h2 {
                text-align: center;
                margin-bottom: 25px;
                color: #333;
            }
            .results-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 20px;
            }
            .result-item {
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 5px;
                background-color: #fff;
                display: flex;
                flex-direction: column;
                text-align: left;
            }
            .result-item img {
                max-width: 100%;
                height: 150px;
                object-fit: cover;
                display: block;
                margin-bottom: 10px;
                border-radius: 4px;
                background-color: #eee;
            }
            .result-item h4 {
                margin-top: 0;
                margin-bottom: 10px;
                color: #444;
                font-size: 1.0em;
                line-height: 1.3;
            }
            .result-item .button {
                margin-top: auto;
                align-self: flex-start;
                padding: 8px 12px;
                font-size: 0.9em;
            }
            .no-artworks-message {
                text-align: center;
                color: #777;
                font-style: italic;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <!-- Navbar -->
        <div id="navbar">
            <a href="../index.html">Home</a>
            <a href="../search.html">Search</a>
            <a href="../about.html">About</a>
        </div>
        <script>"../script.js"</script>

        <div class="detail-container">
            <?php if ($artist): ?>
                <img src="<?php echo $portraitUrl; ?>" alt="Portrait of <?php echo htmlspecialchars($artist['first_name'] . ' ' . $artist['last_name']); ?>" class="artist-portrait"
                    onerror="this.onerror=null; this.src='https://placehold.co/400x400/EEE/31343C?text=No+Portrait';">

                <h1><?php echo htmlspecialchars($artist['first_name'] . ' ' . $artist['last_name']); ?></h1>
                <?php if (!empty($artist['dob'])): ?>
                    <p class="dob">Born: <?php echo htmlspecialchars(date("F j, Y", strtotime($artist['dob']))); ?></p>
                <?php endif; ?>

                <?php if (!empty($artist['description'])): ?>
                    <div class="description">
                        <h3>About the Artist</h3>
                        <p><?php echo nl2br(htmlspecialchars($artist['description'])); ?></p>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #777;"><em>No description available for this artist.</em></p>
                <?php endif; ?>

                <!-- Artworks Section -->
                <section class="artist-artworks-section">
                    <h2>Artworks by <?php echo htmlspecialchars($artist['first_name'] . ' ' . $artist['last_name']); ?></h2>
                    <?php if (!empty($artworks)): ?>
                        <div class="results-grid">
                            <?php foreach ($artworks as $artwork): ?>
                                <div class="result-item">
                                    <?php
                                        $artworkImageUrl = '../images/' . $artwork['art_id'] . '.jpg';
                                        $artworkImagePng = '../images/' . $artwork['art_id'] . '.png';
                                        $artworkImageJpeg = '../images/' . $artwork['art_id'] . '.jpeg';
                                        $placeholderUrl = 'https://placehold.co/300x200/EEE/31343C?text=Not+Found';
                                    ?>
                                    <img src="<?php echo $artworkImageUrl; ?>"
                                        alt="<?php echo htmlspecialchars($artwork['title']); ?>"
                                        onerror="this.onerror=null; this.src='<?php echo $artworkImagePng; ?>'; this.onerror=()=>{this.onerror=null; this.src='<?php echo $artworkImageJpeg; ?>'; this.onerror=()=>{this.onerror=null; this.src='<?php echo $placeholderUrl; ?>';};};">
                                    <h4><?php echo htmlspecialchars($artwork['title']); ?></h4>
                                    <a href="artwork_detail.php?id=<?php echo $artwork['art_id']; ?>" class="button small">View Details</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-artworks-message">No artworks found for this artist in the gallery.</p>
                    <?php endif; ?>
                </section>

            <?php elseif ($error): ?>
                <h2>Error</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php else: ?>
                <h2>Artist Not Found</h2>
                <p>The requested artist (ID: <?php echo htmlspecialchars($artist_id); ?>) could not be found.</p>
            <?php endif; ?>
            <hr style="margin: 30px 0;">
            <p style="text-align: center;"><a href="javascript:history.back()" class="button small">Go Back</a></p>
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
                console.warn("Global logout function not found. Logout link might not work as expected via JS.");
            }
        </script>

    </body>
</html>