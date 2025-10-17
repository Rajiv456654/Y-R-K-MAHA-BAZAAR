<?php
// Include required files first
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

$page_title = "About Us";
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="display-6 fw-bold mb-4">About Y R K MAHA BAZAAR</h1>
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="product-list.php">Products</a></li>
                    <li class="breadcrumb-item active">About Us</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-5">
                    <h2 class="display-5 fw-bold mb-3">Your Trusted Shopping Destination</h2>
                    <p class="lead mb-0">Bringing you quality products at unbeatable prices since our inception</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Our Story -->
    <div class="row mb-5">
        <div class="col-lg-6">
            <h3 class="fw-bold mb-4">Our Story</h3>
            <p class="mb-3">
                Y R K MAHA BAZAAR started with a simple vision: to make quality products accessible to everyone at affordable prices. What began as a small local business has grown into a comprehensive e-commerce platform serving customers across the region.
            </p>
            <p class="mb-3">
                We believe that shopping should be convenient, reliable, and enjoyable. That's why we've built our platform with cutting-edge technology to provide you with a seamless shopping experience across all devices.
            </p>
            <p class="mb-4">
                Our commitment to customer satisfaction drives everything we do, from carefully selecting our products to ensuring fast and secure delivery.
            </p>
            <a href="product-list.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
            </a>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-4">
                            <div class="stat-item">
                                <h3 class="text-primary fw-bold">10,000+</h3>
                                <p class="text-muted">Happy Customers</p>
                            </div>
                        </div>
                        <div class="col-6 mb-4">
                            <div class="stat-item">
                                <h3 class="text-primary fw-bold">5,000+</h3>
                                <p class="text-muted">Products</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                <h3 class="text-primary fw-bold">50+</h3>
                                <p class="text-muted">Categories</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                <h3 class="text-primary fw-bold">24/7</h3>
                                <p class="text-muted">Support</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Our Values -->
    <div class="row mb-5">
        <div class="col-12">
            <h3 class="fw-bold text-center mb-5">Our Values</h3>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="value-icon mb-3">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h5 class="card-title">Trust & Security</h5>
                    <p class="card-text">We prioritize your security with encrypted transactions and secure payment processing to protect your personal information.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="value-icon mb-3">
                        <i class="fas fa-star"></i>
                    </div>
                    <h5 class="card-title">Quality Products</h5>
                    <p class="card-text">Every product in our catalog is carefully selected and tested to ensure it meets our high standards of quality and reliability.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="value-icon mb-3">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h5 class="card-title">Customer First</h5>
                    <p class="card-text">Our customers are at the heart of everything we do. We're committed to providing exceptional service and support.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- What We Offer -->
    <div class="row mb-5">
        <div class="col-12">
            <h3 class="fw-bold text-center mb-5">What We Offer</h3>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="feature-item d-flex">
                <div class="feature-icon me-3">
                    <i class="fas fa-laptop"></i>
                </div>
                <div>
                    <h5>Electronics & Technology</h5>
                    <p class="text-muted">Latest smartphones, laptops, gadgets, and tech accessories from top brands.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="feature-item d-flex">
                <div class="feature-icon me-3">
                    <i class="fas fa-tshirt"></i>
                </div>
                <div>
                    <h5>Fashion & Clothing</h5>
                    <p class="text-muted">Trendy apparel, shoes, and accessories for men, women, and children.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="feature-item d-flex">
                <div class="feature-icon me-3">
                    <i class="fas fa-home"></i>
                </div>
                <div>
                    <h5>Home & Garden</h5>
                    <p class="text-muted">Everything you need for your home, from furniture to garden supplies.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="feature-item d-flex">
                <div class="feature-icon me-3">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <div>
                    <h5>Sports & Fitness</h5>
                    <p class="text-muted">Sports equipment, fitness gear, and outdoor recreation products.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Why Choose Us -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h3 class="fw-bold text-center mb-4">Why Choose Y R K MAHA BAZAAR?</h3>
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <div class="benefit-icon mb-2">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <h6>Free Shipping</h6>
                            <small class="text-muted">On all orders with fast delivery</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="benefit-icon mb-2">
                                <i class="fas fa-undo"></i>
                            </div>
                            <h6>Easy Returns</h6>
                            <small class="text-muted">30-day hassle-free returns</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="benefit-icon mb-2">
                                <i class="fas fa-lock"></i>
                            </div>
                            <h6>Secure Payment</h6>
                            <small class="text-muted">Multiple secure payment options</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="benefit-icon mb-2">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h6>24/7 Support</h6>
                            <small class="text-muted">Always here to help you</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Section -->
    <div class="row mb-5">
        <div class="col-12">
            <h3 class="fw-bold text-center mb-5">Meet Our Team</h3>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="team-avatar mx-auto mb-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <h5>Rajesh Kumar</h5>
                    <p class="text-muted">Founder & CEO</p>
                    <p class="small">Leading the vision and strategy of Y R K MAHA BAZAAR with over 15 years of retail experience.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="team-avatar mx-auto mb-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <h5>Priya Sharma</h5>
                    <p class="text-muted">Head of Operations</p>
                    <p class="small">Ensuring smooth operations and exceptional customer service across all departments.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="team-avatar mx-auto mb-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <h5>Amit Patel</h5>
                    <p class="text-muted">Technology Director</p>
                    <p class="small">Building and maintaining our cutting-edge e-commerce platform and mobile experience.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="row">
        <div class="col-12">
            <div class="card bg-primary text-white text-center">
                <div class="card-body py-5">
                    <h3 class="fw-bold mb-3">Ready to Start Shopping?</h3>
                    <p class="lead mb-4">Join thousands of satisfied customers and discover amazing products at great prices!</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="product-list.php" class="btn btn-light btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i>Browse Products
                        </a>
                        <a href="contact.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-envelope me-2"></i>Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.value-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #E50914, #F7C600);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    margin: 0 auto;
}

.feature-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #E50914, #F7C600);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.benefit-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #E50914, #F7C600);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    margin: 0 auto;
}

.team-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #E50914, #F7C600);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
}

.stat-item h3 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}
</style>

<?php include '../includes/footer.php'; ?>
