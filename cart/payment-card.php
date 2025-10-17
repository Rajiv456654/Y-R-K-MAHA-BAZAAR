<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
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

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $card_number = sanitizeInput($_POST['card_number']);
    $card_holder = sanitizeInput($_POST['card_holder']);
    $expiry_month = sanitizeInput($_POST['expiry_month']);
    $expiry_year = sanitizeInput($_POST['expiry_year']);
    $cvv = sanitizeInput($_POST['cvv']);
    
    // Basic validation
    if (empty($card_number) || empty($card_holder) || empty($expiry_month) || empty($expiry_year) || empty($cvv)) {
        $error_message = "Please fill in all card details.";
    } elseif (strlen($card_number) < 16) {
        $error_message = "Please enter a valid card number.";
    } elseif (strlen($cvv) < 3) {
        $error_message = "Please enter a valid CVV.";
    } else {
        // In a real application, you would integrate with a payment gateway like Razorpay, Stripe, etc.
        // For demo purposes, we'll simulate payment processing
        
        // Simulate payment processing delay
        sleep(2);
        
        // Generate a mock transaction ID
        $transaction_id = 'TXN' . time() . rand(1000, 9999);
        
        try {
            $conn->begin_transaction();
            
            // Create order with confirmed payment status
            $order_query = "INSERT INTO orders (user_id, total_price, customer_name, customer_email, customer_phone, shipping_address, payment_method, payment_status, transaction_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'Confirmed', ?)";
            $order_stmt = $conn->prepare($order_query);
            $order_stmt->bind_param("idssssss", $user_id, $checkout_data['total'], $checkout_data['customer_name'], 
                                  $checkout_data['customer_email'], $checkout_data['customer_phone'], 
                                  $checkout_data['shipping_address'], $checkout_data['payment_method'], 
                                  $transaction_id);
            $order_stmt->execute();
            
            $order_id = $conn->insert_id;
            
            // Add order items and update stock
            foreach ($checkout_data['checkout_items'] as $item) {
                // Add order item
                $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_query);
                $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $item_stmt->execute();
                
                // Update product stock
                $stock_query = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
                $stock_stmt = $conn->prepare($stock_query);
                $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stock_stmt->execute();
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
            $_SESSION['success_message'] = "Payment successful! Your order has been confirmed. Order ID: #" . $order_id;
            header("Location: order-success.php?order_id=" . $order_id);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Payment failed. Please try again or contact support.";
        }
    }
}

$page_title = "Card Payment";
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card payment-card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Debit/Credit Card Payment</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Card Form -->
                        <div class="col-md-8">
                            <form method="POST" class="needs-validation" novalidate id="cardForm">
                                <div class="card-details">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-credit-card me-2"></i>Card Details
                                    </h5>
                                    
                                    <div class="mb-3">
                                        <label for="card_number" class="form-label">Card Number *</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="card_number" name="card_number" 
                                                   placeholder="1234 5678 9012 3456" maxlength="19" required>
                                            <span class="input-group-text">
                                                <i class="fas fa-credit-card" id="cardIcon"></i>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback">Please enter a valid card number.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="card_holder" class="form-label">Cardholder Name *</label>
                                        <input type="text" class="form-control" id="card_holder" name="card_holder" 
                                               placeholder="John Doe" style="text-transform: uppercase;" required>
                                        <div class="invalid-feedback">Please enter the cardholder name.</div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="expiry_month" class="form-label">Month *</label>
                                            <select class="form-select" id="expiry_month" name="expiry_month" required>
                                                <option value="">MM</option>
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                            <div class="invalid-feedback">Select expiry month.</div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="expiry_year" class="form-label">Year *</label>
                                            <select class="form-select" id="expiry_year" name="expiry_year" required>
                                                <option value="">YYYY</option>
                                                <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                            <div class="invalid-feedback">Select expiry year.</div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="cvv" class="form-label">CVV *</label>
                                            <input type="password" class="form-control" id="cvv" name="cvv" 
                                                   placeholder="123" maxlength="4" required>
                                            <div class="invalid-feedback">Enter CVV.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="security-info mb-4">
                                        <div class="alert alert-info">
                                            <i class="fas fa-shield-alt me-2"></i>
                                            <strong>Secure Payment:</strong> Your card details are encrypted and secure. 
                                            We use industry-standard SSL encryption to protect your information.
                                        </div>
                                    </div>
                                    
                                    <div class="accepted-cards mb-4">
                                        <h6 class="text-muted mb-2">Accepted Cards:</h6>
                                        <div class="card-icons">
                                            <i class="fab fa-cc-visa fa-2x text-primary me-2"></i>
                                            <i class="fab fa-cc-mastercard fa-2x text-warning me-2"></i>
                                            <i class="fab fa-cc-amex fa-2x text-info me-2"></i>
                                            <i class="fas fa-credit-card fa-2x text-secondary"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="checkout.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Checkout
                                        </a>
                                        
                                        <button type="submit" name="process_payment" class="btn btn-primary btn-lg" id="payButton">
                                            <i class="fas fa-lock me-2"></i>
                                            <span class="button-text">Pay ₹<?php echo number_format($checkout_data['total'], 2); ?></span>
                                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Payment Summary -->
                        <div class="col-md-4">
                            <div class="payment-summary">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-receipt me-2"></i>Payment Summary
                                </h5>
                                
                                <div class="summary-card">
                                    <div class="amount-display text-center mb-3">
                                        <h3 class="text-primary mb-0">
                                            ₹<?php echo number_format($checkout_data['total'], 2); ?>
                                        </h3>
                                        <small class="text-muted">Total Amount</small>
                                    </div>
                                    
                                    <div class="price-breakdown">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal:</span>
                                            <span>₹<?php echo number_format($checkout_data['subtotal'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Tax (GST):</span>
                                            <span>₹<?php echo number_format($checkout_data['tax'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Shipping:</span>
                                            <span class="text-success">FREE</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <strong>Total:</strong>
                                            <strong class="text-primary">₹<?php echo number_format($checkout_data['total'], 2); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="security-badges mt-3">
                                    <div class="text-center">
                                        <i class="fas fa-shield-alt text-success fa-2x mb-2"></i>
                                        <p class="small text-muted mb-0">256-bit SSL Encrypted</p>
                                        <p class="small text-muted">Secure Payment</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>Order Items</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($checkout_data['checkout_items'] as $item): ?>
                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <img src="../assets/images/products/<?php echo $item['image'] ?: 'default-product.jpg'; ?>" 
                             class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <small class="text-muted">Quantity: <?php echo $item['quantity']; ?> × ₹<?php echo number_format($item['price'], 2); ?></small>
                        </div>
                        <div class="text-end">
                            <strong class="text-primary">₹<?php echo number_format($item['total'], 2); ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Card number formatting and validation
document.getElementById('card_number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    
    if (formattedValue.length <= 19) {
        e.target.value = formattedValue;
    }
    
    // Detect card type
    const cardIcon = document.getElementById('cardIcon');
    if (value.startsWith('4')) {
        cardIcon.className = 'fab fa-cc-visa text-primary';
    } else if (value.startsWith('5') || value.startsWith('2')) {
        cardIcon.className = 'fab fa-cc-mastercard text-warning';
    } else if (value.startsWith('3')) {
        cardIcon.className = 'fab fa-cc-amex text-info';
    } else {
        cardIcon.className = 'fas fa-credit-card';
    }
});

// CVV input restriction
document.getElementById('cvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
});

// Form submission with loading state
document.getElementById('cardForm').addEventListener('submit', function(e) {
    const payButton = document.getElementById('payButton');
    const buttonText = payButton.querySelector('.button-text');
    const spinner = payButton.querySelector('.spinner-border');
    
    payButton.disabled = true;
    buttonText.textContent = 'Processing...';
    spinner.classList.remove('d-none');
});

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
                    
                    // Reset button state if validation fails
                    const payButton = document.getElementById('payButton');
                    const buttonText = payButton.querySelector('.button-text');
                    const spinner = payButton.querySelector('.spinner-border');
                    
                    payButton.disabled = false;
                    buttonText.textContent = 'Pay ₹<?php echo number_format($checkout_data['total'], 2); ?>';
                    spinner.classList.add('d-none');
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

.summary-card {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid #dee2e6;
}

.amount-display {
    background: white;
    border-radius: 10px;
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-icons i {
    margin-right: 0.5rem;
}

.security-badges {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
    border: 1px solid #e9ecef;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

#payButton {
    min-width: 200px;
    position: relative;
}

#payButton:disabled {
    opacity: 0.8;
}

.price-breakdown {
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .card-icons i {
        font-size: 1.5rem !important;
        margin-right: 0.25rem;
    }
    
    .amount-display h3 {
        font-size: 1.5rem;
    }
    
    #payButton {
        min-width: auto;
        width: 100%;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
