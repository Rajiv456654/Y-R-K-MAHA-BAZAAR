<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header("Location: manage-orders.php");
    exit();
}

// Get order details
$order_query = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                FROM orders o 
                JOIN users u ON o.user_id = u.user_id 
                WHERE o.order_id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows == 0) {
    header("Location: manage-orders.php");
    exit();
}

$order = $order_result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, p.name, p.image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

// Calculate totals
$subtotal = 0;
$items = [];
while ($item = $items_result->fetch_assoc()) {
    $item['total'] = $item['price'] * $item['quantity'];
    $subtotal += $item['total'];
    $items[] = $item;
}

$tax_rate = 0.18; // 18% GST
$tax_amount = $subtotal * $tax_rate;
$shipping_cost = 0; // Free shipping
$total_amount = $subtotal + $tax_amount + $shipping_cost;

// Include header after all processing is done
$page_title = "Invoice #" . $order_id;
include 'includes/admin-header.php';
?>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    .invoice-container {
        box-shadow: none !important;
        border: none !important;
    }
    
    body {
        background: white !important;
    }
}

.invoice-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 2rem;
    margin: 2rem 0;
}

.invoice-header {
    border-bottom: 3px solid #007bff;
    padding-bottom: 1rem;
    margin-bottom: 2rem;
}

.company-logo {
    font-size: 2rem;
    font-weight: bold;
    color: #007bff;
}

.invoice-title {
    font-size: 2.5rem;
    font-weight: bold;
    color: #333;
}

.invoice-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
}

.table-invoice {
    border: 1px solid #dee2e6;
}

.table-invoice th {
    background: #007bff;
    color: white;
    border: none;
    font-weight: 600;
}

.table-invoice td {
    border-color: #dee2e6;
}

.total-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
}

.total-row {
    font-size: 1.2rem;
    font-weight: bold;
    color: #007bff;
}

.status-badge {
    font-size: 1rem;
    padding: 0.5rem 1rem;
}

.invoice-footer {
    border-top: 2px solid #dee2e6;
    padding-top: 1rem;
    margin-top: 2rem;
    text-align: center;
    color: #6c757d;
}
</style>

<div class="container-fluid">
    <!-- Action Buttons -->
    <div class="row no-print mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <a href="manage-orders.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Orders
                </a>
                <div>
                    <button onclick="window.print()" class="btn btn-primary me-2">
                        <i class="fas fa-print me-1"></i>Print Invoice
                    </button>
                    <button onclick="downloadPDF()" class="btn btn-success">
                        <i class="fas fa-download me-1"></i>Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Container -->
    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <div class="company-logo">
                        <i class="fas fa-store me-2"></i>Y R K MAHA BAZAAR
                    </div>
                    <div class="mt-2">
                        <strong>Complete E-commerce Store</strong><br>
                        123 Business Street<br>
                        City, State 12345<br>
                        Phone: +91 98765 43210<br>
                        Email: info@yrkmaha.com<br>
                        GSTIN: 22AAAAA0000A1Z5
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="invoice-title">INVOICE</div>
                    <div class="mt-3">
                        <strong>Invoice #:</strong> <?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?><br>
                        <strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['order_date'])); ?><br>
                        <strong>Status:</strong> 
                        <span class="badge status-badge bg-<?php 
                            echo $order['status'] == 'Delivered' ? 'success' : 
                                ($order['status'] == 'Cancelled' ? 'danger' : 
                                ($order['status'] == 'Shipped' ? 'primary' : 
                                ($order['status'] == 'Processing' ? 'info' : 'warning'))); 
                        ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer & Order Details -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="invoice-details">
                    <h5 class="fw-bold mb-3">Bill To:</h5>
                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                    <?php echo htmlspecialchars($order['customer_email']); ?><br>
                    <?php echo htmlspecialchars($order['customer_phone']); ?><br><br>
                    <strong>Shipping Address:</strong><br>
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="invoice-details">
                    <h5 class="fw-bold mb-3">Order Details:</h5>
                    <strong>Order ID:</strong> #<?php echo $order['order_id']; ?><br>
                    <strong>Order Date:</strong> <?php echo date('F d, Y g:i A', strtotime($order['order_date'])); ?><br>
                    <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?><br>
                    <strong>Order Status:</strong> <?php echo $order['status']; ?><br>
                    <?php if (!empty($order['tracking_number'])): ?>
                    <strong>Tracking Number:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?><br>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Order Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-invoice">
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        <th width="40%">Product</th>
                        <th width="15%" class="text-center">Quantity</th>
                        <th width="15%" class="text-end">Unit Price</th>
                        <th width="20%" class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="../assets/images/products/<?php echo $item['image'] ?: 'default-product.jpg'; ?>" 
                                     class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                </div>
                            </div>
                        </td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end"><?php echo formatPrice($item['price']); ?></td>
                        <td class="text-end"><?php echo formatPrice($item['total']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals Section -->
        <div class="row">
            <div class="col-md-6">
                <!-- Payment Information -->
                <div class="invoice-details">
                    <h5 class="fw-bold mb-3">Payment Information:</h5>
                    <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?><br>
                    <strong>Payment Status:</strong> 
                    <span class="badge bg-<?php echo $order['status'] == 'Delivered' ? 'success' : 'warning'; ?>">
                        <?php echo $order['status'] == 'Delivered' ? 'Paid' : 'Pending'; ?>
                    </span><br>
                    <strong>Transaction Date:</strong> <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="total-section">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td class="text-end"><?php echo formatPrice($subtotal); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Shipping:</strong></td>
                            <td class="text-end"><?php echo $shipping_cost > 0 ? formatPrice($shipping_cost) : 'FREE'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tax (GST 18%):</strong></td>
                            <td class="text-end"><?php echo formatPrice($tax_amount); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>Total Amount:</strong></td>
                            <td class="text-end"><strong><?php echo formatPrice($total_amount); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Invoice Footer -->
        <div class="invoice-footer">
            <div class="row">
                <div class="col-md-6 text-start">
                    <strong>Terms & Conditions:</strong><br>
                    <small>
                        • Payment is due within 30 days<br>
                        • Returns accepted within 7 days<br>
                        • Warranty as per manufacturer terms
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <strong>Thank you for your business!</strong><br>
                    <small>
                        For any queries, contact us at:<br>
                        Email: support@yrkmaha.com | Phone: +91 98765 43210
                    </small>
                </div>
            </div>
            <hr class="my-3">
            <small class="text-muted">
                This is a computer-generated invoice. No signature required. | Generated on <?php echo date('M d, Y g:i A'); ?>
            </small>
        </div>
    </div>
</div>

<script>
function downloadPDF() {
    // Hide no-print elements
    const noPrintElements = document.querySelectorAll('.no-print');
    noPrintElements.forEach(el => el.style.display = 'none');
    
    // Use browser's print to PDF functionality
    window.print();
    
    // Show no-print elements again after a short delay
    setTimeout(() => {
        noPrintElements.forEach(el => el.style.display = '');
    }, 1000);
}

// Auto-focus on print button for quick access
document.addEventListener('DOMContentLoaded', function() {
    // Add keyboard shortcut for printing (Ctrl+P)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
    });
});
</script>

<?php include 'includes/admin-footer.php'; ?>
