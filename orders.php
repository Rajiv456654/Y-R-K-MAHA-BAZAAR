<?php
$page_title = "My Orders";
include 'includes/header.php';

// Require login
requireLogin();

$user_id = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total orders count
$count_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute([$user_id]);
$total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_orders / $records_per_page);

// Get orders
$orders_query = "SELECT o.order_id, o.customer_name, o.customer_email, o.customer_phone, o.total_price, o.status, o.order_date, o.payment_method, o.shipping_address, COUNT(oi.item_id) as item_count
                 FROM orders o
                 LEFT JOIN order_items oi ON o.order_id = oi.order_id
                 WHERE o.user_id = ?
                 GROUP BY o.order_id, o.customer_name, o.customer_email, o.customer_phone, o.total_price, o.status, o.order_date, o.payment_method, o.shipping_address
                 ORDER BY o.order_date DESC
                 LIMIT ? OFFSET ?";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->execute([$user_id, $records_per_page, $offset]);
$orders_result = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="display-6 fw-bold mb-4">My Orders</h1>
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">My Orders</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (count($orders_result) > 0): ?>
    <!-- Orders List -->
    <div class="row">
        <div class="col-12">
            <?php foreach ($orders_result as $order): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h6 class="mb-1">Order #<?php echo $order['order_id']; ?></h6>
                            <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></small>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Items</small>
                            <div class="fw-bold"><?php echo $order['item_count']; ?></div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Total</small>
                            <div class="fw-bold text-primary"><?php echo formatPrice($order['total_price']); ?></div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Status</small>
                            <div>
                                <span class="badge bg-<?php echo getStatusColor($order['status']); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3 text-end">
                            <button class="btn btn-outline-primary btn-sm" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#order-<?php echo $order['order_id']; ?>">
                                <i class="fas fa-eye me-1"></i>View Details
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="collapse" id="order-<?php echo $order['order_id']; ?>">
                    <div class="card-body">
                        <div class="row">
                            <!-- Order Details -->
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Order Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Customer Name:</strong></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phone:</strong></td>
                                        <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Payment Method:</strong></td>
                                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Shipping Address -->
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Shipping Address</h6>
                                <address class="text-muted">
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                </address>
                            </div>
                        </div>
                        
                        <!-- Order Items -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3">Order Items</h6>
                                <?php
                                // Get order items
                                $items_query = "SELECT oi.*, p.name, p.image 
                                               FROM order_items oi 
                                               JOIN products p ON oi.product_id = p.product_id 
                                               WHERE oi.order_id = ?";
                                $items_stmt = $conn->prepare($items_query);
                                $items_stmt->execute([$order['order_id']]);
                                $items_result = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items_result as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="assets/images/products/<?php echo $item['image'] ?: 'default-product.jpg'; ?>"
                                                             class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;"
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo formatPrice($item['price']); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Actions -->
                        <div class="row mt-3">
                            <div class="col-12 text-end">
                                <?php if ($order['status'] == 'Pending'): ?>
                                <button class="btn btn-outline-danger btn-sm me-2" 
                                        onclick="cancelOrder(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-times me-1"></i>Cancel Order
                                </button>
                                <?php endif; ?>
                                
                                <a href="order-invoice.php?order_id=<?php echo $order['order_id']; ?>" 
                                   class="btn btn-outline-primary btn-sm" target="_blank">
                                    <i class="fas fa-download me-1"></i>Download Invoice
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="row">
        <div class="col-12">
            <nav aria-label="Orders pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- No Orders -->
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-shopping-bag fa-4x text-muted mb-4"></i>
                <h3>No orders yet</h3>
                <p class="text-muted mb-4">You haven't placed any orders yet. Start shopping to see your orders here.</p>
                <a href="products/product-list.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        fetch('cancel-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&csrf_token=${getCSRFToken()}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Order cancelled successfully', 'success');
                location.reload();
            } else {
                showAlert(data.message || 'Failed to cancel order', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'error');
        });
    }
}
</script>

<?php
// Function to get status color
function getStatusColor($status) {
    switch($status) {
        case 'Pending': return 'warning';
        case 'Processing': return 'info';
        case 'Shipped': return 'primary';
        case 'Delivered': return 'success';
        case 'Cancelled': return 'danger';
        default: return 'secondary';
    }
}

include 'includes/footer.php';
?>
