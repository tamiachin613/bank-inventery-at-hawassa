<?php
// CRITICAL: Run this file ONCE to fix password hashes
// Access: http://localhost/hawassa_inventory/fix_passwords.php

require_once 'config/config.php';

echo "<h2>Password Hash Fix Utility</h2>";
echo "<p>This will update all user passwords with correct hashes.</p>";

try {
    // Generate correct password hashes
    // $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
    // $user_hash = password_hash('user123', PASSWORD_DEFAULT);
    
    echo "<h3>Generated Hashes:</h3>";
    echo "<p><strong>Admin Hash:</strong> " . $admin_hash . "</p>";
    echo "<p><strong>User Hash:</strong> " . $user_hash . "</p>";
    
    // Update admin password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $result1 = $stmt->execute([$admin_hash]);
    
    // Update user passwords
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username IN ('user1', 'user2', 'user3')");
    $result2 = $stmt->execute([$user_hash]);
    
    if ($result1 && $result2) {
        echo "<div style='color: green; font-weight: bold; margin: 20px 0;'>";
        echo "<h3>✅ SUCCESS: Passwords Updated Successfully!</h3>";
        echo "</div>";
        
        // Test the passwords
        echo "<h3>Password Verification Test:</h3>";
        
        $stmt = $pdo->query("SELECT username, password, role FROM users");
        $users = $stmt->fetchAll();
        
        foreach ($users as $user) {
            $test_password = ($user['role'] === 'admin') ? 'admin123' : 'user123';
            $is_valid = password_verify($test_password, $user['password']);
            
            echo "<p><strong>" . $user['username'] . "</strong>: ";
            echo "Password '$test_password' - ";
            if ($is_valid) {
                echo "<span style='color: green; font-weight: bold;'>✅ VALID</span>";
            } else {
                echo "<span style='color: red; font-weight: bold;'>❌ INVALID</span>";
            }
            echo "</p>";
        }
        
        echo "<hr>";
        echo "<h3>🎉 Login Credentials Ready:</h3>";
        echo "<div style='background: #f0f8f0; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<p><strong>👨‍💼 Admin Login:</strong></p>";
        echo "<p>Username: <code>admin</code></p>";
        echo "<p>Password: <code>admin123</code></p>";
        echo "<br>";
        echo "<p><strong>👤 User Login:</strong></p>";
        echo "<p>Username: <code>user1</code>, <code>user2</code>, or <code>user3</code></p>";
        echo "<p>Password: <code>user123</code></p>";
        echo "</div>";
        
        echo "<p><a href='login.php' style='background: #228b22; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🚀 Go to Login Page</a></p>";
        
    } else {
        echo "<div style='color: red; font-weight: bold;'>";
        echo "<h3>❌ ERROR: Failed to update passwords!</h3>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>";
    echo "<h3>❌ DATABASE ERROR:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<h4>Troubleshooting Steps:</h4>";
    echo "<ol>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Ensure database 'hawassa_inventory' exists</li>";
    echo "<li>Import the hawassa_inventory_fixed.sql file first</li>";
    echo "<li>Check database connection in config/database.php</li>";
    echo "</ol>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}
h2, h3 {
    color: #228b22;
}
code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
</style>