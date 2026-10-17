<?php
session_start();

require_once "db.php";

// Ensure user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Check that a ticket ID was provided
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: tickets.php");
    exit;
}

$id = (int)$_GET["id"];

// Fetch ticket details from the database with creator and assignee names
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

// Check if the ticket exists
if (!$ticket) {
    die("Ticket not found. <a href='tickets.php'>Back to dashboard</a>");
}

$pageTitle = "Ticket #" . $ticket["id"] . " - " . $ticket["title"];

require_once "header.php";
?>

<div class="page-header">
    <div>
        <a href="tickets.php" class="btn btn-outline btn-sm">&larr; Back to Dashboard</a>
    </div>
    <div>
        <a href="create_ticket.php" class="btn btn-primary btn-sm">+ New Ticket</a>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <span class="card-title">
            Ticket #<?php echo htmlspecialchars($ticket["id"]); ?>: <?php echo htmlspecialchars($ticket["title"]); ?>
        </span>
        <span class="badge badge-<?php echo htmlspecialchars($ticket["status"]); ?>">
            <?php echo htmlspecialchars(str_replace('_', ' ', $ticket["status"])); ?>
        </span>
    </div>

    <div class="card-body">

        <!-- Metadata Grid -->
        <div class="ticket-meta-grid">
            <div>
                <div class="meta-item-label">Status</div>
                <div class="meta-item-value">
                    <span class="badge badge-<?php echo htmlspecialchars($ticket["status"]); ?>">
                        <?php echo htmlspecialchars(str_replace('_', ' ', $ticket["status"])); ?>
                    </span>
                </div>
            </div>

            <div>
                <div class="meta-item-label">Assigned To</div>
                <div class="meta-item-value">
                    <?php echo $ticket["assigned_to_name"] ? htmlspecialchars($ticket["assigned_to_name"]) : "<em>Unassigned</em>"; ?>
                </div>
            </div>

            <div>
                <div class="meta-item-label">Created By</div>
                <div class="meta-item-value">
                    <?php echo htmlspecialchars($ticket["created_by_name"] ?? "Unknown"); ?>
                </div>
            </div>

            <div>
                <div class="meta-item-label">Created Date</div>
                <div class="meta-item-value">
                    <?php echo date("d-m-Y H:i", strtotime($ticket["created_at"])); ?>
                </div>
            </div>

            <div>
                <div class="meta-item-label">Last Updated</div>
                <div class="meta-item-value">
                    <?php echo date("d-m-Y H:i", strtotime($ticket["updated_at"])); ?>
                </div>
            </div>
        </div>

        <!-- Ticket Description Section -->
        <h3 style="font-size: 1.05rem; margin-bottom: 0.5rem; color: #1e293b;">Description</h3>
        <div class="ticket-description-box">
            <?php echo nl2br(htmlspecialchars($ticket["description"])); ?>
        </div>

    </div>
</div>

<?php
require_once "footer.php";
?>