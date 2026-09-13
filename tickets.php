<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>

<DOCTYPE HTML>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <title>Tickets</title>
        <link href="style.css" rel="stylesheet" type="text/css">
    </head>

    <body>
        <h1>Tickets</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</p>

        <!-- ticket overview -->

        <a href="logout.php">Logout</a>
    </body>

    </html>