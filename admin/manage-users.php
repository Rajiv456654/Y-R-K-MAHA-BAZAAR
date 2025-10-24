<?php
$page_title = "Manage Users";
include 'includes/admin-header.php';

// Handle user status toggle
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $user_id = (int)$_GET['toggle_status'];

    // Get current status
    $status_query = "SELECT is_active FROM users WHERE user_id = ?";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->execute([$user_id]);
    $current_status = $status_stmt->fetch(PDO::FETCH_ASSOC)['is_active'];

    // Convert to proper boolean (PostgreSQL might return string 't'/'f' or 1/0)
    $current_status_bool = (bool)$current_status;

    // Toggle status using proper PostgreSQL boolean keywords
    $new_status = $current_status_bool ? FALSE : TRUE;
    $update_query = "UPDATE users SET is_active = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->execute([$new_status, $user_id]);

    if ($update_stmt->rowCount() > 0) {
        $action = $new_status ? 'activated' : 'deactivated';
        $_SESSION['success_message'] = "User $action successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update user status.";
    }

    header("Location: manage-users.php");
    exit();
}

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
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}

if ($status_filter === 'active') {
    $where_conditions[] = "u.is_active = TRUE";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "u.is_active = FALSE";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(u.created_at) >= ?";
    $params[] = $date_from;
    $param_types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(u.created_at) <= ?";
    $params[] = $date_to;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users u WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->execute($params);
} else {
    $count_stmt->execute();
}
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get users with order statistics
$query = "SELECT u.user_id, u.name, u.email, u.phone, u.address, u.is_active, u.created_at,
          COUNT(DISTINCT o.order_id) as total_orders,
          COALESCE(SUM(CASE WHEN o.status != 'Cancelled' THEN o.total_price ELSE 0 END), 0) as total_spent
          FROM users u
          LEFT JOIN orders o ON u.user_id = o.user_id
          WHERE $where_clause
          GROUP BY u.user_id, u.name, u.email, u.phone, u.address, u.is_active, u.created_at
          ORDER BY u.created_at DESC
          LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$users_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Users</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-download me-1"></i>Export Users
            </button>
        </div>
    </div>
</div>

<!-- User Statistics -->
<div class="row mb-4">
    <?php
    $stats_query = "SELECT
                    COUNT(*) as total_users,
                    SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN is_active = FALSE THEN 1 ELSE 0 END) as inactive_users,
                    SUM(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '30 days' THEN 1 ELSE 0 END) as new_users
                    FROM users";
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6>Total Users</h6>
                        <h3><?php echo number_format($stats['total_users'] ?? 0); ?></h3>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6>Active Users</h6>
                        <h3><?php echo number_format($stats['active_users'] ?? 0); ?></h3>
                    </div>
                    <i class="fas fa-user-check fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6>Inactive Users</h6>
                        <h3><?php echo number_format($stats['inactive_users'] ?? 0); ?></h3>
                    </div>
                    <i class="fas fa-user-times fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6>New Users (30d)</h6>
                        <h3><?php echo number_format($stats['new_users'] ?? 0); ?></h3>
                    </div>
                    <i class="fas fa-user-plus fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search Users</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Name, email, phone...">
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
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
            
            <div class="col-md-2">
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

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Users (<?php echo $total_records; ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (count($users_result) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Contact</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_result as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    <br>
                                    <small class="text-muted">ID: <?php echo $user['user_id']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                                <br>
                                <?php if ($user['phone']): ?>
                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($user['phone']); ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo $user['total_orders']; ?> orders</span>
                        </td>
                        <td>
                            <strong><?php echo formatPrice($user['total_spent'] ?? 0); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            <br>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($user['created_at'])); ?></small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-info" 
                                        data-bs-toggle="collapse" data-bs-target="#user-<?php echo $user['user_id']; ?>"
                                        title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="?toggle_status=<?php echo $user['user_id']; ?>" 
                                   class="btn btn-outline-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>"
                                   title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                   onclick="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')">
                                    <i class="fas fa-<?php echo $user['is_active'] ? 'user-slash' : 'user-check'; ?>"></i>
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- User Details Row -->
                    <tr class="collapse" id="user-<?php echo $user['user_id']; ?>">
                        <td colspan="7">
                            <div class="p-3 bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Address:</h6>
                                        <address>
                                            <?php echo $user['address'] ? nl2br(htmlspecialchars($user['address'])) : 'No address provided'; ?>
                                        </address>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Recent Orders:</h6>
                                        <?php
                                        // Get recent orders for this user
                                        $recent_orders_query = "SELECT order_id, total_price, status, order_date 
                                                               FROM orders 
                                                               WHERE user_id = ?
                                                               ORDER BY order_date DESC 
                                                               LIMIT 3";
                                        $recent_orders_stmt = $conn->prepare($recent_orders_query);
                                        $recent_orders_stmt->execute([$user['user_id']]);
                                        $recent_orders_result = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        ?>
                                        
                                        <?php if (count($recent_orders_result) > 0): ?>
                                        <ul class="list-unstyled">
                                            <?php foreach ($recent_orders_result as $order): ?>
                                            <li class="mb-1">
                                                <strong>#<?php echo $order['order_id']; ?></strong> - 
                                                <?php echo formatPrice($order['total_price'] ?? 0); ?>
                                                <span class="badge bg-<?php echo getOrderStatusColor($order['status']); ?> ms-1">
                                                    <?php echo $order['status']; ?>
                                                </span>
                                                <br>
                                                <small class="text-muted"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></small>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php else: ?>
                                        <p class="text-muted">No orders yet</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <h5>No users found</h5>
            <p class="text-muted">Try adjusting your search criteria.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Users pagination" class="mt-4">
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

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
</style>

<?php
function getOrderStatusColor($status) {
    switch($status) {
        case 'Pending': return 'warning';
        case 'Processing': return 'info';
        case 'Shipped': return 'primary';
        case 'Delivered': return 'success';
        case 'Cancelled': return 'danger';
        default: return 'secondary';
    }
}

include 'includes/admin-footer.php';
?>
