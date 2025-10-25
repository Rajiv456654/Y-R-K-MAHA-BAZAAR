<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
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
    
    // Validation
    if (empty($name) || empty($description) || $price <= 0 || $stock < 0 || $category_id <= 0) {
        $error_message = "Please fill in all required fields with valid values.";
    } else {
        // Handle image upload
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image_name = uploadImage($_FILES['image']);
            if (!$image_name) {
                $error_message = "Failed to upload image. Please check file format and size.";
            }
        }
        
        if (empty($error_message)) {
            // Insert product using PDO
            $query = "INSERT INTO products (name, category_id, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$name, $category_id, $description, $price, $stock, $image_name]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "Product added successfully!";
                header("Location: manage-products.php");
                exit();
            } else {
                $error_message = "Failed to add product. Please try again.";
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
$page_title = "Add Product";
include 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add New Product</h1>
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
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   required>
                            <div class="invalid-feedback">Please enter product name.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories_result as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"
                                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
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
                                  required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <div class="invalid-feedback">Please enter product description.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" id="price" name="price" 
                                       step="0.01" min="0" 
                                       value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>" 
                                       required>
                                <div class="invalid-feedback">Please enter a valid price.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stock Quantity *</label>
                            <input type="number" class="form-control" id="stock" name="stock" 
                                   min="0" 
                                   value="<?php echo isset($_POST['stock']) ? $_POST['stock'] : ''; ?>" 
                                   required>
                            <div class="invalid-feedback">Please enter stock quantity.</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image" 
                               accept="image/*" data-preview="image-preview">
                        <div class="form-text">Upload an image for the product (JPG, PNG, GIF - Max 5MB)</div>
                        <div class="mt-2">
                            <img id="image-preview" src="#" alt="Image Preview" 
                                 class="img-thumbnail" style="max-width: 200px; display: none;">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="manage-products.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Product Guidelines</h5>
            </div>
            <div class="card-body">
                <h6>Image Requirements:</h6>
                <ul class="list-unstyled small">
                    <li><i class="fas fa-check text-success me-1"></i>Format: JPG, PNG, GIF</li>
                    <li><i class="fas fa-check text-success me-1"></i>Max size: 5MB</li>
                    <li><i class="fas fa-check text-success me-1"></i>Recommended: 800x800px</li>
                    <li><i class="fas fa-check text-success me-1"></i>Square aspect ratio preferred</li>
                </ul>
                
                <hr>
                
                <h6>Product Information:</h6>
                <ul class="list-unstyled small">
                    <li><i class="fas fa-info text-info me-1"></i>Use clear, descriptive names</li>
                    <li><i class="fas fa-info text-info me-1"></i>Include key features in description</li>
                    <li><i class="fas fa-info text-info me-1"></i>Set competitive pricing</li>
                    <li><i class="fas fa-info text-info me-1"></i>Keep stock levels updated</li>
                </ul>
                
                <hr>
                
                <h6>SEO Tips:</h6>
                <ul class="list-unstyled small">
                    <li><i class="fas fa-lightbulb text-warning me-1"></i>Use relevant keywords</li>
                    <li><i class="fas fa-lightbulb text-warning me-1"></i>Write detailed descriptions</li>
                    <li><i class="fas fa-lightbulb text-warning me-1"></i>Choose appropriate categories</li>
                </ul>
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
                imagePreview.src = '#';
                imagePreview.style.display = 'none';
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
</script>
