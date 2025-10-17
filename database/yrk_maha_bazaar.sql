-- Y R K MAHA BAZAAR E-commerce Database
-- Created for complete e-commerce functionality

CREATE DATABASE IF NOT EXISTS yrk_maha_bazaar;
USE yrk_maha_bazaar;

-- Users table for customer registration
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Admin table for admin login
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table for product categories
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table for all products
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Orders table for customer orders
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_price DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(15) NOT NULL,
    shipping_address TEXT NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'Cash on Delivery',
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Order items table for individual items in orders
CREATE TABLE order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Contact messages table for customer inquiries
CREATE TABLE contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE
);

-- Cart table for shopping cart functionality
CREATE TABLE cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- Insert default admin user (password: admin123)
INSERT INTO admin (username, password, email) VALUES 
('admin', '$2y$10$7rLSvRVyTQORapkDOqmkhetjF6H9lJHiBJk0nBwWGqyQJcKt6xauu', 'admin@yrkmaha.com');

-- Insert sample categories
INSERT INTO categories (category_name, description) VALUES 
('Electronics', 'Electronic devices and gadgets'),
('Clothing', 'Fashion and apparel'),
('Home & Garden', 'Home improvement and garden supplies'),
('Books', 'Books and educational materials'),
('Sports', 'Sports and fitness equipment'),
('Beauty', 'Beauty and personal care products');

-- Insert sample products
INSERT INTO products (name, category_id, description, price, stock, image) VALUES 
('Smartphone', 1, 'Latest Android smartphone with advanced features', 15999.00, 50, 'smartphone.jpg'),
('Laptop', 1, 'High-performance laptop for work and gaming', 45999.00, 25, 'laptop.jpg'),
('T-Shirt', 2, 'Comfortable cotton t-shirt', 599.00, 100, 'tshirt.jpg'),
('Jeans', 2, 'Premium quality denim jeans', 1299.00, 75, 'jeans.jpg'),
('Coffee Maker', 3, 'Automatic coffee maker for home', 2999.00, 30, 'coffee-maker.jpg'),
('Novel Book', 4, 'Bestselling fiction novel', 299.00, 200, 'novel.jpg'),
('Yoga Mat', 5, 'Non-slip yoga mat for fitness', 899.00, 60, 'yoga-mat.jpg'),
('Face Cream', 6, 'Anti-aging face cream', 799.00, 80, 'face-cream.jpg');

-- Insert sample user (password: user123)
INSERT INTO users (name, email, password, phone, address) VALUES 
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543210', '123 Main Street, City, State');
