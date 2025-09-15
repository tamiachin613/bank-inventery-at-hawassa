
<?php
require_once __DIR__ . '/../config/config.php';
check_admin();

$page_title = 'Admin Dashboard';

// Get statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$stats['total_users'] = $stmt->fetchColumn();

// Total inventory items
$stmt = $pdo->query("SELECT COUNT(*) FROM items");
$stats['total_items'] = $stmt->fetchColumn();

// Total inventory value (sum of quantities)
$stmt = $pdo->query("SELECT SUM(quantity) FROM items");
$stats['total_inventory'] = $stmt->fetchColumn() ?: 0;

// Pending requests
$stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'");
$stats['pending_requests'] = $stmt->fetchColumn();

// Today's requests
$stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE DATE(created_at) = CURDATE()");
$stats['today_requests'] = $stmt->fetchColumn();

// This month's approved requests
$stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'approved' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stats['monthly_approved'] = $stmt->fetchColumn();

// Recent requests
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, i.item_name 
    FROM requests r
    JOIN users u ON r.user_id = u.id
    JOIN items i ON r.item_id = i.id
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_requests = $stmt->fetchAll();

// Low stock items (quantity < 10)
$stmt = $pdo->query("SELECT * FROM items WHERE quantity < 10 ORDER BY quantity ASC");
$low_stock_items = $stmt->fetchAll();

// Monthly request statistics for chart
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        DATE_FORMAT(created_at, '%M %Y') as month_name,
        COUNT(*) as total_requests,
        SUM(status = 'approved') as approved_requests,
        SUM(status = 'rejected') as rejected_requests
    FROM requests
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
");
$monthly_stats = $stmt->fetchAll();

// Top requested items this month
$stmt = $pdo->query("
    SELECT i.item_name, COUNT(r.id) as request_count, SUM(r.quantity) as total_quantity
    FROM requests r
    JOIN items i ON r.item_id = i.id
    WHERE MONTH(r.created_at) = MONTH(CURDATE()) AND YEAR(r.created_at) = YEAR(CURDATE())
    GROUP BY r.item_id
    ORDER BY request_count DESC
    LIMIT 5
");
$top_items = $stmt->fetchAll();

// Recent activities
$stmt = $pdo->query("
    SELECT 
        'request' as type,
        CONCAT(u.full_name, ' requested ', r.quantity, ' ', i.item_name) as activity,
        r.created_at as activity_time,
        r.status
    FROM requests r
    JOIN users u ON r.user_id = u.id
    JOIN items i ON r.item_id = i.id
    WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY r.created_at DESC
    LIMIT 10
");
$recent_activities = $stmt->fetchAll();

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                </h1>
                <p class="text-muted mb-0">
                    Welcome back, <?php echo $_SESSION['full_name']; ?>! 
                    <span class="badge bg-success ms-2">Online</span>
                </p>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-tools me-2"></i>Quick Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="users.php"><i class="fas fa-user-plus me-2"></i>Add User</a></li>
                        <li><a class="dropdown-item" href="inventory.php"><i class="fas fa-plus me-2"></i>Add Item</a></li>
                        <li><a class="dropdown-item" href="requests.php"><i class="fas fa-clipboard-check me-2"></i>Review Requests</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Generate Report</a></li>
                    </ul>
                </div>
                <a href="settings.php" class="btn btn-outline-success">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
                <button class="btn btn-sm btn-outline-primary d-md-none" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="content-body">
        <!-- Statistics Cards -->
        <div class="row mb-4 g-3">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 data-stat="total_users"><?php echo number_format($stats['total_users']); ?></h3>
                    <p>Total Users</p>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up text-success"></i>
                        <small class="text-success">Active</small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3 data-stat="total_items"><?php echo number_format($stats['total_items']); ?></h3>
                    <p>Inventory Items</p>
                    <div class="stat-trend">
                        <i class="fas fa-chart-line text-info"></i>
                        <small class="text-muted">Categories</small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <h3 data-stat="total_inventory"><?php echo number_format($stats['total_inventory']); ?></h3>
                    <p>Total Stock Units</p>
                    <div class="stat-trend">
                        <i class="fas fa-cubes text-success"></i>
                        <small class="text-muted">In Stock</small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 data-stat="pending_requests"><?php echo number_format($stats['pending_requests']); ?></h3>
                    <p>Pending Requests</p>
                    <div class="stat-trend">
                        <i class="fas fa-hourglass-half text-warning"></i>
                        <small class="text-muted">Awaiting</small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon text-danger">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <h3 data-stat="today_requests"><?php echo number_format($stats['today_requests']); ?></h3>
                    <p>Today's Requests</p>
                    <div class="stat-trend">
                        <i class="fas fa-clock text-danger"></i>
                        <small class="text-muted">Today</small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 data-stat="monthly_approved"><?php echo number_format($stats['monthly_approved']); ?></h3>
                    <p>Monthly Approved</p>
                    <div class="stat-trend">
                        <i class="fas fa-thumbs-up text-success"></i>
                        <small class="text-muted">This Month</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Requests and Activities -->
            <div class="col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>Recent Requests
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_requests)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                                No requests found
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-2">
                                                            <i class="fas fa-user-circle fa-lg text-muted"></i>
                                                        </div>
                                                        <span><?php echo htmlspecialchars($request['full_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['item_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark"><?php echo number_format($request['quantity']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge status-<?php echo $request['status']; ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo format_date($request['created_at']); ?></td>
                                                <td>
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-success" onclick="approveRequestQuick(<?php echo $request['id']; ?>)" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="rejectRequestQuick(<?php echo $request['id']; ?>)" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-info" onclick="viewRequestDetails(<?php echo htmlspecialchars(json_encode($request)); ?>)" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($request['status'] === 'approved'): ?>
                                                            <a href="../ajax/print_request_pdf.php?request_id=<?php echo $request['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Print">
                                                                <i class="fas fa-print"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="requests.php" class="btn btn-outline-primary">
                                    View All Requests <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar with Multiple Widgets -->
            <div class="col-lg-5">
                <!-- Low Stock Alert -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($low_stock_items)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-check-circle fa-2x mb-3 d-block text-success"></i>
                                All items are well stocked
                            </div>
                        <?php else: ?>
                            <?php foreach ($low_stock_items as $item): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['item_code']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge <?php echo $item['quantity'] == 0 ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                            <?php echo $item['quantity']; ?> <?php echo $item['quantity'] == 0 ? 'OUT' : 'left'; ?>
                                        </span>
                                        <div class="mt-1">
                                            <button class="btn btn-xs btn-outline-success" onclick="quickAddStock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')" title="Add Stock">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="inventory.php" class="btn btn-outline-warning">
                                    Manage Inventory <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Top Requested Items This Month -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-star me-2"></i>Top Items This Month
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_items)): ?>
                            <p class="text-muted text-center">No requests this month</p>
                        <?php else: ?>
                            <?php foreach ($top_items as $index => $item): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 <?php echo $index < count($top_items) - 1 ? 'border-bottom' : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo $item['total_quantity']; ?> units total</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-info"><?php echo $item['request_count']; ?> requests</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="card mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Activities
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($recent_activities)): ?>
                            <p class="text-muted text-center">No recent activities</p>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="d-flex align-items-start py-2 border-bottom">
                                    <div class="activity-icon me-3">
                                        <i class="fas fa-circle fa-xs text-<?php echo $activity['status'] === 'approved' ? 'success' : ($activity['status'] === 'rejected' ? 'danger' : 'warning'); ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 small"><?php echo htmlspecialchars($activity['activity']); ?></p>
                                        <small class="text-muted"><?php echo format_datetime($activity['activity_time']); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Statistics Chart -->
        <?php if (!empty($monthly_stats)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>Monthly Request Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Add Stock Modal -->
<div class="modal fade" id="quickAddStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Add Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Item</label>
                    <input type="text" class="form-control" id="quickAddItemName" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Amount to Add <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="quickAddAmount" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="executeQuickAddStock()">
                    <i class="fas fa-plus me-2"></i>Add Stock
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Request Details Modal -->
<div class="modal fade" id="requestDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-4"><strong>User:</strong></div>
                    <div class="col-sm-8" id="detail_user"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-4"><strong>Item:</strong></div>
                    <div class="col-sm-8" id="detail_item"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-4"><strong>Quantity:</strong></div>
                    <div class="col-sm-8" id="detail_quantity"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-4"><strong>Branch:</strong></div>
                    <div class="col-sm-8" id="detail_branch"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-4"><strong>Status:</strong></div>
                    <div class="col-sm-8" id="detail_status"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-4"><strong>Request Date:</strong></div>
                    <div class="col-sm-8" id="detail_date"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-4"><strong>Remark:</strong></div>
                    <div class="col-sm-8" id="detail_remark"></div>
                </div>
                <div class="row mt-2" id="admin_remark_row" style="display: none;">
                    <div class="col-sm-4"><strong>Admin Note:</strong></div>
                    <div class="col-sm-8" id="detail_admin_remark"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    <?php if (!empty($monthly_stats)): ?>
    // Monthly Chart
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php echo "'" . implode("','", array_reverse(array_column($monthly_stats, 'month_name'))) . "'"; ?>],
            datasets: [{
                label: 'Total Requests',
                data: [<?php echo implode(',', array_reverse(array_column($monthly_stats, 'total_requests'))); ?>],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Approved',
                data: [<?php echo implode(',', array_reverse(array_column($monthly_stats, 'approved_requests'))); ?>],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Rejected',
                data: [<?php echo implode(',', array_reverse(array_column($monthly_stats, 'rejected_requests'))); ?>],
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1
                }
            }
        }
    });
    <?php endif; ?>
    
    let quickAddItemId = null;
    
    // Quick add stock functionality
    function quickAddStock(itemId, itemName) {
        quickAddItemId = itemId;
        document.getElementById('quickAddItemName').value = itemName;
        document.getElementById('quickAddAmount').value = '';
        new bootstrap.Modal(document.getElementById('quickAddStockModal')).show();
    }
    
    function executeQuickAddStock() {
        const amount = document.getElementById('quickAddAmount').value;
        
        if (!amount || amount <= 0) {
            showNotificationToast('Please enter a valid amount', 'error');
            return;
        }
        
        const button = document.querySelector('#quickAddStockModal .btn-success');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
        button.disabled = true;
        
        fetch('../ajax/update_inventory.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `item_id=${quickAddItemId}&action=increase&amount=${amount}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotificationToast(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('quickAddStockModal')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotificationToast(data.message, 'error');
            }
        })
        .catch(error => {
            showNotificationToast('Error updating inventory', 'error');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
    
    // Quick action functions for dashboard
    function approveRequestQuick(requestId) {
        if (confirm('Are you sure you want to approve this request? This will update inventory automatically.')) {
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            $.ajax({
                url: '../ajax/approve_request.php',
                method: 'POST',
                data: { request_id: requestId },
                success: function(response) {
                    if (response.success) {
                        showNotificationToast('Request approved successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotificationToast(response.message || 'Error approving request', 'error');
                    }
                },
                error: function() {
                    showNotificationToast('Error processing request', 'error');
                },
                complete: function() {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            });
        }
    }
    
    function rejectRequestQuick(requestId) {
        const reason = prompt('Please provide a reason for rejection:');
        if (reason && reason.trim()) {
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            $.ajax({
                url: '../ajax/reject_request.php',
                method: 'POST',
                data: { request_id: requestId, admin_remark: reason },
                success: function(response) {
                    if (response.success) {
                        showNotificationToast('Request rejected successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotificationToast(response.message || 'Error rejecting request', 'error');
                    }
                },
                error: function() {
                    showNotificationToast('Error processing request', 'error');
                },
                complete: function() {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            });
        }
    }
    
    function viewRequestDetails(request) {
        document.getElementById('detail_user').textContent = request.full_name;
        document.getElementById('detail_item').textContent = request.item_name;
        document.getElementById('detail_quantity').textContent = new Intl.NumberFormat().format(request.quantity);
        document.getElementById('detail_branch').textContent = request.branch;
        document.getElementById('detail_status').innerHTML = `<span class="badge status-${request.status}">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span>`;
        document.getElementById('detail_date').textContent = formatDateTime(request.created_at);
        document.getElementById('detail_remark').textContent = request.remark || 'None';
        
        if (request.admin_remark) {
            document.getElementById('detail_admin_remark').textContent = request.admin_remark;
            document.getElementById('admin_remark_row').style.display = 'flex';
        } else {
            document.getElementById('admin_remark_row').style.display = 'none';
        }
        
        new bootstrap.Modal(document.getElementById('requestDetailsModal')).show();
    }
    
    function formatDateTime(datetime) {
        const date = new Date(datetime);
        return date.toLocaleDateString() + ' - ' + date.toLocaleTimeString();
    }
</script>

<?php include '../includes/footer.php'; ?>