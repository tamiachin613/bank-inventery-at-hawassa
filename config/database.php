<?php
// Database configuration for Commercial Bank of Ethiopia - Hawassa Branch
// Inventory and Request Management System

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hawassa_inventory';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>