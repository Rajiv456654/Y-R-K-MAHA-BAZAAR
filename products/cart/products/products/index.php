<?php
// Include required files first
require_once '../../../../includes/db_connect.php';
require_once '../../../../includes/functions.php';
startSession();

// Get featured products
$featured_query = "SELECT p.*, c.category_name FROM products p 
                   JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.is_active = 1 
                   ORDER BY p.created_at DESC 
                   LIMIT 12";
$featured_products = $conn->query($featured_query);

// Get categories with product counts
$cat_query = "SELECT c.*, COUNT(p.product_id) as product_count 
              FROM categories c 
              LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1 
              GROUP BY c.category_id 
              ORDER BY c.category_name";
$categories = $conn->query($cat_query);

// Get statistics
$stats_query = "SELECT 
                (SELECT COUNT(*) FROM products WHERE is_active = 1) as total_products,
                (SELECT COUNT(*) FROM categories) as total_categories,
                (SELECT COUNT(*) FROM users WHERE is_active = 1) as total_users,
                (SELECT COUNT(*) FROM orders WHERE status = 'Delivered') as total_orders";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get latest deals/offers
$deals_query = "SELECT p.*, c.category_name FROM products p 
                JOIN categories c ON p.category_id = c.category_id 
                WHERE p.is_active = 1 AND p.stock > 0
                ORDER BY p.price ASC 
                LIMIT 6";
$deals_products = $conn->query($deals_query);

// Include header after all processing is done
$page_title = "Products Hub - Your Shopping Destination";
include '../../../../includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-gradient py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold text-white mb-3">🏪 Welcome to Products Hub</h1>
                <p class="lead text-white-50 mb-4">Your ultimate shopping destination with premium products, unbeatable prices, and exceptional service</p>
                <div class="d-flex gap-3 flex-wrap">
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">✨ Premium Quality</span>
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">🚚 Fast Delivery</span>
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">💯 Best Prices</span>
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">🔒 Secure Shopping</span>
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">🎯 24/7 Support</span>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="hero-stats">
                    <div class="stat-circle mx-auto mb-3">
                        <h2 class="text-white fw-bold mb-0"><?php echo number_format($stats['total_products']); ?>+</h2>
                        <p class="text-white-50 mb-0">Products</p>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <div class="mini-stat">
                            <h5 class="text-white mb-0"><?php echo number_format($stats['total_categories']); ?></h5>
                            <small class="text-white-50">Categories</small>
                        </div>
                        <div class="mini-stat">
                            <h5 class="text-white mb-0"><?php echo number_format($stats['total_users']); ?>+</h5>
                            <small class="text-white-50">Customers</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Quick Actions -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="text-center mb-4">
                <h2 class="fw-bold">🚀 Quick Actions</h2>
                <p class="text-muted">Everything you need at your fingertips</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="../product-list.php" class="text-decoration-none">
                <div class="action-card text-center">
                    <div class="action-icon mb-3">
                        🛍️
                    </div>
                    <h5 class="fw-bold mb-2">Browse Products</h5>
                    <p class="text-muted small">Explore our complete catalog</p>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="cart/cart.php" class="text-decoration-none">
                <div class="action-card text-center">
                    <div class="action-icon mb-3">
                        🛒
                    </div>
                    <h5 class="fw-bold mb-2">View Cart</h5>
                    <p class="text-muted small">Check your selected items</p>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="orders.php" class="text-decoration-none">
                <div class="action-card text-center">
                    <div class="action-icon mb-3">
                        📦
                    </div>
                    <h5 class="fw-bold mb-2">My Orders</h5>
                    <p class="text-muted small">Track your purchases</p>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="profile.php" class="text-decoration-none">
                <div class="action-card text-center">
                    <div class="action-icon mb-3">
                        👤
                    </div>
                    <h5 class="fw-bold mb-2">My Profile</h5>
                    <p class="text-muted small">Manage your account</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Statistics Dashboard -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="stats-section">
                <div class="row text-center">
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon">📦</div>
                            <div class="stat-number text-primary"><?php echo number_format($stats['total_products']); ?></div>
                            <div class="stat-label">Products Available</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon">📂</div>
                            <div class="stat-number text-success"><?php echo number_format($stats['total_categories']); ?></div>
                            <div class="stat-label">Categories</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon">👥</div>
                            <div class="stat-number text-info"><?php echo number_format($stats['total_users']); ?></div>
                            <div class="stat-label">Happy Customers</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon">✅</div>
                            <div class="stat-number text-warning"><?php echo number_format($stats['total_orders']); ?></div>
                            <div class="stat-label">Orders Delivered</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">🏷️ Shop by Category</h2>
                    <p class="text-muted mb-0">Find products in your favorite categories</p>
                </div>
                <a href="../product-list.php" class="btn btn-outline-primary">
                    View All Products →
                </a>
            </div>
        </div>
        
        <?php if ($categories->num_rows > 0): ?>
            <?php while ($category = $categories->fetch_assoc()): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="../product-list.php?category=<?php echo $category['category_id']; ?>" class="text-decoration-none">
                    <div class="category-card">
                        <div class="category-content">
                            <div class="category-icon">
                                <?php 
                                $icons = [
                                    'Electronics' => '💻',
                                    'Clothing' => '👕', 
                                    'Home & Garden' => '🏠',
                                    'Books' => '📚',
                                    'Sports' => '⚽',
                                    'Beauty' => '💄',
                                    'Toys' => '🧸',
                                    'Automotive' => '🚗'
                                ];
                                echo $icons[$category['category_name']] ?? '🏷️';
                                ?>
                            </div>
                            <div class="category-info">
                                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($category['category_name']); ?></h5>
                                <p class="text-muted mb-2"><?php echo $category['product_count']; ?> products available</p>
                                <?php if (!empty($category['description'])): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="category-arrow">→</div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="col-12">
            <div class="text-center py-5">
                <h4>No categories available</h4>
                <p class="text-muted">Categories will appear here once they are added.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Best Deals Section -->
    <?php if ($deals_products->num_rows > 0): ?>
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">🔥 Best Deals</h2>
                    <p class="text-muted mb-0">Amazing products at unbeatable prices</p>
                </div>
                <a href="../product-list.php?sort=price_low" class="btn btn-outline-success">
                    View All Deals →
                </a>
            </div>
        </div>
        
        <?php while ($product = $deals_products->fetch_assoc()): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card product-card h-100 shadow-hover border-0">
                <div class="position-relative overflow-hidden">
                    <div class="deal-badge">
                        <span class="badge bg-danger">🔥 Deal</span>
                    </div>
                    <img src="../../../../assets/images/products/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                         class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    <div class="product-overlay">
                        <div class="overlay-content">
                            <a href="../../product-detail.php?id=<?php echo $product['product_id']; ?>" 
                               class="btn btn-light btn-sm rounded-pill">
                                👁️ Quick View
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body d-flex flex-column p-4">
                    <div class="mb-2">
                        <span class="badge bg-light text-dark rounded-pill"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    </div>
                    <h6 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h6>
                    <p class="card-text text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?></p>
                    
                    <div class="product-price mb-3">
                        <span class="h6 text-success fw-bold"><?php echo formatPrice($product['price']); ?></span>
                        <small class="text-muted d-block">Stock: <?php echo $product['stock']; ?></small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="../../product-detail.php?id=<?php echo $product['product_id']; ?>" 
                           class="btn btn-outline-primary btn-sm">
                            View Details
                        </a>
                        <button class="btn btn-success btn-sm" 
                                onclick="addToCart(<?php echo $product['product_id']; ?>, 1)">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Featured Products -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">⭐ Featured Products</h2>
                    <p class="text-muted mb-0">Handpicked products just for you</p>
                </div>
                <a href="../product-list.php" class="btn btn-outline-primary">
                    View All Products →
                </a>
            </div>
        </div>
        
        <?php if ($featured_products->num_rows > 0): ?>
            <?php while ($product = $featured_products->fetch_assoc()): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card product-card h-100 shadow-hover border-0">
                    <div class="position-relative overflow-hidden">
                        <img src="../../../../assets/images/products/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                             class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        
                        <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="badge bg-warning rounded-pill px-3">⚠️ Low Stock</span>
                        </div>
                        <?php elseif ($product['stock'] == 0): ?>
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="badge bg-danger rounded-pill px-3">❌ Out of Stock</span>
                        </div>
                        <?php else: ?>
                        <div class="position-absolute top-0 start-0 m-3">
                            <span class="badge bg-success rounded-pill px-3">✓ In Stock</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="product-overlay">
                            <div class="overlay-content">
                                <a href="../../product-detail.php?id=<?php echo $product['product_id']; ?>" 
                                   class="btn btn-light btn-sm rounded-pill">
                                    👁️ Quick View
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body d-flex flex-column p-4">
                        <div class="mb-2">
                            <span class="badge bg-light text-dark rounded-pill"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        </div>
                        <h6 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <p class="card-text text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?></p>
                        
                        <div class="product-price mb-3">
                            <span class="h6 text-primary fw-bold"><?php echo formatPrice($product['price']); ?></span>
                            <small class="text-muted d-block">Stock: <?php echo $product['stock']; ?></small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="../../product-detail.php?id=<?php echo $product['product_id']; ?>" 
                               class="btn btn-outline-primary btn-sm">
                                View Details
                            </a>
                            <?php if ($product['stock'] > 0): ?>
                            <button class="btn btn-primary btn-sm" 
                                    onclick="addToCart(<?php echo $product['product_id']; ?>, 1)">
                                Add to Cart
                            </button>
                            <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled>
                                Out of Stock
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="col-12">
            <div class="text-center py-5">
                <div class="mb-4">
                    <div style="font-size: 4rem;">📦</div>
                </div>
                <h4>No Products Available</h4>
                <p class="text-muted mb-4">Products will appear here once they are added to the catalog.</p>
                <a href="../../../../index.php" class="btn btn-primary">Back to Home</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Call to Action -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="cta-section text-center">
                <div class="cta-content">
                    <h3 class="fw-bold mb-3">Ready to Start Shopping?</h3>
                    <p class="lead mb-4">Join thousands of satisfied customers and discover amazing products at great prices!</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="../product-list.php" class="btn btn-primary btn-lg">
                            🛍️ Browse All Products
                        </a>
                        <a href="../../../../contact.php" class="btn btn-outline-primary btn-lg">
                            💬 Need Help?
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function addToCart(productId, quantity) {
    <?php if (isLoggedIn()): ?>
    fetch('../../../../cart/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Product added to cart! 🎉', 'success');
        } else {
            showAlert('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error adding to cart. Please try again.', 'danger');
    });
    <?php else: ?>
    window.location.href = '../../../../login.php?redirect=' + encodeURIComponent(window.location.href);
    <?php endif; ?>
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
/* Hero Gradient Background */
.hero-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow: hidden;
}

.hero-gradient::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
    opacity: 0.3;
}

.stat-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}

.mini-stat {
    text-align: center;
}

/* Action Cards */
.action-card {
    background: white;
    border-radius: 20px;
    padding: 2rem 1.5rem;
    transition: all 0.3s ease;
    border: 2px solid #f8f9fa;
    height: 100%;
}

.action-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border-color: #667eea;
}

.action-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

/* Stats Section */
.stats-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 20px;
    padding: 3rem 2rem;
}

.stat-card {
    padding: 1rem;
}

.stat-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-weight: 500;
}

/* Category Cards */
.category-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    border: 2px solid #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    border-color: #667eea;
}

.category-content {
    display: flex;
    align-items: center;
    flex-grow: 1;
}

.category-icon {
    font-size: 2.5rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.category-arrow {
    font-size: 1.5rem;
    color: #667eea;
    font-weight: bold;
}

/* Product Cards */
.product-card {
    border-radius: 20px;
    transition: all 0.3s ease;
    overflow: hidden;
    background: white;
}

.shadow-hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.product-image {
    height: 200px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.deal-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 2;
}

/* Product Overlay */
.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.product-card:hover .product-overlay {
    opacity: 1;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 4rem 2rem;
    color: white;
}

/* Buttons */
.btn {
    border-radius: 12px;
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

/* Responsive Design */
@media (max-width: 768px) {
    .hero-gradient {
        padding: 3rem 0 !important;
    }
    
    .display-4 {
        font-size: 2rem;
    }
    
    .action-card, .category-card {
        margin-bottom: 1rem;
    }
    
    .stats-section {
        padding: 2rem 1rem;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .mini-stat h5 {
        font-size: 1rem;
    }
}

/* Loading Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.action-card, .category-card, .product-card {
    animation: fadeInUp 0.6s ease forwards;
}
</style>

<?php include '../../../../includes/footer.php'; ?>
