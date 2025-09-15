<?php
require_once '../config/config.php';
check_login();

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_inventory_status':
            // Get current inventory status
            $stmt = $pdo->query("
                SELECT 
                    id, item_name, quantity,
                    CASE 
                        WHEN quantity = 0 THEN 'out-of-stock'
                        WHEN quantity <= 10 THEN 'low-stock'
                        ELSE 'in-stock'
                    END as status
                FROM items 
                ORDER BY item_name
            ");
            $items = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'items' => $items]);
            break;
            
        case 'get_pending_requests':
            // Get pending requests count
            $stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'");
            $pending_count = $stmt->fetchColumn();
            
            echo json_encode(['success' => true, 'pending_count' => $pending_count]);
            break;
            
        case 'get_notifications':
            // Get latest notifications
            $stmt = $pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $notifications = $stmt->fetchAll();
            
            $unread_count = get_unread_notifications_count($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true, 
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ]);
            break;
            
        case 'get_dashboard_stats':
            // Get dashboard statistics
            if ($_SESSION['role'] === 'admin') {
                $stats = [];
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
                $stats['total_users'] = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM items");
                $stats['total_items'] = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'");
                $stats['pending_requests'] = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE DATE(created_at) = CURDATE()");
                $stats['today_requests'] = $stmt->fetchColumn();
                
                echo json_encode(['success' => true, 'stats' => $stats]);
            } else {
                $stats = [];
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $stats['total_requests'] = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ? AND status = 'pending'");
                $stmt->execute([$_SESSION['user_id']]);
                $stats['pending_requests'] = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ? AND status = 'approved'");
                $stmt->execute([$_SESSION['user_id']]);
                $stats['approved_requests'] = $stmt->fetchColumn();
                
                echo json_encode(['success' => true, 'stats' => $stats]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>