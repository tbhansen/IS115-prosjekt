<?php
/**
 * Ticketheaven - Database Connection Factory
 * 
 * Purpose:
 * Establishes and configures the central PDO (PHP Data Objects) connection instance
 * used by all data-access operations across the Ticketheaven application.
 * 
 * Architectural & Design Choices:
 * - Uses PDO instead of the legacy mysqli extension to provide a uniform data-access interface,
 *   native support for prepared statements with named or positional parameters, and robust
 *   exception-based error handling.
 * - Forces UTF-8 encoding via 'charset=utf8mb4' in the Data Source Name (DSN) to ensure full
 *   four-byte Unicode support, properly handling special characters and emojis without data corruption.
 * - Configures PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION so that database errors trigger catchable
 *   PDOException instances rather than silent failures or standard PHP warnings, preventing
 *   unhandled query failures from corrupting state.
 * - Sets PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC so queries return clean associative arrays
 *   by default, avoiding the duplicate memory overhead of PDO::FETCH_BOTH.
 * 
 * Assumptions & Limitations:
 * - Environment Assumption: Configured for the standard local DDEV container environment where
 *   the database host, database name, username, and password all default to 'db'.
 * - Limitation: Database credentials are hardcoded here for development convenience. In a production
 *   environment, these settings should be extracted into environment variables (.env) or an external
 *   configuration file stored outside the web root.
 */

// Database connection credentials (matching DDEV local defaults)
$host     = "db";
$dbname   = "db";
$username = "db";
$password = "db";

try {
    // Instantiate the PDO database connection with UTF-8 character encoding
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Ensure all database errors throw exceptions for consistent try/catch handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Standardize query result structures to associative arrays across the application
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Catch connection failure, prevent stack trace exposure, and display a safe message.
    // Why: Output escaping with htmlspecialchars prevents potential XSS if the error message
    // contains untrusted network or host strings.
    die("Database connection failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8"));
}
