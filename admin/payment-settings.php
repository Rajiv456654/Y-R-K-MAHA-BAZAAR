<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit();
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $upi_id = sanitizeInput($_POST['upi_id']);
    $merchant_name = sanitizeInput($_POST['merchant_name']);
    $auto_verify_payments = isset($_POST['auto_verify_payments']) ? 1 : 0;
    $payment_gateway = sanitizeInput($_POST['payment_gateway']);
    $webhook_secret = sanitizeInput($_POST['webhook_secret']);
    $webhook_url = sanitizeInput($_POST['webhook_url']);
    
    try {
        $conn->begin_transaction();
        
        // Update or insert settings
        $settings = [
            'upi_id' => $upi_id,
            'merchant_name' => $merchant_name,
            'payment_verification_required' => $payment_verification_required,
            'payment_timeout_minutes' => $payment_timeout_minutes,
            'min_order_amount' => $min_order_amount,
            'max_order_amount' => $max_order_amount,
            'auto_verify_payments' => $auto_verify_payments,
            'payment_gateway' => $payment_gateway,
            'webhook_secret' => $webhook_secret,
            'webhook_url' => $webhook_url
        ];
        
        foreach ($settings as $key => $value) {
            $query = "INSERT INTO payment_settings (setting_key, setting_value) VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
        }
        
        $conn->commit();
        $_SESSION['success_message'] = "Payment settings updated successfully!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error updating settings: " . $e->getMessage();
    }
    
    header("Location: payment-settings.php");
    exit();
}

// Get current settings
$settings_query = "SELECT setting_key, setting_value FROM payment_settings";
$settings_result = $conn->query($settings_query);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Set defaults if not exists
$defaults = [
    'upi_id' => 'rajuyadav75908-5@okaxis',
    'merchant_name' => 'Y R K MAHA BAZAAR',
    'payment_verification_required' => '0',
    'payment_timeout_minutes' => '30',
    'min_order_amount' => '50',
    'max_order_amount' => '100000',
    'auto_verify_payments' => '0',
    'payment_gateway' => 'manual',
    'webhook_secret' => '',
    'webhook_url' => ''
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

$page_title = "Payment Settings";
include 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">⚙️ Payment Settings</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="payment-verifications.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-check-circle me-1"></i>Payment Verifications
            </a>
        </div>
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

<form method="POST" class="needs-validation" novalidate>
    <div class="row">
        <!-- UPI Settings -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>UPI Payment Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="upi_id" class="form-label">UPI ID *</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="upi_id" name="upi_id" 
                                   value="<?php echo htmlspecialchars($settings['upi_id']); ?>" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="testUPIID()">
                                <i class="fas fa-check"></i> Test
                            </button>
                        </div>
                        <div class="form-text">This UPI ID will be used to receive payments from customers</div>
                        <div class="invalid-feedback">Please enter a valid UPI ID</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="merchant_name" class="form-label">Merchant Name *</label>
                        <input type="text" class="form-control" id="merchant_name" name="merchant_name" 
                               value="<?php echo htmlspecialchars($settings['merchant_name']); ?>" required>
                        <div class="form-text">This name will be displayed to customers during payment</div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="payment_verification_required" 
                               name="payment_verification_required" <?php echo $settings['payment_verification_required'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="payment_verification_required">
                            <strong>Require Manual Verification</strong>
                            <div class="form-text">UPI payments will need admin approval before order confirmation</div>
                        </label>
                    </div>
                </div>
            </div>
    </div>
    
    <!-- General Payment Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>General Payment Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="payment_timeout_minutes" class="form-label">Payment Timeout (Minutes)</label>
                    <input type="number" class="form-control" id="payment_timeout_minutes" name="payment_timeout_minutes" 
                           value="<?php echo $settings['payment_timeout_minutes']; ?>" min="5" max="120" required>
                    <div class="form-text">Time limit for completing payment</div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="min_order_amount" class="form-label">Minimum Order Amount (₹)</label>
                    <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" 
                           value="<?php echo $settings['min_order_amount']; ?>" min="1" step="0.01" required>
                    <div class="form-text">Minimum amount required for placing order</div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="max_order_amount" class="form-label">Maximum Order Amount (₹)</label>
                    <input type="number" class="form-control" id="max_order_amount" name="max_order_amount" 
                           value="<?php echo $settings['max_order_amount']; ?>" min="100" step="0.01" required>
                    <div class="form-text">Maximum amount allowed per order</div>
                </div>
            </div>
    </div>
    
    <!-- Automatic Verification Settings -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-robot me-2"></i>Automatic Payment Verification</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Advanced Feature:</strong> Automatic verification requires payment gateway integration (Razorpay, PayU, etc.)
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="auto_verify_payments"
                               name="auto_verify_payments" <?php echo $settings['auto_verify_payments'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="auto_verify_payments">
                            <strong>Enable Automatic Payment Verification</strong>
                            <div class="form-text">Automatically confirm orders when payment is received via webhook</div>
                        </label>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="payment_gateway" class="form-label">Payment Gateway</label>
                    <select class="form-select" id="payment_gateway" name="payment_gateway">
                        <option value="manual" <?php echo $settings['payment_gateway'] == 'manual' ? 'selected' : ''; ?>>Manual Verification</option>
                        <option value="razorpay" <?php echo $settings['payment_gateway'] == 'razorpay' ? 'selected' : ''; ?>>Razorpay</option>
                        <option value="payu" <?php echo $settings['payment_gateway'] == 'payu' ? 'selected' : ''; ?>>PayU</option>
                        <option value="cashfree" <?php echo $settings['payment_gateway'] == 'cashfree' ? 'selected' : ''; ?>>Cashfree</option>
                    </select>
                    <div class="form-text">Choose your payment gateway for automatic verification</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="webhook_secret" class="form-label">Webhook Secret</label>
                    <input type="password" class="form-control" id="webhook_secret" name="webhook_secret"
                           value="<?php echo htmlspecialchars($settings['webhook_secret']); ?>"
                           placeholder="From your payment gateway dashboard">
                    <div class="form-text">Secret key for webhook signature verification</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="webhook_url" class="form-label">Webhook URL</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="webhook_url" name="webhook_url"
                               value="https://<?php echo $_SERVER['HTTP_HOST']; ?>/webhook.php" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyWebhookURL()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <div class="form-text">Configure this URL in your payment gateway webhook settings</div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>How it works:</strong> When a customer pays, your payment gateway sends a webhook to the URL above.
                The system automatically verifies the payment and confirms the order without manual intervention.
            </div>
        </div>
    </div>

    <!-- Payment Gateway Integration -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-plug me-2"></i>Payment Gateway Integration</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Production Setup Required:</strong> For live payments, integrate with professional payment gateways.
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="gateway-card">
                        <div class="text-center">
                            <i class="fas fa-rupee-sign fa-3x text-primary mb-2"></i>
                            <h6>Razorpay</h6>
                            <p class="text-muted small">Popular Indian payment gateway</p>
                            <span class="badge bg-secondary">Not Configured</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="gateway-card">
                        <div class="text-center">
                            <i class="fab fa-stripe fa-3x text-info mb-2"></i>
                            <h6>Stripe</h6>
                            <p class="text-muted small">International payment processing</p>
                            <span class="badge bg-secondary">Not Configured</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="gateway-card">
                        <div class="text-center">
                            <i class="fas fa-credit-card fa-3x text-success mb-2"></i>
                            <h6>PayU</h6>
                            <p class="text-muted small">Comprehensive payment solution</p>
                            <span class="badge bg-secondary">Not Configured</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- QR Code Generator -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>UPI QR Code</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p>Generate QR codes for your UPI ID to make payments easier for customers.</p>
                    <button type="button" class="btn btn-success" onclick="generateQRCode()">
                        <i class="fas fa-qrcode me-2"></i>Generate QR Code
                    </button>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            QR codes will be automatically generated based on your UPI ID and order amount.
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="qr-preview text-center">
                        <div class="qr-placeholder">
                            <i class="fas fa-qrcode fa-4x text-muted mb-2"></i>
                            <p class="text-muted">QR Code Preview</p>
                            <small class="text-muted">Will be generated for each payment</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Save Button -->
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <small class="text-muted">* Required fields</small>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save me-2"></i>Save Payment Settings
        </button>
    </div>
</form>

<script>
function copyWebhookURL() {
    const webhookInput = document.getElementById('webhook_url');
    webhookInput.select();
    webhookInput.setSelectionRange(0, 99999);
    document.execCommand('copy');

    // Show feedback
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i>';
    button.classList.remove('btn-outline-secondary');
    button.classList.add('btn-success');

    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}

function generateQRCode() {
    const upiId = document.getElementById('upi_id').value;
    const merchantName = document.getElementById('merchant_name').value;
    
    if (!upiId) {
        alert('Please enter a UPI ID first');
        return;
    }
    
    alert('QR Code generation feature will be implemented with a QR code library.\n\nUPI ID: ' + upiId + '\nMerchant: ' + merchantName);
}

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
</script>

<style>
.card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: none;
}

.gateway-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    height: 100%;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.gateway-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.qr-placeholder {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 10px;
    padding: 2rem;
    margin: 1rem 0;
}

.card-types .badge {
    font-size: 0.9rem;
}

.form-check-label .form-text {
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

@media (max-width: 768px) {
    .gateway-card {
        margin-bottom: 1rem;
    }
    
    .card-types .badge {
        display: block;
        margin-bottom: 0.5rem;
    }
}
</style>

<?php include 'includes/admin-footer.php'; ?>
