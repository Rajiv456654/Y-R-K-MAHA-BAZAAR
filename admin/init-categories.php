<?php
// Sample categories initialization script
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Sample categories to insert if they don't exist
$sample_categories = [
    'Electronics',
    'Clothing',
    'Home & Garden',
    'Sports & Outdoors',
    'Books',
    'Health & Beauty',
    'Toys & Games',
    'Automotive'
];

try {
    // Check if categories table exists and has data
    $check_query = "SELECT COUNT(*) as count FROM categories";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute();
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        // Insert sample categories
        foreach ($sample_categories as $category_name) {
            $insert_query = "INSERT INTO categories (category_name) VALUES (?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->execute([$category_name]);
        }
        echo "Sample categories added successfully!";
    } else {
        echo "Categories already exist (" . $result['count'] . " categories found)";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
