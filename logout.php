<?php
session_start();

// Store user info for logout message
$user_name = $_SESSION['full_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'user';

// Add logout notification if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
        $logout_message = "You logged out successfully at " . date('Y-m-d H:i:s');
        $stmt->execute([$_SESSION['user_id'], $logout_message]);
    } catch (Exception $e) {
        // Silently handle error
    }
}

// Destroy all session data
session_unset();
session_destroy();

// Clear any cookies if they exist
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Start new session for logout message
session_start();
$_SESSION['logout_message'] = "Goodbye, $user_name! You have been successfully logged out.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - CBE Hawassa Branch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #228b22 0%, #1e7e34 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .logout-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .logout-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #228b22, #ffd700, #228b22);
        }
        
        .logout-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 30px;
            animation: checkmark 0.6s ease-in-out;
        }
        
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .logout-title {
            color: #228b22;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .logout-message {
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .btn-return {
            background: linear-gradient(45deg, #228b22, #32cd32);
            border: none;
            padding: 15px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-return:hover {
            background: linear-gradient(45deg, #1e7e34, #228b22);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(34, 139, 34, 0.3);
        }
        
        .security-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            text-align: left;
        }
        
        .security-info h6 {
            color: #228b22;
            margin-bottom: 15px;
        }
        
        .security-info ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .security-info li {
            margin-bottom: 8px;
            color: #6c757d;
        }
        
        .countdown {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 20px;
        }
        
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .floating-element {
            position: absolute;
            color: rgba(34, 139, 34, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            top: 10%;
            left: 10%;
            font-size: 2rem;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            top: 20%;
            right: 15%;
            font-size: 1.5rem;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            bottom: 20%;
            left: 20%;
            font-size: 1.8rem;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="floating-elements">
            <i class="fas fa-shield-alt floating-element"></i>
            <i class="fas fa-lock floating-element"></i>
            <i class="fas fa-university floating-element"></i>
        </div>
        
        <div class="logout-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h2 class="logout-title">Successfully Logged Out</h2>
        
        <p class="logout-message">
            <?php echo htmlspecialchars($_SESSION['logout_message'] ?? 'You have been safely logged out of the system.'); ?>
        </p>
        
        <div class="d-grid mb-3">
            <a href="login.php" class="btn btn-success btn-return">
                <i class="fas fa-sign-in-alt me-2"></i>Return to Login
            </a>
        </div>
        
        <div class="security-info">
            <h6><i class="fas fa-shield-alt me-2"></i>Security Information</h6>
            <ul>
                <li>Your session has been completely terminated</li>
                <li>All temporary data has been cleared</li>
                <li>For security, close your browser if using a shared computer</li>
                <li>Your logout has been recorded for audit purposes</li>
            </ul>
        </div>
        
        <div class="countdown">
            <i class="fas fa-clock me-1"></i>
            Redirecting to login page in <span id="countdown">10</span> seconds...
        </div>
    </div>
    
    <script>
        // Countdown timer
        let timeLeft = 10;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            timeLeft--;
            countdownElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                window.location.href = 'login.php';
            }
        }, 1000);
        
        // Clear logout message from session
        <?php unset($_SESSION['logout_message']); ?>
    </script>
</body>
</html>