<?php
/**
 * logout.php
 * 
 * Logger ut saksbehandleren ved å tømme og slette sesjonen,
 * og videresender deretter brukeren tilbake til forsiden.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tøm alle sesjonsvariabler
$_SESSION = [];

// Slett sesjons-cookie dersom den eksisterer
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

// Avslutt og ødelegg sesjonen
session_destroy();

// Videresend til offentlig forside
header("Location: index.php");
exit;
