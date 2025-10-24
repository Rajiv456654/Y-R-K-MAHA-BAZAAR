<?php
// Include required files first
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';
startSession();

// Require login
requireLogin();

$page_title = "Shopping Cart";
include '../../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get cart items
$query = "SELECT c.*, p.name, p.price, p.image, p.stock 
          FROM cart c 
          JOIN products p ON c.product_id = p.product_id 
          WHERE c.user_id = ? 
          ORDER BY c.added_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
$total_items = 0;
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="display-6 fw-bold mb-4">
                <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
            </h1>
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="../product-list.php">Products</a></li>
                    <li class="breadcrumb-item active">Shopping Cart</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (count($cart_items) > 0): ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Cart Items</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart_items as $item):
                        $item_total = $item['price'] * $item['quantity'];
                        $subtotal += $item_total;
                        $total_items += $item['quantity'];
                    ?>
                    <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="../../assets/images/products/<?php echo $item['image'] ?: 'default-product.jpg'; ?>"
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="img-fluid rounded" style="max-height: 80px;">
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <p class="text-muted mb-0">Price: <?php echo formatPrice($item['price']); ?></p>
                                <small class="text-success">In Stock: <?php echo $item['stock']; ?></small>
                            </div>
                            <div class="col-md-3">
                                <div class="quantity-controls">
                                    <button type="button" class="btn-quantity" onclick="updateQuantity(<?php echo $item['product_id']; ?>, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>"
                                           min="1" max="<?php echo $item['stock']; ?>"
                                           onchange="updateQuantity(<?php echo $item['product_id']; ?>, 0, this.value)">
                                    <button type="button" class="btn-quantity" onclick="updateQuantity(<?php echo $item['product_id']; ?>, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="fw-bold text-primary item-total">
                                    <?php echo formatPrice($item_total); ?>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="removeFromCart(<?php echo $item['product_id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Continue Shopping -->
            <div class="mt-3">
                <a href="../product-list.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                </a>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Cart Summary -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Items (<?php echo $total_items; ?>):</span>
                        <span id="subtotal"><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span class="text-success">FREE</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax:</span>
                        <span>Included</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-primary" id="total"><?php echo formatPrice($subtotal); ?></strong>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="../../cart/checkout.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                        </a>
                        <button class="btn btn-outline-secondary" onclick="clearCart()">
                            <i class="fas fa-trash me-2"></i>Clear Cart
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Recommended Products -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">You Might Also Like</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Get recommended products
                    $rec_query = "SELECT product_id, name, price, image FROM products
                                  WHERE is_active = TRUE AND product_id NOT IN
                                  (SELECT product_id FROM cart WHERE user_id = ?)
                                  ORDER BY RAND() LIMIT 3";
                    $rec_stmt = $conn->prepare($rec_query);
                    $rec_stmt->execute([$user_id]);
                    $recommended = $rec_stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($recommended as $product):
                    ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="../../assets/images/products/<?php echo $product['image'] ?: 'default-product.jpg'; ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="mb-0 small"><?php echo htmlspecialchars($product['name']); ?></h6>
                            <small class="text-primary"><?php echo formatPrice($product['price']); ?></small>
                        </div>
                        <button class="btn btn-outline-primary btn-sm"
                                onclick="addToCart(<?php echo $product['product_id']; ?>, 1)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Empty Cart -->
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                <h3>Your Cart is Empty</h3>
                <p class="text-muted mb-4">Add some products to your cart to get started!</p>
                <a href="../product-list.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(productId, change, newValue = null) {
    const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
    const quantityInput = cartItem.querySelector('.quantity-input');
    
    let quantity;
    if (newValue !== null) {
        quantity = parseInt(newValue);
    } else {
        quantity = parseInt(quantityInput.value) + change;
    }
    
    if (quantity < 1) return;
    
    fetch('../../cart/update-quantity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            quantityInput.value = quantity;
            updateCartTotals();
        } else {
            alert('Error updating quantity: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error updating quantity. Please try again.');
    });
}

function removeFromCart(productId) {
    if (confirm('Remove this item from cart?')) {
        fetch('../../cart/remove-from-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error removing item: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error removing item. Please try again.');
        });
    }
}

function addToCart(productId, quantity) {
    fetch('../../cart/add-to-cart.php', {
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
            alert('Error adding to cart: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error adding to cart. Please try again.');
    });
}

function clearCart() {
    if (confirm('Are you sure you want to clear your entire cart?')) {
        fetch('../../cart/clear-cart.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error clearing cart: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error clearing cart. Please try again.');
        });
    }
}

function updateCartTotals() {
    fetch('../../cart/get-cart-totals.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('subtotal').textContent = data.subtotal_formatted;
                document.getElementById('total').textContent = data.total_formatted;
            }
        })
        .catch(error => {
            console.error('Error updating totals:', error);
        });
}
</script>

<style>
.cart-item {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    background: white;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-quantity {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    border: 1px solid #E50914;
    background: white;
    color: #E50914;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.btn-quantity:hover {
    background: #E50914;
    color: white;
}

.quantity-input {
    width: 60px;
    text-align: center;
    border: 1px solid #e9ecef;
    border-radius: 5px;
    padding: 0.25rem;
}
</style>

<?php include '../../includes/footer.php'; ?>
