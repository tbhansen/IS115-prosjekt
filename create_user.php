<?php
session_start();

require_once "db.php";

$pageTitle = "Create User";
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($name) || empty($username) || empty($password)) {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } else {
        // Check if username is already taken
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $message = "Username is already registered. Please choose another.";
            $messageType = "error";
        } else {
            // Hash password securely
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, username, password_hash) VALUES (?, ?, ?)"
            );
            $stmt->execute([$name, $username, $passwordHash]);

            $message = "User created successfully! You can now log in.";
            $messageType = "success";
        }
    }
}

require_once "header.php";
?>

<div class="page-header">
    <h1 class="page-title">User Registration</h1>
    <?php if (isset($_SESSION["user_id"])): ?>
        <a href="tickets.php" class="btn btn-outline">&larr; Back to Dashboard</a>
    <?php else: ?>
        <a href="login.php" class="btn btn-outline">&larr; Back to Login</a>
    <?php endif; ?>
</div>

<div style="max-width: 500px; margin: 1.5rem auto;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Add New User</span>
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
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
require_once "footer.php";
?>