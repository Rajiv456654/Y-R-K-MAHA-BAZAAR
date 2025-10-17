<?php
// Include required files first
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Require login
requireLogin();

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Get user info
$user_info = getUserInfo($user_id);

// Check if direct product purchase
$direct_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$direct_quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;

if ($direct_product_id > 0) {
    // Direct purchase from product page
    $product_query = "SELECT * FROM products WHERE product_id = ? AND is_active = 1";
    $product_stmt = $conn->prepare($product_query);
    $product_stmt->bind_param("i", $direct_product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    
    if ($product_result->num_rows == 0) {
        header("Location: ../products/product-list.php");
        exit();
    }
    
    $product = $product_result->fetch_assoc();
    $checkout_items = [[
        'product_id' => $product['product_id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => $direct_quantity,
        'image' => $product['image'],
        'total' => $product['price'] * $direct_quantity
    ]];
    $subtotal = $product['price'] * $direct_quantity;
} else {
    // Regular cart checkout
    $cart_query = "SELECT c.*, p.name, p.price, p.image, p.stock 
                   FROM cart c 
                   JOIN products p ON c.product_id = p.product_id 
                   WHERE c.user_id = ? AND p.is_active = 1 
                   ORDER BY c.added_at DESC";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    $checkout_items = [];
    $subtotal = 0;
    
    while ($item = $cart_result->fetch_assoc()) {
        $item_total = $item['price'] * $item['quantity'];
        $item['total'] = $item_total;
        $subtotal += $item_total;
        $checkout_items[] = $item;
    }
    
    if (empty($checkout_items)) {
        header("Location: cart.php");
        exit();
    }
}

$tax = $subtotal * 0.18; // 18% GST
$shipping = 0; // Free shipping
$total = $subtotal + $tax + $shipping;

// Process order
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = sanitizeInput($_POST['customer_name']);
    $customer_email = sanitizeInput($_POST['customer_email']);
    $customer_phone = sanitizeInput($_POST['customer_phone']);
    $shipping_address = sanitizeInput($_POST['shipping_address']);
    $payment_method = sanitizeInput($_POST['payment_method']);
    
    // Validation
    if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($shipping_address)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif ($payment_method == 'UPI') {
        // For UPI payments, redirect to payment gateway
        $_SESSION['checkout_data'] = [
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'shipping_address' => $shipping_address,
            'payment_method' => $payment_method,
            'checkout_items' => $checkout_items,
            'total' => $total,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'direct_product_id' => $direct_product_id
        ];
        
        header("Location: payment-upi.php");
        exit();
    } else {
        try {
            $conn->begin_transaction();
            
            // Create order
            $order_query = "INSERT INTO orders (user_id, total_price, customer_name, customer_email, customer_phone, shipping_address, payment_method) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $order_stmt = $conn->prepare($order_query);
            $order_stmt->bind_param("idsssss", $user_id, $total, $customer_name, $customer_email, $customer_phone, $shipping_address, $payment_method);
            $order_stmt->execute();
            
            $order_id = $conn->insert_id;
            
            // Add order items and update stock
            foreach ($checkout_items as $item) {
                // Add order item
                $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_query);
                $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $item_stmt->execute();
                
                // Update product stock
                $stock_query = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
                $stock_stmt = $conn->prepare($stock_query);
                $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stock_stmt->execute();
            }
            
            // Clear cart if not direct purchase
            if ($direct_product_id == 0) {
                $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
                $clear_cart_stmt = $conn->prepare($clear_cart_query);
                $clear_cart_stmt->bind_param("i", $user_id);
                $clear_cart_stmt->execute();
            }
            
            $conn->commit();
            
            // Redirect to success page
            $_SESSION['success_message'] = "Order placed successfully! Order ID: #" . $order_id;
            header("Location: order-success.php?order_id=" . $order_id);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "An error occurred while processing your order. Please try again.";
        }
    }
}

?>

<?php
// Include header after all processing is done
$page_title = "Checkout";
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="display-6 fw-bold mb-4">Checkout</h1>
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                    <li class="breadcrumb-item active">Checkout</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="needs-validation" novalidate>
        <div class="row">
            <!-- Checkout Form -->
            <div class="col-lg-8">
                <!-- Customer Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                       value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : htmlspecialchars($user_info['name']); ?>" 
                                       required>
                                <div class="invalid-feedback">Please enter your full name.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customer_email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                       value="<?php echo isset($_POST['customer_email']) ? htmlspecialchars($_POST['customer_email']) : htmlspecialchars($user_info['email']); ?>" 
                                       required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                       value="<?php echo isset($_POST['customer_phone']) ? htmlspecialchars($_POST['customer_phone']) : htmlspecialchars($user_info['phone']); ?>" 
                                       pattern="[0-9]{10}" required>
                                <div class="invalid-feedback">Please enter a valid 10-digit phone number.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Shipping Address</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Complete Address *</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="4" 
                                      required><?php echo isset($_POST['shipping_address']) ? htmlspecialchars($_POST['shipping_address']) : htmlspecialchars($user_info['address']); ?></textarea>
                            <div class="invalid-feedback">Please enter your complete shipping address.</div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" id="cod" value="Cash on Delivery" checked>
                            <label class="form-check-label" for="cod">
                                <i class="fas fa-money-bill-wave me-2"></i>Cash on Delivery
                                <small class="text-muted d-block">Pay when your order is delivered</small>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="upi" value="UPI">
                            <label class="form-check-label" for="upi">
                                <i class="fas fa-mobile-alt me-2"></i>UPI Payment
                                <small class="text-muted d-block">Pay using UPI apps like PhonePe, Google Pay</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <!-- Order Items -->
                        <div class="order-items mb-3">
                            <?php foreach ($checkout_items as $item): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <img src="../assets/images/products/<?php echo $item['image'] ?: 'default-product.jpg'; ?>" 
                                     class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted">Qty: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?></small>
                                </div>
                                <div class="text-end">
                                    <strong><?php echo formatPrice($item['total']); ?></strong>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="price-breakdown">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span><?php echo formatPrice($subtotal); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping:</span>
                                <span class="text-success">FREE</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (GST 18%):</span>
                                <span><?php echo formatPrice($tax); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong class="text-primary"><?php echo formatPrice($total); ?></strong>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i>Place Order
                        </button>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your payment information is secure and encrypted
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
