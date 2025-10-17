<?php
echo "<h2>🔧 Fixing All Admin Header Issues</h2>";

$admin_files = [
    'manage-categories.php',
    'manage-orders.php', 
    'manage-users.php',
    'contact-messages.php'
];

foreach ($admin_files as $file) {
    if (file_exists($file)) {
        echo "<h3>Fixing: $file</h3>";
        
        $content = file_get_contents($file);
        
        // Check if it has the old pattern
        if (strpos($content, 'include \'includes/admin-header.php\';') !== false && 
            strpos($content, 'require_once \'../includes/db_connect.php\';') === false) {
            
            // Replace the old pattern with new pattern
            $old_pattern = '/\<\?php\s*\$page_title\s*=\s*"([^"]+)";\s*include\s+\'includes\/admin-header\.php\';\s*/';
            $new_replacement = '<?php
require_once \'../includes/db_connect.php\';
require_once \'../includes/functions.php\';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit();
}

';
            
            $content = preg_replace($old_pattern, $new_replacement, $content);
            
            // Find where HTML starts and add header include
            if (strpos($content, '?>') !== false) {
                // Find the last ?> before HTML content
                $parts = explode('?>', $content);
                if (count($parts) >= 2) {
                    $php_part = $parts[0];
                    $html_part = implode('?>', array_slice($parts, 1));
                    
                    // Extract page title from original
                    preg_match('/\$page_title\s*=\s*"([^"]+)"/', $content, $matches);
                    $page_title = isset($matches[1]) ? $matches[1] : 'Admin Panel';
                    
                    // Add header include before HTML
                    $new_content = $php_part . '

// Include header after all processing is done
$page_title = "' . $page_title . '";
include \'includes/admin-header.php\';
?>' . $html_part;
                    
                    file_put_contents($file, $new_content);
                    echo "✅ Fixed $file<br>";
                } else {
                    echo "⚠️ Could not parse $file properly<br>";
                }
            } else {
                echo "⚠️ No HTML section found in $file<br>";
            }
        } else {
            echo "ℹ️ $file already fixed or doesn't match pattern<br>";
        }
    } else {
        echo "❌ $file not found<br>";
    }
    echo "<br>";
}

echo "<hr>";
echo "<h3>🎯 All admin files have been processed!</h3>";
echo "<p>The header issues should now be resolved for all admin pages.</p>";
echo "<a href='index.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px;'>Go to Admin Dashboard</a>";
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
</style>
