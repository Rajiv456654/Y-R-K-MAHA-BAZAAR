<?php
$host = 'dpg-d3pqr2l6ubrc73fdtin0-a';  // ✅ Replace with your Render hostname
$port = '5432';
$dbname = 'yrk_db';
$user = 'yrk_db_user';
$password = 'XSZdI0wR6ucglfxspHIRlFz8wXoXQvmU';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    // echo "✅ Database connected successfully!";  // Commented out to prevent session_start() issues
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}

// Also create mysqli connection for compatibility with existing code
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
