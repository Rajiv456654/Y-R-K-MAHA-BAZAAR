<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Verify order belongs to user and can be cancelled
    $verify_query = "SELECT status FROM orders WHERE order_id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $order_id, $user_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    $order = $result->fetch_assoc();
    
    // Check if order can be cancelled
    if ($order['status'] !== 'Pending') {
        echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled. Current status: ' . $order['status']]);
        exit();
    }
    
    $conn->begin_transaction();
    
    // Update order status to cancelled
    $cancel_query = "UPDATE orders SET status = 'Cancelled' WHERE order_id = ?";
    $cancel_stmt = $conn->prepare($cancel_query);
    $cancel_stmt->bind_param("i", $order_id);
    $cancel_stmt->execute();
    
    // Restore product stock
    $items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        $restore_stock_query = "UPDATE products SET stock = stock + ? WHERE product_id = ?";
        $restore_stock_stmt = $conn->prepare($restore_stock_query);
        $restore_stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $restore_stock_stmt->execute();
    }
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'An error occurred while cancelling the order']);
}
?>
