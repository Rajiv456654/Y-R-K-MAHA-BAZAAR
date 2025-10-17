<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/qr-generator.php';
startSession();

// Require login
requireLogin();

// Check if checkout data exists
if (!isset($_SESSION['checkout_data'])) {
    header("Location: checkout.php");
    exit();
}

$checkout_data = $_SESSION['checkout_data'];
$user_id = $_SESSION['user_id'];

// Get UPI settings from database
$upi_settings = getUPISettings($conn);
$UPI_ID = $upi_settings['upi_id'];
$MERCHANT_NAME = $upi_settings['merchant_name'];

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payment_confirmed'])) {
    $transaction_id = sanitizeInput($_POST['transaction_id']);
    $payment_screenshot = '';
    
    // Handle screenshot upload
    if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] == 0) {
        $upload_dir = '../assets/images/payment_proofs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['payment_screenshot']['name'], PATHINFO_EXTENSION);
        $payment_screenshot = 'payment_' . time() . '_' . $user_id . '.' . $file_extension;
        $upload_path = $upload_dir . $payment_screenshot;
        
        if (move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $upload_path)) {
            // File uploaded successfully
        } else {
            $payment_screenshot = '';
        }
    }
    
    if (empty($transaction_id)) {
        $error_message = "Please enter the transaction ID.";
    } else {
        try {
            $conn->begin_transaction();
            
            // Create order with pending payment status
            $order_query = "INSERT INTO orders (user_id, total_price, customer_name, customer_email, customer_phone, shipping_address, payment_method, payment_status, transaction_id, payment_screenshot) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?)";
            $order_stmt = $conn->prepare($order_query);
            $order_stmt->bind_param("idsssssss", $user_id, $checkout_data['total'], $checkout_data['customer_name'], 
                                  $checkout_data['customer_email'], $checkout_data['customer_phone'], 
                                  $checkout_data['shipping_address'], $checkout_data['payment_method'], 
                                  $transaction_id, $payment_screenshot);
            $order_stmt->execute();
            
            $order_id = $conn->insert_id;
            
            // Add order items
            foreach ($checkout_data['checkout_items'] as $item) {
                $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_query);
                $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $item_stmt->execute();
            }
            
            // Clear cart if not direct purchase
            if ($checkout_data['direct_product_id'] == 0) {
                $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
                $clear_cart_stmt = $conn->prepare($clear_cart_query);
                $clear_cart_stmt->bind_param("i", $user_id);
                $clear_cart_stmt->execute();
            }
            
            $conn->commit();
            
            // Clear checkout data
            unset($_SESSION['checkout_data']);
            
            // Redirect to success page
            $_SESSION['success_message'] = "Payment submitted successfully! Your order will be confirmed once payment is verified. Order ID: #" . $order_id;
            header("Location: order-success.php?order_id=" . $order_id);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "An error occurred while processing your payment. Please try again.";
        }
    }
}

$page_title = "UPI Payment";
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card payment-card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>UPI Payment</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Payment Instructions -->
                        <div class="col-md-6">
                            <h5 class="text-success mb-3">
                                <i class="fas fa-info-circle me-2"></i>Payment Instructions
                            </h5>
                            
                            <div class="payment-steps">
                                <div class="step mb-3">
                                    <div class="step-number">1</div>
                                    <div class="step-content">
                                        <strong>Open your UPI app</strong>
                                        <p class="text-muted mb-0">PhonePe, Google Pay, Paytm, or any UPI app</p>
                                    </div>
                                </div>
                                
                                <div class="step mb-3">
                                    <div class="step-number">2</div>
                                    <div class="step-content">
                                        <strong>Scan QR Code or Pay to UPI ID</strong>
                                        <p class="text-muted mb-0">Use the QR code or UPI ID below</p>
                                    </div>
                                </div>
                                
                                <div class="step mb-3">
                                    <div class="step-number">3</div>
                                    <div class="step-content">
                                        <strong>Enter Amount</strong>
                                        <p class="text-muted mb-0">₹<?php echo number_format($checkout_data['total'], 2); ?></p>
                                    </div>
                                </div>
                                
                                <div class="step mb-3">
                                    <div class="step-number">4</div>
                                    <div class="step-content">
                                        <strong>Complete Payment</strong>
                                        <p class="text-muted mb-0">Enter your UPI PIN and confirm</p>
                                    </div>
                                </div>
                                
                                <div class="step">
                                    <div class="step-number">5</div>
                                    <div class="step-content">
                                        <strong>Submit Transaction Details</strong>
                                        <p class="text-muted mb-0">Enter transaction ID below</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- QR Code and UPI Details -->
                        <div class="col-md-6">
                            <div class="payment-details text-center">
                                <!-- Dynamic QR Code -->
                                <div class="qr-code-container mb-4">
                                    <?php echo generateUPIQRCodeHTML($UPI_ID, $MERCHANT_NAME, $checkout_data['total'], 'ORD' . time()); ?>
                                </div>
                                
                                <!-- UPI Details -->
                                <div class="upi-details">
                                    <div class="alert alert-info">
                                        <h6 class="mb-2"><i class="fas fa-user me-2"></i>Pay To:</h6>
                                        <strong><?php echo $MERCHANT_NAME; ?></strong>
                                    </div>
                                    
                                    <div class="upi-id-container mb-3">
                                        <label class="form-label"><strong>UPI ID:</strong></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control text-center fw-bold" 
                                                   value="<?php echo $UPI_ID; ?>" readonly>
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="copyUPIID()" title="Copy UPI ID">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="amount-display">
                                        <h4 class="text-success mb-3">
                                            <i class="fas fa-rupee-sign me-1"></i>
                                            <?php echo number_format($checkout_data['total'], 2); ?>
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Payment Confirmation Form -->
                    <div class="payment-confirmation">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-check-circle me-2"></i>Confirm Your Payment
                        </h5>
                        
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="transaction_id" class="form-label">Transaction ID / Reference Number *</label>
                                    <input type="text" class="form-control" id="transaction_id" name="transaction_id" 
                                           placeholder="Enter transaction ID from your UPI app" required>
                                    <div class="invalid-feedback">Please enter the transaction ID.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="payment_screenshot" class="form-label">Payment Screenshot (Optional)</label>
                                    <input type="file" class="form-control" id="payment_screenshot" name="payment_screenshot" 
                                           accept="image/*">
                                    <div class="form-text">Upload screenshot of successful payment</div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> Your order will be confirmed only after payment verification. 
                                Please ensure you have completed the payment before submitting.
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="checkout.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Checkout
                                </a>
                                
                                <button type="submit" name="payment_confirmed" class="btn btn-success btn-lg">
                                    <i class="fas fa-check me-2"></i>Confirm Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <?php foreach ($checkout_data['checkout_items'] as $item): ?>
                            <div class="d-flex align-items-center mb-2">
                                <img src="../assets/images/products/<?php echo $item['image'] ?: 'default-product.jpg'; ?>" 
                                     class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                </div>
                                <span class="fw-bold"><?php echo formatPrice($item['total']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-4">
                            <div class="price-summary">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Subtotal:</span>
                                    <span><?php echo formatPrice($checkout_data['subtotal']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Tax (GST):</span>
                                    <span><?php echo formatPrice($checkout_data['tax']); ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total:</strong>
                                    <strong class="text-success"><?php echo formatPrice($checkout_data['total']); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Copy UPI ID function
function copyUPIID() {
    const upiInput = document.querySelector('input[value="<?php echo $UPI_ID; ?>"]');
    upiInput.select();
    upiInput.setSelectionRange(0, 99999);
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
.payment-card {
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: none;
}

.payment-steps {
    padding-left: 0;
}

.step {
    display: flex;
    align-items-flex-start;
    margin-bottom: 1rem;
}

.step-number {
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.step-content {
    flex-grow: 1;
}

.qr-placeholder {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 15px;
    padding: 2rem;
    margin: 1rem 0;
}

.upi-id-container input {
    font-size: 1.1rem;
    color: #28a745;
    background-color: #f8f9fa;
}

.amount-display {
    background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
    border-radius: 10px;
    padding: 1rem;
    border: 2px solid #28a745;
}

.price-summary {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
}

@media (max-width: 768px) {
    .step-number {
        width: 25px;
        height: 25px;
        font-size: 0.8rem;
    }
    
    .qr-placeholder {
        padding: 1rem;
    }
    
    .qr-placeholder i {
        font-size: 3rem !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
