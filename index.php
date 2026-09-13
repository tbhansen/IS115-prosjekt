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

    <h1>Welcome to Ticketheaven!</h1>

    <p>Ticket tracking software created by Noah and Tobias.</p>
    <p> <a href="login.php">Login</a> to access your account. </p>

    <h2 style="margin-top: 6rem;">Database connection test</h2>
    <h3 style="margin-bottom: 1.5rem;">List of all users:</h3>

    <?php foreach ($users as $user): ?>
        <p>
            <?php echo htmlspecialchars($user["name"]); ?>
        </p>
    <?php endforeach; ?>



</body>

</html>