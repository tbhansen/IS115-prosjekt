<?php
require_once "db.php";

$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>

    <h1 class="page-title-1">Welcome to Ticketheaven!</h1>

    <p>Ticket tracking software created by Noah and Tobias.</p>
    <p> <button><a href="create_ticket.php">Create Ticket</a></button> here to create a new ticket.</p>
    <p> <button><a href="login.php">Login</a></button> to access your account for administration. </p>

</body>
</html>