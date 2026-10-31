<?php
/**
 * logout.php
 * 
 * Logs out the staff member by clearing session variables, destroying the session,
 * and redirecting back to the public homepage.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Delete session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session data on server
session_destroy();

// Redirect to public homepage
header("Location: index.php");
exit;
