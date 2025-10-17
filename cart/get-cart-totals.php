<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get cart items with totals
    $query = "SELECT c.cart_id, c.quantity, p.price, (c.quantity * p.price) as total
              FROM cart c 
              JOIN products p ON c.product_id = p.product_id 
              WHERE c.user_id = ? AND p.is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $subtotal = 0;
    
    while ($item = $result->fetch_assoc()) {
        $items[] = [
            'cart_id' => $item['cart_id'],
            'total' => number_format($item['total'], 2)
        ];
        $subtotal += $item['total'];
    }
    
    $tax = $subtotal * 0.18; // 18% GST
    $total = $subtotal + $tax;
    
    echo json_encode([
        'success' => true,
        'subtotal' => number_format($subtotal, 2),
        'tax' => number_format($tax, 2),
        'total' => number_format($total, 2),
        'items' => $items
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred while calculating totals']);
}
?>
