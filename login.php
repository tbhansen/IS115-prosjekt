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
    <title>Login</title>
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>

    <h1>Welcome</h1>

	
    <h2 style="margin-bottom: 1rem;">Database connection test</h2>
    <h3 style="margin-bottom: 1.5rem;">List of all users:</h3>

    <?php foreach ($users as $user): ?>
        <p>
            <?php echo htmlspecialchars($user["name"]); ?>
        </p>
    <?php endforeach; ?>


    <p class="bottom-text">
        Please contact your administrator if you have issues with the login process.
    </p>

</body>
</html>
