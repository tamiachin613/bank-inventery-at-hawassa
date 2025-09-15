<?php
// System configuration
session_start();

// Include database connection
require_once __DIR__ . '/database.php';

// System settings
define('SITE_NAME', 'CBE - Hawassa Branch');
define('SITE_TITLE', 'Inventory & Request Management System');
define('BASE_URL', 'http://localhost/hawassa_inventory/');

// Security functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function check_login() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        header('Location: ../index.php');
        exit();
    }
}

function check_admin() {
    check_login();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ../user/dashboard.php');
        exit();
    }
}

function check_user() {
    check_login();
    if ($_SESSION['role'] !== 'user') {
        header('Location: ../admin/dashboard.php');
        exit();
    }
}

// Get unread notifications count
function get_unread_notifications_count($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// Format date
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime
function format_datetime($datetime) {
    return date('M d, Y - h:i A', strtotime($datetime));
}
?>