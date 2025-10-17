<?php
/**
 * UPI QR Code Generator
 * Generates QR codes for UPI payments
 */

function generateUPIQRCode($upi_id, $merchant_name, $amount, $order_id = '') {
    // UPI URL format for QR code
    $upi_url = "upi://pay?pa=" . urlencode($upi_id) . 
               "&pn=" . urlencode($merchant_name) . 
               "&am=" . $amount . 
               "&cu=INR" .
               "&tn=" . urlencode("Order Payment #" . $order_id);
    
    return $upi_url;
}

function getQRCodeImageURL($upi_url) {
    // Using Google Charts API for QR code generation
    $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($upi_url);
    return $qr_api_url;
}

function generateUPIQRCodeHTML($upi_id, $merchant_name, $amount, $order_id = '') {
    $upi_url = generateUPIQRCode($upi_id, $merchant_name, $amount, $order_id);
    $qr_image_url = getQRCodeImageURL($upi_url);
    
    $html = '
    <div class="upi-qr-container text-center">
        <div class="qr-code-wrapper">
            <img src="' . $qr_image_url . '" alt="UPI QR Code" class="qr-code-image img-fluid" style="max-width: 250px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        </div>
        <div class="qr-instructions mt-3">
            <p class="text-muted mb-2"><strong>Scan with any UPI app</strong></p>
            <div class="upi-apps">
                <span class="badge bg-primary me-1">PhonePe</span>
                <span class="badge bg-success me-1">Google Pay</span>
                <span class="badge bg-info me-1">Paytm</span>
                <span class="badge bg-warning">BHIM</span>
            </div>
        </div>
        <div class="manual-payment-info mt-3">
            <small class="text-muted">
                <strong>Or pay manually:</strong><br>
                UPI ID: <code>' . htmlspecialchars($upi_id) . '</code><br>
                Amount: ₹' . number_format($amount, 2) . '
            </small>
        </div>
    </div>';
    
    return $html;
}

// Function to get UPI settings from database
function getUPISettings($conn) {
    $settings = [];
    $query = "SELECT setting_key, setting_value FROM payment_settings WHERE setting_key IN ('upi_id', 'merchant_name')";
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Set defaults if not found
    if (!isset($settings['upi_id'])) {
        $settings['upi_id'] = 'yrkmaha@paytm';
    }
    if (!isset($settings['merchant_name'])) {
        $settings['merchant_name'] = 'Y R K MAHA BAZAAR';
    }
    
    return $settings;
}
?>
