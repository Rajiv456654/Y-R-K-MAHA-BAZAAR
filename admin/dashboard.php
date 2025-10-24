<?php
$page_title = "Dashboard";
include 'includes/admin-header.php';

// Get dashboard statistics
$stats = [];

// Total products
$products_query = "SELECT COUNT(*) as total FROM products WHERE is_active = TRUE";
$products_stmt = $conn->prepare($products_query);
$products_stmt->execute();
$stats['total_products'] = $products_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total orders
$orders_query = "SELECT COUNT(*) as total FROM orders";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->execute();
$stats['total_orders'] = $orders_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total users
$users_query = "SELECT COUNT(*) as total FROM users WHERE is_active = TRUE";
$users_stmt = $conn->prepare($users_query);
$users_stmt->execute();
$stats['total_users'] = $users_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total revenue
$revenue_query = "SELECT SUM(total_price) as total FROM orders WHERE status != 'Cancelled'";
$revenue_stmt = $conn->prepare($revenue_query);
$revenue_stmt->execute();
$stats['total_revenue'] = $revenue_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;

// Recent orders
$recent_orders_query = "SELECT o.*, u.name as customer_name
                        FROM orders o
                        LEFT JOIN users u ON o.user_id = u.user_id
                        ORDER BY o.order_date DESC
                        LIMIT 5";
$recent_orders_stmt = $conn->prepare($recent_orders_query);
$recent_orders_stmt->execute();
$recent_orders_result = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock products
$low_stock_query = "SELECT * FROM products WHERE stock <= 5 AND is_active = TRUE ORDER BY stock ASC LIMIT 5";
$low_stock_stmt = $conn->prepare($low_stock_query);
$low_stock_stmt->execute();
$low_stock_result = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

// Order status distribution
$status_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$status_stmt = $conn->prepare($status_query);
$status_stmt->execute();
$order_status = [];
while ($row = $status_stmt->fetch(PDO::FETCH_ASSOC)) {
    $order_status[$row['status']] = $row['count'];
}

// Monthly sales data (last 6 months)
$monthly_sales_query = "SELECT
                        DATE_TRUNC('month', order_date) as month,
                        SUM(total_price) as sales,
                        COUNT(*) as orders
                        FROM orders
                        WHERE order_date >= NOW() - INTERVAL '6 MONTH'
                        AND status != 'Cancelled'
                        GROUP BY DATE_TRUNC('month', order_date)
                        ORDER BY month DESC";
$monthly_sales_stmt = $conn->prepare($monthly_sales_query);
$monthly_sales_stmt->execute();
$monthly_data = [];
while ($row = $monthly_sales_stmt->fetch(PDO::FETCH_ASSOC)) {
    $monthly_data[] = $row;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-download me-1"></i>Export
            </button>
        </div>
        <button type="button" class="btn btn-sm btn-primary">
            <i class="fas fa-calendar me-1"></i>This week
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Total Products</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_products']); ?></h2>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-box fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Total Orders</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_orders']); ?></h2>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card success h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Total Users</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_users']); ?></h2>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card info h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Total Revenue</h6>
                        <h2 class="mb-0"><?php echo formatPrice($stats['total_revenue']); ?></h2>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-rupee-sign fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Charts -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Sales Overview</h5>
            </div>
            <div class="card-body">
                <canvas id="salesChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Order Status Distribution -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Order Status</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" width="200" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Orders -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Orders</h5>
                <a href="manage-orders.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_orders_result) > 0): ?>
                                <?php foreach ($recent_orders_result as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?: $order['customer_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo formatPrice($order['total_price']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusColor($order['status']); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No recent orders</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Low Stock Products -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock</h5>
                <a href="manage-products.php" class="btn btn-sm btn-outline-warning">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (count($low_stock_result) > 0): ?>
                        <?php foreach ($low_stock_result as $product): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <small class="text-muted">Stock: <?php echo $product['stock']; ?></small>
                            </div>
                            <span class="badge bg-warning rounded-pill"><?php echo $product['stock']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="list-group-item text-center text-muted">
                        All products are well stocked
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

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

// Prepare data for charts
$monthly_labels = [];
$monthly_sales = [];
foreach (array_reverse($monthly_data) as $data) {
    $monthly_labels[] = date('M Y', strtotime($data['month']));
    $monthly_sales[] = $data['sales'];
}

$status_labels = array_keys($order_status);
$status_data = array_values($order_status);

$additional_scripts = "
<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: " . json_encode($monthly_labels) . ",
        datasets: [{
            label: 'Sales (₹)',
            data: " . json_encode($monthly_sales) . ",
            borderColor: '#E50914',
            backgroundColor: 'rgba(229, 9, 20, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: " . json_encode($status_labels) . ",
        datasets: [{
            data: " . json_encode($status_data) . ",
            backgroundColor: [
                '#ffc107',
                '#17a2b8',
                '#E50914',
                '#28a745',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
";

include 'includes/admin-footer.php';
?>
