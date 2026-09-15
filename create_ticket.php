<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = ($_POST["title"]);
    $description = $_POST["description"] ?? "";
    $status = $_POST["status"] ?? "";
    $created_by = $_SESSION["user_id"];
    $date = $_POST["created_at"] ?? "";

    if (empty($title) || empty($description) || empty($status) || empty($created_by) || empty($date)) {
        $message = "Please fill in all fields.";
    } else {

        // Insert the new ticket into the database
        $stmt = $pdo->prepare(
            "INSERT INTO tickets (title, description, status, created_by, created_at)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $title,
            $description,
            $status,
            $created_by,
            $date
        ]);

        $message = "Ticket created successfully.";
    }
}

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

    <h1 class="page-title-1">Welcome to ticket creator!</h1>

    <!-- Form for creating a new ticket -->
    <h2 style="margin-top: 6rem;">Fill out the form below to create a new ticket:</h2>
        <form action="create_ticket.php" method="POST">
            <label for="title">Title:</label>
            <input 
                type="text" 
                id="title" 
                name="title" 
                required><p></p>
        <label for="description">Description:</label>
        <textarea 
            id="description" 
            name="description" 
            required></textarea><p></p>
        <label for="created_by">Ticket creators name:</label>
        <input 
            type="text" 
            id="created_by" 
            value="<?php echo htmlspecialchars($_SESSION["name"]); ?>" readonly>
        <input 
            type="hidden" 
            name="status" 
            value="open">
        <input 
            type="hidden" 
            name="created_at" 
            value="<?php echo date('Y-m-d H:i:s'); ?>"><p></p>
        <button type="submit">Create Ticket</button>
    </form>

    <p>
        <a href="index.php">Back to homepage</a>
    </p>

</body>
</html>

