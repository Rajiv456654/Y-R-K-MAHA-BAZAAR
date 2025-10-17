<?php
// Include required files first
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
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
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="display-6 fw-bold mb-4">Shopping Cart</h1>
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item active">Cart</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (empty($cart_items)): ?>
    <!-- Empty Cart -->
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                <h3>Your cart is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                <a href="../products/product-list.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Cart Items -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Cart Items (<?php echo count($cart_items); ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item p-3 border-bottom">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="../assets/images/products/<?php echo $item['image'] ?: 'default-product.jpg'; ?>" 
                                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     style="height: 80px; object-fit: cover;">
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <p class="text-muted mb-0">Price: <?php echo formatPrice($item['price']); ?></p>
                                <small class="text-muted">Stock: <?php echo $item['stock']; ?> available</small>
                            </div>
                            <div class="col-md-3">
                                <div class="quantity-controls">
                                    <button type="button" class="btn btn-sm btn-outline-primary quantity-minus">-</button>
                                    <input type="number" class="form-control quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock']; ?>"
                                           data-cart-id="<?php echo $item['cart_id']; ?>">
                                    <button type="button" class="btn btn-sm btn-outline-primary quantity-plus">+</button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <strong class="item-total"><?php echo formatPrice($item['total']); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-sm btn-outline-danger remove-from-cart-btn" 
                                        data-cart-id="<?php echo $item['cart_id']; ?>"
                                        title="Remove item">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mt-3">
                <a href="../products/product-list.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                </a>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span id="cart-subtotal"><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span class="text-success">FREE</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax (GST 18%):</span>
                        <span><?php echo formatPrice($tax); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong id="cart-total" class="text-primary"><?php echo formatPrice($total); ?></strong>
                    </div>
                    
                    <a href="checkout.php" class="btn btn-primary w-100 btn-lg">
                        <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                    </a>
                </div>
            </div>
            
            <!-- Promo Code -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6>Have a promo code?</h6>
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Enter promo code">
                        <button class="btn btn-outline-secondary" type="button">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Update cart totals after quantity change
function updateCartTotals() {
    fetch('get-cart-totals.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cart-subtotal').textContent = '₹' + data.subtotal;
            document.getElementById('cart-total').textContent = '₹' + data.total;
            
            // Update individual item totals
            data.items.forEach(item => {
                const itemTotalElement = document.querySelector(`[data-cart-id="${item.cart_id}"]`)
                    .closest('.cart-item').querySelector('.item-total');
                if (itemTotalElement) {
                    itemTotalElement.textContent = '₹' + item.total;
                }
            });
        }
    })
    .catch(error => {
        console.error('Error updating totals:', error);
    });
}
</script>

<?php include '../includes/footer.php'; ?>
