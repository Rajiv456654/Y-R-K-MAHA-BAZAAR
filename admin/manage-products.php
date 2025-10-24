<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit();
}

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    // Check if product has orders
    $check_orders = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
    $check_stmt = $conn->prepare($check_orders);
    $check_stmt->execute([$product_id]);
    $order_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($order_count > 0) {
        // Soft delete - just deactivate
        $delete_query = "UPDATE products SET is_active = FALSE WHERE product_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->execute([$product_id]);
        
        if ($delete_stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Product deactivated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to deactivate product.";
        }
    } else {
        // Hard delete
        $delete_query = "DELETE FROM products WHERE product_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->execute([$product_id]);
        
        if ($delete_stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Product deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to delete product.";
        }
    }
    
    header("Location: manage-products.php");
    exit();
}

// Get filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Build query
$where_conditions = ["1=1"];
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

if ($status_filter === 'active') {
    $where_conditions[] = "p.is_active = TRUE";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "p.is_active = FALSE";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->execute($params);
} else {
    $count_stmt->execute();
}
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get products
$query = "SELECT p.*, c.category_name FROM products p
          LEFT JOIN categories c ON p.category_id = c.category_id
          WHERE $where_clause
          ORDER BY p.created_at DESC
          LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$products_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories_result = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header after all processing is done
$page_title = "Manage Products";
include 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Products</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add-product.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add New Product
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
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
                    <?php foreach ($categories_result as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>"
                            <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Products (<?php echo $total_records; ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (count($products_result) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products_result as $product): ?>
                    <tr>
                        <td>
                            <img src="../assets/images/products/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                                 class="rounded" style="width: 50px; height: 50px; object-fit: cover;" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </td>
                        <td>
                            <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . '...'; ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td><?php echo formatPrice($product['price']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $product['stock'] <= 5 ? 'warning' : 'success'; ?>">
                                <?php echo $product['stock']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'danger'; ?>">
                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="../products/product-detail.php?id=<?php echo $product['product_id']; ?>" 
                                   class="btn btn-outline-info" target="_blank" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit-product.php?id=<?php echo $product['product_id']; ?>" 
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?php echo $product['product_id']; ?>" 
                                   class="btn btn-outline-danger" title="Delete"
                                   onclick="return confirmDelete('Are you sure you want to delete this product?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-box fa-3x text-muted mb-3"></i>
            <h5>No products found</h5>
            <p class="text-muted">Try adjusting your search criteria or add a new product.</p>
            <a href="add-product.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add New Product
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Products pagination" class="mt-4">
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

<?php include 'includes/admin-footer.php'; ?>
