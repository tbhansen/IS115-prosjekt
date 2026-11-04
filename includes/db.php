<?php
/**
 * Database connection (PDO)
 * 
 * Connects to the MariaDB database using default DDEV credentials.
 * Configures PDO in exception error mode so SQL issues throw catchable exceptions.
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
