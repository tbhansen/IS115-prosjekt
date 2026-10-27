<?php
/**
 * Databasetilkobling (PDO)
 * 
 * Kobler til MariaDB-databasen ved hjelp av standardinnstillinger for DDEV.
 * Setter PDO i feilsøkingsmodus slik at eventuelle SQL-feil kaster unntak (exceptions).
 */

// Database-innstillinger (standard for DDEV)
$host     = "db";
$dbname   = "db";
$username = "db";
$password = "db";

try {
    // Opprett en ny PDO-tilkobling
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Konfigurer PDO til å kaste feilmeldinger dersom en spørring feiler
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sett standard fetch-modus til assosiativt array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Vis en forståelig feilmelding dersom tilkoblingen feiler
    die("Kunne ikke koble til databasen: " . htmlspecialchars($e->getMessage()));
}
