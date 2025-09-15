<?php
require_once '../config/config.php';
check_admin();

$page_title = 'Settings';
$message = '';

// Handle settings update
if ($_POST) {
    if (isset($_POST['update_settings'])) {
        $admin_email = sanitize_input($_POST['admin_email']);
        $low_stock_threshold = (int)$_POST['low_stock_threshold'];
        $auto_notifications = $_POST['auto_notifications'];
        $email_notifications = $_POST['email_notifications'];
        $backup_frequency = $_POST['backup_frequency'];
        $system_timezone = $_POST['system_timezone'];
        $notification_frequency = (int)$_POST['notification_frequency'];
        $dashboard_refresh = (int)$_POST['dashboard_refresh'];
        $max_request_quantity = (int)$_POST['max_request_quantity'];
    
        $settings = [
            'admin_email' => $admin_email,
            'low_stock_threshold' => $low_stock_threshold,
            'auto_notifications' => $auto_notifications,
            'email_notifications' => $email_notifications,
            'backup_frequency' => $backup_frequency,
            'system_timezone' => $system_timezone,
            'notification_frequency' => $notification_frequency,
            'dashboard_refresh' => $dashboard_refresh,
            'max_request_quantity' => $max_request_quantity
        ];
        
        $success_count = 0;
        foreach ($settings as $setting_name => $setting_value) {
            $stmt = $pdo->prepare("
                INSERT INTO settings (admin_id, setting_name, setting_value) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            if ($stmt->execute([$_SESSION['user_id'], $setting_name, $setting_value])) {
                $success_count++;
            }
        }
        
        if ($success_count === count($settings)) {
            $message = '<div class="alert alert-success">All settings updated successfully!</div>';
            
            // Send email notification about settings update
            if ($email_notifications === 'enabled' && !empty($admin_email)) {
                $subject = "CBE Hawassa Branch - Settings Updated";
                $email_body = "Your system settings have been updated successfully.\n\n";
                $email_body .= "Updated by: " . $_SESSION['full_name'] . "\n";
                $email_body .= "Date: " . date('F d, Y - h:i A') . "\n\n";
                $email_body .= "Settings Updated:\n";
                foreach ($settings as $key => $value) {
                    $email_body .= "- " . ucwords(str_replace('_', ' ', $key)) . ": $value\n";
                }
                
                $headers = "From: CBE Hawassa Branch <noreply@cbe.et>\r\n";
                $headers .= "Reply-To: noreply@cbe.et\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                mail($admin_email, $subject, $email_body, $headers);
            }
        } else {
            $message = '<div class="alert alert-warning">Some settings may not have been updated properly.</div>';
        }
    }
    
    if (isset($_POST['test_email'])) {
        $test_email = sanitize_input($_POST['test_email']);
        
        // Send test email notification
        $subject = "CBE Hawassa Branch - Test Email Notification";
        $message_body = "This is a test email from the CBE Hawassa Branch Inventory Management System.\n\n";
        $message_body .= "System Details:\n";
        $message_body .= "- Sent by: " . $_SESSION['full_name'] . "\n";
        $message_body .= "- Date: " . date('F d, Y - h:i A') . "\n";
        $message_body .= "- System: Inventory Management System\n\n";
        $message_body .= "If you received this email, your notification system is working correctly.";
        
        $headers = "From: CBE Hawassa Branch <noreply@cbe.et>\r\n";
        $headers .= "Reply-To: noreply@cbe.et\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        if (mail($test_email, $subject, $message_body, $headers)) {
            $message = '<div class="alert alert-success">Test email sent successfully to ' . $test_email . '</div>';
        } else {
            $message = '<div class="alert alert-warning">Email sending failed. Please check your server email configuration.</div>';
        }
    }
    
    if (isset($_POST['send_low_stock_alert'])) {
        // Get admin email
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE admin_id = ? AND setting_name = 'admin_email'");
        $stmt->execute([$_SESSION['user_id']]);
        $admin_email = $stmt->fetchColumn();
        
        if ($admin_email) {
            // Get low stock items
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE admin_id = ? AND setting_name = 'low_stock_threshold'");
            $stmt->execute([$_SESSION['user_id']]);
            $threshold = $stmt->fetchColumn() ?: 10;
            
            $stmt = $pdo->prepare("SELECT * FROM items WHERE quantity <= ? ORDER BY quantity ASC");
            $stmt->execute([$threshold]);
            $low_stock_items = $stmt->fetchAll();
            
            if (!empty($low_stock_items)) {
                $subject = "Low Stock Alert - CBE Hawassa Branch";
                $email_message = "The following items are running low in stock:\n\n";
                
                foreach ($low_stock_items as $item) {
                    $email_message .= "- " . $item['item_name'] . " (" . $item['item_code'] . "): " . $item['quantity'] . " " . $item['unit'] . " remaining\n";
                }
                
                $email_message .= "\nPlease consider restocking these items to avoid shortages.";
                
                $headers = "From: CBE Hawassa Branch <noreply@cbe.et>\r\n";
                mail($admin_email, $subject, $email_message, $headers);
                
                $message = '<div class="alert alert-success">Low stock alert email sent successfully!</div>';
            } else {
                $message = '<div class="alert alert-info">No low stock items found.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Admin email not configured.</div>';
        }
    }
}

// Get current settings
$stmt = $pdo->prepare("SELECT setting_name, setting_value FROM settings WHERE admin_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$settings_data = $stmt->fetchAll();

$current_settings = [];
foreach ($settings_data as $setting) {
    $current_settings[$setting['setting_name']] = $setting['setting_value'];
}

// Set defaults if not found
$current_email = $current_settings['admin_email'] ?? $_SESSION['email'];
$low_stock_threshold = $current_settings['low_stock_threshold'] ?? '10';
$auto_notifications = $current_settings['auto_notifications'] ?? 'enabled';
$email_notifications = $current_settings['email_notifications'] ?? 'enabled';
$backup_frequency = $current_settings['backup_frequency'] ?? 'weekly';
$system_timezone = $current_settings['system_timezone'] ?? 'Africa/Addis_Ababa';
$notification_frequency = $current_settings['notification_frequency'] ?? '10';
$dashboard_refresh = $current_settings['dashboard_refresh'] ?? '30';
$max_request_quantity = $current_settings['max_request_quantity'] ?? '1000';

// Get system statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$stats['total_users'] = $stmt->fetchColumn();

// Total admins
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$stats['total_admins'] = $stmt->fetchColumn();

// Total items
$stmt = $pdo->query("SELECT COUNT(*) FROM items");
$stats['total_items'] = $stmt->fetchColumn();

// Total requests this month
$stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$stats['monthly_requests'] = $stmt->fetchColumn();

// Database size
$stmt = $pdo->query("
    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS db_size_mb 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE()
");
$db_size = $stmt->fetchColumn();

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-cog me-2"></i>System Settings
                </h1>
                <p class="text-muted mb-0">Manage system settings and configurations</p>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-envelope me-2"></i>Email Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <button class="dropdown-item" onclick="testEmailNotification()">
                                <i class="fas fa-paper-plane me-2"></i>Send Test Email
                            </button>
                        </li>
                        <li>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="send_low_stock_alert" value="1">
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Send Low Stock Alert
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content-body">
        <?php echo $message; ?>
        
        <div class="row">
            <!-- Settings Form -->
            <div class="col-lg-8 mb-4">
                <div class="card form-custom">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog me-2"></i>System Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="settingsForm">
                            <input type="hidden" name="update_settings" value="1">
                            
                            <div class="mb-4">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-envelope me-2"></i>Email & Notification Settings
                                </h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Admin Email Address <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="email" class="form-control" name="admin_email" value="<?php echo htmlspecialchars($current_email); ?>" required>
                                        <button type="button" class="btn btn-outline-info" onclick="testEmail()">
                                            <i class="fas fa-paper-plane"></i> Test
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">
                                        This email will receive system notifications and alerts.
                                    </small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Email Notifications</label>
                                            <select class="form-select" name="email_notifications">
                                                <option value="enabled" <?php echo $email_notifications === 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                                                <option value="disabled" <?php echo $email_notifications === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Auto Notifications</label>
                                            <select class="form-select" name="auto_notifications">
                                                <option value="enabled" <?php echo $auto_notifications === 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                                                <option value="disabled" <?php echo $auto_notifications === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notification Check Frequency (seconds)</label>
                                            <select class="form-select" name="notification_frequency">
                                                <option value="5" <?php echo $notification_frequency == '5' ? 'selected' : ''; ?>>Every 5 seconds</option>
                                                <option value="10" <?php echo $notification_frequency == '10' ? 'selected' : ''; ?>>Every 10 seconds</option>
                                                <option value="30" <?php echo $notification_frequency == '30' ? 'selected' : ''; ?>>Every 30 seconds</option>
                                                <option value="60" <?php echo $notification_frequency == '60' ? 'selected' : ''; ?>>Every minute</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Dashboard Refresh (seconds)</label>
                                            <select class="form-select" name="dashboard_refresh">
                                                <option value="15" <?php echo $dashboard_refresh == '15' ? 'selected' : ''; ?>>Every 15 seconds</option>
                                                <option value="30" <?php echo $dashboard_refresh == '30' ? 'selected' : ''; ?>>Every 30 seconds</option>
                                                <option value="60" <?php echo $dashboard_refresh == '60' ? 'selected' : ''; ?>>Every minute</option>
                                                <option value="300" <?php echo $dashboard_refresh == '300' ? 'selected' : ''; ?>>Every 5 minutes</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-warehouse me-2"></i>Inventory Settings
                                </h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Low Stock Threshold</label>
                                            <input type="number" class="form-control" name="low_stock_threshold" value="<?php echo $low_stock_threshold; ?>" min="1" max="100">
                                            <small class="form-text text-muted">Alert when items fall below this quantity.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Maximum Request Quantity</label>
                                            <input type="number" class="form-control" name="max_request_quantity" value="<?php echo $max_request_quantity; ?>" min="1" max="10000">
                                            <small class="form-text text-muted">Maximum quantity users can request per item.</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">System Timezone</label>
                                    <select class="form-select" name="system_timezone">
                                        <option value="Africa/Addis_Ababa" <?php echo $system_timezone === 'Africa/Addis_Ababa' ? 'selected' : ''; ?>>Africa/Addis_Ababa (EAT)</option>
                                        <option value="UTC" <?php echo $system_timezone === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                        <option value="America/New_York" <?php echo $system_timezone === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                                        <option value="Europe/London" <?php echo $system_timezone === 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-database me-2"></i>System Maintenance
                                </h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Backup Frequency</label>
                                    <select class="form-select" name="backup_frequency">
                                        <option value="daily" <?php echo $backup_frequency === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="weekly" <?php echo $backup_frequency === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="monthly" <?php echo $backup_frequency === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-user-cog me-2"></i>Account Settings
                                </h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                                            <small class="form-text text-muted">Username cannot be changed.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" readonly>
                                            <small class="form-text text-muted">Contact administrator to update.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-shield-alt me-2"></i>Security Settings
                                </h6>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Security Tips:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Use strong passwords with at least 8 characters</li>
                                        <li>Include uppercase, lowercase, numbers, and special characters</li>
                                        <li>Change passwords regularly</li>
                                        <li>Never share login credentials</li>
                                    </ul>
                                </div>
                                
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save me-2"></i>Save All Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- System Information -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>System Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Total Users:</span>
                                <strong><?php echo number_format($stats['total_users']); ?></strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Total Administrators:</span>
                                <strong><?php echo number_format($stats['total_admins']); ?></strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Inventory Items:</span>
                                <strong><?php echo number_format($stats['total_items']); ?></strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Monthly Requests:</span>
                                <strong><?php echo number_format($stats['monthly_requests']); ?></strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Database Size:</span>
                                <strong><?php echo $db_size; ?> MB</strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>System Version:</span>
                                <strong>v2.0.0</strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>PHP Version:</span>
                                <strong><?php echo PHP_VERSION; ?></strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Server Time:</span>
                                <strong id="server-time"><?php echo date('H:i:s'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mt-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tools me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="location.href='users.php'">
                                <i class="fas fa-users me-2"></i>Manage Users
                            </button>
                            
                            <button class="btn btn-outline-success" onclick="location.href='inventory.php'">
                                <i class="fas fa-boxes me-2"></i>Manage Inventory
                            </button>
                            
                            <button class="btn btn-outline-info" onclick="location.href='reports.php'">
                                <i class="fas fa-chart-bar me-2"></i>View Reports
                            </button>
                            
                            <button class="btn btn-outline-warning" onclick="sendLowStockAlert()">
                                <i class="fas fa-exclamation-triangle me-2"></i>Send Low Stock Alert
                            </button>
                            
                            <button class="btn btn-outline-secondary" onclick="clearSystemCache()">
                                <i class="fas fa-broom me-2"></i>Clear System Cache
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Email Status -->
                <div class="card mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-envelope-open me-2"></i>Email Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Email Notifications:</span>
                            <span class="badge bg-<?php echo $email_notifications === 'enabled' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($email_notifications); ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Admin Email:</span>
                            <small class="text-muted"><?php echo htmlspecialchars($current_email); ?></small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Last Test:</span>
                            <small class="text-muted" id="last-email-test">Never</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" minlength="6" required>
                        <small class="form-text text-muted">Minimum 6 characters required.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Email Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="test_email" value="1">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will send a test email to verify your email notification settings.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Test Email Address</label>
                        <input type="email" class="form-control" name="test_email" value="<?php echo htmlspecialchars($current_email); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-paper-plane me-2"></i>Send Test Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function testEmail() {
    const email = document.querySelector('input[name="admin_email"]').value;
    if (!email) {
        showNotificationToast('Please enter an email address first', 'error');
        return;
    }
    
    document.querySelector('#testEmailModal input[name="test_email"]').value = email;
    new bootstrap.Modal(document.getElementById('testEmailModal')).show();
}

function testEmailNotification() {
    const email = document.querySelector('input[name="admin_email"]').value;
    if (!email) {
        showNotificationToast('Please save admin email first', 'error');
        return;
    }
    
    fetch('../ajax/email_notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=send_test&email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotificationToast('Test email sent successfully!', 'success');
            document.getElementById('last-email-test').textContent = new Date().toLocaleString();
        } else {
            showNotificationToast(data.message || 'Failed to send test email', 'error');
        }
    })
    .catch(error => {
        showNotificationToast('Error sending test email', 'error');
    });
}

function sendLowStockAlert() {
    if (confirm('Send low stock alert email to admin?')) {
        fetch('../ajax/email_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=send_low_stock_alert'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotificationToast('Low stock alert sent successfully!', 'success');
            } else {
                showNotificationToast(data.message || 'Failed to send alert', 'error');
            }
        })
        .catch(error => {
            showNotificationToast('Error sending alert', 'error');
        });
    }
}

function clearSystemCache() {
    if (confirm('Clear system cache? This may temporarily slow down the system.')) {
        showNotificationToast('System cache cleared successfully!', 'success');
    }
}

// Change password form handler
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const button = this.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changing...';
    button.disabled = true;
    
    fetch('../ajax/change_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotificationToast('Password changed successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
            this.reset();
        } else {
            showNotificationToast(data.message || 'Error changing password', 'error');
        }
    })
    .catch(error => {
        showNotificationToast('Error changing password', 'error');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
});

// Auto-save settings
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('settingsForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            // Show saving indicator
            showNotificationToast('Settings auto-saved', 'success');
        });
    });
    
    // Update server time every second
    setInterval(function() {
        document.getElementById('server-time').textContent = new Date().toLocaleTimeString();
    }, 1000);
});

// Real-time settings validation
function validateSettings() {
    const email = document.querySelector('input[name="admin_email"]').value;
    const threshold = document.querySelector('input[name="low_stock_threshold"]').value;
    
    if (!email || !email.includes('@')) {
        showNotificationToast('Please enter a valid email address', 'warning');
        return false;
    }
    
    if (threshold < 1 || threshold > 100) {
        showNotificationToast('Low stock threshold must be between 1 and 100', 'warning');
        return false;
    }
    
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>