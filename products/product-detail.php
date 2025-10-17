<?php
// Include required files first
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

$page_title = "Product Details";
include '../includes/header.php';

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: product-list.php");
    exit();
}

// Get product details
$query = "SELECT p.*, c.category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE p.product_id = ? AND p.is_active = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: product-list.php");
    exit();
}

$product = $result->fetch_assoc();

// Get related products
$related_query = "SELECT p.*, c.category_name FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  WHERE p.category_id = ? AND p.product_id != ? AND p.is_active = 1 
                  ORDER BY RAND() LIMIT 4";
$related_stmt = $conn->prepare($related_query);
$related_stmt->bind_param("ii", $product['category_id'], $product_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();

$page_title = $product['name'];
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="product-list.php">Products</a></li>
            <li class="breadcrumb-item"><a href="product-list.php?category=<?php echo $product['category_id']; ?>">
                <?php echo htmlspecialchars($product['category_name']); ?>
            </a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Images -->
        <div class="col-lg-6 mb-4">
            <div class="product-images">
                <div class="main-image mb-3">
                    <img id="main-product-image" 
                         src="../assets/images/products/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                         class="img-fluid rounded shadow" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         style="width: 100%; height: 400px; object-fit: cover;">
                </div>
                
                <!-- Thumbnail images (placeholder for multiple images) -->
                <div class="thumbnail-images">
                    <div class="row">
                        <div class="col-3">
                            <img src="../assets/images/products/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                                 class="img-fluid rounded product-thumbnail active" 
                                 style="height: 80px; object-fit: cover; cursor: pointer;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-lg-6">
            <div class="product-details">
                <h1 class="h2 fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-meta mb-3">
                    <span class="badge bg-primary me-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                    <span class="badge bg-warning">Low Stock</span>
                    <?php elseif ($product['stock'] == 0): ?>
                    <span class="badge bg-danger">Out of Stock</span>
                    <?php else: ?>
                    <span class="badge bg-success">In Stock</span>
                    <?php endif; ?>
                </div>

                <div class="product-price mb-4">
                    <span class="h3 text-primary fw-bold"><?php echo formatPrice($product['price']); ?></span>
                    <div class="text-muted mt-2">
                        <i class="fas fa-box me-1"></i>
                        Stock Available: <?php echo $product['stock']; ?> units
                    </div>
                </div>

                <div class="product-description mb-4">
                    <h5>Description</h5>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <?php if ($product['stock'] > 0): ?>
                <div class="product-actions mb-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <div class="quantity-controls">
                                <button type="button" class="btn btn-outline-primary quantity-minus">-</button>
                                <input type="number" id="quantity" class="form-control quantity-input" 
                                       value="1" min="1" max="<?php echo $product['stock']; ?>">
                                <button type="button" class="btn btn-outline-primary quantity-plus">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <?php if (isLoggedIn()): ?>
                        <button class="btn btn-primary btn-lg me-3 add-to-cart-btn" 
                                data-product-id="<?php echo $product['product_id']; ?>">
                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                        </button>
                        <a href="../cart/checkout.php?product_id=<?php echo $product['product_id']; ?>&quantity=1" 
                           class="btn btn-warning btn-lg buy-now-btn">
                            <i class="fas fa-bolt me-2"></i>Buy Now
                        </a>
                        <?php else: ?>
                        <a href="../login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                           class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Buy
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This product is currently out of stock.
                </div>
                <?php endif; ?>

                <!-- Product Features -->
                <div class="product-features">
                    <h6 class="fw-bold mb-3">Why Choose Us?</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Fast & Free Delivery</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>7-Day Return Policy</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Secure Payment</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>24/7 Customer Support</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if ($related_result->num_rows > 0): ?>
    <section class="related-products mt-5">
        <h3 class="fw-bold mb-4">Related Products</h3>
        <div class="row">
            <?php while ($related = $related_result->fetch_assoc()): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card product-card h-100">
                    <img src="../assets/images/products/<?php echo $related['image'] ?: 'default-product.jpg'; ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($related['name']); ?>">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h6>
                        <p class="card-text text-muted small"><?php echo htmlspecialchars($related['category_name']); ?></p>
                        <div class="product-price mb-3 mt-auto">
                            <span class="h6 text-primary fw-bold"><?php echo formatPrice($related['price']); ?></span>
                        </div>
                        <a href="product-detail.php?id=<?php echo $related['product_id']; ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<script>
// Update quantity for buy now button
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity');
    const buyNowBtn = document.querySelector('.buy-now-btn');
    const addToCartBtn = document.querySelector('.add-to-cart-btn');
    
    function updateQuantity() {
        const quantity = quantityInput.value;
        if (buyNowBtn) {
            const href = buyNowBtn.getAttribute('href');
            const newHref = href.replace(/quantity=\d+/, 'quantity=' + quantity);
            buyNowBtn.setAttribute('href', newHref);
        }
        if (addToCartBtn) {
            addToCartBtn.setAttribute('data-quantity', quantity);
        }
    }
    
    // Quantity controls
    document.querySelector('.quantity-minus').addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        if (value > 1) {
            quantityInput.value = value - 1;
            updateQuantity();
        }
    });
    
    document.querySelector('.quantity-plus').addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        const max = parseInt(quantityInput.getAttribute('max'));
        if (value < max) {
            quantityInput.value = value + 1;
            updateQuantity();
        }
    });
    
    quantityInput.addEventListener('change', updateQuantity);
    
    // Add to cart with quantity
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const quantity = quantityInput.value;
            addToCart(productId, quantity);
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
