<?php
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';
startSession();

// Require login
requireLogin();

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if email is already taken by another user
        $email_check = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $email_stmt = $conn->prepare($email_check);
        $email_stmt->execute([$email, $user_id]);

        if ($email_stmt->fetch(PDO::FETCH_ASSOC)) {
            $error_message = "This email is already registered by another user.";
        } else {
            // Update basic info
            $update_query = "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->execute([$name, $email, $phone, $address, $user_id]);

            if ($update_stmt->rowCount() > 0) {
                $success_message = "Profile updated successfully!";
                
                // Handle password change if provided
                if (!empty($current_password) && !empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        $error_message = "New passwords do not match.";
                    } elseif (strlen($new_password) < 6) {
                        $error_message = "New password must be at least 6 characters long.";
                    } else {
                        // Verify current password
                        $pass_query = "SELECT password FROM users WHERE user_id = ?";
                        $pass_stmt = $conn->prepare($pass_query);
                        $pass_stmt->execute([$user_id]);
                        $current_hash = $pass_stmt->fetch(PDO::FETCH_ASSOC)['password'];

                        if (password_verify($current_password, $current_hash)) {
                            // Update password
                            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $pass_update = "UPDATE users SET password = ? WHERE user_id = ?";
                            $pass_update_stmt = $conn->prepare($pass_update);
                            $pass_update_stmt->execute([$new_hash, $user_id]);

                            if ($pass_update_stmt->rowCount() > 0) {
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

// Get user info
$user_info = getUserInfo($user_id);

// Get user statistics
$stats_query = "SELECT
    COUNT(*) as total_orders,
    SUM(total_price) as total_spent,
    COUNT(CASE WHEN status = 'Delivered' THEN TRUE END) as completed_orders
    FROM orders
    WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute([$user_id]);
$user_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Include header after all processing is done
$page_title = "My Profile";
include '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Home</a></li>
                    <li class="breadcrumb-item active">My Profile</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-lg-4 mb-4">
            <div class="card profile-sidebar">
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <div class="avatar-circle">
                            <?php echo strtoupper(substr($user_info['name'], 0, 2)); ?>
                        </div>
                    </div>
                    <h4 class="fw-bold"><?php echo htmlspecialchars($user_info['name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user_info['email']); ?></p>
                    <div class="member-since">
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Member since <?php echo date('M Y', strtotime($user_info['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- User Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>My Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-shopping-bag text-primary me-2"></i>Total Orders</span>
                            <span class="fw-bold"><?php echo $user_stats['total_orders'] ?? 0; ?></span>
                        </div>
                    </div>
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-check-circle text-success me-2"></i>Completed</span>
                            <span class="fw-bold"><?php echo $user_stats['completed_orders'] ?? 0; ?></span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-rupee-sign text-warning me-2"></i>Total Spent</span>
                            <span class="fw-bold"><?php echo formatPrice($user_stats['total_spent'] ?? 0); ?></span>
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
                        <a href="orders.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>View Orders
                        </a>
                        <a href="../cart/cart.php" class="btn btn-outline-success">
                            <i class="fas fa-shopping-cart me-2"></i>View Cart
                        </a>
                        <a href="../product-list.php" class="btn btn-outline-info">
                            <i class="fas fa-store me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile</h4>
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
                        <!-- Personal Information -->
                        <div class="section-header mb-4">
                            <h5 class="fw-bold text-primary">👤 Personal Information</h5>
                            <hr>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($user_info['name']); ?>" required>
                                <div class="invalid-feedback">Please enter your full name.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user_info['phone']); ?>"
                                       placeholder="+91 98765 43210">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" 
                                          placeholder="Enter your complete address"><?php echo htmlspecialchars($user_info['address']); ?></textarea>
                            </div>
                        </div>

                        <!-- Password Change -->
                        <div class="section-header mb-4 mt-5">
                            <h5 class="fw-bold text-primary">🔒 Change Password</h5>
                            <p class="text-muted small">Leave blank if you don't want to change your password</p>
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
.profile-sidebar {
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    font-weight: bold;
    margin: 0 auto;
}

.stat-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.stat-item:last-child {
    border-bottom: none;
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0 !important;
}

.btn {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
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

.form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.breadcrumb {
    background: none;
    padding: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #6c757d;
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
}
</style>

<?php include '../../includes/footer.php'; ?>
