<?php
require_once '../config/config.php';
check_login();

header('Content-Type: application/json');

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Get user notifications
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$_SESSION['user_id'], $limit]);
    $notifications = $stmt->fetchAll();
    
    // Get unread count
    $unread_count = get_unread_notifications_count($_SESSION['user_id']);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading notifications'
    ]);
}
?>