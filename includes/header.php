<?php
// Start sesjon dersom den ikke allerede er aktiv
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " - Ticketheaven" : "Ticketheaven - Kundeservice"; ?></title>
    <!-- Stilark for hele nettstedet -->
    <link href="assets/css/style.css" rel="stylesheet" type="text/css">
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>

    <!-- Felles toppmeny / navigasjon for nettstedet -->
    <header class="top-nav">
        <div class="nav-left">
            <a href="<?php echo isset($_SESSION['user_id']) ? 'tickets.php' : 'index.php'; ?>" class="brand-logo">
                <span class="logo-icon">&#9776;</span>
                <span class="logo-text">TICKETHEAVEN</span>
            </a>
            <nav class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="tickets.php" class="nav-item">Staff Dashboard</a>
                    <a href="create_ticket.php" class="nav-item">+ New Ticket (Internal)</a>
                    <a href="create_user.php" class="nav-item">Staff Accounts</a>
                    <a href="index.php" class="nav-item" target="_blank">View Customer Form &UpperRightArrow;</a>
                <?php else: ?>
                    <a href="index.php" class="nav-item">Submit Ticket (Customer)</a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="nav-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="user-greeting">
                    Staff: <strong><?php echo htmlspecialchars($_SESSION["name"] ?? $_SESSION["username"]); ?></strong>
                </span>
                <a href="create_ticket.php" class="btn btn-primary btn-sm">+ Create ticket</a>
                <a href="logout.php" class="btn btn-outline btn-sm">Log out</a>
            <?php else: ?>
                <span class="user-greeting">Are you an employee?</span>
                <a href="login.php" class="btn btn-primary btn-sm">Staff Login</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Hovedinnhold -->
    <main class="main-content">
