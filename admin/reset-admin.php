<?php
require_once '../includes/db_connect.php';

echo "<h2>🔄 Resetting Admin Credentials</h2>";

// New admin credentials
$new_username = 'superadmin';
$new_password = 'password123';
$new_email = 'superadmin@yrkmaha.com';

try {
    // First, delete any existing admin users
    echo "<p>🗑️ Clearing existing admin users...</p>";
    $delete_query = "DELETE FROM admin";
    $conn->query($delete_query);
    echo "✅ Existing admin users cleared<br><br>";
    
    // Create new admin with proper password hash
    echo "<p>👤 Creating new admin user...</p>";
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $insert_query = "INSERT INTO admin (username, password, email) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sss", $new_username, $password_hash, $new_email);
    
    if ($stmt->execute()) {
        echo "✅ <strong>New admin user created successfully!</strong><br><br>";
        
        // Test the new credentials immediately
        echo "<h3>🧪 Testing new credentials...</h3>";
        
        $test_query = "SELECT admin_id, username, password FROM admin WHERE username = ?";
        $test_stmt = $conn->prepare($test_query);
        $test_stmt->bind_param("s", $new_username);
        $test_stmt->execute();
        $result = $test_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($new_password, $admin['password'])) {
                echo "✅ <strong style='color: green;'>Password verification SUCCESSFUL!</strong><br>";
                echo "✅ <strong style='color: green;'>Admin login is ready to use!</strong><br><br>";
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
                echo "<h3>🎉 SUCCESS! New Admin Credentials</h3>";
                echo "<p><strong>Username:</strong> " . htmlspecialchars($new_username) . "</p>";
                echo "<p><strong>Password:</strong> " . htmlspecialchars($new_password) . "</p>";
                echo "<p><strong>Email:</strong> " . htmlspecialchars($new_email) . "</p>";
                echo "</div>";
                
            } else {
                echo "❌ Password verification failed<br>";
            }
        }
        
    } else {
        echo "❌ Failed to create admin user: " . $conn->error . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";
echo "<h3>🎯 Next Steps:</h3>";
echo "<p>1. The admin login page will be updated with new credentials</p>";
echo "<p>2. Use the new credentials shown above to login</p>";
echo "<p>3. <a href='admin-login.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>Go to Admin Login</a></p>";
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
