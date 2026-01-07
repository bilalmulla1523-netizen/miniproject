<?php
$host     = "localhost";
$dbname   = "site_checker";
$username = "root";    // default XAMPP user
$password = "";        // default XAMPP password (empty)

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database connection failed";
    exit;
}
