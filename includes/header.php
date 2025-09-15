<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? SITE_TITLE; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- XLSX for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>
    <!-- Top Header Bar -->
    <div class="top-header-bar">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div class="header-left">
                    <button class="btn btn-sm btn-outline-light d-md-none" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="header-title d-none d-md-inline">
                        <i class="fas fa-university me-2"></i><?php echo SITE_NAME; ?>
                    </span>
                </div>
                
                <div class="header-center d-none d-lg-block">
                    <div class="search-box">
                        <input type="text" class="form-control" placeholder="Search..." id="globalSearch">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="d-flex align-items-center gap-3">
                        <!-- Real-time Clock -->
                        <div class="header-clock d-none d-md-block">
                            <div class="time" id="header-time"></div>
                            <div class="date" id="header-date"></div>
                        </div>
                        
                        <!-- Notifications Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-light position-relative" data-bs-toggle="dropdown" id="notificationBtn">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge" id="headerNotificationBadge" style="display: none;">0</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px;">
                                <div class="dropdown-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Notifications</h6>
                                    <button class="btn btn-sm btn-outline-light" onclick="markAllNotificationsRead()">
                                        <i class="fas fa-check-double"></i>
                                    </button>
                                </div>
                                <div class="notification-list" id="headerNotificationList" style="max-height: 300px; overflow-y: auto;">
                                    <div class="text-center p-3">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </div>
                                </div>
                                <div class="dropdown-footer text-center">
                                    <?php
                                        // Build the correct notifications URL depending on role
                                        $notifications_path = $_SESSION['role'] === 'admin' ? 'admin/notifications.php' : 'user/notifications.php';
                                    ?>
                                    <a href="<?php echo BASE_URL . $notifications_path; ?>" class="btn btn-sm btn-outline-primary">
                                        View All Notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>
                                <span class="d-none d-md-inline"><?php echo $_SESSION['full_name']; ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="dropdown-header">
                                    <div class="text-center">
                                        <i class="fas fa-user-circle fa-2x mb-2"></i>
                                        <div><strong><?php echo $_SESSION['full_name']; ?></strong></div>
                                        <small class="text-muted"><?php echo $_SESSION['email']; ?></small>
                                        <div><span class="badge bg-<?php echo $_SESSION['role'] === 'admin' ? 'danger' : 'primary'; ?>"><?php echo ucfirst($_SESSION['role']); ?></span></div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                    <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="request.php"><i class="fas fa-plus me-2"></i>New Request</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alert Container -->
    <div id="alert-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; width: 300px;"></div>
    
    <script>
        // Global variables for JavaScript
        const userId = <?php echo $_SESSION['user_id']; ?>;
        const userRole = '<?php echo $_SESSION['role']; ?>';
        
        <?php if (isset($items_js)): ?>
        const itemOptions = <?php echo $items_js; ?>;
        <?php endif; ?>
    </script>