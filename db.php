<?php

// Database connection details (DDEV defaults)
$host = "db";
$dbname = "db";
$username = "db";
$password = "db";

// Connect to MariaDB using PDO
$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password
);

// Throw exceptions if a database error happens
$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);
