    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="text-warning mb-3">
                        <i class="fas fa-store me-2"></i>Y R K MAHA BAZAAR
                    </h5>
                    <p class="mb-3">Your one-stop destination for quality products at affordable prices. We provide excellent customer service and fast delivery.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-warning mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light text-decoration-none">Home</a></li>
                        <li><a href="products/product-list.php" class="text-light text-decoration-none">Products</a></li>
                        <li><a href="about.php" class="text-light text-decoration-none">About Us</a></li>
                        <li><a href="contact.php" class="text-light text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-warning mb-3">Categories</h6>
                    <ul class="list-unstyled">
                        <li><a href="products/product-list.php?category=1" class="text-light text-decoration-none">Electronics</a></li>
                        <li><a href="products/product-list.php?category=2" class="text-light text-decoration-none">Clothing</a></li>
                        <li><a href="products/product-list.php?category=3" class="text-light text-decoration-none">Home & Garden</a></li>
                        <li><a href="products/product-list.php?category=4" class="text-light text-decoration-none">Books</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <h6 class="text-warning mb-3">Contact Info</h6>
                    <div class="contact-info">
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt me-2 text-warning"></i>
                            123 Business Street, City, State 12345
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-phone me-2 text-warning"></i>
                            +91 98765 43210
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-envelope me-2 text-warning"></i>
                            info@yrkmaha.com
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-clock me-2 text-warning"></i>
                            Mon - Sat: 9:00 AM - 8:00 PM
                        </p>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Y R K MAHA BAZAAR. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-light text-decoration-none me-3">Privacy Policy</a>
                    <a href="#" class="text-light text-decoration-none me-3">Terms of Service</a>
                    <a href="#" class="text-light text-decoration-none">Return Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    
    <!-- PWA Service Worker Registration -->
    <script>
        // Register Service Worker for PWA functionality
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/Y R K MAHA BAZAAR/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                        
                        // Check for updates
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // Show update available notification
                                    showUpdateNotification();
                                }
                            });
                        });
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
        
        // Show update notification
        function showUpdateNotification() {
            if (confirm('A new version is available! Would you like to update?')) {
                window.location.reload();
            }
        }
        
        // Install prompt for PWA
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later
            deferredPrompt = e;
            // Show install button
            showInstallButton();
        });
        
        function showInstallButton() {
            const installBtn = document.createElement('button');
            installBtn.className = 'btn btn-primary btn-sm position-fixed';
            installBtn.style.cssText = 'bottom: 20px; right: 20px; z-index: 1050; border-radius: 25px; padding: 8px 16px;';
            installBtn.innerHTML = '<i class="fas fa-download me-1"></i>Install App';
            installBtn.onclick = installApp;
            document.body.appendChild(installBtn);
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                if (installBtn.parentNode) {
                    installBtn.remove();
                }
            }, 10000);
        }
        
        function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    }
                    deferredPrompt = null;
                });
            }
        }
        
        // Handle app installed event
        window.addEventListener('appinstalled', (evt) => {
            console.log('App was installed successfully');
            // Remove install button if it exists
            const installBtn = document.querySelector('.btn[onclick="installApp()"]');
            if (installBtn) {
                installBtn.remove();
            }
        });
        
        // Mobile-specific enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Add touch feedback for mobile
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
                
                // Add haptic feedback for buttons (if supported)
                document.querySelectorAll('.btn, .nav-link, .card').forEach(element => {
                    element.addEventListener('touchstart', function() {
                        if (navigator.vibrate) {
                            navigator.vibrate(10); // Very short vibration
                        }
                    });
                });
            }
            
            // Handle network status
            function updateOnlineStatus() {
                const status = navigator.onLine ? 'online' : 'offline';
                document.body.classList.toggle('offline', !navigator.onLine);
                
                if (!navigator.onLine) {
                    showOfflineNotification();
                }
            }
            
            function showOfflineNotification() {
                const notification = document.createElement('div');
                notification.className = 'alert alert-warning position-fixed';
                notification.style.cssText = 'top: 70px; left: 50%; transform: translateX(-50%); z-index: 1060; min-width: 300px; text-align: center;';
                notification.innerHTML = '<i class="fas fa-wifi-slash me-2"></i>You are offline. Some features may not work.';
                document.body.appendChild(notification);
                
                // Auto-remove when back online
                const removeNotification = () => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                    window.removeEventListener('online', removeNotification);
                };
                window.addEventListener('online', removeNotification);
            }
            
            window.addEventListener('online', updateOnlineStatus);
            window.addEventListener('offline', updateOnlineStatus);
            updateOnlineStatus();
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
    
    <!-- Additional page-specific scripts -->
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
</body>
</html>
