<?php
/**
 * create_ticket.php
 * 
 * Internal form for staff to manually log customer inquiries
 * (e.g., requests received via phone call or in-person walk-ins).
 */

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/functions.php";

// Require staff authentication
require_login();

$pageTitle = "Create Ticket (Staff)";
$message = "";
$messageType = "";

// Fetch staff members for assignment dropdown
$staffMembers = get_all_staff($pdo);

// Handle manual ticket submission (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Read and trim form inputs
    $customer_name  = trim($_POST["customer_name"] ?? "");
    $customer_email = trim($_POST["customer_email"] ?? "");
    $customer_phone = trim($_POST["customer_phone"] ?? "");
    $title          = trim($_POST["title"] ?? "");
    $description    = trim($_POST["description"] ?? "");
    $status         = $_POST["status"] ?? "open";
    $assigned_to    = !empty($_POST["assigned_to"]) ? (int)$_POST["assigned_to"] : null;
    $created_by     = get_logged_in_user_id();

    // Validate required fields
    if (empty($customer_name) || empty($customer_email) || empty($title) || empty($description)) {
        $message = "Please fill in all required fields (Customer Name, Email, Subject, Description).";
        $messageType = "error";
    } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid customer email address.";
        $messageType = "error";
    } else {
        // Create ticket in database using reusable repository function
        $newTicketId = create_staff_ticket(
            $pdo,
            $title,
            $description,
            $customer_name,
            $customer_email,
            $customer_phone,
            $status,
            $assigned_to,
            $created_by
        );

        // Redirect directly to the newly created ticket view
        header("Location: ticket_view.php?id=" . $newTicketId . "&created=1");
        exit;
    }
}

require_once __DIR__ . "/includes/header.php";
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Create Ticket on Behalf of Customer</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Internal staff tool to log phone calls, in-person requests, or external emails.</p>
    </div>
    <a href="tickets.php" class="btn btn-outline">&larr; Back to Dashboard</a>
</div>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">New Ticket Details</span>
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo e($messageType); ?>">
                    <?php echo e($message); ?>
                </div>
            <?php endif; ?>

            <form action="create_ticket.php" method="POST">
                
                <h3 style="font-size: 0.95rem; color: #1e3a60; margin-bottom: 0.85rem; padding-bottom: 0.35rem; border-bottom: 1px solid #edf2f7;">
                    Customer Information
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="customer_name">Customer Full Name *</label>
                        <input 
                            type="text" 
                            id="customer_name" 
                            name="customer_name" 
                            class="form-control" 
                            value="<?php echo e($_POST["customer_name"] ?? ""); ?>" 
                            placeholder="e.g. John Doe" 
                            required>
                    </div>

                    <div class="form-group">
                        <label for="customer_email">Customer Email *</label>
                        <input 
                            type="email" 
                            id="customer_email" 
                            name="customer_email" 
                            class="form-control" 
                            value="<?php echo e($_POST["customer_email"] ?? ""); ?>" 
                            placeholder="john@example.com" 
                            required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="customer_phone">Customer Phone (Optional)</label>
                    <input 
                        type="text" 
                        id="customer_phone" 
                        name="customer_phone" 
                        class="form-control" 
                        value="<?php echo e($_POST["customer_phone"] ?? ""); ?>" 
                        placeholder="+47 00 00 00 00">
                </div>

                <h3 style="font-size: 0.95rem; color: #1e3a60; margin-top: 1.25rem; margin-bottom: 0.85rem; padding-bottom: 0.35rem; border-bottom: 1px solid #edf2f7;">
                    Ticket Data &amp; Assignment
                </h3>

                <div class="form-group">
                    <label for="title">Subject / Title *</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-control" 
                        value="<?php echo e($_POST["title"] ?? ""); ?>" 
                        placeholder="Brief summary of the issue..." 
                        required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="status">Initial Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="assigned_to">Assign to Staff</label>
                        <select id="assigned_to" name="assigned_to" class="form-control">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($staffMembers as $member): ?>
                                <option value="<?php echo e($member['id']); ?>" <?php echo ($member['id'] == get_logged_in_user_id()) ? 'selected' : ''; ?>>
                                    <?php echo e($member['name']); ?> (<?php echo e($member['username']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description *</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="5" 
                        class="form-control" 
                        placeholder="Enter the customer inquiry or case details..." 
                        required><?php echo e($_POST["description"] ?? ""); ?></textarea>
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
require_once __DIR__ . "/includes/footer.php";
?>
