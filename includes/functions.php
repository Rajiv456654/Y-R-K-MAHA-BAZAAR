<?php
// Common functions for Y R K MAHA BAZAAR

// Start session if not already started
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    startSession();
    return isset($_SESSION['admin_id']);
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Redirect to admin login if not logged in as admin
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: admin-login.php");
        exit();
    }
}

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Format price with currency
function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

// Get cart item count for logged in user
function getCartCount() {
    if (!isLoggedIn()) {
        return 0;
    }

    global $conn;
    $user_id = $_SESSION['user_id'];
    $query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result['total'] ? $result['total'] : 0;
}

// Add product to cart
function addToCart($user_id, $product_id, $quantity = 1) {
    global $conn;

    // Check if product already in cart
    $check_query = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$user_id, $product_id]);
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Update quantity
        $update_query = "UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?";
        $update_stmt = $conn->prepare($update_query);
        return $update_stmt->execute([$quantity, $user_id, $product_id]);
    } else {
        // Insert new item
        $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        return $insert_stmt->execute([$user_id, $product_id, $quantity]);
    }
}

// Generate CSRF token
function generateCSRFToken() {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Upload image file
function uploadImage($file, $target_dir = "assets/images/products/") {
    $target_file = $target_dir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return false;
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return false;
    }
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        return false;
    }
    
    // Generate unique filename
    $unique_name = time() . '_' . uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $unique_name;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $unique_name;
    } else {
        return false;
    }
}

// Get user info by ID
function getUserInfo($user_id) {
    global $conn;
    $query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

// Get product info by ID
function getProductInfo($product_id) {
    global $conn;
    $query = "SELECT p.*, c.category_name FROM products p
              LEFT JOIN categories c ON p.category_id = c.category_id
              WHERE p.product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

// Pagination function
function paginate($total_records, $records_per_page, $current_page) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'offset' => $offset,
        'current_page' => $current_page
    ];
}
?>
