<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

echo "<h2>🧪 Admin Login Test</h2>";

// Test credentials
$test_username = 'admin';
$test_password = 'admin123';

echo "<p><strong>Testing with:</strong></p>";
echo "Username: " . htmlspecialchars($test_username) . "<br>";
echo "Password: " . htmlspecialchars($test_password) . "<br><br>";

try {
    // Step 1: Check if admin table exists
    echo "<h3>Step 1: Checking admin table</h3>";
    $table_check = $conn->query("SHOW TABLES LIKE 'admin'");
    if ($table_check->num_rows > 0) {
        echo "✅ Admin table exists<br><br>";
    } else {
        echo "❌ Admin table does not exist<br>";
        echo "Please import the database first!<br>";
        exit;
    }
    
    // Step 2: Check if admin user exists
    echo "<h3>Step 2: Looking for admin user</h3>";
    $query = "SELECT admin_id, username, password FROM admin WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $test_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        echo "✅ Admin user found<br>";
        $admin = $result->fetch_assoc();
        echo "Admin ID: " . $admin['admin_id'] . "<br>";
        echo "Username: " . htmlspecialchars($admin['username']) . "<br>";
        echo "Password Hash: " . substr($admin['password'], 0, 20) . "...<br><br>";
        
        // Step 3: Test password verification
        echo "<h3>Step 3: Testing password verification</h3>";
        if (password_verify($test_password, $admin['password'])) {
            echo "✅ <strong style='color: green;'>Password verification SUCCESSFUL!</strong><br>";
            echo "The login should work now.<br><br>";
            
            // Step 4: Test session creation
            echo "<h3>Step 4: Testing session creation</h3>";
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_username'])) {
                echo "✅ Session variables set successfully<br>";
                echo "Admin ID in session: " . $_SESSION['admin_id'] . "<br>";
                echo "Admin username in session: " . htmlspecialchars($_SESSION['admin_username']) . "<br><br>";
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h4>🎉 SUCCESS! Everything is working correctly!</h4>";
                echo "<p>The admin login should work now. Try logging in again.</p>";
                echo "</div>";
                
            } else {
                echo "❌ Failed to set session variables<br>";
            }
            
        } else {
            echo "❌ <strong style='color: red;'>Password verification FAILED!</strong><br>";
            echo "The stored password hash doesn't match.<br><br>";
            
            // Fix the password
            echo "<h3>🔧 Fixing the password hash</h3>";
            $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
            
            $update_query = "UPDATE admin SET password = ? WHERE username = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ss", $new_hash, $test_username);
            
            if ($update_stmt->execute()) {
                echo "✅ Password hash updated successfully!<br>";
                echo "New hash: " . $new_hash . "<br><br>";
                
                // Test the new hash
                if (password_verify($test_password, $new_hash)) {
                    echo "✅ <strong style='color: green;'>New password verification SUCCESSFUL!</strong><br>";
                    echo "The login should work now.<br>";
                } else {
                    echo "❌ Still having issues with password verification<br>";
                }
            } else {
                echo "❌ Failed to update password hash: " . $conn->error . "<br>";
            }
        }
        
    } else {
        echo "❌ Admin user not found<br>";
        echo "Creating admin user...<br><br>";
        
        // Create admin user
        $password_hash = password_hash($test_password, PASSWORD_DEFAULT);
        $email = 'admin@yrkmaha.com';
        
        $insert_query = "INSERT INTO admin (username, password, email) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("sss", $test_username, $password_hash, $email);
        
        if ($insert_stmt->execute()) {
            echo "✅ Admin user created successfully!<br>";
            echo "Username: " . $test_username . "<br>";
            echo "Password: " . $test_password . "<br>";
            echo "Hash: " . $password_hash . "<br>";
        } else {
            echo "❌ Failed to create admin user: " . $conn->error . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";
echo "<h3>🎯 Try Login Now:</h3>";
echo "<a href='admin-login.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>Go to Admin Login Page</a><br><br>";

echo "<h3>📋 Login Credentials:</h3>";
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px;'>";
echo "<strong>Username:</strong> admin<br>";
echo "<strong>Password:</strong> admin123";
echo "</div>";
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
h2, h3 {
    color: #333;
}
</style>
