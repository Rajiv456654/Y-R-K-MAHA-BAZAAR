<?php
// Only include files if they haven't been included already
if (!function_exists('startSession')) {
    require_once __DIR__ . '/db_connect.php';
    require_once __DIR__ . '/functions.php';
}
startSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Y R K MAHA BAZAAR</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Y R K MAHA BAZAAR - Your complete online shopping destination for electronics, clothing, home goods and more. Fast delivery, secure payments, best prices.">
    <meta name="keywords" content="online shopping, e-commerce, electronics, clothing, home goods, fast delivery, secure payments">
    <meta name="author" content="Y R K MAHA BAZAAR">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Y R K MAHA BAZAAR">
    <meta property="og:description" content="Your complete online shopping destination with fast delivery and secure payments.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="<?php echo 'http://' . $_SERVER['HTTP_HOST']; ?>/Y R K MAHA BAZAAR/assets/images/og-image.jpg">
    <meta property="og:site_name" content="Y R K MAHA BAZAAR">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Y R K MAHA BAZAAR">
    <meta name="twitter:description" content="Your complete online shopping destination with fast delivery and secure payments.">
    <meta name="twitter:image" content="<?php echo 'http://' . $_SERVER['HTTP_HOST']; ?>/Y R K MAHA BAZAAR/assets/images/og-image.jpg">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#E50914">
    <meta name="msapplication-navbutton-color" content="#E50914">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="YRK BAZAAR">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="YRK BAZAAR">
    
    <!-- PWA Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/icons/icon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/icons/icon-180x180.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/images/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="144x144" href="assets/images/icons/icon-144x144.png">
    <link rel="apple-touch-icon" sizes="120x120" href="assets/images/icons/icon-120x120.png">
    <link rel="apple-touch-icon" sizes="114x114" href="assets/images/icons/icon-114x114.png">
    <link rel="apple-touch-icon" sizes="76x76" href="assets/images/icons/icon-76x76.png">
    <link rel="apple-touch-icon" sizes="72x72" href="assets/images/icons/icon-72x72.png">
    <link rel="apple-touch-icon" sizes="60x60" href="assets/images/icons/icon-60x60.png">
    <link rel="apple-touch-icon" sizes="57x57" href="assets/images/icons/icon-57x57.png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Microsoft Tiles -->
    <meta name="msapplication-TileColor" content="#E50914">
    <meta name="msapplication-TileImage" content="assets/images/icons/icon-144x144.png">
    <meta name="msapplication-config" content="browserconfig.xml">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style">
    <link rel="preload" href="assets/css/style.css" as="style">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-store me-2"></i>Y R K MAHA BAZAAR
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                
                <!-- Search Form -->
                <form class="d-flex me-3" method="GET" action="products/product-list.php">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search products..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-outline-light" type="submit">
                        Search
                    </button>
                </form>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="cart/cart.php">
                                Cart
                                <?php 
                                $cart_count = getCartCount();
                                if ($cart_count > 0): 
                                ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $cart_count; ?>
                                </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <?php 
                                $user_info = getUserInfo($_SESSION['user_id']);
                                echo htmlspecialchars($user_info['name']); 
                                ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-0" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-0" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Main Content -->
