<?php
session_start();

require_once "db.php";

/** @var PDO $pdo */

// Ensure staff is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Check that a valid ticket ID was provided
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: tickets.php");
    exit;
}

$id = (int)$_GET["id"];
$updateMessage = "";
$updateMessageType = "";

// Handle staff action: Update status or reassign ticket
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_ticket"])) {
    $newStatus = $_POST["status"] ?? "open";
    $newAssignedTo = !empty($_POST["assigned_to"]) ? (int)$_POST["assigned_to"] : null;

    // Validate allowed status values
    $allowedStatuses = ["open", "in_progress", "closed"];
    if (in_array($newStatus, $allowedStatuses, true)) {
        $updateStmt = $pdo->prepare(
            "UPDATE tickets SET status = ?, assigned_to = ? WHERE id = ?"
        );
        $updateStmt->execute([$newStatus, $newAssignedTo, $id]);

        $updateMessage = "Ticket #$id updated successfully.";
        $updateMessageType = "success";
    }
}

// Fetch ticket details with customer info, assigned staff, and creator staff
$stmt = $pdo->prepare(
    "SELECT
        tickets.*,
        creator.name AS created_by_staff_name,
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

if (!$ticket) {
    die("Ticket not found. <a href='tickets.php'>Return to dashboard</a>");
}

// Fetch list of staff for the assignment dropdown
$staffList = $pdo->query("SELECT id, name, username FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Ticket #" . $ticket["id"] . " - " . $ticket["title"];

require_once "header.php";
?>

<div class="page-header">
    <div>
        <a href="tickets.php" class="btn btn-outline btn-sm">&larr; Back to Dashboard</a>
    </div>
    <div>
        <a href="create_ticket.php" class="btn btn-primary btn-sm">+ Create New Ticket</a>
    </div>
</div>

<?php if (isset($_GET["created"])): ?>
    <div class="alert alert-success">
        Ticket #<?php echo htmlspecialchars($ticket["id"]); ?> created successfully!
    </div>
<?php endif; ?>

<?php if (!empty($updateMessage)): ?>
    <div class="alert alert-<?php echo $updateMessageType; ?>">
        <?php echo htmlspecialchars($updateMessage); ?>
    </div>
<?php endif; ?>

<div class="dashboard-grid">

    <!-- LEFT COLUMN: Ticket Content & Customer Info -->
    <div class="dashboard-column">

        <!-- Main Ticket Card -->
        <div class="card">
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
                        <div class="meta-item-label">Customer Name</div>
                        <div class="meta-item-value">
                            <strong><?php echo htmlspecialchars($ticket["customer_name"]); ?></strong>
                        </div>
                    </div>

                    <div>
                        <div class="meta-item-label">Customer Email</div>
                        <div class="meta-item-value">
                            <a href="mailto:<?php echo htmlspecialchars($ticket["customer_email"]); ?>" class="ticket-link">
                                <?php echo htmlspecialchars($ticket["customer_email"]); ?>
                            </a>
                        </div>
                    </div>

                    <div>
                        <div class="meta-item-label">Customer Phone</div>
                        <div class="meta-item-value">
                            <?php echo !empty($ticket["customer_phone"]) ? htmlspecialchars($ticket["customer_phone"]) : "<em>Not provided</em>"; ?>
                        </div>
                    </div>

                    <div>
                        <div class="meta-item-label">Submitted via</div>
                        <div class="meta-item-value">
                            <?php if ($ticket["created_by"]): ?>
                                Staff logged (<?php echo htmlspecialchars($ticket["created_by_staff_name"] ?? "Staff"); ?>)
                            <?php else: ?>
                                Public Customer Form
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <div class="meta-item-label">Created</div>
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

                <!-- Description Box -->
                <h3 style="font-size: 0.95rem; color: #1e3a60; margin-bottom: 0.5rem;">Inquiry Message / Description</h3>
                <div class="ticket-description-box">
                    <?php echo nl2br(htmlspecialchars($ticket["description"])); ?>
                </div>

            </div>
        </div>

    </div>

    <!-- RIGHT COLUMN: Staff Actions & Workflow Controls -->
    <div class="dashboard-column">

        <!-- Ticket Controls Card (Status & Assignment) -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Ticket Management</span>
            </div>
            <div class="card-body">
                <form action="ticket-view.php?id=<?php echo $ticket['id']; ?>" method="POST">
                    <input type="hidden" name="update_ticket" value="1">

                    <div class="form-group">
                        <label for="status">Ticket Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="open" <?php echo ($ticket["status"] === "open") ? "selected" : ""; ?>>
                                Open
                            </option>
                            <option value="in_progress" <?php echo ($ticket["status"] === "in_progress") ? "selected" : ""; ?>>
                                In Progress
                            </option>
                            <option value="closed" <?php echo ($ticket["status"] === "closed") ? "selected" : ""; ?>>
                                Closed
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="assigned_to">Assignee / Owner (Eier)</label>
                        <select id="assigned_to" name="assigned_to" class="form-control">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($staffList as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>" <?php echo ($ticket['assigned_to'] == $staff['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['username']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            Update Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Contact Info -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Direct Actions</span>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; gap: 0.6rem;">
                <a href="mailto:<?php echo htmlspecialchars($ticket['customer_email']); ?>?subject=Re:%20Ticket%20#<?php echo $ticket['id']; ?>%20-%20<?php echo urlencode($ticket['title']); ?>" class="btn btn-outline" style="text-align: center;">
                    &#9993; Email Customer
                </a>
                <a href="tickets.php" class="btn btn-outline" style="text-align: center;">
                    &larr; Return to Dashboard
                </a>
            </div>
        </div>

    </div>

</div>

<?php
require_once "footer.php";
?>