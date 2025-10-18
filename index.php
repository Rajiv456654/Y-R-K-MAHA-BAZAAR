<?php
$page_title = "Home";
include 'includes/header.php';

// Include database connection
include 'includes/db_connect.php';

// Fetch featured products
$featured_query = "SELECT p.*, c.category_name FROM products p
                   LEFT JOIN categories c ON p.category_id = c.category_id
                   WHERE p.is_active = TRUE
                   ORDER BY p.created_at DESC
                   LIMIT 8";
$featured_result = $conn->query($featured_query);

// Fetch categories
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Welcome to YRK MAHA BAZAAR</h1>
                <p class="lead mb-4">Your one-stop destination for quality products at unbeatable prices. Shop from thousands of products across multiple categories.</p>
                <div class="hero-buttons">
                    <a href="products/product-list.php" class="btn btn-warning btn-lg me-3">
                        <i class="fas fa-shopping-bag me-2"></i>Shop Now
                    </a>
                    <a href="about.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-info-circle me-2"></i>Learn More
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="hero-logo-container">
                    <img src="assets/images/yrk-logo-hero.svg" alt="YRK MAHA BAZAAR" class="img-fluid yrk-hero-logo" style="max-height: 400px;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-3 text-center mb-4">
                <div class="feature-icon mb-3">
                    <i class="fas fa-shipping-fast fa-3x text-primary"></i>
                </div>
                <h5>Fast Delivery</h5>
                <p class="text-muted">Quick and reliable delivery to your doorstep</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="feature-icon mb-3">
                    <i class="fas fa-shield-alt fa-3x text-success"></i>
                </div>
                <h5>Secure Payment</h5>
                <p class="text-muted">100% secure and encrypted payment methods</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="feature-icon mb-3">
                    <i class="fas fa-undo fa-3x text-warning"></i>
                </div>
                <h5>Easy Returns</h5>
                <p class="text-muted">Hassle-free return and exchange policy</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="feature-icon mb-3">
                    <i class="fas fa-headset fa-3x text-info"></i>
                </div>
                <h5>24/7 Support</h5>
                <p class="text-muted">Round-the-clock customer support</p>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5 fw-bold mb-3">Shop by Category</h2>
                <p class="lead text-muted">Explore our wide range of product categories</p>
            </div>
        </div>

        <div class="row">
            <?php while ($category = $categories_result->fetch(PDO::FETCH_ASSOC)): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="products/product-list.php?category=<?php echo $category['category_id']; ?>" class="category-card d-block text-decoration-none">
                    <div class="category-content">
                        <i class="fas fa-<?php echo getCategoryIcon($category['category_name']); ?> fa-3x mb-3"></i>
                        <h4><?php echo htmlspecialchars($category['category_name']); ?></h4>
                        <p class="mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5 fw-bold mb-3">Featured Products</h2>
                <p class="lead text-muted">Check out our latest and most popular products</p>
            </div>
        </div>

        <div class="row">
            <?php while ($product = $featured_result->fetch(PDO::FETCH_ASSOC)): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card product-card h-100">
                    <img src="assets/images/products/<?php echo $product['image'] ?: 'default-product.jpg'; ?>"
                         class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text text-muted small"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                        <div class="product-price mb-3">
                            <span class="h5 text-primary fw-bold"><?php echo formatPrice($product['price']); ?></span>
                        </div>
                        <div class="product-actions">
                            <a href="products/product-detail.php?id=<?php echo $product['product_id']; ?>"
                               class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                            <?php if (isLoggedIn()): ?>
                            <button class="btn btn-primary btn-sm add-to-cart-btn"
                                    data-product-id="<?php echo $product['product_id']; ?>">
                                <i class="fas fa-cart-plus me-1"></i>Add to Cart
                            </button>
                            <?php else: ?>
                            <a href="login.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-sign-in-alt me-1"></i>Login to Buy
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="row">
            <div class="col-12 text-center">
                <a href="products/product-list.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-right me-2"></i>View All Products
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h3 class="fw-bold mb-3">Stay Updated with Our Latest Offers</h3>
                <p class="mb-0">Subscribe to our newsletter and get exclusive deals and discounts.</p>
            </div>
            <div class="col-lg-6">
                <form class="newsletter-form">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Enter your email address" required>
                        <button class="btn btn-warning" type="submit">
                            <i class="fas fa-paper-plane me-1"></i>Subscribe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php
// Function to get category icon
function getCategoryIcon($category_name) {
    $icons = [
        'Electronics' => 'laptop',
        'Clothing' => 'tshirt',
        'Home & Garden' => 'home',
        'Books' => 'book',
        'Sports' => 'dumbbell',
        'Beauty' => 'heart'
    ];
    return $icons[$category_name] ?? 'tag';
}

include 'includes/footer.php';
?>
