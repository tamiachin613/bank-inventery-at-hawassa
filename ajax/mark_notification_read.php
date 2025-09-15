<?php
require_once '../config/config.php';
check_login();

header('Content-Type: application/json');

if ($_POST) {
    try {
        if (isset($_POST['mark_all'])) {
            // Mark all notifications as read
            $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
        } else {
            $notification_id = (int)$_POST['notification_id'];
            
            $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?");
            $stmt->execute([$notification_id, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Notification not found']);
            }
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating notification']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>