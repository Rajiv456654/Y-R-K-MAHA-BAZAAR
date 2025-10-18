<?php
$servername = 'dpg-d3pqr2l6ubrc73fdtin0-a';  // Your PostgreSQL host
$username = 'yrk_db_user';
$password = 'XSZdI0wR6ucglfxspHIRlFz8wXoXQvmU';
$database = 'yrk_db';

try {
    // Create PDO connection for PostgreSQL with explicit port 5432
    $pdo = new PDO("pgsql:host=$servername;port=5432;dbname=$database", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Also create mysqli connection for compatibility (if needed for MySQL)
    // Note: mysqli is for MySQL, not PostgreSQL - this may need adjustment
    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
