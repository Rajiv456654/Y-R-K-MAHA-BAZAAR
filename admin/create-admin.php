<?php
// This script creates the admin user with proper password hashing
require_once '../includes/db_connect.php';

// Admin credentials
$username = 'admin';
$password = 'admin123';
$email = 'admin@yrkmaha.com';

// Hash the password properly
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if admin already exists
    $check_query = "SELECT admin_id FROM admin WHERE username = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing admin
        $update_query = "UPDATE admin SET password = ?, email = ? WHERE username = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sss", $hashed_password, $email, $username);
        
        if ($update_stmt->execute()) {
            echo "✅ Admin user updated successfully!<br>";
            echo "<strong>Username:</strong> admin<br>";
            echo "<strong>Password:</strong> admin123<br>";
        } else {
            echo "❌ Error updating admin user: " . $conn->error;
        }
    } else {
        // Create new admin
        $insert_query = "INSERT INTO admin (username, password, email) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("sss", $username, $hashed_password, $email);
        
        if ($insert_stmt->execute()) {
            echo "✅ Admin user created successfully!<br>";
            echo "<strong>Username:</strong> admin<br>";
            echo "<strong>Password:</strong> admin123<br>";
        } else {
            echo "❌ Error creating admin user: " . $conn->error;
        }
    }
    
    echo "<br><a href='admin-login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 600px;
    margin: 50px auto;
    padding: 20px;
    background: #f8f9fa;
}
</style>
