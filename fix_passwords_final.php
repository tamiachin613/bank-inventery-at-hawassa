<?php
// CRITICAL: Run this file ONCE to fix password hashes
// Access: http://localhost/hawassa_inventory/fix_passwords_final.php

require_once 'config/config.php';

echo "<h2>🔧 Password Hash Fix Utility - FINAL VERSION</h2>";
echo "<p>This will update all user passwords with correct hashes.</p>";

try {
    // Generate correct password hashes
    $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $user_hash = password_hash('user123', PASSWORD_DEFAULT);
    
    echo "<h3>✅ Generated Hashes:</h3>";
    echo "<p><strong>Admin Hash:</strong> " . $admin_hash . "</p>";
    echo "<p><strong>User Hash:</strong> " . $user_hash . "</p>";
    
    // Update admin password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $result1 = $stmt->execute([$admin_hash]);
    
    // Update user passwords
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username IN ('user1', 'user2', 'user3')");
    $result2 = $stmt->execute([$user_hash]);
    
    if ($result1 && $result2) {
        echo "<div style='color: green; font-weight: bold; margin: 20px 0; padding: 20px; background: #d4edda; border-radius: 10px;'>";
        echo "<h3>✅ SUCCESS: Passwords Updated Successfully!</h3>";
        echo "</div>";
        
        // Test the passwords
        echo "<h3>🔍 Password Verification Test:</h3>";
        
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
        echo "<div style='background: #f0f8f0; padding: 30px; border-radius: 15px; margin: 20px 0; border: 2px solid #28a745;'>";
        echo "<h3>🎉 Login Credentials Ready:</h3>";
        echo "<div style='display: flex; gap: 30px;'>";
        echo "<div style='flex: 1;'>";
        echo "<h4 style='color: #dc3545;'>👨‍💼 Admin Login:</h4>";
        echo "<p style='font-size: 18px;'><strong>Username:</strong> <code style='background: #f8f9fa; padding: 5px 10px; border-radius: 5px;'>admin</code></p>";
        echo "<p style='font-size: 18px;'><strong>Password:</strong> <code style='background: #f8f9fa; padding: 5px 10px; border-radius: 5px;'>admin123</code></p>";
        echo "</div>";
        echo "<div style='flex: 1;'>";
        echo "<h4 style='color: #007bff;'>👤 User Login:</h4>";
        echo "<p style='font-size: 18px;'><strong>Username:</strong> <code style='background: #f8f9fa; padding: 5px 10px; border-radius: 5px;'>user1</code>, <code style='background: #f8f9fa; padding: 5px 10px; border-radius: 5px;'>user2</code>, or <code style='background: #f8f9fa; padding: 5px 10px; border-radius: 5px;'>user3</code></p>";
        echo "<p style='font-size: 18px;'><strong>Password:</strong> <code style='background: #f8f9fa; padding: 5px 10px; border-radius: 5px;'>user123</code></p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        
        echo "<div style='text-align: center; margin: 30px 0;'>";
        echo "<a href='login.php' style='background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 20px 40px; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 18px; box-shadow: 0 4px 15px rgba(34, 139, 34, 0.3);'>🚀 Go to Login Page</a>";
        echo "</div>";
        
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border: 1px solid #ffeaa7;'>";
        echo "<h4 style='color: #856404;'>📋 System Features:</h4>";
        echo "<ul style='color: #856404;'>";
        echo "<li>✅ Real-time inventory management</li>";
        echo "<li>✅ Interactive dashboards with live updates</li>";
        echo "<li>✅ Email notification system</li>";
        echo "<li>✅ PDF printing for approved requests</li>";
        echo "<li>✅ Monthly and quarterly reports</li>";
        echo "<li>✅ Multi-request submission system</li>";
        echo "<li>✅ Mobile responsive design</li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        echo "<div style='color: red; font-weight: bold; background: #f8d7da; padding: 20px; border-radius: 10px;'>";
        echo "<h3>❌ ERROR: Failed to update passwords!</h3>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold; background: #f8d7da; padding: 20px; border-radius: 10px;'>";
    echo "<h3>❌ DATABASE ERROR:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<h4>🔧 Troubleshooting Steps:</h4>";
    echo "<ol>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Ensure database 'hawassa_inventory' exists</li>";
    echo "<li>Import the hawassa_inventory_complete.sql file first</li>";
    echo "<li>Check database connection in config/database.php</li>";
    echo "</ol>";
    echo "</div>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}
h2, h3, h4 {
    color: #228b22;
}
code {
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-weight: bold;
}
p {
    line-height: 1.6;
}
</style>