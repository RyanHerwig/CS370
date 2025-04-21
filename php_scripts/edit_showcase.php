<?php
session_start(); // Resume the existing session

// 1. Check using session variables set by YOUR login.php (userid and username)
// 2. Ensure the user is the 'admin' (adjust if your admin username is different)
if (!isset($_SESSION['userid']) || !isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    // 3. Redirect UP one level (../) to the correct login.html location
    header('Location: ../login.html');
    exit; // Stop script execution after redirect
}
// If the check passes, the script continues and renders the HTML below
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Edit Showcase - Art Gallery</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <!-- IMPORTANT: Fix CSS/JS paths to point UP one level -->
    <link rel="stylesheet" href="../assets/css/main.css" />
    <noscript><link rel="stylesheet" href="../assets/css/noscript.css" /></noscript>
    <style>
        /* Styles remain the same */
        html { scroll-behavior: smooth; }
        body { background: linear-gradient(115deg, white, rgb(210, 210, 210)); font-family: sans-serif; }
        #navbar { overflow: hidden; background-color: #333; padding: 10px 0; text-align: center; }
        #navbar a { display: inline-block; color: #f2f2f2; text-align: center; padding: 14px 16px; text-decoration: none; font-size: 17px; margin: 0 5px; border-radius: 5px; }
        #navbar a:hover { background-color: #ddd; color: black; }
        #navbar a.active { background-color: #555; color: white; }
        #navbar a#welcome-message { float: right; margin-right: 10px; }

        .edit-container { max-width: 900px; margin: 30px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .edit-slot { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; background-color: #f9f9f9; }
        .edit-slot h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .edit-slot label { display: block; margin-bottom: 5px; font-weight: bold; }
        .edit-slot select, .edit-slot textarea { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .edit-slot textarea { min-height: 80px; resize: vertical; }
        .edit-slot .current-selection { font-size: 0.9em; color: #555; margin-bottom: 10px; }
        .edit-slot .current-selection img { max-width: 100px; max-height: 75px; vertical-align: middle; margin-right: 10px; background-color: #eee; }
        #status-message { margin-top: 15px; padding: 10px; border-radius: 5px; text-align: center; display: none; /* Hidden by default */ }
        #status-message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        #status-message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .loader { border: 5px solid #f3f3f3; border-top: 5px solid #555; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 10px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        /* Add this CSS to the <style> block in edit_showcase.php */

        /* In the <style> block of edit_showcase.php */

        .button-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
        }

        .save-button {
            /* --- Add Flexbox properties to center the text INSIDE the button --- */
            display: inline-flex;    /* Make the button a flex container (inline-level) */
            align-items: center;     /* Vertically center the text node(s) inside */
            justify-content: center; /* Horizontally center the text node(s) inside */
            /* --- End of added properties --- */

            max-width: 300px;
            padding: 15px; /* Keep padding for spacing */
            background-color: rgb(181, 181, 181);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 13px;
            cursor: pointer;
            /* text-align: center; /* Not strictly needed anymore as justify-content handles it */
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .save-button:hover {
            background-color: rgb(123, 123, 123);
            transform: scale(1.05);
        }



    </style>
</head>
<body class="is-preload">
    <div id="navbar">
        <a href="../index.html">Home</a>
        <a href="../search.html">Search</a>
        <a href="../about.html">About</a>
    </div>
    <script>
        const panelButton = document.createElement('a');
        panelButton.id = 'panel-button';
        panelButton.href = "admin_panel.php";
        panelButton.textContent = 'Panel';
        navbar.appendChild(panelButton);
    </script>

    <div class="edit-container">
        <h2>Edit Showcase Content</h2>
        <p>Select artwork and enter custom descriptions for each section on the homepage.</p>
        <div id="loading-indicator" style="text-align: center;">
            <div class="loader"></div>
            <p>Loading configuration and artwork list...</p>
        </div>

        <form id="editShowcaseForm" style="display: none;">
            <!-- Form content remains the same -->
            <!-- Spotlights -->
            <h3>Spotlights</h3>
            <div class="edit-slot" data-slot-id="spotlight1">
                <h4>Spotlight 1</h4>
                <label for="spotlight1-art">Select Artwork:</label>
                <select name="spotlight1_art_id" id="spotlight1-art">
                    <option value="">-- None --</option>
                    <!-- Options will be populated by JS -->
                </select>
                <div class="current-selection" id="spotlight1-current"></div>
                <label for="spotlight1-desc">Custom Description:</label>
                <textarea name="spotlight1_desc" id="spotlight1-desc" placeholder="Enter a custom description for Spotlight 1..."></textarea>
            </div>
            <div class="edit-slot" data-slot-id="spotlight2">
                 <h4>Spotlight 2</h4>
                 <label for="spotlight2-art">Select Artwork:</label>
                 <select name="spotlight2_art_id" id="spotlight2-art">
                     <option value="">-- None --</option>
                 </select>
                 <div class="current-selection" id="spotlight2-current"></div>
                 <label for="spotlight2-desc">Custom Description:</label>
                 <textarea name="spotlight2_desc" id="spotlight2-desc" placeholder="Enter a custom description for Spotlight 2..."></textarea>
            </div>
             <div class="edit-slot" data-slot-id="spotlight3">
                 <h4>Spotlight 3</h4>
                 <label for="spotlight3-art">Select Artwork:</label>
                 <select name="spotlight3_art_id" id="spotlight3-art">
                     <option value="">-- None --</option>
                 </select>
                 <div class="current-selection" id="spotlight3-current"></div>
                 <label for="spotlight3-desc">Custom Description:</label>
                 <textarea name="spotlight3_desc" id="spotlight3-desc" placeholder="Enter a custom description for Spotlight 3..."></textarea>
            </div>

            <!-- Gallery -->
            <h3>Gallery Items</h3>
            <div class="edit-slot" data-slot-id="gallery1">
                <h4>Gallery Item 1</h4>
                <label for="gallery1-art">Select Artwork:</label>
                <select name="gallery1_art_id" id="gallery1-art">
                    <option value="">-- None --</option>
                </select>
                <div class="current-selection" id="gallery1-current"></div>
                <label for="gallery1-desc">Custom Description:</label>
                <textarea name="gallery1_desc" id="gallery1-desc" placeholder="Enter a custom description for Gallery Item 1..."></textarea>
            </div>
            <!-- Repeat for gallery2, gallery3, gallery4, gallery5 -->
             <div class="edit-slot" data-slot-id="gallery2">
                 <h4>Gallery Item 2</h4>
                 <label for="gallery2-art">Select Artwork:</label>
                 <select name="gallery2_art_id" id="gallery2-art"><option value="">-- None --</option></select>
                 <div class="current-selection" id="gallery2-current"></div>
                 <label for="gallery2-desc">Custom Description:</label>
                 <textarea name="gallery2_desc" id="gallery2-desc" placeholder="Enter a custom description for Gallery Item 2..."></textarea>
            </div>
             <div class="edit-slot" data-slot-id="gallery3">
                 <h4>Gallery Item 3</h4>
                 <label for="gallery3-art">Select Artwork:</label>
                 <select name="gallery3_art_id" id="gallery3-art"><option value="">-- None --</option></select>
                 <div class="current-selection" id="gallery3-current"></div>
                 <label for="gallery3-desc">Custom Description:</label>
                 <textarea name="gallery3_desc" id="gallery3-desc" placeholder="Enter a custom description for Gallery Item 3..."></textarea>
            </div>
             <div class="edit-slot" data-slot-id="gallery4">
                 <h4>Gallery Item 4</h4>
                 <label for="gallery4-art">Select Artwork:</label>
                 <select name="gallery4_art_id" id="gallery4-art"><option value="">-- None --</option></select>
                 <div class="current-selection" id="gallery4-current"></div>
                 <label for="gallery4-desc">Custom Description:</label>
                 <textarea name="gallery4_desc" id="gallery4-desc" placeholder="Enter a custom description for Gallery Item 4..."></textarea>
            </div>
             <div class="edit-slot" data-slot-id="gallery5">
                 <h4>Gallery Item 5</h4>
                 <label for="gallery5-art">Select Artwork:</label>
                 <select name="gallery5_art_id" id="gallery5-art"><option value="">-- None --</option></select>
                 <div class="current-selection" id="gallery5-current"></div>
                 <label for="gallery5-desc">Custom Description:</label>
                 <textarea name="gallery5_desc" id="gallery5-desc" placeholder="Enter a custom description for Gallery Item 5..."></textarea>
            </div>

            <button type="submit" class="save-button">Save Showcase Configuration</button>
            <div id="status-message"></div>
        </form>
    </div>

    <script>
        // Javascript remains the same, BUT the fetch paths need fixing

        let allArtworksData = []; // Store artwork data globally for previews

        async function loadEditPageData() {
            const loadingIndicator = document.getElementById('loading-indicator');
            const form = document.getElementById('editShowcaseForm');
            const statusMessage = document.getElementById('status-message');

            try {
                // Fetch both artwork list and current config concurrently
                // IMPORTANT: Fix fetch paths - they are relative to edit_showcase.php now
                const [artworksResponse, configResponse] = await Promise.all([
                    fetch('get_all_artworks.php'), // No 'php_scripts/' needed
                    fetch('get_showcase_data.php') // No 'php_scripts/' needed
                ]);

                // Rest of the function remains the same...
                if (!artworksResponse.ok) throw new Error(`Failed to load artworks: ${artworksResponse.statusText}`);
                if (!configResponse.ok) throw new Error(`Failed to load config: ${configResponse.statusText}`);

                allArtworksData = await artworksResponse.json();
                const currentConfig = await configResponse.json();

                if (allArtworksData.error) throw new Error(`Artwork Error: ${allArtworksData.error}`);
                if (currentConfig.error) throw new Error(`Config Error: ${currentConfig.error}`);

                populateArtworkSelectors(allArtworksData);
                populateFormFields(currentConfig);

                loadingIndicator.style.display = 'none';
                form.style.display = 'block';

            } catch (error) {
                console.error("Error loading edit page data:", error);
                loadingIndicator.innerHTML = `<p style="color: red;">Error loading data: ${error.message}</p>`;
                statusMessage.textContent = `Error loading data: ${error.message}`;
                statusMessage.className = 'error';
                statusMessage.style.display = 'block';
            } finally {
                 document.body.classList.remove('is-preload');
            }
        }

        function populateArtworkSelectors(artworks) {
            const selects = document.querySelectorAll('select[name$="_art_id"]');
            selects.forEach(select => {
                while (select.options.length > 1) {
                    select.remove(1);
                }
                artworks.forEach(art => {
                    const option = document.createElement('option');
                    option.value = art.art_id;
                    option.textContent = `${art.title} (${art.artist_name || 'Unknown Artist'})`;
                    // IMPORTANT: Fix image path in preview data
                    option.dataset.imageUrl = `../images/${art.art_id}.jpg`; // Add ../
                    option.dataset.title = art.title;
                    select.appendChild(option);
                });
            });
        }

        function populateFormFields(config) {
            const slots = document.querySelectorAll('.edit-slot');
            slots.forEach(slot => {
                const slotId = slot.dataset.slotId;
                const slotConfig = config[slotId];

                if (slotConfig) {
                    const select = slot.querySelector('select');
                    const textarea = slot.querySelector('textarea');

                    select.value = slotConfig.art_id || "";
                    textarea.value = slotConfig.custom_description || "";

                    updatePreview({ target: select });
                }
            });
        }

        function updatePreview(event) {
            const select = event.target;
            const slotId = select.closest('.edit-slot').dataset.slotId;
            const previewDiv = document.getElementById(`${slotId}-current`);
            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption && selectedOption.value) {
                const artId = selectedOption.value;
                const art = allArtworksData.find(a => a.art_id == artId);
                if (art) {
                    // IMPORTANT: Use the corrected image path from dataset
                    const imageUrl = selectedOption.dataset.imageUrl || `../images/${art.art_id}.jpg`; // Fallback just in case
                    previewDiv.innerHTML = `
                        Currently selected:
                        <img src="${imageUrl}" alt="${art.title}" onerror="this.style.display='none'; this.nextSibling.textContent=' (Image not found)';">
                        <strong>${art.title}</strong>
                    `;
                } else {
                     previewDiv.innerHTML = `Currently selected: Artwork ID ${artId} (Details not found)`;
                }
            } else {
                previewDiv.innerHTML = 'Currently selected: <em>None</em>';
            }
        }


        async function handleFormSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const statusMessage = document.getElementById('status-message');
            const saveButton = form.querySelector('.save-button');

            saveButton.disabled = true;
            saveButton.textContent = 'Saving...';
            statusMessage.style.display = 'none';

            try {
                // IMPORTANT: Fix fetch path
                const response = await fetch('save_showcase_data.php', { // No 'php_scripts/' needed
                    method: 'POST',
                    body: formData
                });

                // Rest of the function remains the same...
                if (!response.ok) {
                     let errorMsg = `HTTP error! Status: ${response.status}`;
                     try {
                         const errData = await response.json();
                         if (errData && errData.error) {
                             errorMsg = errData.error;
                         }
                     } catch (e) { /* Ignore parsing error, use default message */ }
                     throw new Error(errorMsg);
                }

                const result = await response.json();

                if (result.success) {
                    statusMessage.textContent = 'Showcase configuration saved successfully!';
                    statusMessage.className = 'success';
                } else {
                    throw new Error(result.error || 'An unknown error occurred.');
                }

            } catch (error) {
                console.error('Error saving showcase data:', error);
                statusMessage.textContent = `Error: ${error.message}`;
                statusMessage.className = 'error';
            } finally {
                statusMessage.style.display = 'block';
                saveButton.disabled = false;
                saveButton.textContent = 'Save Showcase Configuration';
            }
        }

         // No changes needed for this part
         document.addEventListener('DOMContentLoaded', function() {
            loadEditPageData();

            const form = document.getElementById('editShowcaseForm');
            form.addEventListener('submit', handleFormSubmit);

            const selects = form.querySelectorAll('select[name$="_art_id"]');
            selects.forEach(select => {
                select.addEventListener('change', updatePreview);
            });
        });

         window.addEventListener('load', () => {
             // Don't remove is-preload until data is loaded in loadEditPageData
         });

    </script>
    <!-- IMPORTANT: Fix script path -->
    <script src="../script.js"></script> <!-- Add ../ -->

</body>
</html>