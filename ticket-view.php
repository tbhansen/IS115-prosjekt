<?php
session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Check that a ticket ID was provided
if (!isset($_GET["id"])) {
    echo "No ticket ID provided";
    exit;
}

//Get ticket ID from the URL
$id = $_GET["id"];

//Fetch ticket details from the database
$stmt = $pdo->prepare(
    "SELECT
        tickets.*,
        creator.name AS created_by_name,
        assignee.name AS assigned_to_name
     FROM tickets
     LEFT JOIN users AS creator
        ON tickets.created_by = creator.id
     LEFT JOIN users AS assignee
        ON tickets.assigned_to = assignee.id
     WHERE tickets.id = ?"
);

$stmt->execute([$id]);

$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

//Check if the ticket exists
if (!$ticket) {
    echo "Ticket not found";
    exit;
}
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Ticket: <?php echo htmlspecialchars($ticket["title"]); ?></title>
    <link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
    <a class="back-link" href="tickets.php">Ticket overview</a>
    <br>

    <h3>Ticket #<?php echo $ticket["id"]; ?></h3>
    <h2>
        <?php echo htmlspecialchars($ticket["title"]); ?>
    </h2>
    <p>
        <strong>Status:</strong>
        <?php echo htmlspecialchars($ticket["status"]); ?>
    </p>
    <p>
        <strong>Assigned to:</strong>
        <?php if ($ticket["assigned_to_name"]): ?>
            <?php echo htmlspecialchars($ticket["assigned_to_name"]); ?>
        <?php else: ?>
            Unassigned
        <?php endif; ?>
    </p>
    <p>
    <strong>Created by:</strong>
        <?php echo htmlspecialchars($ticket["created_by_name"]); ?>
    </p>
    <p>
        <strong>Created:</strong>
        <?php echo date("d-m-Y H:i", strtotime($ticket["created_at"])); ?>
    </p>
    <p>
        <strong>Updated at:</strong>
        <?php echo date("d-m-Y H:i", strtotime($ticket["updated_at"])); ?>
    </p>




    <h3>Description</h3>
    <p>
        <?php echo nl2br(htmlspecialchars($ticket["description"])); ?>
    </p>

</body>

</html>