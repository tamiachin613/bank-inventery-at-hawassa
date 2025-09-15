<?php
require_once '../config/config.php';
check_admin();

$page_title = 'Admin Notifications';
$message = '';

// Mark notification as read if requested
if (isset($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    header('Location: ' . BASE_URL . 'admin/notifications.php');
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $message = '<div class="alert alert-success">All notifications marked as read!</div>';
}

// Delete notification
if (isset($_POST['delete_notification'])) {
    $notification_id = (int)$_POST['notification_id'];
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$notification_id, $_SESSION['user_id']])) {
        $message = '<div class="alert alert-success">Notification deleted!</div>';
    }
}

// Send email notification
if (isset($_POST['send_email_alert'])) {
    $alert_type = $_POST['alert_type'];
    
    // Get admin email
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE admin_id = ? AND setting_name = 'admin_email'");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_email = $stmt->fetchColumn();
    
    if ($admin_email) {
        $subject = "CBE Hawassa Branch - System Alert";
        $email_message = "";
        
        switch ($alert_type) {
            case 'low_stock':
                $stmt = $pdo->query("SELECT * FROM items WHERE quantity <= 10 ORDER BY quantity ASC");
                $low_stock_items = $stmt->fetchAll();
                
                $email_message = "Low Stock Alert:\n\n";
                foreach ($low_stock_items as $item) {
                    $email_message .= "- " . $item['item_name'] . ": " . $item['quantity'] . " " . $item['unit'] . " remaining\n";
                }
                break;
                
            case 'pending_requests':
                $stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'");
                $pending_count = $stmt->fetchColumn();
                
                $email_message = "Pending Requests Alert:\n\n";
                $email_message .= "You have " . $pending_count . " pending requests awaiting approval.\n";
                $email_message .= "Please review and process these requests promptly.";
                break;
        }
        
        // Simple email sending (you may want to use PHPMailer for production)
        $headers = "From: CBE Hawassa Branch <noreply@cbe.et>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        if (mail($admin_email, $subject, $email_message, $headers)) {
            $message = '<div class="alert alert-success">Email alert sent successfully!</div>';
        } else {
            $message = '<div class="alert alert-warning">Email sending failed. Please check server configuration.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Admin email not configured. Please update settings.</div>';
    }
}

// Get notifications with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Count total notifications
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_notifications = $stmt->fetchColumn();
$total_pages = ceil($total_notifications / $per_page);

// Get notifications for current page
$per_page = (int)$per_page;
$offset = (int)$offset;
$sql = "SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Get unread count
$unread_count = get_unread_notifications_count($_SESSION['user_id']);

// Get system statistics for notifications
$stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'");
$pending_requests = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM items WHERE quantity <= 10");
$low_stock_count = $stmt->fetchColumn();

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    Admin Notifications 
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </h1>
                <p class="text-muted mb-0">System notifications and alerts</p>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-envelope me-2"></i>Email Alerts
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="send_email_alert" value="1">
                                <input type="hidden" name="alert_type" value="low_stock">
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Send Low Stock Alert
                                </button>
                            </form>
                        </li>
                        <li>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="send_email_alert" value="1">
                                <input type="hidden" name="alert_type" value="pending_requests">
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-clock me-2"></i>Send Pending Requests Alert
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                
                <?php if ($unread_count > 0): ?>
                    <a href="?mark_all_read=1" class="btn btn-outline-primary">
                        <i class="fas fa-check-double me-2"></i>Mark All Read
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="content-body">
        <?php echo $message; ?>
        
        <!-- System Alerts -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">Low Stock Items</h6>
                        <p class="mb-0"><?php echo $low_stock_count; ?> items are running low on stock</p>
                    </div>
                    <a href="inventory.php" class="btn btn-sm btn-warning ms-auto">View</a>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="alert alert-info d-flex align-items-center">
                    <i class="fas fa-clock fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">Pending Requests</h6>
                        <p class="mb-0"><?php echo $pending_requests; ?> requests awaiting approval</p>
                    </div>
                    <a href="requests.php" class="btn btn-sm btn-info ms-auto">Review</a>
                </div>
            </div>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Notifications</h5>
                    <p class="text-muted">System notifications will appear here when events occur.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body p-0">
                    <?php foreach ($notifications as $index => $notification): ?>
                        <div class="d-flex align-items-start p-3 border-bottom <?php echo $notification['status'] === 'unread' ? 'bg-light' : ''; ?>">
                            <div class="me-3">
                                <?php 
                                $icon_class = 'fas fa-info-circle text-primary';
                                if ($notification['type'] === 'success') {
                                    $icon_class = 'fas fa-check-circle text-success';
                                } elseif ($notification['type'] === 'error') {
                                    $icon_class = 'fas fa-times-circle text-danger';
                                } elseif ($notification['type'] === 'warning') {
                                    $icon_class = 'fas fa-exclamation-triangle text-warning';
                                }
                                ?>
                                <i class="<?php echo $icon_class; ?>"></i>
                            </div>
                            
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-1 <?php echo $notification['status'] === 'unread' ? 'fw-bold' : ''; ?>">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo format_datetime($notification['created_at']); ?>
                                        </small>
                                        
                                        <?php if ($notification['status'] === 'unread'): ?>
                                            <span class="badge bg-primary ms-2">New</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if ($notification['status'] === 'unread'): ?>
                                                <li>
                                                    <a class="dropdown-item" href="?mark_read=<?php echo $notification['id']; ?>">
                                                        <i class="fas fa-check me-2"></i>Mark as Read
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                    <button type="submit" name="delete_notification" class="dropdown-item text-danger" 
                                                            onclick="return confirm('Delete this notification?')">
                                                        <i class="fas fa-trash me-2"></i>Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav>
                        <ul class="pagination">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Real-time notification updates
setInterval(function() {
    if (!document.hidden) {
        loadHeaderNotifications();
    }
}, 5000); // Update every 5 seconds

function loadHeaderNotifications() {
    fetch('../ajax/notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateHeaderNotificationBadge(data.unread_count);
            }
        })
        .catch(error => console.log('Notification update error:', error));
}

function updateHeaderNotificationBadge(count) {
    const badge = document.getElementById('headerNotificationBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}
</script>

<?php include '../includes/footer.php'; ?>