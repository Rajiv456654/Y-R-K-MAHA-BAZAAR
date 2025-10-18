<?php
// Include required files first
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

$page_title = "Discover Amazing Products";
include '../includes/header.php';

// Get filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$price_range = isset($_GET['price_range']) ? sanitizeInput($_GET['price_range']) : '';
$sort_by = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'name';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 12;
$offset = ($page - 1) * $records_per_page;

// Build query
$where_conditions = ["p.is_active = TRUE"];
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

if (!empty($price_range)) {
    switch ($price_range) {
        case 'under_500':
            $where_conditions[] = "p.price < 500";
            break;
        case '500_1000':
            $where_conditions[] = "p.price BETWEEN 500 AND 1000";
            break;
        case '1000_5000':
            $where_conditions[] = "p.price BETWEEN 1000 AND 5000";
            break;
        case 'over_5000':
            $where_conditions[] = "p.price > 5000";
            break;
    }
}

$where_clause = implode(" AND ", $where_conditions);

// Sort options
$sort_options = [
    'name' => 'p.name ASC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'newest' => 'p.created_at DESC'
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
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get products
$query = "SELECT p.*, c.category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE $where_clause 
          ORDER BY $order_by 
          LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
// $products_result = $stmt->get_result();  // Removed for PDO

// Get categories for filter
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);
?>

<!-- Hero Section -->
<div class="hero-gradient py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold text-white mb-3">Discover Amazing Products</h1>
                <p class="lead text-white-50 mb-4">Find everything you need from our curated collection of premium products</p>
                <div class="d-flex gap-3">
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">✨ Premium Quality</span>
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">🚚 Fast Delivery</span>
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">💯 Best Prices</span>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="hero-stats">
                    <div class="stat-item">
                        <h3 class="text-white fw-bold"><?php echo $total_records; ?>+</h3>
                        <p class="text-white-50">Products Available</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">

    <!-- Enhanced Filters -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-sm border-0 filter-card">
                <div class="card-header bg-gradient text-white">
                    <h5 class="mb-0">🔍 Find Your Perfect Product</h5>
                </div>
                <div class="card-body p-4">
                    <form method="GET" class="row g-4">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Products</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by name or description...">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
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
                            <label for="price_range" class="form-label">Price Range</label>
                            <select class="form-select" id="price_range" name="price_range">
                                <option value="">All Prices</option>
                                <option value="under_500" <?php echo $price_range == 'under_500' ? 'selected' : ''; ?>>Under ₹500</option>
                                <option value="500_1000" <?php echo $price_range == '500_1000' ? 'selected' : ''; ?>>₹500 - ₹1,000</option>
                                <option value="1000_5000" <?php echo $price_range == '1000_5000' ? 'selected' : ''; ?>>₹1,000 - ₹5,000</option>
                                <option value="over_5000" <?php echo $price_range == 'over_5000' ? 'selected' : ''; ?>>Over ₹5,000</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                <option value="price_low" <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price Low-High</option>
                                <option value="price_high" <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price High-Low</option>
                                <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Apply Filters
                            </button>
                            <a href="product-list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Info -->
    <div class="row mb-3">
        <div class="col-12">
            <p class="text-muted">
                Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $records_per_page, $total_records); ?> 
                of <?php echo $total_records; ?> products
                <?php if (!empty($search)): ?>
                for "<?php echo htmlspecialchars($search); ?>"
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="row">
        <?php if ($stmt->rowCount() > 0): ?>
            <?php while ($product = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card product-card h-100 shadow-hover border-0">
                    <div class="position-relative overflow-hidden">
                        <img src="../assets/images/products/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
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
                                <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" 
                                   class="btn btn-light btn-sm rounded-pill me-2">
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
                            <small class="text-muted d-block">Stock: <?php echo $product['stock']; ?></small>
                        </div>
                        
                        <div class="product-actions">
                            <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" 
                               class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                            
                            <?php if ($product['stock'] > 0): ?>
                                <?php if (isLoggedIn()): ?>
                                <button class="btn btn-primary btn-sm add-to-cart-btn" 
                                        data-product-id="<?php echo $product['product_id']; ?>">
                                    <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                </button>
                                <?php else: ?>
                                <a href="../login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-sign-in-alt me-1"></i>Login to Buy
                                </a>
                                <?php endif; ?>
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
        <?php else: ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h3>No products found</h3>
                <p class="text-muted">Try adjusting your search criteria or browse all products.</p>
                <a href="product-list.php" class="btn btn-primary">View All Products</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="row">
        <div class="col-12">
            <nav aria-label="Product pagination">
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
        </div>
    </div>
    <?php endif; ?>
</div>

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

/* Filter Card Styling */
.filter-card {
    border-radius: 20px;
    overflow: hidden;
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

.overlay-content {
    text-align: center;
}

/* Price Styling */
.product-price {
    border-top: 1px solid #f0f0f0;
    padding-top: 1rem;
}

/* Badges */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
}

/* Form Controls */
.form-control, .form-select {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
    border-radius: 12px;
    margin: 0 0.25rem;
    border: 2px solid #e9ecef;
    color: #667eea;
    transition: all 0.3s ease;
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
}

.pagination .page-link:hover {
    background-color: #667eea;
    border-color: #667eea;
    color: white;
    transform: translateY(-2px);
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
        margin-bottom: 2rem;
    }
    
    .filter-card .card-body {
        padding: 1.5rem !important;
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

.product-card:nth-child(1) { animation-delay: 0.1s; }
.product-card:nth-child(2) { animation-delay: 0.2s; }
.product-card:nth-child(3) { animation-delay: 0.3s; }
.product-card:nth-child(4) { animation-delay: 0.4s; }
</style>

<?php include '../includes/footer.php'; ?>
