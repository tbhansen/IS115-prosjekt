<?php
/**
 * create_user.php
 * 
 * New staff member registration.
 * Validates form inputs, checks username uniqueness,
 * and securely hashes password using password_hash().
 */

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/functions.php";

$pageTitle = "Create User";
$message = "";
$messageType = "";

// Handle registration form submission (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Read and trim form fields
    $name     = trim($_POST["name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // Validate that all required fields are provided
    if (empty($name) || empty($username) || empty($password)) {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $messageType = "error";
    } else {
        // Check if username is already registered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $message = "Username is already registered. Please choose another.";
            $messageType = "error";
        } else {
            // Hash password securely with default bcrypt algorithm
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new staff member record into users table
            $insertStmt = $pdo->prepare(
                "INSERT INTO users (name, username, password_hash) VALUES (?, ?, ?)"
            );
            $insertStmt->execute([$name, $username, $passwordHash]);

            $message = "User created successfully! You can now log in.";
            $messageType = "success";
        }
    }
}

require_once __DIR__ . "/includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">Staff Registration</h1>
    <?php if (is_logged_in()): ?>
        <a href="tickets.php" class="btn btn-outline">&larr; Back to Dashboard</a>
    <?php else: ?>
        <a href="login.php" class="btn btn-outline">&larr; Back to Login</a>
    <?php endif; ?>
</div>

<div style="max-width: 500px; margin: 1.5rem auto;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Add New Staff Member</span>
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo e($messageType); ?>">
                    <?php echo e($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="create_user.php">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        value="<?php echo e($_POST["name"] ?? ""); ?>"
                        placeholder="e.g. Noah Smith" 
                        required>
                </div>

                <div class="form-group">
                    <label for="username">Username *</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        value="<?php echo e($_POST["username"] ?? ""); ?>"
                        placeholder="Choose a username" 
                        required>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Minimum 6 characters" 
                        required>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1.25rem;">
                    <button type="submit" class="btn btn-primary">Create User</button>
                    <a href="login.php" class="btn btn-outline">Go to Login</a>
                </div>
            </form>

        </div>
    </div>
</div>

<?php
require_once __DIR__ . "/includes/footer.php";
?>
