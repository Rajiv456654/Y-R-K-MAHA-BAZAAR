<?php
// Standalone Database connection test script for Y R K MAHA BAZAAR

// Fetch environment variables (Render) or fallback to defaults
$servername = getenv('DB_HOST') ?: "localhost";
$username   = getenv('DB_USER') ?: "root";
$password   = getenv('DB_PASS') ?: "";
$database   = getenv('DB_NAME') ?: "yrk_maha_bazaar";

try {
    // --- PDO Connection Test ---
    $pdo = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h3>✅ PDO Connection Successful!</h3>";
    echo "<p>Connected to database: <strong>$database</strong> on host: <strong>$servername</strong></p>";

    // --- MySQLi Connection Test ---
    $mysqli = new mysqli($servername, $username, $password, $database);
    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }
    if ($mysqli->ping()) {
        echo "<h3>✅ MySQLi Connection Successful!</h3>";
        echo "<p>MySQLi connection is active.</p>";
    }

    // --- Simple Query Test ---
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && $result['test'] == 1) {
        echo "<h3>✅ Database Query Test Successful!</h3>";
        echo "<p>Database is responding correctly.</p>";
    }

    // --- Show database info ---
    echo "<h3>📊 Database Information:</h3>";
    echo "<ul>";
    echo "<li>Host: $servername</li>";
    echo "<li>Database: $database</li>";
    echo "<li>User: $username</li>";
    echo "<li>PDO Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "</li>";
    echo "</ul>";

} catch(PDOException $e) {
    echo "<h3>❌ PDO Connection Failed!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ul>";
    echo "<li>Check if PDO MySQL driver is installed (Dockerfile should have 'pdo_mysql')</li>";
    echo "<li>Verify DB_HOST, DB_USER, DB_PASS, DB_NAME environment variables</li>";
    echo "<li>Ensure database server is running</li>";
    echo "</ul>";
} catch(Exception $e) {
    echo "<h3>❌ General Error!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
