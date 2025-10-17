<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
startSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify_payment'])) {
        $order_id = (int)$_POST['order_id'];
        $action = $_POST['action']; // 'approve' or 'reject'
        $notes = sanitizeInput($_POST['verification_notes']);
        
        try {
            $conn->begin_transaction();
            
            if ($action == 'approve') {
                // Update order status to confirmed
                $update_order = "UPDATE orders SET payment_status = 'Confirmed', payment_verified_at = NOW(), payment_verified_by = ?, status = 'Processing' WHERE order_id = ?";
                $update_stmt = $conn->prepare($update_order);
                $update_stmt->bind_param("ii", $admin_id, $order_id);
                $update_stmt->execute();
                
                // Update product stock for approved orders
                $stock_query = "UPDATE products p 
                               JOIN order_items oi ON p.product_id = oi.product_id 
                               SET p.stock = p.stock - oi.quantity 
                               WHERE oi.order_id = ?";
                $stock_stmt = $conn->prepare($stock_query);
                $stock_stmt->bind_param("i", $order_id);
                $stock_stmt->execute();
                
                $verification_status = 'Approved';
                $success_message = "Payment approved and order confirmed successfully!";
            } else {
                // Update order status to failed
                $update_order = "UPDATE orders SET payment_status = 'Failed', payment_verified_at = NOW(), payment_verified_by = ?, status = 'Cancelled' WHERE order_id = ?";
                $update_stmt = $conn->prepare($update_order);
                $update_stmt->bind_param("ii", $admin_id, $order_id);
                $update_stmt->execute();
                
                $verification_status = 'Rejected';
                $success_message = "Payment rejected and order cancelled.";
            }
            
            // Log the verification
            $log_verification = "INSERT INTO admin_payment_verifications (order_id, admin_id, transaction_id, verification_status, verification_notes) 
                                SELECT ?, ?, transaction_id, ?, ? FROM orders WHERE order_id = ?";
            $log_stmt = $conn->prepare($log_verification);
            $log_stmt->bind_param("iissi", $order_id, $admin_id, $verification_status, $notes, $order_id);
            $log_stmt->execute();
            
            $conn->commit();
            $_SESSION['success_message'] = $success_message;
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error processing verification: " . $e->getMessage();
        }
        
        header("Location: payment-verifications.php");
        exit();
    }
}

// Get pending payments for verification
$pending_query = "SELECT o.*, u.name as user_name, u.email as user_email,
                         (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.order_id) as items_total
                  FROM orders o 
                  JOIN users u ON o.user_id = u.user_id 
                  WHERE o.payment_status = 'Pending' AND o.payment_method IN ('UPI', 'Debit/Credit Card')
                  ORDER BY o.order_date DESC";
$pending_result = $conn->query($pending_query);

// Get auto-verified orders
$auto_verified_query = "SELECT o.*, u.name as user_name, u.email as user_email,
                               (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.order_id) as items_total
                        FROM orders o
                        JOIN users u ON o.user_id = u.user_id
                        WHERE o.webhook_verified = TRUE AND o.payment_status = 'Confirmed'
                        ORDER BY o.auto_confirmed_at DESC
                        LIMIT 10";
$auto_verified_result = $conn->query($auto_verified_query);

// Get webhook logs
$webhook_logs_query = "SELECT pl.*, o.customer_name, o.total_price
                      FROM payment_logs pl
                      JOIN orders o ON pl.order_id = o.order_id
                      WHERE pl.webhook_status != 'Pending'
                      ORDER BY pl.created_at DESC
                      LIMIT 20";
$webhook_logs_result = $conn->query($webhook_logs_query);

// Get payment statistics
$stats_query = "SELECT
    COUNT(CASE WHEN payment_status = 'Pending' THEN 1 END) as pending_payments,
    COUNT(CASE WHEN payment_status = 'Confirmed' THEN 1 END) as confirmed_payments,
    COUNT(CASE WHEN payment_status = 'Failed' THEN 1 END) as failed_payments,
    COUNT(CASE WHEN webhook_verified = TRUE THEN 1 END) as auto_verified_payments,
    SUM(CASE WHEN payment_status = 'Confirmed' THEN total_price ELSE 0 END) as confirmed_amount,
    SUM(CASE WHEN payment_status = 'Pending' THEN total_price ELSE 0 END) as pending_amount
    FROM orders
    WHERE payment_method IN ('UPI', 'Debit/Credit Card')";
$stats = $conn->query($stats_query)->fetch_assoc();

$page_title = "Payment Verifications";
include 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">💳 Payment Verifications</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="manage-orders.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-list me-1"></i>All Orders
            </a>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error_message']); endif; ?>

<!-- Payment Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0"><?php echo $stats['pending_payments']; ?></h5>
                        <p class="card-text">Pending Payments</p>
                        <small>₹<?php echo number_format($stats['pending_amount'], 2); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0"><?php echo $stats['confirmed_payments']; ?></h5>
                        <p class="card-text">Confirmed</p>
                        <small>₹<?php echo number_format($stats['confirmed_amount'], 2); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0"><?php echo $stats['failed_payments']; ?></h5>
                        <p class="card-text">Failed/Rejected</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-rupee-sign fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">₹<?php echo number_format($stats['confirmed_amount'], 2); ?></h5>
                        <p class="card-text">Total Confirmed</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto-Verified Orders -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-robot me-2"></i>Auto-Verified Orders
            <span class="badge bg-success ms-2"><?php echo $auto_verified_result->num_rows; ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if ($auto_verified_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Transaction ID</th>
                        <th>Auto-Confirmed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $auto_verified_result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
                            </div>
                        </td>
                        <td>
                            <strong class="text-success">₹<?php echo number_format($order['total_price'], 2); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo $order['payment_method']; ?></span>
                        </td>
                        <td>
                            <code><?php echo htmlspecialchars($order['transaction_id']); ?></code>
                        </td>
                        <td>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>
                                <?php echo date('M d, H:i', strtotime($order['auto_confirmed_at'])); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-robot fa-3x text-muted mb-3"></i>
            <h5>No Auto-Verified Orders</h5>
            <p class="text-muted">Orders confirmed via webhook will appear here</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pending Payments -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-hourglass-half me-2"></i>Pending Payment Verifications
            <span class="badge bg-warning ms-2"><?php echo $pending_result->num_rows; ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if ($pending_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Transaction ID</th>
                        <th>Date</th>
                        <th>Screenshot</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $pending_result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small><br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                            </div>
                        </td>
                        <td>
                            <strong class="text-primary">₹<?php echo number_format($order['total_price'], 2); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo $order['payment_method']; ?></span>
                        </td>
                        <td>
                            <code><?php echo htmlspecialchars($order['transaction_id']); ?></code>
                        </td>
                        <td>
                            <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?>
                        </td>
                        <td>
                            <?php if ($order['payment_screenshot']): ?>
                            <a href="../assets/images/payment_proofs/<?php echo $order['payment_screenshot']; ?>" 
                               target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-image me-1"></i>View
                            </a>
                            <?php else: ?>
                            <span class="text-muted">No screenshot</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success me-1" 
                                    onclick="showVerificationModal(<?php echo $order['order_id']; ?>, 'approve', '<?php echo htmlspecialchars($order['customer_name']); ?>', '<?php echo $order['total_price']; ?>')">
                                <i class="fas fa-check me-1"></i>Approve
                            </button>
                            <button class="btn btn-sm btn-danger" 
                                    onclick="showVerificationModal(<?php echo $order['order_id']; ?>, 'reject', '<?php echo htmlspecialchars($order['customer_name']); ?>', '<?php echo $order['total_price']; ?>')">
                                <i class="fas fa-times me-1"></i>Reject
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h5>No Pending Payments</h5>
            <p class="text-muted">All payments have been verified!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Verifications -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-history me-2"></i>Recent Verifications
        </h5>
    </div>
    <div class="card-body">
        <?php if ($recent_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Verified By</th>
                        <th>Date</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($verification = $recent_result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $verification['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($verification['customer_name']); ?></td>
                        <td>₹<?php echo number_format($verification['total_price'], 2); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $verification['verification_status'] == 'Approved' ? 'success' : 'danger'; ?>">
                                <?php echo $verification['verification_status']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($verification['admin_name']); ?></td>
                        <td><?php echo date('M d, H:i', strtotime($verification['verified_at'])); ?></td>
                        <td>
                            <?php if ($verification['verification_notes']): ?>
                            <small><?php echo htmlspecialchars($verification['verification_notes']); ?></small>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Webhook Logs -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-exchange-alt me-2"></i>Webhook Activity Logs
            <span class="badge bg-info ms-2"><?php echo $webhook_logs_result->num_rows; ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if ($webhook_logs_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Gateway</th>
                        <th>Transaction ID</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = $webhook_logs_result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $log['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($log['customer_name']); ?></td>
                        <td>₹<?php echo number_format($log['amount'], 2); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $log['webhook_status'] == 'Processed' ? 'success' : ($log['webhook_status'] == 'Failed' ? 'danger' : 'warning'); ?>">
                                <?php echo $log['webhook_status']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?php echo $log['payment_method']; ?></span>
                        </td>
                        <td>
                            <small><?php echo htmlspecialchars(substr($log['transaction_id'], 0, 20) . '...'); ?></small>
                        </td>
                        <td>
                            <?php echo date('M d, H:i', strtotime($log['created_at'])); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
            <h5>No Webhook Activity</h5>
            <p class="text-muted">Webhook events will appear here when automatic verification is enabled</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Verification Modal -->
<div class="modal fade" id="verificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Payment Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="modalOrderId">
                    <input type="hidden" name="action" id="modalAction">
                    
                    <div class="alert" id="modalAlert">
                        <div id="modalMessage"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="verification_notes" class="form-label">Verification Notes (Optional)</label>
                        <textarea class="form-control" id="verification_notes" name="verification_notes" rows="3" 
                                  placeholder="Add any notes about this verification..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="verify_payment" class="btn" id="modalSubmitBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showVerificationModal(orderId, action, customerName, amount) {
    document.getElementById('modalOrderId').value = orderId;
    document.getElementById('modalAction').value = action;
    
    const modal = document.getElementById('verificationModal');
    const title = document.getElementById('modalTitle');
    const alert = document.getElementById('modalAlert');
    const message = document.getElementById('modalMessage');
    const submitBtn = document.getElementById('modalSubmitBtn');
    
    if (action === 'approve') {
        title.textContent = 'Approve Payment';
        alert.className = 'alert alert-success';
        message.innerHTML = `<strong>Approve payment for Order #${orderId}</strong><br>Customer: ${customerName}<br>Amount: ₹${amount}`;
        submitBtn.className = 'btn btn-success';
        submitBtn.textContent = 'Approve Payment';
    } else {
        title.textContent = 'Reject Payment';
        alert.className = 'alert alert-danger';
        message.innerHTML = `<strong>Reject payment for Order #${orderId}</strong><br>Customer: ${customerName}<br>Amount: ₹${amount}`;
        submitBtn.className = 'btn btn-danger';
        submitBtn.textContent = 'Reject Payment';
    }
    
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}
</script>

<style>
.card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: none;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75rem;
}

code {
    background-color: #f8f9fa;
    color: #e83e8c;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
}
</style>

<?php include 'includes/admin-footer.php'; ?>
?>
