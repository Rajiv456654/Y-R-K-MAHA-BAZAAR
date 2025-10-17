<?php
// Include required files first
require_once '../../../../includes/db_connect.php';
require_once '../../../../includes/functions.php';
startSession();

// Get search parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort_by = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'name';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 12;
$offset = ($page - 1) * $records_per_page;

// Build WHERE clause
$where_conditions = ["p.is_active = 1"];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $param_types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

// Sort options
$sort_options = [
    'name' => 'p.name ASC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'newest' => 'p.created_at DESC',
    'popular' => 'p.name ASC'
];

$order_by = isset($sort_options[$sort_by]) ? $sort_options[$sort_by] : 'p.name ASC';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get products
$query = "SELECT p.*, c.category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE $where_clause 
          ORDER BY $order_by 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$products_result = $stmt->get_result();

// Get categories for filter
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);

// Include header after all processing is done
$page_title = !empty($search) ? "Search: " . htmlspecialchars($search) : "Product Catalog";
include '../../../../includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-gradient py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <?php if (!empty($search)): ?>
                <h1 class="display-4 fw-bold text-white mb-3">🔍 Search Results</h1>
                <p class="lead text-white-50 mb-4">Found <?php echo $total_records; ?> products matching "<?php echo htmlspecialchars($search); ?>"</p>
                <?php else: ?>
                <h1 class="display-4 fw-bold text-white mb-3">🛍️ Product Catalog</h1>
                <p class="lead text-white-50 mb-4">Explore our amazing collection of premium products</p>
                <?php endif; ?>
                <div class="d-flex gap-3 flex-wrap">
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">✨ Premium Quality</span>
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">🚚 Fast Delivery</span>
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">💯 Best Prices</span>
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">🔒 Secure Shopping</span>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="hero-stats">
                    <div class="stat-circle mx-auto">
                        <h2 class="text-white fw-bold mb-0"><?php echo $total_records; ?></h2>
                        <p class="text-white-50 mb-0">Products</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Search Alert -->
    <?php if (!empty($search)): ?>
    <div class="alert alert-primary mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-search me-3 fa-2x"></i>
            <div>
                <h5 class="mb-1">🔍 Searching for: "<?php echo htmlspecialchars($search); ?>"</h5>
                <p class="mb-0">
                    <?php if ($total_records > 0): ?>
                    Found <strong><?php echo $total_records; ?></strong> matching products in our catalog
                    <?php else: ?>
                    No products found. Try different keywords or <a href="product-list.php" class="alert-link">browse all products</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="card filter-card mb-4">
        <div class="card-header bg-gradient text-white">
            <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Advanced Product Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search_input" class="form-label">🔍 Search Products</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="search_input" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search for products...">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label for="category" class="form-label">📂 Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['category_id']; ?>" 
                                <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="sort" class="form-label">🔄 Sort By</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="price_low" <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="popular" <?php echo $sort_by == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Grid -->
    <?php if ($products_result->num_rows > 0): ?>
    <div class="row">
        <?php while ($product = $products_result->fetch_assoc()): ?>
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="card product-card h-100 shadow-hover border-0">
                <div class="position-relative overflow-hidden">
                    <img src="../../../../assets/images/products/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                         class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    <!-- Stock Badges -->
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
                    
                    <!-- Hover Overlay -->
                    <div class="product-overlay">
                        <div class="overlay-content">
                            <a href="../../../product-detail.php?id=<?php echo $product['product_id']; ?>" 
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
                    <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="card-text text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($product['description'], 0, 80)) . '...'; ?></p>
                    
                    <div class="product-price mb-3">
                        <span class="h5 text-primary fw-bold"><?php echo formatPrice($product['price']); ?></span>
                        <small class="text-muted d-block">Stock: <?php echo $product['stock']; ?> units</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="../../../product-detail.php?id=<?php echo $product['product_id']; ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>View Details
                        </a>
                        <?php if ($product['stock'] > 0): ?>
                        <button class="btn btn-primary btn-sm" 
                                onclick="addToCart(<?php echo $product['product_id']; ?>, 1)">
                            <i class="fas fa-cart-plus me-1"></i>Add to Cart
                        </button>
                        <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled>
                            <i class="fas fa-times me-1"></i>Out of Stock
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Products pagination" class="mt-5">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <!-- No Products Found -->
    <div class="text-center py-5">
        <div class="mb-4">
            <?php if (!empty($search)): ?>
            <i class="fas fa-search-minus fa-5x text-muted"></i>
            <?php else: ?>
            <i class="fas fa-box-open fa-5x text-muted"></i>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($search)): ?>
        <h3 class="fw-bold mb-3">No Results for "<?php echo htmlspecialchars($search); ?>"</h3>
        <p class="text-muted mb-4">We couldn't find any products matching your search criteria.</p>
        
        <div class="row justify-content-center mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">💡 Search Suggestions</h5>
                        <ul class="list-unstyled text-start">
                            <li>• Double-check your spelling</li>
                            <li>• Try more general keywords</li>
                            <li>• Use fewer search terms</li>
                            <li>• Browse by category instead</li>
                            <li>• Check out our featured products</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="product-list.php" class="btn btn-primary">
                <i class="fas fa-th-large me-1"></i>Browse All Products
            </a>
            <a href="../../../../index.php" class="btn btn-outline-primary">
                <i class="fas fa-home me-1"></i>Back to Home
            </a>
        </div>
        
        <?php else: ?>
        <h3 class="fw-bold mb-3">No Products Available</h3>
        <p class="text-muted mb-4">Our product catalog is currently empty. Please check back later!</p>
        <a href="../../../../index.php" class="btn btn-primary">
            <i class="fas fa-home me-1"></i>Back to Home
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
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
            // Update cart count if element exists
            const cartCount = document.querySelector('.badge');
            if (cartCount && data.cart_count) {
                cartCount.textContent = data.cart_count;
            }
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

/* Filter Card Styling */
.filter-card {
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    height: 250px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
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

/* Pagination */
.pagination .page-link {
    border-radius: 8px;
    margin: 0 2px;
    border: none;
    color: #667eea;
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-gradient {
        padding: 3rem 0 !important;
    }
    
    .display-4 {
        font-size: 2rem;
    }
    
    .product-card {
        margin-bottom: 1rem;
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

.product-card {
    animation: fadeInUp 0.6s ease forwards;
}
</style>

<?php include '../../../../includes/footer.php'; ?>
