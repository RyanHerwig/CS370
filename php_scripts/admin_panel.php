<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: ../index.html"); // Redirect to the home page
    exit;
}

// The rest of your admin panel code
?>
<!DOCTYPE HTML>
<html>

<head>
    <title>Admin Panel - Art Gallery</title>
    <meta charset="utf-8" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="../assets/css/main.css" />
    <noscript>
        <link rel="stylesheet" href="../assets/css/noscript.css" />
    </noscript>
    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            /* Simple gradient background */
            background: linear-gradient(115deg, white, rgb(210, 210, 210));
            font-family: sans-serif;
            /* Add a default font */
        }

        /* Basic Navbar Styling (can be enhanced in main.css) */
        #navbar {
            overflow: hidden;
            background-color: #333;
            /* Dark background for navbar */
            padding: 10px 0;
            text-align: center;
        }

        #navbar a {
            display: inline-block;
            /* Display links side-by-side */
            color: #f2f2f2;
            text-align: center;
            padding: 14px 16px;
            text-decoration: none;
            font-size: 17px;
            margin: 0 5px;
            /* Add some space between links */
            border-radius: 5px;
            /* Rounded corners for links */
        }

        #navbar a:hover {
            background-color: #ddd;
            color: black;
        }

        #navbar a.active {
            /* Style for the active/current link */
            background-color: #555;
            color: white;
        }

        #navbar a#welcome-message {
            float: right;
            /* Align the welcome message to the right */
            margin-right: 10px;
            /* Add some space between the message and the edge */
        }

        #navbar a#panel-button {
            float: left;
            /* Align the panel button to the left */
            margin-left: 10px;
            /* Add some space between the button and the edge */
        }

        /* Style for the panel button */
        #panel-button:hover {
            transform: scale(1.1) !important;
            background-color: #47D3E5 !important;
            color: black !important;
        }

        #panel-button {
            cursor: pointer;
        }
    </style>
</head>

<body class="is-preload">
    <div id="navbar">
        <a href="../index.html">Home</a>
        <a href="../search.html">Search</a>
        <a href="../about.html">About</a>
        <a href="../login.html">Login</a>
    </div>
    <!-- Main content -->
    <section class="wrapper style1 align-center">
        <div class="inner">
            <header>
                <h2>Admin Panel</h2>
                <p>Manage art, artists, and other features.</p>
            </header>
            <div class="items style3 small onscroll-fade-in">
                <section>
                    <span class="icon major style1 fa-gem"></span>
                    <button id="panel-button">Edit Showcase Page</button>
                    <p>Edit the homepage showcase display.</p>
                </section>
                <section>
                    <span class="icon major style1 fa-trash-alt"></span>
                    <h3>Add Art</h3>
                    <p>Add and upload art entries to the database.</p>
                </section>
                <section>
                    <span class="icon major style1 fa-pencil-alt"></span>
                    <h3>Edit Art</h3>
                    <p>Edit or delete existing artwork information.</p>
                </section>
            </div>
        </div>
    </section>
    <script src="../script.js"></script>
</body>

</html>
