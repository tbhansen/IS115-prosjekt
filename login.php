<?php
session_start();

require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE username = ?"
    );

    $stmt->execute([$username]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password_hash"])) {

        //Save user information in session variables
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["name"] = $user["name"];

        //Sends the user to tickets.php page
        header("Location: tickets.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

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

    <form method="POST">

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

        <button type="submit">Login</button>

    </form>

    <?php if ($error): ?>
        <p>
            <?php echo htmlspecialchars($error); ?>
        </p>

    <?php endif; ?>


    <p class="bottom-text">
        Please contact your administrator if you have issues with the login process. <a href="create_user.php">Create a new user</a> if you are an administrator.
    </p>

</body>

</html>