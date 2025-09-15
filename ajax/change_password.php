<?php
require_once '../config/config.php';
check_login();

header('Content-Type: application/json');

if ($_POST) {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit();
        }
        
        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit();
        }
        
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
            exit();
        }
        
        // Get current password hash
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current_hash = $stmt->fetchColumn();
        
        // Verify current password
        if (!password_verify($current_password, $current_hash)) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit();
        }
        
        // Update password
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_hash, $_SESSION['user_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error changing password']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>