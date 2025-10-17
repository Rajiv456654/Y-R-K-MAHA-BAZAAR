<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email)) {
        $error_message = "Username and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if username/email is already taken by another admin
        $check_query = "SELECT admin_id FROM admin WHERE (username = ? OR email = ?) AND admin_id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ssi", $username, $email, $admin_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = "Username or email is already taken by another admin.";
        } else {
            // Update basic info
            $update_query = "UPDATE admin SET username = ?, email = ? WHERE admin_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssi", $username, $email, $admin_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Profile updated successfully!";
                
                // Handle password change if provided
                if (!empty($current_password) && !empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        $error_message = "New passwords do not match.";
                    } elseif (strlen($new_password) < 6) {
                        $error_message = "New password must be at least 6 characters long.";
                    } else {
                        // Verify current password
                        $pass_query = "SELECT password FROM admin WHERE admin_id = ?";
                        $pass_stmt = $conn->prepare($pass_query);
                        $pass_stmt->bind_param("i", $admin_id);
                        $pass_stmt->execute();
                        $current_hash = $pass_stmt->get_result()->fetch_assoc()['password'];
                        
                        if (password_verify($current_password, $current_hash)) {
                            // Update password
                            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $pass_update = "UPDATE admin SET password = ? WHERE admin_id = ?";
                            $pass_update_stmt = $conn->prepare($pass_update);
                            $pass_update_stmt->bind_param("si", $new_hash, $admin_id);
                            
                            if ($pass_update_stmt->execute()) {
                                $success_message = "Profile and password updated successfully!";
                            } else {
                                $error_message = "Profile updated but failed to change password.";
                            }
                        } else {
                            $error_message = "Current password is incorrect.";
                        }
                    }
                }
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
        }
    }
}

// Get admin info
$admin_query = "SELECT * FROM admin WHERE admin_id = ?";
$admin_stmt = $conn->prepare($admin_query);
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_info = $admin_stmt->get_result()->fetch_assoc();

// Get admin activity statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM products) as total_products,
    (SELECT COUNT(*) FROM orders) as total_orders,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM categories) as total_categories,
    (SELECT SUM(total_price) FROM orders WHERE status = 'Delivered') as total_revenue";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get recent admin activities (this would require an admin_logs table in a real system)
$recent_activities = [
    ['action' => 'Product Added', 'details' => 'Added new product "Sample Product"', 'time' => '2 hours ago'],
    ['action' => 'Order Updated', 'details' => 'Updated order #1001 status to Shipped', 'time' => '4 hours ago'],
    ['action' => 'User Management', 'details' => 'Activated user account', 'time' => '1 day ago'],
    ['action' => 'Category Added', 'details' => 'Created new category "Electronics"', 'time' => '2 days ago'],
    ['action' => 'Settings Updated', 'details' => 'Modified system settings', 'time' => '3 days ago']
];

// Include header after all processing is done
$page_title = "Admin Profile";
include 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">👤 Admin Profile</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="settings.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-cog me-1"></i>Settings
            </a>
            <a href="index.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-dashboard me-1"></i>Dashboard
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Profile Sidebar -->
    <div class="col-lg-4 mb-4">
        <!-- Admin Info Card -->
        <div class="card admin-profile-card">
            <div class="card-body text-center">
                <div class="admin-avatar mb-3">
                    <div class="avatar-circle">
                        <?php echo strtoupper(substr($admin_info['username'], 0, 2)); ?>
                    </div>
                    <div class="admin-status">
                        <i class="fas fa-shield-alt text-success"></i>
                    </div>
                </div>
                <h4 class="fw-bold"><?php echo htmlspecialchars($admin_info['username']); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($admin_info['email']); ?></p>
                <div class="admin-since">
                    <small class="text-muted">
                        <i class="fas fa-calendar-alt me-1"></i>
                        Admin since <?php echo date('M Y', strtotime($admin_info['created_at'])); ?>
                    </small>
                </div>
                <div class="admin-badges mt-3">
                    <span class="badge bg-primary">🛡️ Administrator</span>
                    <span class="badge bg-success">✅ Active</span>
                </div>
            </div>
        </div>

        <!-- System Statistics -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>System Overview</h5>
            </div>
            <div class="card-body">
                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-box text-primary me-2"></i>Total Products</span>
                        <span class="fw-bold"><?php echo number_format($stats['total_products'] ?? 0); ?></span>
                    </div>
                </div>
                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-shopping-cart text-success me-2"></i>Total Orders</span>
                        <span class="fw-bold"><?php echo number_format($stats['total_orders'] ?? 0); ?></span>
                    </div>
                </div>
                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-users text-info me-2"></i>Total Users</span>
                        <span class="fw-bold"><?php echo number_format($stats['total_users'] ?? 0); ?></span>
                    </div>
                </div>
                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-tags text-warning me-2"></i>Categories</span>
                        <span class="fw-bold"><?php echo number_format($stats['total_categories'] ?? 0); ?></span>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-rupee-sign text-danger me-2"></i>Total Revenue</span>
                        <span class="fw-bold"><?php echo formatPrice($stats['total_revenue'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="add-product.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>Add Product
                    </a>
                    <a href="manage-orders.php" class="btn btn-outline-success">
                        <i class="fas fa-list me-2"></i>Manage Orders
                    </a>
                    <a href="manage-users.php" class="btn btn-outline-info">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                    <a href="reports.php" class="btn btn-outline-warning">
                        <i class="fas fa-chart-line me-2"></i>View Reports
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activities</h5>
            </div>
            <div class="card-body">
                <?php foreach (array_slice($recent_activities, 0, 5) as $activity): ?>
                <div class="activity-item mb-3">
                    <div class="d-flex">
                        <div class="activity-icon me-3">
                            <i class="fas fa-circle text-primary"></i>
                        </div>
                        <div class="activity-content">
                            <strong><?php echo $activity['action']; ?></strong><br>
                            <small class="text-muted"><?php echo $activity['details']; ?></small><br>
                            <small class="text-muted"><?php echo $activity['time']; ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Profile Form -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Admin Profile</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <!-- Basic Information -->
                    <div class="section-header mb-4">
                        <h5 class="fw-bold text-primary">👤 Basic Information</h5>
                        <p class="text-muted small">Update your admin account details</p>
                        <hr>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($admin_info['username']); ?>" required>
                            <div class="invalid-feedback">Please enter a username.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($admin_info['email']); ?>" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="section-header mb-4 mt-5">
                        <h5 class="fw-bold text-primary">🔒 Security Settings</h5>
                        <p class="text-muted small">Change your password (leave blank if you don't want to change)</p>
                        <hr>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6">
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="section-header mb-4 mt-5">
                        <h5 class="fw-bold text-primary">ℹ️ Account Information</h5>
                        <hr>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Account Created</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo date('F d, Y g:i A', strtotime($admin_info['created_at'])); ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Login</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo isset($admin_info['last_login']) ? date('F d, Y g:i A', strtotime($admin_info['last_login'])) : 'Never'; ?>" readonly>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <small class="text-muted">* Required fields</small>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- System Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-server me-2"></i>System Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <strong>PHP Version:</strong><br>
                            <span class="text-muted"><?php echo phpversion(); ?></span>
                        </div>
                        <div class="info-item mb-3">
                            <strong>Server Software:</strong><br>
                            <span class="text-muted"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                        </div>
                        <div class="info-item mb-3">
                            <strong>Database:</strong><br>
                            <span class="text-muted">MySQL <?php echo $conn->server_info; ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <strong>Server Time:</strong><br>
                            <span class="text-muted"><?php echo date('Y-m-d H:i:s'); ?></span>
                        </div>
                        <div class="info-item mb-3">
                            <strong>Memory Limit:</strong><br>
                            <span class="text-muted"><?php echo ini_get('memory_limit'); ?></span>
                        </div>
                        <div class="info-item mb-3">
                            <strong>Upload Max Size:</strong><br>
                            <span class="text-muted"><?php echo ini_get('upload_max_filesize'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('new_password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        confirmPassword.dispatchEvent(new Event('input'));
    }
});
</script>

<style>
.admin-profile-card {
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
}

.admin-avatar {
    position: relative;
    display: inline-block;
}

.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    font-weight: bold;
    margin: 0 auto;
}

.admin-status {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: white;
    border-radius: 50%;
    padding: 3px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.admin-badges .badge {
    margin: 0 0.25rem;
    font-size: 0.75rem;
}

.stat-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.stat-item:last-child {
    border-bottom: none;
}

.activity-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    margin-top: 0.25rem;
}

.info-item {
    padding: 0.5rem 0;
}

.section-header h5 {
    margin-bottom: 0.5rem;
}

.card {
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
}

.card-header {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    color: white;
    border-radius: 15px 15px 0 0 !important;
    border: none;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
}

.form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

@media (max-width: 768px) {
    .avatar-circle {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }
    
    .admin-badges .badge {
        font-size: 0.65rem;
        margin: 0.1rem;
    }
}
</style>

<?php include 'includes/admin-footer.php'; ?>
