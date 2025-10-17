<?php
// Include required files first
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Require login
requireLogin();

$page_title = "My Orders";
include '../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get user orders
$query = "SELECT o.*, COUNT(oi.item_id) as item_count 
          FROM orders o 
          LEFT JOIN order_items oi ON o.order_id = oi.order_id 
          WHERE o.user_id = ? 
          GROUP BY o.order_id 
          ORDER BY o.order_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="display-6 fw-bold mb-4">My Orders</h1>
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="product-list.php">Products</a></li>
                    <li class="breadcrumb-item active">My Orders</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($orders->num_rows > 0): ?>
        <div class="row">
            <?php while ($order = $orders->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Order #<?php echo $order['order_id']; ?></h6>
                        <span class="badge bg-<?php 
                            echo $order['status'] == 'Delivered' ? 'success' : 
                                ($order['status'] == 'Cancelled' ? 'danger' : 
                                ($order['status'] == 'Shipped' ? 'primary' : 
                                ($order['status'] == 'Processing' ? 'info' : 'warning'))); 
                        ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-6">
                                <small class="text-muted">Order Date:</small>
                                <div class="fw-bold"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Total Amount:</small>
                                <div class="fw-bold text-primary"><?php echo formatPrice($order['total_price']); ?></div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Items:</small>
                                <div class="fw-bold"><?php echo $order['item_count']; ?> item(s)</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Payment:</small>
                                <div class="fw-bold"><?php echo $order['payment_method']; ?></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Shipping Address:</small>
                            <div class="small"><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                <i class="fas fa-eye me-1"></i>View Details
                            </button>
                            <?php if ($order['status'] == 'Pending'): ?>
                            <button class="btn btn-outline-danger btn-sm" onclick="cancelOrder(<?php echo $order['order_id']; ?>)">
                                <i class="fas fa-times me-1"></i>Cancel Order
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-shopping-bag fa-4x text-muted mb-4"></i>
                    <h3>No Orders Yet</h3>
                    <p class="text-muted mb-4">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                    <a href="product-list.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="product-list.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-search me-2"></i>Browse Products
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="cart/cart.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-shopping-cart me-2"></i>View Cart
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="profile.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="contact.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-envelope me-2"></i>Contact Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewOrderDetails(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const content = document.getElementById('orderDetailsContent');
    
    // Show loading spinner
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch order details
    fetch('../get-order-details.php?order_id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load order details.</div>';
            }
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Error loading order details.</div>';
        });
}

function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        fetch('../cancel-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + orderId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order cancelled successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error cancelling order. Please try again.');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
