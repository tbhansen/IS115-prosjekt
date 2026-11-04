<?php
/**
 * Ticketheaven - Staff Authentication & Session Initialization
 * 
 * Purpose:
 * Provides the login portal for support staff and administrators.
 * Validates submitted credentials against stored bcrypt/argon2 password hashes in the database,
 * initializes authenticated session state ($_SESSION), and redirects the user to the staff dashboard.
 * 
 * Architectural & Design Choices:
 * - Employs PHP's native password_verify() to match the plaintext password against password_hash().
 *   This protects against timing attacks and supports algorithm auto-rehashing without exposing raw secrets.
 * - Enforces generic authentication failure notices ("Invalid username or password") rather than
 *   stating whether the username or password specifically failed. This prevents username enumeration attacks.
 * - Guards against redundant logins: if an active staff session is detected, the user is immediately
 *   redirected to tickets.php, preventing session confusion.
 * - Retains the submitted username in the input field upon failure (sanitized with e()) so users
 *   do not need to retype their username after an incorrect password attempt.
 * 
 * Assumptions & Limitations:
 * - Assumption: Assumes staff accounts have been pre-created in the users table with valid password hashes.
 * - Limitation: Does not currently implement account lockout or rate-limiting (e.g., maximum 5 attempts
 *   per IP address per 15 minutes). This is a recommended security addition for production.
 * - Limitation: Session fixation protection (session_regenerate_id(true)) can be added upon successful
 *   authentication to further harden session management.
 */

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/functions.php";

// Redirect users who already hold an authenticated session
if (is_logged_in()) {
    header("Location: tickets.php");
    exit;
}

$pageTitle = "Login";
$error = "";

// Handle authentication attempt (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Query user record by username using a prepared statement to prevent SQL injection
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify that the user exists and that the provided password matches the stored cryptographic hash
        if ($user && password_verify($password, $user["password_hash"])) {
            // Populate authenticated session state with minimal non-sensitive user identity data
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["name"] = $user["name"];

            // Redirect to the staff management dashboard
            header("Location: tickets.php");
            exit;
        } else {
            // Generic error message to prevent user enumeration
            $error = "Invalid username or password.";
        }
    }
}

require_once __DIR__ . "/includes/header.php";
?>

<div style="max-width: 440px; margin: 3rem auto;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Staff Login (Kundebehandler)</span>
        </div>
        <div class="card-body">

            <?php if (!empty($error)): ?>
                <!-- Alert box rendered on validation or authentication failure -->
                <div class="alert alert-error">
                    <?php echo e($error); ?>
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
                        value="<?php echo e($_POST["username"] ?? ""); ?>"
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
require_once __DIR__ . "/includes/footer.php";
?>
