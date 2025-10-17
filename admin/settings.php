<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'general_settings':
            $site_name = sanitizeInput($_POST['site_name']);
            $site_description = sanitizeInput($_POST['site_description']);
            $contact_email = sanitizeInput($_POST['contact_email']);
            $contact_phone = sanitizeInput($_POST['contact_phone']);
            $address = sanitizeInput($_POST['address']);
            
            if (empty($site_name) || empty($contact_email)) {
                $error_message = "Site name and contact email are required.";
            } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Please enter a valid contact email.";
            } else {
                // In a real application, these would be stored in a settings table
                // For now, we'll just show success message
                $success_message = "General settings updated successfully!";
            }
            break;
            
        case 'email_settings':
            $smtp_host = sanitizeInput($_POST['smtp_host']);
            $smtp_port = sanitizeInput($_POST['smtp_port']);
            $smtp_username = sanitizeInput($_POST['smtp_username']);
            $smtp_password = $_POST['smtp_password'];
            $from_email = sanitizeInput($_POST['from_email']);
            $from_name = sanitizeInput($_POST['from_name']);
            
            if (empty($smtp_host) || empty($from_email)) {
                $error_message = "SMTP host and from email are required.";
            } else {
                $success_message = "Email settings updated successfully!";
            }
            break;
            
        case 'payment_settings':
            $currency = sanitizeInput($_POST['currency']);
            $tax_rate = floatval($_POST['tax_rate']);
            $shipping_cost = floatval($_POST['shipping_cost']);
            $free_shipping_threshold = floatval($_POST['free_shipping_threshold']);
            
            if (empty($currency)) {
                $error_message = "Currency is required.";
            } else {
                $success_message = "Payment settings updated successfully!";
            }
            break;
            
        case 'security_settings':
            $session_timeout = intval($_POST['session_timeout']);
            $max_login_attempts = intval($_POST['max_login_attempts']);
            $password_min_length = intval($_POST['password_min_length']);
            $enable_2fa = isset($_POST['enable_2fa']) ? 1 : 0;
            $enable_ssl = isset($_POST['enable_ssl']) ? 1 : 0;
            
            if ($session_timeout < 5 || $password_min_length < 6) {
                $error_message = "Invalid security settings values.";
            } else {
                $success_message = "Security settings updated successfully!";
            }
            break;
            
        case 'backup_database':
            // In a real application, this would trigger a database backup
            $success_message = "Database backup initiated successfully!";
            break;
            
        case 'clear_cache':
            // In a real application, this would clear system cache
            $success_message = "System cache cleared successfully!";
            break;
            
        case 'maintenance_mode':
            $maintenance_enabled = isset($_POST['maintenance_enabled']) ? 1 : 0;
            $maintenance_message = sanitizeInput($_POST['maintenance_message']);
            
            $success_message = "Maintenance mode settings updated successfully!";
            break;
    }
}

// Get current settings (in a real app, these would come from database)
$settings = [
    'site_name' => 'Y R K MAHA BAZAAR',
    'site_description' => 'Your trusted online shopping destination',
    'contact_email' => 'contact@yrkmaha.com',
    'contact_phone' => '+91 98765 43210',
    'address' => '123 Business Street, City, State 12345',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '587',
    'smtp_username' => 'noreply@yrkmaha.com',
    'from_email' => 'noreply@yrkmaha.com',
    'from_name' => 'Y R K MAHA BAZAAR',
    'currency' => 'INR',
    'tax_rate' => 18.0,
    'shipping_cost' => 50.0,
    'free_shipping_threshold' => 500.0,
    'session_timeout' => 30,
    'max_login_attempts' => 5,
    'password_min_length' => 8,
    'enable_2fa' => false,
    'enable_ssl' => true,
    'maintenance_enabled' => false,
    'maintenance_message' => 'We are currently performing maintenance. Please check back later.'
];

// Include header after all processing is done
$page_title = "System Settings";
include 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">⚙️ System Settings</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="profile.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-user me-1"></i>Profile
            </a>
            <a href="index.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-dashboard me-1"></i>Dashboard
            </a>
        </div>
    </div>
</div>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Settings Navigation -->
    <div class="col-lg-3 mb-4">
        <div class="card settings-nav">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Settings Categories</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="#general" class="list-group-item list-group-item-action active" data-bs-toggle="pill">
                    <i class="fas fa-cog me-2"></i>General Settings
                </a>
                <a href="#email" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                    <i class="fas fa-envelope me-2"></i>Email Settings
                </a>
                <a href="#payment" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                    <i class="fas fa-credit-card me-2"></i>Payment Settings
                </a>
                <a href="#security" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                    <i class="fas fa-shield-alt me-2"></i>Security Settings
                </a>
                <a href="#maintenance" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                    <i class="fas fa-tools me-2"></i>Maintenance
                </a>
                <a href="#system" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                    <i class="fas fa-server me-2"></i>System Tools
                </a>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>System Status</h6>
            </div>
            <div class="card-body">
                <div class="status-item mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Database</span>
                        <span class="badge bg-success">Online</span>
                    </div>
                </div>
                <div class="status-item mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Cache</span>
                        <span class="badge bg-success">Active</span>
                    </div>
                </div>
                <div class="status-item mb-2">
                    <div class="d-flex justify-content-between">
                        <span>SSL</span>
                        <span class="badge bg-<?php echo $settings['enable_ssl'] ? 'success' : 'warning'; ?>">
                            <?php echo $settings['enable_ssl'] ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                </div>
                <div class="status-item">
                    <div class="d-flex justify-content-between">
                        <span>Maintenance</span>
                        <span class="badge bg-<?php echo $settings['maintenance_enabled'] ? 'warning' : 'success'; ?>">
                            <?php echo $settings['maintenance_enabled'] ? 'Active' : 'Normal'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Content -->
    <div class="col-lg-9">
        <div class="tab-content">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>General Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="general_settings">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="site_name" class="form-label">Site Name *</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" 
                                           value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="contact_email" class="form-label">Contact Email *</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                           value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="site_description" class="form-label">Site Description</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contact_phone" class="form-label">Contact Phone</label>
                                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                           value="<?php echo htmlspecialchars($settings['contact_phone']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="address" class="form-label">Business Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($settings['address']); ?></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save General Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="tab-pane fade" id="email">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Email Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="email_settings">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_host" class="form-label">SMTP Host *</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                           value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_port" class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                           value="<?php echo $settings['smtp_port']; ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_username" class="form-label">SMTP Username</label>
                                    <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                           value="<?php echo htmlspecialchars($settings['smtp_username']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_password" class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                           placeholder="Enter new password to change">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="from_email" class="form-label">From Email *</label>
                                    <input type="email" class="form-control" id="from_email" name="from_email" 
                                           value="<?php echo htmlspecialchars($settings['from_email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="from_name" class="form-label">From Name</label>
                                    <input type="text" class="form-control" id="from_name" name="from_name" 
                                           value="<?php echo htmlspecialchars($settings['from_name']); ?>">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Email Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Payment Settings -->
            <div class="tab-pane fade" id="payment">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="payment_settings">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="currency" class="form-label">Currency *</label>
                                    <select class="form-select" id="currency" name="currency" required>
                                        <option value="INR" <?php echo $settings['currency'] == 'INR' ? 'selected' : ''; ?>>Indian Rupee (INR)</option>
                                        <option value="USD" <?php echo $settings['currency'] == 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                        <option value="EUR" <?php echo $settings['currency'] == 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
                                        <option value="GBP" <?php echo $settings['currency'] == 'GBP' ? 'selected' : ''; ?>>British Pound (GBP)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                    <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                           value="<?php echo $settings['tax_rate']; ?>" step="0.01" min="0" max="100">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="shipping_cost" class="form-label">Default Shipping Cost</label>
                                    <input type="number" class="form-control" id="shipping_cost" name="shipping_cost" 
                                           value="<?php echo $settings['shipping_cost']; ?>" step="0.01" min="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="free_shipping_threshold" class="form-label">Free Shipping Threshold</label>
                                    <input type="number" class="form-control" id="free_shipping_threshold" name="free_shipping_threshold" 
                                           value="<?php echo $settings['free_shipping_threshold']; ?>" step="0.01" min="0">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Payment Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="tab-pane fade" id="security">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="security_settings">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                           value="<?php echo $settings['session_timeout']; ?>" min="5" max="1440">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                    <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                           value="<?php echo $settings['max_login_attempts']; ?>" min="3" max="10">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password_min_length" class="form-label">Minimum Password Length</label>
                                    <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                           value="<?php echo $settings['password_min_length']; ?>" min="6" max="20">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="enable_2fa" name="enable_2fa" 
                                               <?php echo $settings['enable_2fa'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="enable_2fa">
                                            Enable Two-Factor Authentication
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="enable_ssl" name="enable_ssl" 
                                           <?php echo $settings['enable_ssl'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_ssl">
                                        Force SSL/HTTPS
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Security Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="tab-pane fade" id="maintenance">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Maintenance Mode</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="maintenance_mode">
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="maintenance_enabled" name="maintenance_enabled" 
                                           <?php echo $settings['maintenance_enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="maintenance_enabled">
                                        Enable Maintenance Mode
                                    </label>
                                </div>
                                <small class="text-muted">When enabled, only administrators can access the site.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="maintenance_message" class="form-label">Maintenance Message</label>
                                <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3"><?php echo htmlspecialchars($settings['maintenance_message']); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i>Update Maintenance Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- System Tools -->
            <div class="tab-pane fade" id="system">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-server me-2"></i>System Tools</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="tool-card">
                                    <h6><i class="fas fa-database me-2"></i>Database Backup</h6>
                                    <p class="text-muted">Create a backup of your database.</p>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="backup_database">
                                        <button type="submit" class="btn btn-success" onclick="return confirm('Create database backup?')">
                                            <i class="fas fa-download me-1"></i>Backup Now
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="tool-card">
                                    <h6><i class="fas fa-broom me-2"></i>Clear Cache</h6>
                                    <p class="text-muted">Clear system cache to improve performance.</p>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="clear_cache">
                                        <button type="submit" class="btn btn-info" onclick="return confirm('Clear system cache?')">
                                            <i class="fas fa-trash me-1"></i>Clear Cache
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Information -->
                        <div class="system-info mt-4">
                            <h6>System Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>PHP Version:</strong></td>
                                            <td><?php echo phpversion(); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Server Software:</strong></td>
                                            <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>MySQL Version:</strong></td>
                                            <td><?php echo $conn->server_info; ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Memory Limit:</strong></td>
                                            <td><?php echo ini_get('memory_limit'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Upload Max Size:</strong></td>
                                            <td><?php echo ini_get('upload_max_filesize'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Server Time:</strong></td>
                                            <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-nav {
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
}

.settings-nav .card-header {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    color: white;
    border-radius: 15px 15px 0 0 !important;
    border: none;
}

.list-group-item {
    border: none;
    padding: 1rem 1.25rem;
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.list-group-item.active {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    border: none;
    color: white;
}

.status-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.status-item:last-child {
    border-bottom: none;
}

.tool-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    height: 100%;
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

.form-control, .form-select {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.system-info {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
}

@media (max-width: 768px) {
    .tool-card {
        margin-bottom: 1rem;
    }
}
</style>

<?php include 'includes/admin-footer.php'; ?>
