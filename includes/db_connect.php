<?php
// Database connection configuration for Y R K MAHA BAZAAR

// Use environment variables for production (Render) or fallback to local defaults
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$database = getenv('DB_NAME') ?: "yrk_maha_bazaar";

try {
    // Create connection using PDO
    $pdo = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Also create mysqli connection for compatibility
    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
