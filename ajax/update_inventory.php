<?php
require_once '../config/config.php';
check_admin();

header('Content-Type: application/json');

if ($_POST) {
    try {
        $item_id = (int)$_POST['item_id'];
        $action = $_POST['action']; // 'increase' or 'decrease'
        $amount = (int)$_POST['amount'];
        
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
            exit();
        }
        
        // Get current item details
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            exit();
        }
        
        $old_quantity = $item['quantity'];
        
        if ($action === 'increase') {
            $new_quantity = $old_quantity + $amount;
            $stmt = $pdo->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
            $stmt->execute([$amount, $item_id]);
            $message = "Added $amount units to {$item['item_name']}";
        } elseif ($action === 'decrease') {
            if ($old_quantity < $amount) {
                echo json_encode(['success' => false, 'message' => 'Cannot decrease by more than current stock']);
                exit();
            }
            $new_quantity = $old_quantity - $amount;
            $stmt = $pdo->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
            $stmt->execute([$amount, $item_id]);
            $message = "Removed $amount units from {$item['item_name']}";
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
        }
        
        // Add notification for admin
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
        $notification_message = "Inventory manually updated: {$item['item_name']} quantity changed from $old_quantity to $new_quantity";
        $stmt->execute([$_SESSION['user_id'], $notification_message]);
        
        // Get updated item details
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $updated_item = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'old_quantity' => $old_quantity,
            'new_quantity' => $updated_item['quantity'],
            'item' => $updated_item
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating inventory: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>