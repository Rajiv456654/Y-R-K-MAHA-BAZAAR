<?php
// Database connection configuration for Y R K MAHA BAZAAR

// Use environment variables for production (Render) or fallback to local defaults
$host = getenv('DB_HOST') ?: 'dpg-d3pqr2l6ubrc73fdtin0-a.internal';
$db   = getenv('DB_NAME') ?: 'yrk_db';
$user = getenv('DB_USER') ?: 'yrk_db_user';
$pass = getenv('DB_PASS') ?: 'XSZdI0wR6ucglfxspHIRlFz8wXoXQvmU';
$port = getenv('DB_PORT') ?: '5432';

$dsn = "pgsql:host=$host;port=$port;dbname=$db;";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Database connected successfully!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>
