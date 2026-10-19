<?php
session_start();

require_once "db.php";

/** @var PDO $pdo */

$pageTitle = "Customer Support - Submit a Request";
$message = "";
$messageType = "";
$submittedTicketId = null;

// Handle customer support form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Read and trim customer inputs
    $customer_name  = trim($_POST["customer_name"] ?? "");
    $customer_email = trim($_POST["customer_email"] ?? "");
    $customer_phone = trim($_POST["customer_phone"] ?? "");
    $title          = trim($_POST["title"] ?? "");
    $description    = trim($_POST["description"] ?? "");

    // Validate required fields and email format
    if (empty($customer_name) || empty($customer_email) || empty($title) || empty($description)) {
        $message = "Please fill in all required fields (Name, Email, Subject, and Description).";
        $messageType = "error";
    } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please provide a valid email address.";
        $messageType = "error";
    } else {
        // Save new ticket with status 'open' and created_by = NULL (customer request)
        $stmt = $pdo->prepare(
            "INSERT INTO tickets (title, description, customer_name, customer_email, customer_phone, status, created_by)
             VALUES (?, ?, ?, ?, ?, 'open', NULL)"
        );

        $stmt->execute([
            $title,
            $description,
            $customer_name,
            $customer_email,
            $customer_phone ?: null
        ]);

        // Retrieve generated ticket ID to show reference confirmation
        $submittedTicketId = $pdo->lastInsertId();
        $message = "Thank you! Your support request has been submitted successfully.";
        $messageType = "success";
    }
}

require_once "header.php";
?>

<?php if (isset($_SESSION["user_id"])): ?>
    <div style="background-color: #e0f2fe; border: 1px solid #bae6fd; padding: 0.6rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; font-size: 0.88rem; color: #0369a1; display: flex; justify-content: space-between; align-items: center;">
        <span>You are currently viewing the customer intake form while logged in as staff.</span>
        <a href="tickets.php" class="btn btn-primary btn-sm">&larr; Return to Staff Dashboard</a>
    </div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Customer Support</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Need help? Fill out the contact form below and our support team will get back to you shortly.</p>
    </div>
</div>

<div style="max-width: 780px; margin: 0 auto;">

    <?php if ($submittedTicketId): ?>
        <!-- Success Confirmation Card -->
        <div class="card" style="border-top: 4px solid #137333;">
            <div class="card-header">
                <span class="card-title" style="color: #137333;">&#10003; Ticket #<?php echo htmlspecialchars($submittedTicketId); ?> Received</span>
            </div>
            <div class="card-body">
                <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
                <p style="margin-top: 0.5rem; color: #475569;">
                    A confirmation has been logged for <strong><?php echo htmlspecialchars($customer_email); ?></strong>.
                    Our staff will review your case and respond as soon as possible.
                </p>
                <div style="margin-top: 1.5rem;">
                    <a href="index.php" class="btn btn-primary">Submit Another Ticket</a>
                </div>
            </div>
        </div>

    <?php else: ?>

        <!-- Customer Intake Form -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Contact &amp; Support Request Form</span>
            </div>
            <div class="card-body">

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <h3 style="font-size: 0.95rem; color: #1e3a60; margin-bottom: 0.85rem; padding-bottom: 0.35rem; border-bottom: 1px solid #edf2f7;">
                        1. Your Contact Information
                    </h3>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="customer_name">Full Name *</label>
                            <input 
                                type="text" 
                                id="customer_name" 
                                name="customer_name" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($_POST["customer_name"] ?? ""); ?>" 
                                placeholder="e.g. Kari Nordmann" 
                                required>
                        </div>

                        <div class="form-group">
                            <label for="customer_email">Email Address *</label>
                            <input 
                                type="email" 
                                id="customer_email" 
                                name="customer_email" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($_POST["customer_email"] ?? ""); ?>" 
                                placeholder="name@example.com" 
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="customer_phone">Phone Number (Optional)</label>
                        <input 
                            type="text" 
                            id="customer_phone" 
                            name="customer_phone" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($_POST["customer_phone"] ?? ""); ?>" 
                            placeholder="+47 12 34 56 78">
                    </div>

                    <h3 style="font-size: 0.95rem; color: #1e3a60; margin-top: 1.25rem; margin-bottom: 0.85rem; padding-bottom: 0.35rem; border-bottom: 1px solid #edf2f7;">
                        2. How Can We Help You?
                    </h3>

                    <div class="form-group">
                        <label for="title">Subject *</label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($_POST["title"] ?? ""); ?>" 
                            placeholder="Brief summary of your question or issue" 
                            required>
                    </div>

                    <div class="form-group">
                        <label for="description">Message / Description *</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="6" 
                            class="form-control" 
                            placeholder="Please describe the issue or question with as much detail as possible..." 
                            required><?php echo htmlspecialchars($_POST["description"] ?? ""); ?></textarea>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-size: 0.95rem;">
                            Submit Support Request &rarr;
                        </button>
                    </div>
                </form>

            </div>
        </div>

    <?php endif; ?>

</div>

<?php
require_once "footer.php";
?>