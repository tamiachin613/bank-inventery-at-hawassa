<?php
require_once '../config/config.php';
check_admin();

header('Content-Type: application/json');

if ($_POST) {
    try {
        $request_id = (int)$_POST['request_id'];
        $admin_remark = sanitize_input($_POST['admin_remark'] ?? '');
        
        // Get request details
        $stmt = $pdo->prepare("
            SELECT r.*, i.quantity as current_stock 
            FROM requests r
            JOIN items i ON r.item_id = i.id
            WHERE r.id = ? AND r.status = 'pending'
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
            exit();
        }
        
        if ($request['current_stock'] < $request['quantity']) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock to approve this request']);
            exit();
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update request status
        $stmt = $pdo->prepare("UPDATE requests SET status = 'approved', admin_remark = ? WHERE id = ?");
        $stmt->execute([$admin_remark, $request_id]);
        
        // Update inventory
        $stmt = $pdo->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
        $stmt->execute([$request['quantity'], $request['item_id']]);
        
        // Add notification
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')");
        $message_text = "Your request has been approved. " . ($admin_remark ? "Admin note: " . $admin_remark : "");
        $stmt->execute([$request['user_id'], $message_text]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Request approved successfully']);
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => 'Error approving request']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>