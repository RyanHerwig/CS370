<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: ../index.html");
    exit;
}

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
            background: linear-gradient(115deg, white, rgb(210, 210, 210));
            font-family: sans-serif;
        }
        #navbar {
            overflow: hidden;
            background-color: #333;
            padding: 10px 0;
            text-align: center;
        }
        #navbar a {
            display: inline-block;
            color: #f2f2f2;
            text-align: center;
            padding: 14px 16px;
            text-decoration: none;
            font-size: 17px;
            margin: 0 5px;
            border-radius: 5px;
        }
        #navbar a:hover {
            background-color: #ddd;
            color: black;
        }
        #navbar a.active {
            background-color: #555;
            color: white;
        }
        #navbar a#welcome-message {
            float: right;
            margin-right: 10px;
        }
        #navbar a#panel-button {
            float: left;
            margin-left: 10px;
        }
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
        </div>
    <section class="wrapper style1 align-center">
            <div class="items style3 small onscroll-fade-in">
                <section>
                    <span class="icon major style1 fa-gem"></span>
                    <a href="edit_showcase.php" id="panel-button" class="button">Edit Showcase Page</a>
                    <p>Edit the homepage showcase display.</p>
                </section>
            </div>
        </div>
    </section>
    <script src="../script.js"></script>
</body>

</html>
