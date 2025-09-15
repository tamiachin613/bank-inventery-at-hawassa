<?php
require_once '../config/config.php';
check_admin();

header('Content-Type: application/json');

if ($_POST) {
    try {
        $request_id = (int)$_POST['request_id'];
        $admin_remark = sanitize_input($_POST['admin_remark']);
        
        if (empty($admin_remark)) {
            echo json_encode(['success' => false, 'message' => 'Please provide a reason for rejection']);
            exit();
        }
        
        // Update request status
        $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected', admin_remark = ? WHERE id = ? AND status = 'pending'");
        $stmt->execute([$admin_remark, $request_id]);
        
        if ($stmt->rowCount() > 0) {
            // Get user_id for notification
            $stmt = $pdo->prepare("SELECT user_id FROM requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $user_id = $stmt->fetchColumn();
            
            // Add notification
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'error')");
            $message_text = "Your request has been rejected. Reason: " . $admin_remark;
            $stmt->execute([$user_id, $message_text]);
            
            echo json_encode(['success' => true, 'message' => 'Request rejected successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error rejecting request']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>