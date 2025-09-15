<?php
require_once '../config/config.php';
check_admin();

header('Content-Type: application/json');

function sendEmailNotification($to_email, $subject, $message, $type = 'info') {
    // Get admin email settings
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE admin_id = ? AND setting_name = 'email_notifications'");
    $stmt->execute([$_SESSION['user_id']]);
    $email_enabled = $stmt->fetchColumn();
    
    if ($email_enabled !== 'enabled') {
        return ['success' => false, 'message' => 'Email notifications are disabled'];
    }
    
    // Prepare email content
    $email_body = "Commercial Bank of Ethiopia - Hawassa Branch\n";
    $email_body .= "Inventory Management System Notification\n";
    $email_body .= str_repeat("=", 50) . "\n\n";
    $email_body .= $message . "\n\n";
    $email_body .= "Notification Details:\n";
    $email_body .= "- Type: " . ucfirst($type) . "\n";
    $email_body .= "- Date: " . date('F d, Y - h:i A') . "\n";
    $email_body .= "- System: CBE Hawassa Branch Inventory System\n\n";
    $email_body .= "Please do not reply to this automated email.\n";
    $email_body .= "For support, contact the system administrator.";
    
    // Email headers
    $headers = "From: CBE Hawassa Branch <noreply@cbe.et>\r\n";
    $headers .= "Reply-To: noreply@cbe.et\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Send email
    if (mail($to_email, $subject, $email_body, $headers)) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to send email'];
    }
}

if ($_POST) {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'send_test':
                $test_email = sanitize_input($_POST['email']);
                $result = sendEmailNotification(
                    $test_email,
                    "CBE Hawassa Branch - Test Email Notification",
                    "This is a test email to verify your notification system is working correctly.",
                    'info'
                );
                echo json_encode($result);
                break;
                
            case 'send_request_notification':
                $request_id = (int)$_POST['request_id'];
                $notification_type = $_POST['notification_type']; // 'approved' or 'rejected'
                
                // Get request and user details
                $stmt = $pdo->prepare("
                    SELECT r.*, u.email, u.full_name, i.item_name
                    FROM requests r
                    JOIN users u ON r.user_id = u.id
                    JOIN items i ON r.item_id = i.id
                    WHERE r.id = ?
                ");
                $stmt->execute([$request_id]);
                $request = $stmt->fetch();
                
                if ($request) {
                    $subject = "Request " . ucfirst($notification_type) . " - " . $request['item_name'];
                    $message = "Dear " . $request['full_name'] . ",\n\n";
                    $message .= "Your inventory request has been " . $notification_type . ".\n\n";
                    $message .= "Request Details:\n";
                    $message .= "- Item: " . $request['item_name'] . "\n";
                    $message .= "- Quantity: " . number_format($request['quantity']) . "\n";
                    $message .= "- Branch: " . $request['branch'] . "\n";
                    $message .= "- Status: " . ucfirst($request['status']) . "\n";
                    
                    if ($request['admin_remark']) {
                        $message .= "- Admin Note: " . $request['admin_remark'] . "\n";
                    }
                    
                    $result = sendEmailNotification($request['email'], $subject, $message, $notification_type);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Request not found']);
                }
                break;
                
            case 'send_low_stock_alert':
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
                        $message = "The following items are running low in stock:\n\n";
                        
                        foreach ($low_stock_items as $item) {
                            $message .= "- " . $item['item_name'] . " (" . $item['item_code'] . "): " . $item['quantity'] . " " . $item['unit'] . " remaining\n";
                        }
                        
                        $message .= "\nPlease consider restocking these items to avoid shortages.";
                        
                        $result = sendEmailNotification($admin_email, $subject, $message, 'warning');
                        echo json_encode($result);
                    } else {
                        echo json_encode(['success' => true, 'message' => 'No low stock items found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Admin email not configured']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>