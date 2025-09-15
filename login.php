<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// Check if logout message exists
if (isset($_SESSION['logout_message'])) {
    $success = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}

if ($_POST) {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, role, email, full_name FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['login_time'] = time();
            
            // Add login notification
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
            $login_message = "Welcome back! You logged in successfully at " . date('Y-m-d H:i:s');
            $stmt->execute([$user['id'], $login_message]);
            
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: user/dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --cbe-green: #228b22;
            --cbe-gold: #ffd700;
            --cbe-dark-green: #1e7e34;
        }
        
        body {
            background: linear-gradient(135deg, var(--cbe-green) 0%, var(--cbe-dark-green) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-left {
            background: linear-gradient(45deg, var(--cbe-green), #32cd32);
            color: white;
            padding: 60px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-right {
            padding: 60px 40px;
        }
        
        .bank-logo {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--cbe-gold);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px 50px 15px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            width: 100%;
        }
        
        .form-control:focus {
            border-color: var(--cbe-green);
            box-shadow: 0 0 0 0.2rem rgba(34, 139, 34, 0.25);
            background: white;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
            background: none;
            border: none;
            padding: 5px;
        }
        
        .password-toggle:hover {
            color: var(--cbe-green);
        }
        
        .btn-login {
            background: linear-gradient(45deg, var(--cbe-green), #32cd32);
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 16px;
            color: white;
        }
        
        .btn-login:hover {
            background: linear-gradient(45deg, var(--cbe-dark-green), var(--cbe-green));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(34, 139, 34, 0.3);
            color: white;
        }
        
        .credentials-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-top: 25px;
            border: 1px solid #e9ecef;
        }
        
        .credentials-info h6 {
            color: var(--cbe-green);
            margin-bottom: 15px;
        }
        
        .credential-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid var(--cbe-green);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .credential-item:hover {
            background: #f0f8f0;
            transform: translateX(5px);
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            left: 80%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 80%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .system-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            padding: 12px 18px;
            border-radius: 25px;
            font-size: 12px;
            color: #666;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse-status 2s infinite;
        }
        
        @keyframes pulse-status {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .features {
            margin-top: 30px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .feature-item:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        
        .feature-item i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .login-card {
                margin: 10px;
            }
            
            .login-left,
            .login-right {
                padding: 40px 30px;
            }
            
            .bank-logo {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="login-card">
                        <div class="row g-0">
                            <!-- Left Side - Bank Info -->
                            <div class="col-lg-6 login-left">
                                <div class="floating-shapes">
                                    <div class="shape"></div>
                                    <div class="shape"></div>
                                    <div class="shape"></div>
                                </div>
                                <div style="position: relative; z-index: 2;">
                                    <div class="bank-logo">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <h2 class="fw-bold mb-3"><?php echo SITE_NAME; ?></h2>
                                    <h4 class="mb-4"><?php echo SITE_TITLE; ?></h4>
                                    <p class="lead mb-4">Secure, Efficient, and Professional Inventory Management</p>
                                    
                                    <div class="features">
                                        <div class="feature-item">
                                            <i class="fas fa-shield-alt fa-lg"></i>
                                            <span>Secure Authentication</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-chart-line fa-lg"></i>
                                            <span>Real-time Analytics</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-mobile-alt fa-lg"></i>
                                            <span>Mobile Responsive</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-clock fa-lg"></i>
                                            <span>24/7 Availability</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Side - Login Form -->
                            <div class="col-lg-6 login-right">
                                <div class="text-center mb-4">
                                    <h3 class="fw-bold text-success">Welcome Back!</h3>
                                    <p class="text-muted">Please sign in to your account</p>
                                </div>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($success): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" id="loginForm">
                                    <div class="form-group">
                                        <label for="username" class="form-label">
                                            <i class="fas fa-user me-2"></i>Username
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   placeholder="Enter your username" required autocomplete="username">
                                            <span class="input-icon">
                                                <i class="fas fa-user"></i>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock me-2"></i>Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   placeholder="Enter your password" required autocomplete="current-password">
                                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                                <i class="fas fa-eye" id="toggleIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-login">
                                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="credentials-info">
                                    <h6>
                                        <i class="fas fa-info-circle me-2"></i>Demo Credentials
                                    </h6>
                                    
                                    <div class="credential-item" onclick="fillCredentials('admin', 'admin123')">
                                        <strong><i class="fas fa-user-shield me-2"></i>Administrator Access:</strong><br>
                                        <code>Username: admin</code><br>
                                        <code>Password: admin123</code>
                                        <small class="text-muted d-block mt-1">Click to auto-fill</small>
                                    </div>
                                    
                                    <div class="credential-item" onclick="fillCredentials('user1', 'user123')">
                                        <strong><i class="fas fa-user me-2"></i>User Access:</strong><br>
                                        <code>Username: user1, user2, user3</code><br>
                                        <code>Password: user123</code>
                                        <small class="text-muted d-block mt-1">Click to auto-fill user1</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Status -->
    <div class="system-status">
        <span class="status-indicator"></span>
        System Online
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Fill credentials automatically
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            document.getElementById('username').focus();
        }
        
        // Form validation and loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
            submitBtn.disabled = true;
            
            // Re-enable button after 3 seconds in case of error
            setTimeout(function() {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
        
        // Auto-focus username field
        document.getElementById('username').focus();
        
        // Enter key support
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });
        
        // Add interactive effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>