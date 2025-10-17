<?php
ob_start(); // Start output buffering
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitizeInput($_POST['status']);

    $valid_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

    if (in_array($new_status, $valid_statuses)) {
        $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $order_id);

        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Order status updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update order status.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid status selected.";
    }

    header("Location: manage-orders.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Get filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Build query
$where_conditions = ["1=1"];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_conditions[] = "(o.order_id LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(o.order_date) >= ?";
    $params[] = $date_from;
    $param_types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(o.order_date) <= ?";
    $params[] = $date_to;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM orders o WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!$count_stmt) {
    die("Count query preparation failed: " . $conn->error);
}

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

if (!$count_stmt->execute()) {
    die("Count query execution failed: " . $count_stmt->error);
}

$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get orders
$query = "SELECT o.*, u.name as user_name, COUNT(oi.item_id) as item_count 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.user_id 
          LEFT JOIN order_items oi ON o.order_id = oi.order_id 
          WHERE $where_clause 
          GROUP BY o.order_id 
          ORDER BY o.order_date DESC 
          LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Database query preparation failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

if (!$stmt->execute()) {
    die("Database query execution failed: " . $stmt->error);
}

$orders_result = $stmt->get_result();

// Now output HTML after PHP processing
$page_title = "Manage Orders";
include 'includes/admin-header.php';
?>

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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Orders</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-download me-1"></i>Export
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search Orders</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Order ID, customer name, email...">
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Processing" <?php echo $status_filter == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="Shipped" <?php echo $status_filter == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="Delivered" <?php echo $status_filter == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Orders (<?php echo $total_records; ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if ($orders_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong>#<?php echo $order['order_id']; ?></strong>
                        </td>
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo $order['item_count']; ?> items</span>
                        </td>
                        <td>
                            <strong><?php echo formatPrice($order['total_price']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo htmlspecialchars($order['payment_method']); ?></small>
                        </td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <select name="status" class="form-select form-select-sm status-select" 
                                        onchange="this.form.submit()" style="width: auto;">
                                    <option value="Pending" <?php echo $order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Processing" <?php echo $order['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Shipped" <?php echo $order['status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="Delivered" <?php echo $order['status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="Cancelled" <?php echo $order['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                            <br>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($order['order_date'])); ?></small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-info" 
                                        data-bs-toggle="collapse" data-bs-target="#order-<?php echo $order['order_id']; ?>"
                                        title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="order-invoice.php?order_id=<?php echo $order['order_id']; ?>" 
                                   class="btn btn-outline-primary" target="_blank" title="Invoice">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Order Details Row -->
                    <tr class="collapse" id="order-<?php echo $order['order_id']; ?>">
                        <td colspan="7">
                            <div class="p-3 bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Shipping Address:</h6>
                                        <address><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></address>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Order Items:</h6>
                                        <?php
                                        // Get order items
                                        $items_query = "SELECT oi.*, p.name 
                                                       FROM order_items oi 
                                                       JOIN products p ON oi.product_id = p.product_id 
                                                       WHERE oi.order_id = ?";
                                        $items_stmt = $conn->prepare($items_query);
                                        $items_stmt->bind_param("i", $order['order_id']);
                                        $items_stmt->execute();
                                        $items_result = $items_stmt->get_result();
                                        ?>
                                        <ul class="list-unstyled">
                                            <?php while ($item = $items_result->fetch_assoc()): ?>
                                            <li class="mb-1">
                                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                <br>
                                                <small>Qty: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?> = <?php echo formatPrice($item['quantity'] * $item['price']); ?></small>
                                            </li>
                                            <?php endwhile; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
            <h5>No orders found</h5>
            <p class="text-muted">Try adjusting your search criteria.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Orders pagination" class="mt-4">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        </li>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                <?php echo $i; ?>
            </a>
        </li>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<script>
// Confirm status change
document.querySelectorAll('.status-select').forEach(function(select) {
    select.addEventListener('change', function(e) {
        const newStatus = this.value;
        const orderId = this.form.querySelector('input[name="order_id"]').value;
        
        if (!confirm(`Are you sure you want to change the order status to "${newStatus}"?`)) {
            e.preventDefault();
            // Reset to original value
            this.value = this.getAttribute('data-original-value') || 'Pending';
            return false;
        }
    });
    
    // Store original value
    select.setAttribute('data-original-value', select.value);
});
</script>

<?php include 'includes/admin-footer.php'; ?>
<?php ob_end_flush(); ?>
