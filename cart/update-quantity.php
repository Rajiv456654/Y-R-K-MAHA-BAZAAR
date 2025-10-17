<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($cart_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item or quantity']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Verify cart item belongs to user and get product stock
    $verify_query = "SELECT c.cart_id, p.stock FROM cart c 
                     JOIN products p ON c.product_id = p.product_id 
                     WHERE c.cart_id = ? AND c.user_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $cart_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit();
    }
    
    $cart_item = $verify_result->fetch_assoc();
    
    // Check stock availability
    if ($quantity > $cart_item['stock']) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock available']);
        exit();
    }
    
    // Update quantity
    $update_query = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $quantity, $cart_id);
    $update_stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Quantity updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating quantity']);
}
?>
