<?php
/**
 * Database Connection
 *
 * Purpose:
 * Connects the website to the MySQL/MariaDB database using PDO.
 *
 * Why we wrote it like this:
 * - We use PDO because it makes it easy and safe to run database queries.
 * - 'charset=utf8mb4' ensures letters with accents and special characters work properly.
 * - ATTR_ERRMODE => ERRMODE_EXCEPTION makes PHP show an error if something goes wrong with SQL.
 *
 * Assumptions & Limitations:
 * - Assumes we are running locally in DDEV with default database name and password 'db'.
 */

// Database connection settings (DDEV defaults)
$host     = "db";
$dbname   = "db";
$username = "db";
$password = "db";

try {
    // Create a new PDO instance
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Throw exceptions when a database error occurs
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Show a user-friendly error message if the connection fails
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}
