<?php
session_start();

require_once "db.php";

// If user is not logged in, redirect them to login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$pageTitle = "Create Ticket";
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $created_by = $_SESSION["user_id"];
    $status = "open"; // New tickets start as 'open'

    if (empty($title) || empty($description)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        // Insert the new ticket securely with prepared statement
        $stmt = $pdo->prepare(
            "INSERT INTO tickets (title, description, status, created_by)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $title,
            $description,
            $status,
            $created_by
        ]);

        $message = "Ticket was successfully created!";
        $messageType = "success";
    }
}

require_once "header.php";
?>

<div class="page-header">
    <h1 class="page-title">Create New Ticket</h1>
    <a href="tickets.php" class="btn btn-outline">&larr; Back to Dashboard</a>
</div>

<div style="max-width: 750px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Ticket Information</span>
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="create_ticket.php" method="POST">
                <div class="form-group">
                    <label for="title">Subject / Title *</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-control" 
                        placeholder="Brief summary of the issue..." 
                        required>
                </div>

                <div class="form-group">
                    <label for="created_by_display">Creator</label>
                    <input 
                        type="text" 
                        id="created_by_display" 
                        class="form-control" 
                        value="<?php echo htmlspecialchars($_SESSION["name"] ?? $_SESSION["username"]); ?>" 
                        readonly>
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description *</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="6" 
                        class="form-control" 
                        placeholder="Explain the problem or request in detail..." 
                        required></textarea>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1.25rem;">
                    <button type="submit" class="btn btn-primary">Create Ticket</button>
                    <a href="tickets.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>

        </div>
    </div>
</div>

<?php
require_once "footer.php";
?>