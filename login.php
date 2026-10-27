<?php
/**
 * login.php
 * 
 * Innloggingsside for ansatte / saksbehandlere.
 * Verifiserer passord mot sikker bcrypt-hash i databasen og starter en sesjon.
 */

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/functions.php";

// Dersom brukeren allerede er innlogget, send direkte til saksdashbordet
if (is_logged_in()) {
    header("Location: tickets.php");
    exit;
}

$pageTitle = "Login";
$error = "";

// Behandle innloggingsforsøk (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Finn brukeren i databasen
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifiser passordet mot den lagrede hashen
        if ($user && password_verify($password, $user["password_hash"])) {
            // Lagre brukerinformasjon i sesjonen
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["name"] = $user["name"];

            // Videresend til dashbordet
            header("Location: tickets.php");
            exit;
        } else {
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
