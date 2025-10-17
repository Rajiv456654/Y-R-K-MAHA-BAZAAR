<?php
/**
 * Payment Gateway Webhook Handler
 * Handles automatic payment verification from payment gateways
 */

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Get webhook payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? $_SERVER['HTTP_X_PAYU_SIGNATURE'] ?? '';

if (empty($payload) || empty($signature)) {
    logWebhookError('Empty payload or signature');
    http_response_code(400);
    exit('Invalid request');
}

// Verify webhook signature (implement based on your payment gateway)
if (!verifyWebhookSignature($payload, $signature)) {
    logWebhookError('Invalid signature: ' . $signature);
    http_response_code(401);
    exit('Unauthorized');
}

try {
    $data = json_decode($payload, true);

    if (!$data || !isset($data['event'])) {
        throw new Exception('Invalid payload format');
    }

    // Process different event types
    switch ($data['event']) {
        case 'payment.authorized':
        case 'payment.captured':
        case 'payment.paid':
            processPaymentSuccess($data['payload']['payment']);
            break;

        case 'payment.failed':
            processPaymentFailure($data['payload']['payment']);
            break;

        default:
            logWebhookEvent('Unhandled event: ' . $data['event']);
    }

    // Respond with success
    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    logWebhookError('Processing error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Process successful payment
 */
function processPaymentSuccess($payment_data) {
    global $conn;

    $payment_id = $payment_data['id'];
    $status = $payment_data['status'];
    $amount = $payment_data['amount'] / 100; // Convert from paise to rupees
    $method = $payment_data['method'] ?? 'unknown';

    // Find order by payment ID (check notes or metadata)
    $order_id = findOrderByPaymentId($payment_id);

    if (!$order_id) {
        logWebhookEvent("No order found for payment ID: $payment_id");
        return;
    }

    try {
        $conn->begin_transaction();

        // Update order status
        $update_query = "UPDATE orders SET
                        payment_status = 'Confirmed',
                        status = 'Processing',
                        webhook_verified = TRUE,
                        auto_confirmed_at = NOW()
                        WHERE order_id = ? AND payment_status = 'Pending'";

        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $order_id);

        if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
            // Update product stock
            updateProductStock($order_id);

            // Log successful webhook
            logPaymentWebhook($order_id, 'success', $payment_data);

            // Send confirmation email (if function exists)
            if (function_exists('sendOrderConfirmationEmail')) {
                sendOrderConfirmationEmail($order_id);
            }

            $conn->commit();
            logWebhookEvent("Order $order_id auto-confirmed via webhook");
        } else {
            $conn->rollback();
            logWebhookEvent("Order $order_id already processed or not found");
        }

    } catch (Exception $e) {
        $conn->rollback();
        logWebhookError("Database error for order $order_id: " . $e->getMessage());
    }
}

/**
 * Process failed payment
 */
function processPaymentFailure($payment_data) {
    $payment_id = $payment_data['id'];

    // Find and mark order as failed
    $order_id = findOrderByPaymentId($payment_id);

    if ($order_id) {
        $update_query = "UPDATE orders SET
                        payment_status = 'Failed',
                        status = 'Cancelled'
                        WHERE order_id = ?";

        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $order_id);
        $update_stmt->execute();

        logPaymentWebhook($order_id, 'failed', $payment_data);
    }
}

/**
 * Find order by payment ID
 */
function findOrderByPaymentId($payment_id) {
    global $conn;

    // Check if payment ID is stored in notes or transaction_id
    $query = "SELECT order_id FROM orders WHERE transaction_id = ? OR notes LIKE ?";
    $stmt = $conn->prepare($query);
    $search_pattern = "%{$payment_id}%";
    $stmt->bind_param("ss", $payment_id, $search_pattern);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['order_id'];
    }

    return null;
}

/**
 * Update product stock after order confirmation
 */
function updateProductStock($order_id) {
    global $conn;

    $stock_query = "UPDATE products p
                   JOIN order_items oi ON p.product_id = oi.product_id
                   SET p.stock = p.stock - oi.quantity
                   WHERE oi.order_id = ?";

    $stock_stmt = $conn->prepare($stock_query);
    $stock_stmt->bind_param("i", $order_id);
    $stock_stmt->execute();
}

/**
 * Log payment webhook events
 */
function logPaymentWebhook($order_id, $status, $webhook_data) {
    global $conn;

    $log_query = "INSERT INTO payment_logs (order_id, payment_method, transaction_id, amount, status, gateway_response)
                  VALUES (?, ?, ?, ?, ?, ?)";

    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("issdss",
        $order_id,
        $webhook_data['method'] ?? 'unknown',
        $webhook_data['id'],
        ($webhook_data['amount'] ?? 0) / 100,
        $status,
        json_encode($webhook_data)
    );
    $log_stmt->execute();
}

/**
 * Log webhook events for debugging
 */
function logWebhookEvent($message) {
    $log_file = __DIR__ . '/logs/webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

/**
 * Log webhook errors
 */
function logWebhookError($message) {
    logWebhookEvent("ERROR: $message");
}

/**
 * Verify webhook signature (implement based on payment gateway)
 */
function verifyWebhookSignature($payload, $signature) {
    // This is a basic implementation - implement proper verification based on your payment gateway

    // For Razorpay: https://razorpay.com/docs/webhooks/validate/
    // For PayU: https://developer.payu.in/docs/webhooks

    // Basic signature verification (implement properly)
    $expected_signature = hash_hmac('sha256', $payload, 'YOUR_WEBHOOK_SECRET');

    return hash_equals($expected_signature, $signature);
}

/**
 * Send order confirmation email (implement if not exists)
 */
function sendOrderConfirmationEmail($order_id) {
    // Get order details
    global $conn;
    $order_query = "SELECT o.*, u.email, u.name FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.order_id = ?";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("i", $order_id);
    $order_stmt->execute();
    $order = $order_stmt->get_result()->fetch_assoc();

    if ($order) {
        $subject = "Order Confirmed - #" . $order_id;
        $message = "Your order has been confirmed and is being processed.";

        // Use your existing email function or implement mail()
        if (function_exists('sendEmail')) {
            sendEmail($order['email'], $subject, $message);
        }
    }
}
?>
