<?php
$servername = 'dpg-d3pqr2l6ubrc73fdtin0-a';  // Your PostgreSQL host
$username = 'yrk_db_user';
$password = 'XSZdI0wR6ucglfxspHIRlFz8wXoXQvmU';
$database = 'yrk_db';

try {
    // Create the PDO connection object using the CORRECT PGSQL driver and port
    $pdo = new PDO("pgsql:host=$servername;port=5432;dbname=$database", $username, $password);
    
    // CRITICAL FIX: Assign the PDO object to the variable your index.php expects
    $conn = $pdo; 
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // If the connection fails, set $conn to null to prevent further errors
    $conn = null; 
    die("Connection failed: " . $e->getMessage());
}

// mysqli code removed - using PostgreSQL PDO only
?>
