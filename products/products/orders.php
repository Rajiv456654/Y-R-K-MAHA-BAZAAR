<?php
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';
startSession();

// Require login
requireLogin();

$user_id = $_SESSION['user_id'];

// Handle order cancellation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $order_id = (int)$_GET['cancel'];
    
    // Check if order belongs to user and can be cancelled
    $check_query = "SELECT status FROM orders WHERE order_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $order_id, $user_id);
    $check_stmt->execute();
    $order_status = $check_stmt->get_result()->fetch_assoc();
    
    if ($order_status && in_array($order_status['status'], ['Pending', 'Processing'])) {
        $cancel_query = "UPDATE orders SET status = 'Cancelled' WHERE order_id = ? AND user_id = ?";
        $cancel_stmt = $conn->prepare($cancel_query);
        $cancel_stmt->bind_param("ii", $order_id, $user_id);
        
        if ($cancel_stmt->execute()) {
            $_SESSION['success_message'] = "Order #$order_id has been cancelled successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to cancel order. Please try again.";
        }
    } else {
        $_SESSION['error_message'] = "This order cannot be cancelled.";
    }
    
    header("Location: orders.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total orders count
$count_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $records_per_page);

// Get orders
$orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT ? OFFSET ?";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("iii", $user_id, $records_per_page, $offset);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

// Get order statistics
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_price) as total_spent,
    COUNT(CASE WHEN status = 'Delivered' THEN 1 END) as delivered_orders,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_orders,
    COUNT(CASE WHEN status = 'Processing' THEN 1 END) as processing_orders,
    COUNT(CASE WHEN status = 'Shipped' THEN 1 END) as shipped_orders,
    COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled_orders
    FROM orders 
    WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$order_stats = $stats_stmt->get_result()->fetch_assoc();

// Include header after all processing is done
$page_title = "My Orders";
include '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="profile.php">Profile</a></li>
                    <li class="breadcrumb-item active">My Orders</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error_message']); endif; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">📦 My Orders</h2>
            <p class="text-muted mb-0">Track and manage your orders</p>
        </div>
        <div>
            <a href="product-list.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Continue Shopping
            </a>
        </div>
    </div>

    <!-- Order Statistics -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <h4><?php echo $order_stats['total_orders'] ?? 0; ?></h4>
                    <p>Total Orders</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h4><?php echo $order_stats['delivered_orders'] ?? 0; ?></h4>
                    <p>Delivered</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <h4><?php echo $order_stats['shipped_orders'] ?? 0; ?></h4>
                    <p>Shipped</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h4><?php echo ($order_stats['pending_orders'] ?? 0) + ($order_stats['processing_orders'] ?? 0); ?></h4>
                    <p>Pending</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h4><?php echo $order_stats['cancelled_orders'] ?? 0; ?></h4>
                    <p>Cancelled</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-dark">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-info">
                    <h4><?php echo formatPrice($order_stats['total_spent'] ?? 0); ?></h4>
                    <p>Total Spent</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders List -->
    <?php if ($orders_result->num_rows > 0): ?>
    <div class="orders-container">
        <?php while ($order = $orders_result->fetch_assoc()): ?>
        <div class="order-card mb-4">
            <div class="order-header">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <h5 class="mb-1">Order #<?php echo $order['order_id']; ?></h5>
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('M d, Y g:i A', strtotime($order['order_date'])); ?>
                        </small>
                    </div>
                    <div class="col-md-2">
                        <span class="badge status-badge bg-<?php 
                            echo $order['status'] == 'Delivered' ? 'success' : 
                                ($order['status'] == 'Cancelled' ? 'danger' : 
                                ($order['status'] == 'Shipped' ? 'primary' : 
                                ($order['status'] == 'Processing' ? 'info' : 'warning'))); 
                        ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                    <div class="col-md-2">
                        <strong class="text-primary"><?php echo formatPrice($order['total_price']); ?></strong>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">
                            <i class="fas fa-credit-card me-1"></i>
                            <?php echo htmlspecialchars($order['payment_method']); ?>
                        </small>
                    </div>
                    <div class="col-md-3 text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="order-details.php?id=<?php echo $order['order_id']; ?>" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                            <?php if (in_array($order['status'], ['Pending', 'Processing'])): ?>
                            <a href="?cancel=<?php echo $order['order_id']; ?>" 
                               class="btn btn-outline-danger"
                               onclick="return confirm('Are you sure you want to cancel this order?')">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="order-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="order-info">
                            <div class="row">
                                <div class="col-sm-6">
                                    <strong>Shipping Address:</strong><br>
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></small>
                                </div>
                                <div class="col-sm-6">
                                    <strong>Customer Details:</strong><br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                        <?php echo htmlspecialchars($order['customer_email']); ?><br>
                                        <?php echo htmlspecialchars($order['customer_phone']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="order-progress">
                            <?php
                            $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered'];
                            $current_status = $order['status'];
                            $current_index = array_search($current_status, $statuses);
                            
                            if ($current_status == 'Cancelled') {
                                echo '<div class="text-center"><span class="badge bg-danger fs-6">Order Cancelled</span></div>';
                            } else {
                                echo '<div class="progress-steps">';
                                foreach ($statuses as $index => $status) {
                                    $is_active = $index <= $current_index;
                                    $icon_class = $is_active ? 'fas fa-check-circle text-success' : 'far fa-circle text-muted';
                                    echo '<div class="step ' . ($is_active ? 'active' : '') . '">';
                                    echo '<i class="' . $icon_class . '"></i> ';
                                    echo '<small>' . $status . '</small>';
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Orders pagination" class="mt-4">
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
    <?php endif; ?>

    <?php else: ?>
    <!-- No Orders -->
    <div class="text-center py-5">
        <div class="mb-4">
            <i class="fas fa-shopping-bag fa-5x text-muted"></i>
        </div>
        <h3 class="fw-bold mb-3">No Orders Yet</h3>
        <p class="text-muted mb-4">You haven't placed any orders yet. Start shopping to see your orders here!</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="product-list.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store me-2"></i>Start Shopping
            </a>
            <a href="../../index.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    height: 100%;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-info h4 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: bold;
}

.stat-info p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.order-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    border: none;
}

.order-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem;
    border-bottom: 1px solid #dee2e6;
}

.order-body {
    padding: 1.5rem;
}

.status-badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

.progress-steps {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.step {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.step.active {
    font-weight: 500;
}

.breadcrumb {
    background: none;
    padding: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #6c757d;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.pagination .page-link {
    border-radius: 8px;
    margin: 0 2px;
    border: none;
    color: #667eea;
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

@media (max-width: 768px) {
    .order-header .row > div {
        margin-bottom: 1rem;
    }
    
    .order-header .text-end {
        text-align: start !important;
    }
    
    .btn-group {
        width: 100%;
    }
    
    .btn-group .btn {
        flex: 1;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>
