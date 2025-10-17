<?php
require_once '../../../../../includes/db_connect.php';
require_once '../../../../../includes/functions.php';
startSession();

// Require login
requireLogin();

$user_id = $_SESSION['user_id'];

// Get cart items
$query = "SELECT c.*, p.name, p.price, p.image, p.stock 
          FROM cart c 
          JOIN products p ON c.product_id = p.product_id 
          WHERE c.user_id = ? AND p.is_active = 1 
          ORDER BY c.added_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

$cart_items = [];
$subtotal = 0;

while ($item = $cart_result->fetch_assoc()) {
    $item_total = $item['price'] * $item['quantity'];
    $item['total'] = $item_total;
    $subtotal += $item_total;
    $cart_items[] = $item;
}

$shipping = 0; // Free shipping
$tax = $subtotal * 0.18; // 18% GST
$total = $subtotal + $shipping + $tax;

// Include header after all processing is done
$page_title = "Shopping Cart";
include '../../../../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../../../../index.php">Home</a></li>
                    <li class="breadcrumb-item active">Shopping Cart</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="display-6 fw-bold mb-1">🛒 My Shopping Cart</h1>
                    <p class="text-muted mb-0"><?php echo count($cart_items); ?> items ready for checkout</p>
                </div>
                <div>
                    <a href="../../../../product-list.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($cart_items)): ?>
    <div class="row">
        <!-- Cart Items -->
        <div class="col-lg-8">
            <div class="cart-container">
                <div class="cart-header">
                    <div class="row">
                        <div class="col-md-6"><strong>Product</strong></div>
                        <div class="col-md-2 text-center"><strong>Quantity</strong></div>
                        <div class="col-md-2 text-center"><strong>Price</strong></div>
                        <div class="col-md-2 text-center"><strong>Total</strong></div>
                    </div>
                </div>
                
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="product-image-container me-3">
                                    <img src="../../../../../assets/images/products/<?php echo $item['image'] ?: 'default-product.jpg'; ?>" 
                                         class="product-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                <div class="product-info">
                                    <h6 class="product-name mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <div class="stock-info">
                                        <?php if ($item['stock'] <= 5 && $item['stock'] > 0): ?>
                                        <small class="text-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Only <?php echo $item['stock']; ?> left
                                        </small>
                                        <?php elseif ($item['stock'] == 0): ?>
                                        <small class="text-danger">
                                            <i class="fas fa-times-circle me-1"></i>Out of stock
                                        </small>
                                        <?php else: ?>
                                        <small class="text-success">
                                            <i class="fas fa-check-circle me-1"></i>In stock
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="quantity-controls">
                                <div class="input-group input-group-sm">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo max(1, $item['quantity'] - 1); ?>)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock']; ?>"
                                           onchange="updateQuantity(<?php echo $item['product_id']; ?>, this.value)">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo min($item['stock'], $item['quantity'] + 1); ?>)"
                                            <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 text-center">
                            <span class="product-price"><?php echo formatPrice($item['price']); ?></span>
                        </div>
                        <div class="col-md-2 text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                <strong class="total-price me-2"><?php echo formatPrice($item['total']); ?></strong>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="removeFromCart(<?php echo $item['product_id']; ?>)"
                                        title="Remove item">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Cart Actions -->
            <div class="cart-actions mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <button class="btn btn-outline-secondary" onclick="clearCart()">
                            <i class="fas fa-trash me-2"></i>Clear Entire Cart
                        </button>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-sync me-2"></i>Refresh Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="order-summary">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📋 Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="summary-row">
                            <span>Subtotal (<?php echo count($cart_items); ?> items)</span>
                            <span class="fw-bold"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping & Handling</span>
                            <span class="text-success fw-bold">FREE</span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (GST 18%)</span>
                            <span><?php echo formatPrice($tax); ?></span>
                        </div>
                        <hr>
                        <div class="summary-row total-row">
                            <span class="fw-bold">Order Total</span>
                            <span class="fw-bold text-primary fs-4"><?php echo formatPrice($total); ?></span>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <a href="../../../checkout.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                            </a>
                            <a href="../../../../product-list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-store me-2"></i>Continue Shopping
                            </a>
                        </div>
                        
                        <div class="checkout-benefits mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt text-success me-1"></i>Secure 256-bit SSL encryption<br>
                                <i class="fas fa-truck text-primary me-1"></i>Free shipping on all orders<br>
                                <i class="fas fa-undo text-info me-1"></i>Easy returns within 7 days
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Promo Code -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">🎟️ Have a Promo Code?</h6>
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Enter promo code" id="promoCode">
                            <button class="btn btn-outline-primary" type="button" onclick="applyPromoCode()">Apply</button>
                        </div>
                        <small class="text-muted mt-2 d-block">Enter a valid promo code to get instant discount</small>
                    </div>
                </div>

                <!-- Recommended Products -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">💡 You Might Also Like</h6>
                    </div>
                    <div class="card-body">
                        <div class="recommended-item mb-3">
                            <div class="d-flex align-items-center">
                                <img src="../../../../../assets/images/products/default-product.jpg" 
                                     class="recommended-image me-2" alt="Recommended Product">
                                <div class="flex-grow-1">
                                    <small class="fw-bold">Similar Product</small><br>
                                    <small class="text-primary">₹999</small>
                                </div>
                                <button class="btn btn-outline-primary btn-sm">Add</button>
                            </div>
                        </div>
                        <div class="text-center">
                            <a href="../../../../product-list.php" class="btn btn-sm btn-outline-secondary">View More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Empty Cart -->
    <div class="empty-cart text-center py-5">
        <div class="mb-4">
            <i class="fas fa-shopping-cart fa-5x text-muted"></i>
        </div>
        <h3 class="fw-bold mb-3">Your Cart is Empty</h3>
        <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet. Start shopping to fill it up!</p>
        
        <div class="row justify-content-center mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">🛍️ Why Shop With Us?</h5>
                        <ul class="list-unstyled text-start">
                            <li>• Premium quality products</li>
                            <li>• Competitive prices</li>
                            <li>• Fast & free delivery</li>
                            <li>• Secure payment options</li>
                            <li>• Easy returns & exchanges</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="../../../../product-list.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store me-2"></i>Start Shopping
            </a>
            <a href="../../../../../index.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(productId, quantity) {
    if (quantity < 1) return;
    
    fetch('../../../../../cart/update-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showAlert('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error updating cart. Please try again.', 'danger');
    });
}

function removeFromCart(productId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }
    
    fetch('../../../../../cart/remove-from-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Item removed from cart', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error removing item. Please try again.', 'danger');
    });
}

function clearCart() {
    if (!confirm('Are you sure you want to clear your entire cart?')) {
        return;
    }
    
    fetch('../../../../../cart/clear-cart.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Cart cleared successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('Error clearing cart', 'danger');
        }
    })
    .catch(error => {
        showAlert('Error clearing cart. Please try again.', 'danger');
    });
}

function applyPromoCode() {
    const promoCode = document.getElementById('promoCode').value;
    if (!promoCode) {
        showAlert('Please enter a promo code', 'warning');
        return;
    }
    
    // Placeholder for promo code functionality
    showAlert('Promo code functionality will be implemented here', 'info');
}

function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 3000);
}
</script>

<style>
.cart-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.cart-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    font-weight: 500;
}

.cart-item {
    padding: 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.3s ease;
}

.cart-item:hover {
    background-color: #f8f9fa;
}

.cart-item:last-child {
    border-bottom: none;
}

.product-image-container {
    text-align: center;
}

.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.product-name {
    font-weight: 600;
    color: #333;
}

.product-price {
    color: #667eea;
    font-weight: 500;
}

.quantity-controls .input-group {
    max-width: 120px;
    margin: 0 auto;
}

.quantity-controls .form-control {
    border-left: none;
    border-right: none;
}

.total-price {
    font-size: 1.1rem;
    color: #333;
}

.order-summary .card {
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
}

.order-summary .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0 !important;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.total-row {
    font-size: 1.1rem;
    margin-bottom: 0;
}

.checkout-benefits {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 0.75rem;
}

.recommended-image {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 6px;
}

.cart-actions {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.empty-cart {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin: 2rem 0;
}

.btn {
    border-radius: 8px;
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

.breadcrumb {
    background: none;
    padding: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #6c757d;
}

@media (max-width: 768px) {
    .cart-item {
        padding: 1rem;
    }
    
    .cart-item .row > div {
        margin-bottom: 1rem;
        text-align: center;
    }
    
    .cart-header {
        display: none;
    }
    
    .quantity-controls .input-group {
        max-width: 100px;
    }
}

/* Loading Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.cart-item {
    animation: fadeInUp 0.5s ease forwards;
}
</style>

<?php include '../../../../../includes/footer.php'; ?>
