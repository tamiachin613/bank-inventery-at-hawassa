<?php
require_once '../config/config.php';
check_user();

$page_title = 'Notifications';
$message = '';

// Mark notification as read if requested
if (isset($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    header('Location: ' . BASE_URL . 'user/notifications.php');
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

$stmt = $pdo->prepare(
    "SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT $per_page OFFSET $offset"
);
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Get unread count
$unread_count = get_unread_notifications_count($_SESSION['user_id']);

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    Notifications 
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </h1>
                <p class="text-muted mb-0">Stay updated with your request status</p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($unread_count > 0): ?>
                    <a href="?mark_all_read=1" class="btn btn-outline-success">
                        <i class="fas fa-check-double me-2"></i>Mark All Read
                    </a>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-primary d-md-none" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="content-body">
        <?php echo $message; ?>
        
        <?php if (empty($notifications)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Notifications</h5>
                    <p class="text-muted">You don't have any notifications yet. They will appear here when your requests are processed.</p>
                    <a href="request.php" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Submit a Request
                    </a>
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

<?php include '../includes/footer.php'; ?>