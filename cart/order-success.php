<?php
// Include required files first
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Require login
requireLogin();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header("Location: ../index.php");
    exit();
}

// Get order details
$order_query = "SELECT o.order_id, o.customer_name, o.customer_email, o.customer_phone, o.total_price, o.status, o.order_date, o.payment_method, o.shipping_address, COUNT(oi.item_id) as item_count
                FROM orders o
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE o.order_id = ? AND o.user_id = ?
                GROUP BY o.order_id, o.customer_name, o.customer_email, o.customer_phone, o.total_price, o.status, o.order_date, o.payment_method, o.shipping_address";
$order_stmt = $conn->prepare($order_query);
$order_stmt->execute([$order_id, $_SESSION['user_id']]);
$order_result = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order_result) {
    header("Location: ../index.php");
    exit();
}

$order = $order_result;

// Get order items
$items_query = "SELECT oi.*, p.name, p.image
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->execute([$order_id]);
$items_result = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header after all processing is done
$page_title = "Order Success";
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Message -->
            <div class="text-center mb-5">
                <div class="success-icon mb-4">
                    <i class="fas fa-check-circle fa-5x text-success"></i>
                </div>
                <h1 class="display-5 fw-bold text-success mb-3">Order Placed Successfully!</h1>
                <p class="lead text-muted mb-4">
                    Thank you for your order. We've received your order and will process it shortly.
                </p>
                <div class="alert alert-success">
                    <strong>Order ID: #<?php echo $order_id; ?></strong>
                </div>
            </div>

            <!-- Order Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Information</h6>
                            <p class="mb-1"><strong>Order Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></p>
                            <p class="mb-1"><strong>Status:</strong> <span class="badge bg-warning"><?php echo $order['status']; ?></span></p>
                            <p class="mb-1"><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                            <p class="mb-1"><strong>Total Amount:</strong> <strong class="text-primary"><?php echo formatPrice($order['total_price']); ?></strong></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <h6>Shipping Address</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i>Order Items (<?php echo $order['item_count']; ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($items_result as $item): ?>
                    <div class="d-flex align-items-center p-3 border-bottom">
                        <img src="../assets/images/products/<?php echo $item['image'] ?: 'default-product.jpg'; ?>"
                             class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;"
                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <p class="text-muted mb-0">
                                Quantity: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <strong><?php echo formatPrice($item['quantity'] * $item['price']); ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>What's Next?</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <i class="fas fa-cog fa-2x text-primary mb-2"></i>
                            <h6>Order Processing</h6>
                            <p class="text-muted small">We'll prepare your order for shipment</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="fas fa-truck fa-2x text-warning mb-2"></i>
                            <h6>Shipping</h6>
                            <p class="text-muted small">Your order will be shipped within 2-3 business days</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="fas fa-home fa-2x text-success mb-2"></i>
                            <h6>Delivery</h6>
                            <p class="text-muted small">Delivered to your doorstep in 5-7 business days</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center">
                <a href="../orders.php" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-list me-2"></i>View All Orders
                </a>
                <a href="../products/product-list.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                </a>
            </div>

            <!-- Contact Info -->
            <div class="text-center mt-4">
                <p class="text-muted">
                    <i class="fas fa-phone me-1"></i>
                    Need help? Contact us at <strong>+91 98765 43210</strong> or 
                    <a href="mailto:support@yrkmaha.com">support@yrkmaha.com</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
