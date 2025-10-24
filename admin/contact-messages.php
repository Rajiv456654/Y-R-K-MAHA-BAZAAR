<?php
$page_title = "Contact Messages";
include 'includes/admin-header.php';

// Handle message actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    $action = sanitizeInput($_GET['action']);

    if ($action == 'mark_read') {
        $update_query = "UPDATE contact_messages SET is_read = TRUE WHERE message_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute([$message_id]);

        if ($update_stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Message marked as read.";
        } else {
            $_SESSION['error_message'] = "Failed to update message status.";
        }
    } elseif ($action == 'delete') {
        $delete_query = "DELETE FROM contact_messages WHERE message_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->execute([$message_id]);

        if ($delete_stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Message deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to delete message.";
        }
    }

    header("Location: contact-messages.php");
    exit();
}

// Get filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$subject_filter = isset($_GET['subject']) ? sanitizeInput($_GET['subject']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Build query
$where_conditions = ["1=1"];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}

if ($status_filter === 'read') {
    $where_conditions[] = "is_read = TRUE";
} elseif ($status_filter === 'unread') {
    $where_conditions[] = "is_read = FALSE";
}

if (!empty($subject_filter)) {
    $where_conditions[] = "subject = ?";
    $params[] = $subject_filter;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM contact_messages WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->execute($params);
} else {
    $count_stmt->execute();
}
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get messages
$query = "SELECT * FROM contact_messages 
          WHERE $where_clause 
          ORDER BY date DESC 
          LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$messages_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique subjects for filter
$subjects_query = "SELECT DISTINCT subject FROM contact_messages WHERE subject IS NOT NULL AND subject != '' ORDER BY subject";
$subjects_stmt = $conn->prepare($subjects_query);
$subjects_stmt->execute();
$subjects_result = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Contact Messages</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-download me-1"></i>Export
            </button>
        </div>
    </div>
</div>

<!-- Message Statistics -->
<div class="row mb-4">
    <?php
    $stats_query = "SELECT 
                    COUNT(*) as total_messages,
                    SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread_messages,
                    SUM(CASE WHEN is_read = TRUE THEN 1 ELSE 0 END) as read_messages,
                    SUM(CASE WHEN date >= CURRENT_DATE - INTERVAL '7 days' THEN 1 ELSE 0 END) as recent_messages
                    FROM contact_messages";
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6>Total Messages</h6>
                        <h3><?php echo number_format($stats['total_messages'] ?? 0); ?></h3>
                    </div>
                    <i class="fas fa-envelope fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6>Unread</h6>
                        <h3><?php echo number_format($stats['unread_messages'] ?? 0); ?></h3>
                    </div>
                    <i class="fas fa-envelope-open fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6>Read</h6>
                        <h3><?php echo number_format($stats['read_messages'] ?? 0); ?></h3>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6>This Week</h6>
                        <h3><?php echo number_format($stats['recent_messages'] ?? 0); ?></h3>
                    </div>
                    <i class="fas fa-calendar-week fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search Messages</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Name, email, message content...">
            </div>
            
            <div class="col-md-3">
                <label for="subject" class="form-label">Subject</label>
                <select class="form-select" id="subject" name="subject">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects_result as $subject): ?>
                    <option value="<?php echo htmlspecialchars($subject['subject']); ?>"
                            <?php echo $subject_filter == $subject['subject'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($subject['subject']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="unread" <?php echo $status_filter == 'unread' ? 'selected' : ''; ?>>Unread</option>
                    <option value="read" <?php echo $status_filter == 'read' ? 'selected' : ''; ?>>Read</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Messages Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Messages (<?php echo $total_records; ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (count($messages_result) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages_result as $message): ?>
                    <tr class="<?php echo !$message['is_read'] ? 'table-warning' : ''; ?>">
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($message['email']); ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo htmlspecialchars($message['subject']); ?></span>
                        </td>
                        <td>
                            <div style="max-width: 300px;">
                                <?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>
                                <?php if (strlen($message['message']) > 100): ?>...<?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($message['date'])); ?>
                            <br>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($message['date'])); ?></small>
                        </td>
                        <td>
                            <?php if ($message['is_read']): ?>
                            <span class="badge bg-success">Read</span>
                            <?php else: ?>
                            <span class="badge bg-warning">Unread</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-info" 
                                        data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $message['message_id']; ?>"
                                        title="View Full Message">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (!$message['is_read']): ?>
                                <a href="?action=mark_read&id=<?php echo $message['message_id']; ?>" 
                                   class="btn btn-outline-success" title="Mark as Read">
                                    <i class="fas fa-check"></i>
                                </a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?php echo $message['message_id']; ?>" 
                                   class="btn btn-outline-danger" title="Delete"
                                   onclick="return confirmDelete('Are you sure you want to delete this message?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Message Modal -->
                    <div class="modal fade" id="messageModal<?php echo $message['message_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Message from <?php echo htmlspecialchars($message['name']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>From:</strong> <?php echo htmlspecialchars($message['name']); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Email:</strong> <?php echo htmlspecialchars($message['email']); ?>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Subject:</strong> <?php echo htmlspecialchars($message['subject']); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($message['date'])); ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Message:</strong>
                                        <div class="mt-2 p-3 bg-light rounded">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Re: <?php echo htmlspecialchars($message['subject']); ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-reply me-1"></i>Reply via Email
                                    </a>
                                    <?php if (!$message['is_read']): ?>
                                    <a href="?action=mark_read&id=<?php echo $message['message_id']; ?>" 
                                       class="btn btn-success">
                                        <i class="fas fa-check me-1"></i>Mark as Read
                                    </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
            <h5>No messages found</h5>
            <p class="text-muted">No contact messages match your search criteria.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Messages pagination" class="mt-4">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        </li>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                <?php echo $i; ?>
            </a>
        </li>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include 'includes/admin-footer.php'; ?>
