<?php
session_start();

require_once "db.php";

// If already logged in, redirect directly to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: tickets.php");
    exit;
}

$pageTitle = "Login";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password_hash"])) {
        // Save user information in session
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["name"] = $user["name"];

        // Redirect to tickets dashboard
        header("Location: tickets.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

require_once "header.php";
?>

<div style="max-width: 440px; margin: 3rem auto;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Sign In to Ticketheaven</span>
        </div>
        <div class="card-body">

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        required 
                        autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        required>
                </div>

                <div style="margin-top: 1.25rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Log In</button>
                </div>
            </form>

            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #edf2f7; font-size: 0.82rem; color: #64748b; text-align: center;">
                Need an account? <a href="create_user.php" class="ticket-link">Create a new user</a>
            </div>

        </div>
    </div>
</div>

<?php
require_once "footer.php";
?>