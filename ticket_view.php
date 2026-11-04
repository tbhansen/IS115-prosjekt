<?php
/**
 * Ticketheaven - Single Ticket Management View
 * 
 * Purpose:
 * Serves as the detailed inspection and management interface for a specific support ticket.
 * Authenticated support staff can examine customer contact details, read inquiry messages,
 * change the ticket's lifecycle status, and assign or reassign ownership among staff members.
 * 
 * Architectural & Design Choices:
 * - Follows a Page Controller pattern: handles HTTP parameter validation, processes POST update actions,
 *   retrieves required relational data via helper functions, and renders the responsive split-column UI.
 * - Enforces strict server-side validation on submitted status values using an explicit whitelist array
 *   (in_array with strict checking === true), preventing unauthorized or corrupted state transitions.
 * - Converts an empty assignee selection ("") to PHP null, properly aligning with database NULL foreign keys
 *   rather than inserting invalid integer zeros.
 * 
 * Assumptions & Limitations:
 * - Authentication Assumption: Access is strictly restricted to staff; unauthenticated visitors are redirected.
 * - Permission Assumption: Assumes all logged-in staff members have equal permissions to view and modify any ticket
 *   (fine-grained role-based access control, e.g., Admin vs. Agent, can be added in future iterations).
 * - Limitation: Uses standard HTTP POST form submission causing a full-page reload on update.
 *   AJAX or background updates can be introduced in later sprints for smoother UX.
 */

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/functions.php";

// Enforce staff authentication; redirects immediately if no valid session exists
require_login();

// Validate query parameter: ticket ID must be present and numeric
// Why: Rejecting non-numeric or missing IDs early avoids executing unnecessary database queries
// and prevents unexpected type errors or warnings.
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: tickets.php");
    exit;
}

$id = (int)$_GET["id"];
$updateMessage = "";
$updateMessageType = "";

// Handle status or assignee update (POST request)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_ticket"])) {
    $newStatus = $_POST["status"] ?? "open";
    
    // Why: If the user selects "-- Unassigned --", the submitted value is empty.
    // Casting non-empty values to integer and defaulting to null ensures foreign key integrity.
    $newAssignedTo = !empty($_POST["assigned_to"]) ? (int)$_POST["assigned_to"] : null;

    // Why: Whitelist validation guarantees that only valid, recognized ticket states can be written to the database.
    $allowedStatuses = ["open", "in_progress", "closed"];
    if (in_array($newStatus, $allowedStatuses, true)) {
        update_ticket_status_and_assignee($pdo, $id, $newStatus, $newAssignedTo);
        $updateMessage = "Ticket #$id updated successfully.";
        $updateMessageType = "success";
    }
}

// Fetch the single ticket record using the reusable service function
$ticket = get_ticket_by_id($pdo, $id);

// If the ticket ID does not match any database row, gracefully terminate with an informative prompt
if (!$ticket) {
    die("Ticket not found. <a href='tickets.php'>Return to dashboard</a>");
}

// Fetch list of staff members to populate the assignment dropdown
$staffList = get_all_staff($pdo);

$pageTitle = "Ticket #" . $ticket["id"] . " - " . $ticket["title"];

require_once __DIR__ . "/includes/header.php";
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
    <!-- Notice displayed when redirected after internal ticket creation -->
    <div class="alert alert-success">
        Ticket #<?php echo e($ticket["id"]); ?> created successfully!
    </div>
<?php endif; ?>

<?php if (!empty($updateMessage)): ?>
    <!-- Notice displayed upon successful status or assignee modification -->
    <div class="alert alert-<?php echo e($updateMessageType); ?>">
        <?php echo e($updateMessage); ?>
    </div>
<?php endif; ?>

<div class="dashboard-grid">

    <!-- LEFT COLUMN: Ticket details, customer information, and message body -->
    <div class="dashboard-column">

        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    Ticket #<?php echo e($ticket["id"]); ?>: <?php echo e($ticket["title"]); ?>
                </span>
                <?php echo render_status_badge($ticket["status"]); ?>
            </div>
            <div class="card-body">

                <!-- Metadata Grid -->
                <div class="ticket-meta-grid">
                    <div>
                        <div class="meta-item-label">Customer Name</div>
                        <div class="meta-item-value">
                            <strong><?php echo e($ticket["customer_name"]); ?></strong>
                        </div>
                    </div>

                    <div>
                        <div class="meta-item-label">Customer Email</div>
                        <div class="meta-item-value">
                            <!-- Mailto link allowing direct customer communication in default email client -->
                            <a href="mailto:<?php echo e($ticket["customer_email"]); ?>" class="ticket-link">
                                <?php echo e($ticket["customer_email"]); ?>
                            </a>
                        </div>
                    </div>

                    <div>
                        <div class="meta-item-label">Customer Phone</div>
                        <div class="meta-item-value">
                            <?php echo !empty($ticket["customer_phone"]) ? e($ticket["customer_phone"]) : "<em>Not provided</em>"; ?>
                        </div>
                    </div>

                    <div>
                        <div class="meta-item-label">Submitted via</div>
                        <div class="meta-item-value">
                            <?php if ($ticket["created_by"]): ?>
                                Staff logged (<?php echo e($ticket["created_by_staff_name"] ?? "Staff"); ?>)
                            <?php else: ?>
                                Public Customer Form
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <div class="meta-item-label">Created</div>
                        <div class="meta-item-value">
                            <?php echo format_datetime($ticket["created_at"]); ?>
                        </div>
                    </div>

                    <div>
                        <div class="meta-item-label">Last Updated</div>
                        <div class="meta-item-value">
                            <?php echo format_datetime($ticket["updated_at"]); ?>
                        </div>
                    </div>
                </div>

                <!-- Description / Customer Inquiry Message Body -->
                <h3 style="font-size: 0.95rem; color: #1e3a60; margin-bottom: 0.5rem;">Inquiry Message / Description</h3>
                <div class="ticket-description-box">
                    <?php 
                    // Why: nl2br combined with e() safely converts line breaks to HTML <br> tags
                    // while ensuring all user text remains sanitized against XSS attacks.
                    echo nl2br(e($ticket["description"])); 
                    ?>
                </div>

            </div>
        </div>

    </div>

    <!-- RIGHT COLUMN: Status & assignment modification controls -->
    <div class="dashboard-column">

        <!-- Update status and assignee form -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Ticket Management</span>
            </div>
            <div class="card-body">
                <form action="ticket_view.php?id=<?php echo e($ticket['id']); ?>" method="POST">
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
                        <label for="assigned_to">Assignee / Owner</label>
                        <select id="assigned_to" name="assigned_to" class="form-control">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($staffList as $staff): ?>
                                <option value="<?php echo e($staff['id']); ?>" <?php echo ($ticket['assigned_to'] == $staff['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($staff['name']); ?> (<?php echo e($staff['username']); ?>)
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

        <!-- Direct communication and navigation shortcuts -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Direct Actions</span>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; gap: 0.6rem;">
                <a href="mailto:<?php echo e($ticket['customer_email']); ?>?subject=Re:%20Ticket%20#<?php echo e($ticket['id']); ?>%20-%20<?php echo urlencode($ticket['title']); ?>" class="btn btn-outline" style="text-align: center;">
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
require_once __DIR__ . "/includes/footer.php";
?>
