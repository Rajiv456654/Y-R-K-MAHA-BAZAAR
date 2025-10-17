// Y R K MAHA BAZAAR JavaScript Functions

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeComponents();
});

// Initialize all JavaScript components
function initializeComponents() {
    initializeCartFunctions();
    initializeFormValidation();
    initializeImagePreview();
    initializeQuantityControls();
    initializeSearchFilters();
    initializeTooltips();
}

// Cart Functions
function initializeCartFunctions() {
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            const quantity = this.dataset.quantity || 1;
            addToCart(productId, quantity);
        });
    });

    // Remove from cart functionality
    const removeFromCartButtons = document.querySelectorAll('.remove-from-cart-btn');
    removeFromCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const cartId = this.dataset.cartId;
            removeFromCart(cartId);
        });
    });
}

// Add product to cart
function addToCart(productId, quantity = 1) {
    // Show loading state
    const button = document.querySelector(`[data-product-id="${productId}"]`);
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="loading"></span> Adding...';
    button.disabled = true;

    fetch('cart/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}&csrf_token=${getCSRFToken()}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Product added to cart successfully!', 'success');
            updateCartCount();
        } else {
            showAlert(data.message || 'Failed to add product to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Remove product from cart
function removeFromCart(cartId) {
    if (!confirm('Are you sure you want to remove this item from cart?')) {
        return;
    }

    fetch('cart/remove-from-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cart_id=${cartId}&csrf_token=${getCSRFToken()}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Product removed from cart', 'success');
            location.reload(); // Reload to update cart
        } else {
            showAlert(data.message || 'Failed to remove product from cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'error');
    });
}

// Update cart count in navigation
function updateCartCount() {
    fetch('cart/get-cart-count.php')
    .then(response => response.json())
    .then(data => {
        const cartBadge = document.querySelector('.navbar .badge');
        if (cartBadge) {
            if (data.count > 0) {
                cartBadge.textContent = data.count;
                cartBadge.style.display = 'inline';
            } else {
                cartBadge.style.display = 'none';
            }
        }
    })
    .catch(error => {
        console.error('Error updating cart count:', error);
    });
}

// Quantity Controls
function initializeQuantityControls() {
    const quantityControls = document.querySelectorAll('.quantity-controls');
    
    quantityControls.forEach(control => {
        const minusBtn = control.querySelector('.quantity-minus');
        const plusBtn = control.querySelector('.quantity-plus');
        const input = control.querySelector('.quantity-input');
        
        if (minusBtn && plusBtn && input) {
            minusBtn.addEventListener('click', function() {
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                    updateCartQuantity(input);
                }
            });
            
            plusBtn.addEventListener('click', function() {
                let value = parseInt(input.value);
                const max = parseInt(input.getAttribute('max')) || 999;
                if (value < max) {
                    input.value = value + 1;
                    updateCartQuantity(input);
                }
            });
            
            input.addEventListener('change', function() {
                updateCartQuantity(this);
            });
        }
    });
}

// Update cart quantity
function updateCartQuantity(input) {
    const cartId = input.dataset.cartId;
    const quantity = input.value;
    
    if (quantity < 1) {
        input.value = 1;
        return;
    }
    
    fetch('cart/update-quantity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cart_id=${cartId}&quantity=${quantity}&csrf_token=${getCSRFToken()}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update total price display
            updateCartTotals();
        } else {
            showAlert(data.message || 'Failed to update quantity', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'error');
    });
}

// Update cart totals
function updateCartTotals() {
    fetch('cart/get-cart-totals.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const subtotalElement = document.getElementById('cart-subtotal');
            const totalElement = document.getElementById('cart-total');
            
            if (subtotalElement) subtotalElement.textContent = '₹' + data.subtotal;
            if (totalElement) totalElement.textContent = '₹' + data.total;
        }
    })
    .catch(error => {
        console.error('Error updating totals:', error);
    });
}

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Image Preview
function initializeImagePreview() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const previewId = this.dataset.preview;
            
            if (file && previewId) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(previewId);
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

// Search and Filter Functions
function initializeSearchFilters() {
    const searchInput = document.querySelector('input[name="search"]');
    const categoryFilter = document.querySelector('select[name="category"]');
    const priceFilter = document.querySelector('select[name="price_range"]');
    
    // Auto-submit search form on category/price change
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            this.form.submit();
        });
    }
    
    if (priceFilter) {
        priceFilter.addEventListener('change', function() {
            this.form.submit();
        });
    }
}

// Initialize Bootstrap Tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Alert System
function showAlert(message, type = 'info') {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    alertContainer.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    
    alertContainer.innerHTML = `
        <i class="fas fa-${getAlertIcon(type)} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertContainer);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertContainer.parentNode) {
            alertContainer.remove();
        }
    }, 5000);
}

// Get alert icon based on type
function getAlertIcon(type) {
    switch(type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}

// Get CSRF Token
function getCSRFToken() {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    return tokenMeta ? tokenMeta.getAttribute('content') : '';
}

// Utility Functions
function formatPrice(price) {
    return '₹' + parseFloat(price).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Admin Dashboard Functions
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

function updateOrderStatus(orderId, status) {
    fetch('admin/update-order-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}&status=${status}&csrf_token=${getCSRFToken()}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Order status updated successfully!', 'success');
            location.reload();
        } else {
            showAlert(data.message || 'Failed to update order status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'error');
    });
}

// Product Image Gallery
function initializeProductGallery() {
    const mainImage = document.getElementById('main-product-image');
    const thumbnails = document.querySelectorAll('.product-thumbnail');
    
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            const newSrc = this.src;
            if (mainImage) {
                mainImage.src = newSrc;
            }
            
            // Update active thumbnail
            thumbnails.forEach(thumb => thumb.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

// Initialize product gallery if on product detail page
if (document.getElementById('main-product-image')) {
    initializeProductGallery();
}
