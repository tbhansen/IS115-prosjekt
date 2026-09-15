<?php
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (empty($name) || empty($username) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {

        //Check if the username already exists in the database
        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE username = ?"
        );

        $stmt->execute([$username]);

        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $message = "User already exists.";
        } else {
            //Hash the password
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            //Insert the new user into the database
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, username, password_hash)
                VALUES (?, ?, ?)"
            );

            $stmt->execute([
                $name,
                $username,
                $passwordHash
            ]);

            $message = "User created successfully.";
        }
    }
}

?>

<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create user</title>
    <link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>

    <h1 class="page-title-1">Create User</h1>

    <form method="POST">

        <p>
            <label for="name">Name:</label>
            <input
                type="text"
                id="name"
                name="name"
                required>
        </p>

        <p>
            <label for="username">Username:</label>
            <input
                type="text"
                id="username"
                name="username"
                required>
        </p>

        <p>
            <label for="password">Password:</label>
            <input
                type="password"
                id="password"
                name="password"
                required>
        </p>

        <button type="submit">Create User</button>
    </form>

    <?php if ($message): ?>
        <p>
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>
    <p>
        <a href="index.php">Back to homepage</a>
    </p>
</body>

</html>