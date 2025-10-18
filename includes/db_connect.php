<?php
$servername = 'localhost';  // Use your MySQL host
$username = 'root';         // Your MySQL username
$password = '';             // Your MySQL password
$database = 'yrk_maha_bazaar';  // Your MySQL database name

try {
    // Create PDO connection for MySQL with explicit port to force TCP/IP
    $pdo = new PDO("mysql:host=$servername;port=3306;dbname=$database", $username, $password);
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
