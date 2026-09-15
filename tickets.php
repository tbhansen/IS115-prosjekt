<?php
session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

//Fetch all tickets from the database
$stmt = $pdo->query("SELECT * FROM tickets");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Tickets</title>
    <link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
    <h1 class="page-title-1">Tickets</h1>

    <p>
        Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!
    </p>

    <!-- ticket overview -->

    <table>
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Title</th>
                <th scope="col">Status</th>
                <th scope="col">Created at</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="4">No tickets found.</td>
                </tr>

            <?php else: ?>

                <?php foreach ($tickets as $ticket): ?>

                    <tr>
                        <td>
                            <?php echo htmlspecialchars($ticket["id"]); ?>
                        </td>
                        <td>
                            <a href="ticket-view.php?id=<?php echo htmlspecialchars($ticket["id"]); ?>">
                                <?php echo htmlspecialchars($ticket["title"]); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($ticket["status"]); ?>
                        </td>
                        <td>
                            <?php echo date('d-m-Y H:i', strtotime($ticket["created_at"])); ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

        </tbody>
    </table>

    <a href="logout.php">Logout</a>
</body>

</html>