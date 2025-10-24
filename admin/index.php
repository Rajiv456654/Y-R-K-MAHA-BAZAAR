<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit();
}

// Get dashboard statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM products WHERE is_active = TRUE) as total_products,
    (SELECT COUNT(*) FROM categories) as total_categories,
    (SELECT COUNT(*) FROM users WHERE is_active = TRUE) as total_users,
    (SELECT COUNT(*) FROM orders) as total_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'Pending') as pending_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'Processing') as processing_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'Shipped') as shipped_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'Delivered') as delivered_orders,
    (SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Delivered') as total_revenue";

try {
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Try to get new messages count separately (in case table doesn't exist)
    try {
        $messages_query = "SELECT COUNT(*) as new_messages FROM contact_messages WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'";
        $messages_stmt = $conn->prepare($messages_query);
        $messages_stmt->execute();
        $messages_result = $messages_stmt->fetch(PDO::FETCH_ASSOC);
        if ($messages_result) {
            $stats['new_messages'] = $messages_result['new_messages'];
        } else {
            $stats['new_messages'] = 0;
        }
    } catch (Exception $e) {
        $stats['new_messages'] = 0;
    }
    
} catch (Exception $e) {
    // If there's an error, set default values
    $stats = [
        'total_products' => 0,
        'total_categories' => 0,
        'total_users' => 0,
        'total_orders' => 0,
        'pending_orders' => 0,
        'processing_orders' => 0,
        'shipped_orders' => 0,
        'delivered_orders' => 0,
        'total_revenue' => 0,
        'new_messages' => 0
    ];
}

// Get recent orders
try {
    $recent_orders_query = "SELECT o.*, u.name as customer_name
                            FROM orders o
                            JOIN users u ON o.user_id = u.user_id
                            ORDER BY o.order_date DESC
                            LIMIT 5";
    $recent_orders_stmt = $conn->prepare($recent_orders_query);
    $recent_orders_stmt->execute();
    $recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_orders = [];
}

// Get low stock products
try {
    $low_stock_query = "SELECT * FROM products WHERE stock <= 5 AND is_active = TRUE ORDER BY stock ASC LIMIT 5";
    $low_stock_stmt = $conn->prepare($low_stock_query);
    $low_stock_stmt->execute();
    $low_stock = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $low_stock = [];
}

$page_title = "Admin Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Y R K MAHA BAZAAR</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .admin-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 2rem;
            padding: 0;
            overflow: hidden;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            opacity: 0.3;
        }
        
        .admin-header > * {
            position: relative;
            z-index: 1;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            position: relative;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0;
        }
        
        .stat-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
        
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: white;
            padding: 1rem 1.5rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin: 0.5rem;
            font-weight: 500;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .recent-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .table-modern {
            border: none;
            border-radius: 10px;
            overflow: hidden;
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
        
        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        
        .admin-nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1rem 2rem;
            margin: 1rem 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                margin: 1rem;
            }
            
            .admin-nav {
                margin: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
                margin: 0.25rem 0;
            }
        }
        
        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .welcome-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <a href="index.php" class="admin-brand">
            🏪 Y R K MAHA BAZAAR Admin
        </a>
        <div class="admin-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?>
            </div>
            <div>
                <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                <small class="text-muted">Administrator</small>
            </div>
            <a href="admin-logout.php" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1 class="display-5 fw-bold mb-3">🎯 Admin Dashboard</h1>
            <p class="lead mb-0">Welcome back! Here's what's happening with your store today.</p>
        </div>

        <div class="container-fluid p-4">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-icon">👋</div>
                <h3 class="fw-bold mb-3">Welcome to Your Dashboard!</h3>
                <p class="text-muted mb-4">Manage your e-commerce store efficiently with our comprehensive admin panel.</p>
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="fw-bold text-primary fs-4"><?php echo date('H:i'); ?></div>
                        <small class="text-muted">Current Time</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-success fs-4"><?php echo date('M d'); ?></div>
                        <small class="text-muted">Today's Date</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-info fs-4"><?php echo $stats['pending_orders']; ?></div>
                        <small class="text-muted">Pending Orders</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-warning fs-4"><?php echo $stats['new_messages']; ?></div>
                        <small class="text-muted">New Messages</small>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number text-primary"><?php echo number_format($stats['total_products'] ?? 0); ?></div>
                                <div class="stat-label">Total Products</div>
                            </div>
                            <div class="stat-icon text-primary">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number text-success"><?php echo number_format($stats['total_orders'] ?? 0); ?></div>
                                <div class="stat-label">Total Orders</div>
                            </div>
                            <div class="stat-icon text-success">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number text-info"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
                                <div class="stat-label">Total Customers</div>
                            </div>
                            <div class="stat-icon text-info">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number text-warning">₹<?php echo number_format($stats['total_revenue'] ?? 0); ?></div>
                                <div class="stat-label">Total Revenue</div>
                            </div>
                            <div class="stat-icon text-warning">
                                <i class="fas fa-rupee-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h4 class="fw-bold mb-4">🚀 Quick Actions</h4>
                <div class="text-center">
                    <a href="manage-products.php" class="action-btn">
                        <i class="fas fa-plus"></i> Add Product
                    </a>
                    <a href="manage-categories.php" class="action-btn">
                        <i class="fas fa-tags"></i> Manage Categories
                    </a>
                    <a href="manage-orders.php" class="action-btn">
                        <i class="fas fa-clipboard-list"></i> View Orders
                    </a>
                    <a href="manage-users.php" class="action-btn">
                        <i class="fas fa-user-friends"></i> Manage Users
                    </a>
                    <a href="contact-messages.php" class="action-btn">
                        <i class="fas fa-envelope"></i> Messages
                    </a>
                    <a href="../index.php" class="action-btn">
                        <i class="fas fa-globe"></i> View Website
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Recent Orders -->
                <div class="col-lg-8">
                    <div class="recent-section">
                        <h4 class="fw-bold mb-4">📋 Recent Orders</h4>
                        <?php if (count($recent_orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td class="fw-bold">#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td class="fw-bold text-success">₹<?php echo number_format($order['total_price'] ?? 0); ?></td>
                                        <td>
                                            <span class="badge badge-status bg-<?php
                                                echo $order['status'] == 'Delivered' ? 'success' :
                                                    ($order['status'] == 'Cancelled' ? 'danger' :
                                                    ($order['status'] == 'Shipped' ? 'primary' :
                                                    ($order['status'] == 'Processing' ? 'info' : 'warning')));
                                            ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5>No orders yet</h5>
                            <p class="text-muted">Orders will appear here once customers start purchasing.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="col-lg-4">
                    <div class="recent-section">
                        <h4 class="fw-bold mb-4">⚠️ Low Stock Alert</h4>
                        <?php if (count($low_stock) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($low_stock as $product): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <small class="text-muted">₹<?php echo number_format($product['price']); ?></small>
                                    </div>
                                    <span class="badge bg-<?php echo $product['stock'] == 0 ? 'danger' : 'warning'; ?> rounded-pill">
                                        <?php echo $product['stock']; ?> left
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="manage-products.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye"></i> View All Products
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h6>All products well stocked!</h6>
                            <p class="text-muted small">No low stock alerts at the moment.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-icon">👋</div>
                <h3 class="fw-bold mb-3">Welcome to Your Dashboard!</h3>
                <p class="text-muted mb-4">Manage your e-commerce store efficiently with our comprehensive admin panel.</p>
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="fw-bold text-primary fs-4"><?php echo date('H:i'); ?></div>
                        <small class="text-muted">Current Time</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-success fs-4"><?php echo date('M d'); ?></div>
                        <small class="text-muted">Today's Date</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-info fs-4"><?php echo $stats['pending_orders']; ?></div>
                        <small class="text-muted">Pending Orders</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-warning fs-4"><?php echo $stats['new_messages']; ?></div>
                        <small class="text-muted">New Messages</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat cards on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Update time every minute
            setInterval(() => {
                const timeElement = document.querySelector('.fs-4');
                if (timeElement) {
                    const now = new Date();
                    timeElement.textContent = now.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            }, 60000);
        });
    </script>
</body>
</html>
