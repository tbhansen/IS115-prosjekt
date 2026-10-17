<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " - Ticketheaven" : "Ticketheaven - Service Desk"; ?></title>
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>

    <!-- Top Navigation Bar inspired by Request Tracker (RT) -->
    <header class="top-nav">
        <div class="nav-left">
            <a href="<?php echo isset($_SESSION['user_id']) ? 'tickets.php' : 'index.php'; ?>" class="brand-logo">
                <span class="logo-icon">&#9776;</span>
                <span class="logo-text">TICKETHEAVEN</span>
            </a>
            <nav class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="tickets.php" class="nav-item">Dashboard</a>
                    <a href="create_ticket.php" class="nav-item">New Ticket</a>
                    <a href="create_user.php" class="nav-item">Users</a>
                <?php else: ?>
                    <a href="index.php" class="nav-item">Home</a>
                    <a href="create_ticket.php" class="nav-item">Submit Ticket</a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="nav-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="user-greeting">
                    Logged in as <strong><?php echo htmlspecialchars($_SESSION["name"] ?? $_SESSION["username"]); ?></strong>
                </span>
                <a href="create_ticket.php" class="btn btn-primary btn-sm">+ Create new ticket</a>
                <a href="logout.php" class="btn btn-outline btn-sm">Log out</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">Log in</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="main-content">
