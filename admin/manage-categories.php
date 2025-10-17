<?php
$page_title = "Manage Categories";
include 'includes/admin-header.php';

$error_message = '';
$success_message = '';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        // Add new category
        $category_name = sanitizeInput($_POST['category_name']);
        $description = sanitizeInput($_POST['description']);
        
        if (empty($category_name)) {
            $error_message = "Category name is required.";
        } else {
            // Check if category already exists
            $check_query = "SELECT category_id FROM categories WHERE category_name = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $category_name);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $error_message = "Category already exists.";
            } else {
                $insert_query = "INSERT INTO categories (category_name, description) VALUES (?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("ss", $category_name, $description);
                
                if ($insert_stmt->execute()) {
                    $_SESSION['success_message'] = "Category added successfully!";
                    header("Location: manage-categories.php");
                    exit();
                } else {
                    $error_message = "Failed to add category.";
                }
            }
        }
    } elseif (isset($_POST['edit_category'])) {
        // Edit category
        $category_id = (int)$_POST['category_id'];
        $category_name = sanitizeInput($_POST['category_name']);
        $description = sanitizeInput($_POST['description']);
        
        if (empty($category_name)) {
            $error_message = "Category name is required.";
        } else {
            $update_query = "UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssi", $category_name, $description, $category_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success_message'] = "Category updated successfully!";
                header("Location: manage-categories.php");
                exit();
            } else {
                $error_message = "Failed to update category.";
            }
        }
    }
}

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    // Check if category has products
    $check_products = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    $check_stmt = $conn->prepare($check_products);
    $check_stmt->bind_param("i", $category_id);
    $check_stmt->execute();
    $product_count = $check_stmt->get_result()->fetch_assoc()['count'];
    
    if ($product_count > 0) {
        $_SESSION['error_message'] = "Cannot delete category. It has $product_count products associated with it.";
    } else {
        $delete_query = "DELETE FROM categories WHERE category_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $category_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success_message'] = "Category deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete category.";
        }
    }
    
    header("Location: manage-categories.php");
    exit();
}

// Get categories with product count
$categories_query = "SELECT c.*, COUNT(p.product_id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.category_id = p.category_id 
                     GROUP BY c.category_id 
                     ORDER BY c.category_name";
$categories_result = $conn->query($categories_query);

// Get category for editing if edit ID is provided
$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM categories WHERE category_id = ?";
    $edit_stmt = $conn->prepare($edit_query);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result->num_rows > 0) {
        $edit_category = $edit_result->fetch_assoc();
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Categories</h1>
</div>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
</div>
<?php endif; ?>

<div class="row">
    <!-- Add/Edit Category Form -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <?php if ($edit_category): ?>
                    <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" 
                               value="<?php echo $edit_category ? htmlspecialchars($edit_category['category_name']) : ''; ?>" 
                               required>
                        <div class="invalid-feedback">Please enter category name.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if ($edit_category): ?>
                        <button type="submit" name="edit_category" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Category
                        </button>
                        <a href="manage-categories.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <?php else: ?>
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Category
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Categories List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Categories (<?php echo $categories_result->num_rows; ?>)</h5>
            </div>
            <div class="card-body p-0">
                <?php if ($categories_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($category['description']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $category['product_count']; ?> products</span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?edit=<?php echo $category['category_id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../products/product-list.php?category=<?php echo $category['category_id']; ?>" 
                                           class="btn btn-outline-info" target="_blank" title="View Products">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($category['product_count'] == 0): ?>
                                        <a href="?delete=<?php echo $category['category_id']; ?>" 
                                           class="btn btn-outline-danger" title="Delete"
                                           onclick="return confirmDelete('Are you sure you want to delete this category?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-outline-secondary" disabled title="Cannot delete - has products">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                    <h5>No categories found</h5>
                    <p class="text-muted">Add your first category to get started.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Category Statistics -->
        <div class="row mt-4">
            <?php
            $stats_query = "SELECT 
                            COUNT(DISTINCT c.category_id) as total_categories,
                            COUNT(DISTINCT p.product_id) as total_products,
                            AVG(product_counts.product_count) as avg_products_per_category
                            FROM categories c 
                            LEFT JOIN products p ON c.category_id = p.category_id
                            LEFT JOIN (
                                SELECT category_id, COUNT(*) as product_count 
                                FROM products 
                                GROUP BY category_id
                            ) product_counts ON c.category_id = product_counts.category_id";
            $stats_result = $conn->query($stats_query);
            $stats = $stats_result->fetch_assoc();
            ?>
            
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($stats['total_categories']); ?></h3>
                        <p class="mb-0">Total Categories</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($stats['total_products']); ?></h3>
                        <p class="mb-0">Total Products</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3><?php echo number_format($stats['avg_products_per_category'] ?: 0, 1); ?></h3>
                        <p class="mb-0">Avg Products/Category</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>
