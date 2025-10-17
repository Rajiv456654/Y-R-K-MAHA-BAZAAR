<?php
require_once '../includes/db_connect.php';

echo "<h2>🔍 Admin Debug Information</h2>";

// Check if admin table exists
try {
    $table_check = $conn->query("SHOW TABLES LIKE 'admin'");
    if ($table_check->num_rows > 0) {
        echo "✅ Admin table exists<br><br>";
        
        // Check admin users
        $admin_query = "SELECT admin_id, username, email, password FROM admin";
        $result = $conn->query($admin_query);
        
        if ($result->num_rows > 0) {
            echo "<h3>📋 Admin Users in Database:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Password Hash</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['admin_id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td>" . substr($row['password'], 0, 30) . "...</td>";
                echo "</tr>";
            }
            echo "</table><br>";
            
            // Test password verification
            echo "<h3>🔐 Password Verification Test:</h3>";
            $test_password = 'admin123';
            
            $verify_query = "SELECT username, password FROM admin WHERE username = 'admin'";
            $verify_result = $conn->query($verify_query);
            
            if ($verify_result->num_rows > 0) {
                $admin = $verify_result->fetch_assoc();
                $stored_hash = $admin['password'];
                
                echo "Username: " . htmlspecialchars($admin['username']) . "<br>";
                echo "Stored hash: " . $stored_hash . "<br>";
                echo "Test password: " . $test_password . "<br>";
                
                if (password_verify($test_password, $stored_hash)) {
                    echo "✅ <strong style='color: green;'>Password verification PASSED!</strong><br>";
                } else {
                    echo "❌ <strong style='color: red;'>Password verification FAILED!</strong><br>";
                    
                    // Create new hash
                    $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
                    echo "<br>🔧 <strong>Fixing the password hash...</strong><br>";
                    
                    $update_query = "UPDATE admin SET password = ? WHERE username = 'admin'";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("s", $new_hash);
                    
                    if ($update_stmt->execute()) {
                        echo "✅ Password hash updated successfully!<br>";
                        echo "New hash: " . $new_hash . "<br>";
                        
                        // Test again
                        if (password_verify($test_password, $new_hash)) {
                            echo "✅ <strong style='color: green;'>New password verification PASSED!</strong><br>";
                        }
                    } else {
                        echo "❌ Failed to update password hash<br>";
                    }
                }
            } else {
                echo "❌ Admin user 'admin' not found<br>";
                
                // Create admin user
                echo "<br>🔧 <strong>Creating admin user...</strong><br>";
                $username = 'admin';
                $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
                $email = 'admin@yrkmaha.com';
                
                $create_query = "INSERT INTO admin (username, password, email) VALUES (?, ?, ?)";
                $create_stmt = $conn->prepare($create_query);
                $create_stmt->bind_param("sss", $username, $password_hash, $email);
                
                if ($create_stmt->execute()) {
                    echo "✅ Admin user created successfully!<br>";
                } else {
                    echo "❌ Failed to create admin user: " . $conn->error . "<br>";
                }
            }
            
        } else {
            echo "❌ No admin users found in database<br>";
            
            // Create admin user
            echo "<br>🔧 <strong>Creating admin user...</strong><br>";
            $username = 'admin';
            $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $email = 'admin@yrkmaha.com';
            
            $create_query = "INSERT INTO admin (username, password, email) VALUES (?, ?, ?)";
            $create_stmt = $conn->prepare($create_query);
            $create_stmt->bind_param("sss", $username, $password_hash, $email);
            
            if ($create_stmt->execute()) {
                echo "✅ Admin user created successfully!<br>";
            } else {
                echo "❌ Failed to create admin user: " . $conn->error . "<br>";
            }
        }
        
    } else {
        echo "❌ Admin table does not exist<br>";
        echo "Please run the database setup script first.<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";
echo "<h3>🎯 Next Steps:</h3>";
echo "1. If password verification passed, try logging in again<br>";
echo "2. If there were issues, they should be fixed now<br>";
echo "3. <a href='admin-login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a><br>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #f8f9fa;
    line-height: 1.6;
}
table {
    margin: 10px 0;
}
th, td {
    padding: 8px 12px;
    text-align: left;
}
th {
    background: #007bff;
    color: white;
}
</style>
