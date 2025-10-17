# Y R K MAHA BAZAAR - Complete E-commerce Website

A fully functional, responsive e-commerce website built with HTML, CSS, JavaScript, PHP, and MySQL.

## 🚀 Features

### User Side (Frontend)
- **Homepage**: Attractive banner, featured products, categories showcase
- **Product Catalog**: Advanced search, filtering, pagination, category browsing
- **Product Details**: Detailed product information, image gallery, add to cart
- **Shopping Cart**: Add/remove items, quantity management, real-time totals
- **Checkout Process**: Secure checkout with multiple payment options
- **User Authentication**: Registration, login, logout with secure password hashing
- **User Profile**: Profile management, order history, account settings
- **Order Tracking**: View order status, order details, cancel orders
- **Contact System**: Contact form with inquiry management

### Admin Side (Backend)
- **Admin Dashboard**: Statistics, charts, recent orders, low stock alerts
- **Product Management**: Add, edit, delete products with image upload
- **Order Management**: View orders, update status, order details
- **User Management**: View users, activate/deactivate accounts, user statistics
- **Category Management**: Manage product categories
- **Contact Messages**: View and manage customer inquiries
- **Secure Admin Panel**: Protected admin area with authentication

### Security Features
- **Password Hashing**: Secure password storage using PHP password_hash()
- **Session Management**: Secure session handling and validation
- **Input Sanitization**: Protection against XSS and SQL injection
- **CSRF Protection**: Cross-site request forgery prevention
- **File Upload Security**: Secure image upload with validation
- **Access Control**: Role-based access for admin and user areas

## 🛠️ Technology Stack

| Component | Technology |
|-----------|------------|
| **Frontend** | HTML5, CSS3, JavaScript, Bootstrap 5 |
| **Backend** | PHP 7.4+ |
| **Database** | MySQL 5.7+ |
| **Icons** | Font Awesome 6 |
| **Charts** | Chart.js |
| **Fonts** | Google Fonts (Poppins) |

## 📁 Project Structure

```
Y R K MAHA BAZAAR/
│
├── index.php                 # Homepage
├── about.php                 # About us page
├── contact.php               # Contact page
├── login.php                 # User login
├── register.php              # User registration
├── logout.php                # User logout
├── profile.php               # User profile
├── orders.php                # User order history
├── cancel-order.php          # Order cancellation
│
├── products/
│   ├── product-list.php      # Product catalog
│   └── product-detail.php    # Product details
│
├── cart/
│   ├── cart.php              # Shopping cart
│   ├── checkout.php          # Checkout process
│   ├── order-success.php     # Order confirmation
│   ├── add-to-cart.php       # Add to cart API
│   ├── remove-from-cart.php  # Remove from cart API
│   ├── update-quantity.php   # Update cart quantity API
│   ├── get-cart-totals.php   # Get cart totals API
│   └── get-cart-count.php    # Get cart count API
│
├── admin/
│   ├── admin-login.php       # Admin login
│   ├── admin-logout.php      # Admin logout
│   ├── dashboard.php         # Admin dashboard
│   ├── add-product.php       # Add new product
│   ├── edit-product.php      # Edit product
│   ├── manage-products.php   # Product management
│   ├── manage-orders.php     # Order management
│   ├── manage-users.php      # User management
│   ├── manage-categories.php # Category management
│   ├── contact-messages.php  # Contact messages
│   └── includes/
│       ├── admin-header.php  # Admin header
│       └── admin-footer.php  # Admin footer
│
├── includes/
│   ├── header.php            # Main header
│   ├── footer.php            # Main footer
│   ├── db_connect.php        # Database connection
│   └── functions.php         # Common functions
│
├── assets/
│   ├── css/
│   │   └── style.css         # Custom styles
│   ├── js/
│   │   └── script.js         # Custom JavaScript
│   └── images/
│       ├── products/         # Product images
│       └── default-product.jpg
│
└── database/
    └── yrk_maha_bazaar.sql   # Database structure
```

## 🗄️ Database Schema

### Tables
- **users**: Customer accounts and profiles
- **admin**: Admin user accounts
- **categories**: Product categories
- **products**: Product catalog
- **orders**: Customer orders
- **order_items**: Individual order items
- **contact_messages**: Customer inquiries
- **cart**: Shopping cart items

## 🚀 Installation Guide

### Prerequisites
- XAMPP (Apache, MySQL, PHP 7.4+)
- Web browser
- Text editor (VS Code, Windsurf IDE)

### Step 1: Setup XAMPP
1. Download and install XAMPP
2. Start Apache and MySQL services
3. Access phpMyAdmin at `http://localhost/phpmyadmin`

### Step 2: Database Setup
1. Create a new database named `yrk_maha_bazaar`
2. Import the SQL file: `database/yrk_maha_bazaar.sql`
3. Verify all tables are created successfully

### Step 3: File Setup
1. Copy all project files to `C:\xampp\htdocs\Y R K MAHA BAZAAR\`
2. Ensure proper folder permissions for image uploads
3. Update database credentials in `includes/db_connect.php` if needed

### Step 4: Configuration
1. Check database connection settings
2. Ensure all file paths are correct
3. Create product images directory with write permissions

### Step 5: Testing
1. Access the website at `http://localhost/Y R K MAHA BAZAAR/`
2. Test user registration and login
3. Test admin panel at `http://localhost/Y R K MAHA BAZAAR/admin/`

## 🔐 Default Credentials

### Admin Access
- **URL**: `http://localhost/Y R K MAHA BAZAAR/admin/admin-login.php`
- **Username**: `admin`
- **Password**: `admin123`

### Demo User Account
- **Email**: `john@example.com`
- **Password**: `user123`

## 🎨 Design Features

### Color Scheme
- **Primary**: Red (#E50914)
- **Secondary**: Yellow (#F7C600)
- **Success**: Green (#28a745)
- **Warning**: Orange (#ffc107)

### UI/UX Features
- Fully responsive design (mobile-first approach)
- Modern card-based layout
- Interactive hover effects
- Loading animations
- Toast notifications
- Modal dialogs
- Pagination
- Search and filtering
- Real-time cart updates

## 🔧 Key Features Implementation

### Security Measures
```php
// Password hashing
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Input sanitization
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Session management
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}
```

### Database Operations
```php
// Prepared statements for security
$stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
```

### AJAX Implementation
```javascript
// Add to cart functionality
function addToCart(productId, quantity) {
    fetch('cart/add-to-cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Product added to cart!', 'success');
            updateCartCount();
        }
    });
}
```

## 📱 Responsive Design

The website is fully responsive and works perfectly on:
- **Desktop**: Full-featured experience
- **Tablet**: Optimized layout and navigation
- **Mobile**: Touch-friendly interface with collapsible menus

## 🚀 Deployment

### Local Development
1. Use XAMPP for local development
2. Access via `http://localhost/Y R K MAHA BAZAAR/`

### Production Deployment
1. **Hosting Options**: Hostinger, 000WebHost, InfinityFree
2. **Upload Files**: Copy all files to `public_html` directory
3. **Database**: Import SQL file via hosting control panel
4. **Configuration**: Update database credentials
5. **Testing**: Verify all functionality works

### Post-Deployment Checklist
- [ ] Database connection working
- [ ] User registration/login functional
- [ ] Admin panel accessible
- [ ] Product images uploading
- [ ] Email functionality (if configured)
- [ ] SSL certificate installed
- [ ] Mobile responsiveness verified

## 🔍 Testing Guidelines

### User Testing
1. Register new account
2. Browse products and categories
3. Add items to cart
4. Complete checkout process
5. View order history
6. Update profile information

### Admin Testing
1. Login to admin panel
2. Add/edit products
3. Manage orders and users
4. View dashboard statistics
5. Handle contact messages

## 🛡️ Security Best Practices

1. **Regular Updates**: Keep PHP and MySQL updated
2. **Backup Strategy**: Regular database backups
3. **SSL Certificate**: Use HTTPS in production
4. **File Permissions**: Proper server file permissions
5. **Input Validation**: Always validate user input
6. **Error Handling**: Proper error logging and handling

## 📞 Support & Contact

For support, feature requests, or bug reports:
- **Email**: support@yrkmaha.com
- **Phone**: +91 98765 43210

## 📄 License

This project is created for educational and commercial purposes. Feel free to modify and use as needed.

## 🙏 Acknowledgments

- Bootstrap team for the excellent CSS framework
- Font Awesome for the beautiful icons
- Chart.js for dashboard analytics
- Google Fonts for typography

---

**Y R K MAHA BAZAAR** - Your Complete E-commerce Solution 🛒✨
