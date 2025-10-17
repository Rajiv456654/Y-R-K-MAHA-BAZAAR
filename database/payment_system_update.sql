-- Payment System Database Updates
-- Add payment tracking columns to orders table

ALTER TABLE orders 
ADD COLUMN payment_status ENUM('Pending', 'Confirmed', 'Failed', 'Refunded') DEFAULT 'Pending' AFTER payment_method,
ADD COLUMN transaction_id VARCHAR(100) NULL AFTER payment_status,
ADD COLUMN payment_screenshot VARCHAR(255) NULL AFTER transaction_id,
ADD COLUMN payment_verified_at TIMESTAMP NULL AFTER payment_screenshot,
ADD COLUMN payment_verified_by INT NULL AFTER payment_verified_at;

-- Add webhook support columns
ALTER TABLE orders
ADD COLUMN webhook_verified BOOLEAN DEFAULT FALSE AFTER payment_verified_by,
ADD COLUMN auto_confirmed_at TIMESTAMP NULL AFTER webhook_verified;

-- Create payment_logs table for tracking payment attempts
CREATE TABLE IF NOT EXISTS payment_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Initiated', 'Success', 'Failed', 'Pending') NOT NULL,
    gateway_response TEXT NULL,
    webhook_status ENUM('Pending', 'Received', 'Processed', 'Failed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create payment_settings table for storing UPI ID and other payment configurations
CREATE TABLE IF NOT EXISTS payment_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default payment settings
INSERT INTO payment_settings (setting_key, setting_value, description) VALUES
('upi_id', 'rajuyadav75908-5@okaxis', 'Primary UPI ID for receiving payments'),
('merchant_name', 'Y R K MAHA BAZAAR', 'Merchant name for payment display'),
('payment_verification_required', '0', 'Whether UPI payments need manual verification (1=Yes, 0=No)'),
('auto_confirm_card_payments', '1', 'Whether card payments are auto-confirmed (1=Yes, 0=No)'),
('auto_verify_payments', '0', 'Whether to enable automatic payment verification (1=Yes, 0=No)'),
('payment_gateway', 'manual', 'Payment gateway being used (razorpay, payu, manual)'),
('webhook_secret', '', 'Webhook secret for signature verification'),
('payment_timeout_minutes', '30', 'Payment timeout in minutes'),
('min_order_amount', '50', 'Minimum order amount for processing'),
('max_order_amount', '100000', 'Maximum order amount for processing');

-- Create admin_payment_verifications table for tracking admin verifications
CREATE TABLE IF NOT EXISTS admin_payment_verifications (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    admin_id INT NOT NULL,
    transaction_id VARCHAR(100) NOT NULL,
    verification_status ENUM('Approved', 'Rejected') NOT NULL,
    verification_notes TEXT NULL,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_orders_payment_status ON orders(payment_status);
CREATE INDEX idx_orders_transaction_id ON orders(transaction_id);
CREATE INDEX idx_orders_webhook_verified ON orders(webhook_verified);
CREATE INDEX idx_payment_logs_status ON payment_logs(status);
CREATE INDEX idx_payment_logs_webhook_status ON payment_logs(webhook_status);
CREATE INDEX idx_payment_logs_created_at ON payment_logs(created_at);
