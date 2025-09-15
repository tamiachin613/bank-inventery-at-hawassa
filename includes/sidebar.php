<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="text-center mb-3">
            <i class="fas fa-university fa-2x mb-2"></i>
            <h5><?php echo SITE_NAME; ?></h5>
            <small><?php echo SITE_TITLE; ?></small>
        </div>
        
        <div class="user-info text-center">
            <div class="user-avatar mb-2">
                <i class="fas fa-user-circle fa-2x"></i>
            </div>
            <h6 class="mb-0"><?php echo $_SESSION['full_name']; ?></h6>
            <small class="text-uppercase"><?php echo $_SESSION['role']; ?></small>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <li>
                <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="users.php" class="<?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> User Management
                </a>
            </li>
            <li>
                <a href="inventory.php" class="<?php echo $current_page === 'inventory.php' ? 'active' : ''; ?>">
                    <i class="fas fa-boxes"></i> Inventory Management
                </a>
            </li>
            <li>
                <a href="requests.php" class="<?php echo $current_page === 'requests.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Request Management
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status = 'pending'");
                    $stmt->execute();
                    $pending_count = $stmt->fetchColumn();
                    if ($pending_count > 0): ?>
                        <span class="notification-badge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
        <?php else: ?>
            <li>
                <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="request.php" class="<?php echo $current_page === 'request.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i> New Request
                </a>
            </li>
        <?php endif; ?>
        
        <li class="mt-auto">
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer p-3 text-center" style="position: absolute; bottom: 0; width: 100%;">
        <small class="text-muted">
            <div id="current-time"></div>
            <div id="current-date"></div>
        </small>
    </div>
</div>