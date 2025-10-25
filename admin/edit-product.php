<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit();
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: manage-products.php");
    exit();
}

// Get product details using PDO
$product_query = "SELECT * FROM products WHERE product_id = ?";
$product_stmt = $conn->prepare($product_query);
$product_stmt->execute([$product_id]);
$product = $product_stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['error_message'] = "Product not found.";
    header("Location: manage-products.php");
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = sanitizeInput($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($name) || empty($description) || $price <= 0 || $stock < 0 || $category_id <= 0) {
        $error_message = "Please fill in all required fields with valid values.";
    } else {
        // Handle image upload
        $image_name = $product['image']; // Keep existing image by default
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../assets/images/products/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_image = uploadImage($_FILES['image']);
            if ($new_image) {
                // Delete old image if it exists and is not default
                if ($product['image'] && $product['image'] != 'default-product.jpg' && file_exists($upload_dir . $product['image'])) {
                    unlink($upload_dir . $product['image']);
                }
                $image_name = $new_image;
            } else {
                $error_message = "Failed to upload image. Please check file format and size.";
            }
        }
        
        if (empty($error_message)) {
            // Update product using PDO
            $query = "UPDATE products SET name = ?, category_id = ?, description = ?, price = ?, stock = ?, image = ?, is_active = ? WHERE product_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$name, $category_id, $description, $price, $stock, $image_name, $is_active, $product_id]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "Product updated successfully!";
                header("Location: manage-products.php");
                exit();
            } else {
                $error_message = "Failed to update product. Please try again.";
            }
        }
    }
}

// Get categories using PDO
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories_result = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header after all processing is done
$page_title = "Edit Product";
include 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Product</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manage-products.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Products
        </a>
    </div>
</div>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Product Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($product['name']); ?>" 
                                   required>
                            <div class="invalid-feedback">Please enter product name.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories_result as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"
                                        <?php echo $product['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a category.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  required><?php echo htmlspecialchars($product['description']); ?></textarea>
                        <div class="invalid-feedback">Please enter product description.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" id="price" name="price" 
                                       step="0.01" min="0" 
                                       value="<?php echo $product['price']; ?>" 
                                       required>
                                <div class="invalid-feedback">Please enter a valid price.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stock Quantity *</label>
                            <input type="number" class="form-control" id="stock" name="stock" 
                                   min="0" 
                                   value="<?php echo $product['stock']; ?>" 
                                   required>
                            <div class="invalid-feedback">Please enter stock quantity.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image" 
                               accept="image/*" data-preview="image-preview">
                        <div class="form-text">Upload a new image to replace the current one (JPG, PNG, GIF - Max 5MB)</div>
                        <div class="mt-2">
                            <img id="image-preview" 
                                 src="../assets/images/products/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                                 alt="Current Product Image" 
                                 class="img-thumbnail" style="max-width: 200px;">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Product is active (visible to customers)
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="manage-products.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Product Status</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border rounded p-3">
                            <h6>Status</h6>
                            <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'danger'; ?> fs-6">
                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3">
                            <h6>Stock Level</h6>
                            <span class="badge bg-<?php echo $product['stock'] <= 5 ? 'warning' : 'success'; ?> fs-6">
                                <?php echo $product['stock']; ?> units
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Product Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Product ID:</strong></td>
                        <td><?php echo $product['product_id']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Current Price:</strong></td>
                        <td><?php echo formatPrice($product['price']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Current Stock:</strong></td>
                        <td><?php echo $product['stock']; ?> units</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../products/product-detail.php?id=<?php echo $product['product_id']; ?>" 
                       class="btn btn-outline-info" target="_blank">
                        <i class="fas fa-eye me-1"></i>View on Website
                    </a>
                    <a href="manage-products.php?delete=<?php echo $product['product_id']; ?>" 
                       class="btn btn-outline-danger"
                       onclick="return confirmDelete('Are you sure you want to delete this product?')">
                        <i class="fas fa-trash me-1"></i>Delete Product
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image preview functionality
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = '../assets/images/products/<?php echo $product['image'] ?: 'default-product.jpg'; ?>';
                imagePreview.style.display = 'block';
            }
        });
    }

    // Form validation
    const form = document.querySelector('.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
});

// Confirmation dialog for delete actions
function confirmDelete(message) {
    return confirm(message);
}
</script>
