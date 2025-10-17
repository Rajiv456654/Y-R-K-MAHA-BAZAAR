<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit();
}

// Date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Sales Overview
$sales_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_price) as total_revenue,
    AVG(total_price) as avg_order_value,
    COUNT(CASE WHEN status = 'Delivered' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled_orders,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_orders
    FROM orders 
    WHERE order_date BETWEEN ? AND ?";
$sales_stmt = $conn->prepare($sales_query);
$sales_stmt->bind_param("ss", $start_date, $end_date);
$sales_stmt->execute();
$sales_data = $sales_stmt->get_result()->fetch_assoc();

// Daily sales for chart
$daily_sales_query = "SELECT 
    DATE(order_date) as date,
    COUNT(*) as orders,
    SUM(total_price) as revenue
    FROM orders 
    WHERE order_date BETWEEN ? AND ?
    GROUP BY DATE(order_date)
    ORDER BY date";
$daily_stmt = $conn->prepare($daily_sales_query);
$daily_stmt->bind_param("ss", $start_date, $end_date);
$daily_stmt->execute();
$daily_sales = $daily_stmt->get_result();

// Top selling products
$top_products_query = "SELECT 
    p.name,
    p.price,
    SUM(oi.quantity) as total_sold,
    SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.order_date BETWEEN ? AND ?
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 10";
$top_products_stmt = $conn->prepare($top_products_query);
$top_products_stmt->bind_param("ss", $start_date, $end_date);
$top_products_stmt->execute();
$top_products = $top_products_stmt->get_result();

// Category performance
$category_query = "SELECT 
    c.category_name,
    COUNT(DISTINCT oi.order_id) as orders,
    SUM(oi.quantity) as items_sold,
    SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN categories c ON p.category_id = c.category_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.order_date BETWEEN ? AND ?
    GROUP BY c.category_id
    ORDER BY revenue DESC";
$category_stmt = $conn->prepare($category_query);
$category_stmt->bind_param("ss", $start_date, $end_date);
$category_stmt->execute();
$category_data = $category_stmt->get_result();

// Customer insights
$customer_query = "SELECT 
    COUNT(DISTINCT user_id) as total_customers,
    COUNT(*) / COUNT(DISTINCT user_id) as avg_orders_per_customer,
    MAX(total_price) as highest_order,
    MIN(total_price) as lowest_order
    FROM orders 
    WHERE order_date BETWEEN ? AND ?";
$customer_stmt = $conn->prepare($customer_query);
$customer_stmt->bind_param("ss", $start_date, $end_date);
$customer_stmt->execute();
$customer_data = $customer_stmt->get_result()->fetch_assoc();

// Top customers
$top_customers_query = "SELECT 
    u.name,
    u.email,
    COUNT(o.order_id) as total_orders,
    SUM(o.total_price) as total_spent
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_date BETWEEN ? AND ?
    GROUP BY o.user_id
    ORDER BY total_spent DESC
    LIMIT 10";
$top_customers_stmt = $conn->prepare($top_customers_query);
$top_customers_stmt->bind_param("ss", $start_date, $end_date);
$top_customers_stmt->execute();
$top_customers = $top_customers_stmt->get_result();

// Inventory insights
$inventory_query = "SELECT 
    COUNT(*) as total_products,
    COUNT(CASE WHEN stock <= 5 THEN 1 END) as low_stock_products,
    COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock_products,
    AVG(stock) as avg_stock_level
    FROM products 
    WHERE is_active = 1";
$inventory_data = $conn->query($inventory_query)->fetch_assoc();

// Include header after all processing is done
$page_title = "Reports & Analytics";
include 'includes/admin-header.php';
?>

<style>
.report-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: none;
    transition: transform 0.3s ease;
}

.report-card:hover {
    transform: translateY(-5px);
}

.report-card .card-title {
    color: white;
    font-weight: 600;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
    height: 100%;
}

.metric-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.metric-label {
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 0;
}

.chart-container {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.table-modern {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.table-modern th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    font-weight: 500;
    padding: 1rem;
}

.table-modern td {
    border: none;
    padding: 1rem;
    vertical-align: middle;
}

.table-modern tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}

.filter-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.btn-report {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    color: white;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-report:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    color: white;
}

.progress-modern {
    height: 8px;
    border-radius: 10px;
    background-color: #e9ecef;
}

.progress-modern .progress-bar {
    border-radius: 10px;
}
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">📊 Reports & Analytics</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button onclick="window.print()" class="btn btn-outline-primary me-2">
                <i class="fas fa-print me-1"></i>Print Report
            </button>
            <button onclick="exportToCSV()" class="btn btn-success">
                <i class="fas fa-download me-1"></i>Export CSV
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="report_type" class="form-label">Report Type</label>
                <select class="form-select" id="report_type" name="report_type">
                    <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>Overview</option>
                    <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>Sales</option>
                    <option value="products" <?php echo $report_type == 'products' ? 'selected' : ''; ?>>Products</option>
                    <option value="customers" <?php echo $report_type == 'customers' ? 'selected' : ''; ?>>Customers</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-report w-100">
                    <i class="fas fa-search me-1"></i>Generate Report
                </button>
            </div>
        </form>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-number text-primary"><?php echo number_format($sales_data['total_orders'] ?? 0); ?></div>
                        <div class="metric-label">Total Orders</div>
                    </div>
                    <div class="text-primary" style="font-size: 2rem;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-number text-success">₹<?php echo number_format($sales_data['total_revenue'] ?? 0); ?></div>
                        <div class="metric-label">Total Revenue</div>
                    </div>
                    <div class="text-success" style="font-size: 2rem;">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-number text-info">₹<?php echo number_format($sales_data['avg_order_value'] ?? 0); ?></div>
                        <div class="metric-label">Avg Order Value</div>
                    </div>
                    <div class="text-info" style="font-size: 2rem;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-number text-warning"><?php echo number_format($customer_data['total_customers'] ?? 0); ?></div>
                        <div class="metric-label">Active Customers</div>
                    </div>
                    <div class="text-warning" style="font-size: 2rem;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Sales Chart -->
        <div class="col-lg-8">
            <div class="chart-container">
                <h5 class="fw-bold mb-3">📈 Daily Sales Trend</h5>
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
        
        <!-- Order Status -->
        <div class="col-lg-4">
            <div class="chart-container">
                <h5 class="fw-bold mb-3">📊 Order Status</h5>
                <canvas id="statusChart" height="200"></canvas>
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Completed</span>
                        <span class="fw-bold text-success"><?php echo $sales_data['completed_orders'] ?? 0; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Pending</span>
                        <span class="fw-bold text-warning"><?php echo $sales_data['pending_orders'] ?? 0; ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Cancelled</span>
                        <span class="fw-bold text-danger"><?php echo $sales_data['cancelled_orders'] ?? 0; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables Row -->
    <div class="row">
        <!-- Top Products -->
        <div class="col-lg-6 mb-4">
            <div class="table-modern">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th colspan="4">🏆 Top Selling Products</th>
                        </tr>
                        <tr>
                            <th>Product</th>
                            <th>Sold</th>
                            <th>Revenue</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $max_sold = 0;
                        $products_array = [];
                        while ($product = $top_products->fetch_assoc()) {
                            $products_array[] = $product;
                            if ($product['total_sold'] > $max_sold) {
                                $max_sold = $product['total_sold'];
                            }
                        }
                        
                        foreach ($products_array as $product): 
                            $percentage = $max_sold > 0 ? ($product['total_sold'] / $max_sold) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            </td>
                            <td><?php echo $product['total_sold']; ?> units</td>
                            <td><?php echo formatPrice($product['total_revenue']); ?></td>
                            <td>
                                <div class="progress progress-modern">
                                    <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($products_array)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <i class="fas fa-box fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No sales data available for selected period</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="col-lg-6 mb-4">
            <div class="table-modern">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th colspan="4">👑 Top Customers</th>
                        </tr>
                        <tr>
                            <th>Customer</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $max_spent = 0;
                        $customers_array = [];
                        while ($customer = $top_customers->fetch_assoc()) {
                            $customers_array[] = $customer;
                            if ($customer['total_spent'] > $max_spent) {
                                $max_spent = $customer['total_spent'];
                            }
                        }
                        
                        foreach ($customers_array as $customer): 
                            $percentage = $max_spent > 0 ? ($customer['total_spent'] / $max_spent) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($customer['name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                            </td>
                            <td><?php echo $customer['total_orders']; ?></td>
                            <td><?php echo formatPrice($customer['total_spent']); ?></td>
                            <td>
                                <div class="progress progress-modern">
                                    <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($customers_array)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No customer data available for selected period</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Category Performance -->
    <div class="row">
        <div class="col-12">
            <div class="table-modern">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th colspan="5">📂 Category Performance</th>
                        </tr>
                        <tr>
                            <th>Category</th>
                            <th>Orders</th>
                            <th>Items Sold</th>
                            <th>Revenue</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $max_revenue = 0;
                        $categories_array = [];
                        while ($category = $category_data->fetch_assoc()) {
                            $categories_array[] = $category;
                            if ($category['revenue'] > $max_revenue) {
                                $max_revenue = $category['revenue'];
                            }
                        }
                        
                        foreach ($categories_array as $category): 
                            $percentage = $max_revenue > 0 ? ($category['revenue'] / $max_revenue) * 100 : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                            <td><?php echo $category['orders']; ?></td>
                            <td><?php echo $category['items_sold']; ?> units</td>
                            <td><?php echo formatPrice($category['revenue']); ?></td>
                            <td>
                                <div class="progress progress-modern">
                                    <div class="progress-bar bg-info" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($categories_array)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No category data available for selected period</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesData = {
    labels: [
        <?php 
        $daily_sales->data_seek(0);
        $dates = [];
        $revenues = [];
        while ($day = $daily_sales->fetch_assoc()) {
            $dates[] = "'" . date('M d', strtotime($day['date'])) . "'";
            $revenues[] = $day['revenue'];
        }
        echo implode(',', $dates);
        ?>
    ],
    datasets: [{
        label: 'Daily Revenue',
        data: [<?php echo implode(',', $revenues); ?>],
        borderColor: 'rgb(102, 126, 234)',
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        tension: 0.4,
        fill: true
    }]
};

new Chart(salesCtx, {
    type: 'line',
    data: salesData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'Pending', 'Cancelled'],
        datasets: [{
            data: [
                <?php echo $sales_data['completed_orders'] ?? 0; ?>,
                <?php echo $sales_data['pending_orders'] ?? 0; ?>,
                <?php echo $sales_data['cancelled_orders'] ?? 0; ?>
            ],
            backgroundColor: [
                '#28a745',
                '#ffc107',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

function exportToCSV() {
    // Simple CSV export functionality
    alert('CSV export functionality would be implemented here');
}
</script>

<?php include 'includes/admin-footer.php'; ?>
