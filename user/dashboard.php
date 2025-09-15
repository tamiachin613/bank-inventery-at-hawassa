<?php
require_once '../config/config.php';
check_user();

$page_title = 'User Dashboard';

// Get user statistics
$stats = [];

// Total requests by user
$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats['total_requests'] = $stmt->fetchColumn();

// Pending requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$stats['pending_requests'] = $stmt->fetchColumn();

// Approved requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ? AND status = 'approved'");
$stmt->execute([$_SESSION['user_id']]);
$stats['approved_requests'] = $stmt->fetchColumn();

// Rejected requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ? AND status = 'rejected'");
$stmt->execute([$_SESSION['user_id']]);
$stats['rejected_requests'] = $stmt->fetchColumn();

// This month's requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stmt->execute([$_SESSION['user_id']]);
$stats['monthly_requests'] = $stmt->fetchColumn();

// Today's requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ? AND DATE(created_at) = CURDATE()");
$stmt->execute([$_SESSION['user_id']]);
$stats['today_requests'] = $stmt->fetchColumn();

// Recent requests
$stmt = $pdo->prepare("
    SELECT r.*, i.item_name 
    FROM requests r
    JOIN items i ON r.item_id = i.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$recent_requests = $stmt->fetchAll();

// Available items for quick request
$stmt = $pdo->query("SELECT * FROM items WHERE quantity > 0 ORDER BY item_name ASC");
$available_items = $stmt->fetchAll();

// User's request history chart data
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        DATE_FORMAT(created_at, '%M %Y') as month_name,
        COUNT(*) as total_requests,
        SUM(status = 'approved') as approved,
        SUM(status = 'rejected') as rejected,
        SUM(status = 'pending') as pending
    FROM requests 
    WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
");
$stmt->execute([$_SESSION['user_id']]);
$user_chart_data = $stmt->fetchAll();

// Most requested items by user
$stmt = $pdo->prepare("
    SELECT i.item_name, COUNT(r.id) as request_count, SUM(r.quantity) as total_quantity
    FROM requests r
    JOIN items i ON r.item_id = i.id
    WHERE r.user_id = ?
    GROUP BY r.item_id
    ORDER BY request_count DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$user_top_items = $stmt->fetchAll();

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-tachometer-alt me-2"></i>My Dashboard
                </h1>
                <p class="text-muted mb-0">
                    Welcome back, <?php echo $_SESSION['full_name']; ?>! 
                    <span class="badge bg-success ms-2">Online</span>
                </p>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-plus me-2"></i>Quick Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="request.php"><i class="fas fa-plus-circle me-2"></i>New Request</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="showQuickRequestModal()"><i class="fas fa-bolt me-2"></i>Quick Request</a></li>
                    </ul>
                </div>
                <a href="request.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>New Request
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
                    <div class="stat-icon text-info">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3 data-stat="total_requests"><?php echo number_format($stats['total_requests']); ?></h3>
                    <p>Total Requests</p>
                    <div class="stat-trend">
                        <i class="fas fa-chart-line text-info"></i>
                        <small class="text-muted">All Time</small>
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
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 data-stat="approved_requests"><?php echo number_format($stats['approved_requests']); ?></h3>
                    <p>Approved Requests</p>
                    <div class="stat-trend">
                        <i class="fas fa-thumbs-up text-success"></i>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h3 data-stat="rejected_requests"><?php echo number_format($stats['rejected_requests']); ?></h3>
                    <p>Rejected Requests</p>
                    <div class="stat-trend">
                        <i class="fas fa-times text-danger"></i>
                        <small class="text-muted">Declined</small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 data-stat="monthly_requests"><?php echo number_format($stats['monthly_requests']); ?></h3>
                    <p>This Month</p>
                    <div class="stat-trend">
                        <i class="fas fa-calendar text-primary"></i>
                        <small class="text-muted">Current</small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon text-secondary">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <h3 data-stat="today_requests"><?php echo number_format($stats['today_requests']); ?></h3>
                    <p>Today's Requests</p>
                    <div class="stat-trend">
                        <i class="fas fa-clock text-secondary"></i>
                        <small class="text-muted">Today</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Requests -->
            <div class="col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Requests
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_requests)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                                <p>No requests found</p>
                                <a href="request.php" class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Submit Your First Request
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Branch</th>
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
                                                        <i class="fas fa-box me-2 text-muted"></i>
                                                        <?php echo htmlspecialchars($request['item_name']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark"><?php echo number_format($request['quantity']); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['branch']); ?></td>
                                                <td>
                                                    <span class="badge status-<?php echo $request['status']; ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                        <?php if ($request['status'] === 'pending'): ?>
                                                            <i class="fas fa-clock me-1"></i>
                                                        <?php elseif ($request['status'] === 'approved'): ?>
                                                            <i class="fas fa-check me-1"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-times me-1"></i>
                                                        <?php endif; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo format_date($request['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-outline-info" onclick="viewRequestDetails(<?php echo htmlspecialchars(json_encode($request)); ?>)" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($request['status'] === 'approved'): ?>
                                                            <a href="../ajax/print_request_pdf.php?request_id=<?php echo $request['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Print Request">
                                                                <i class="fas fa-print"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="request.php" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i>Submit New Request
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar Widgets -->
            <div class="col-lg-5">
                <!-- Enhanced Quick Request -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Request
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="request.php" method="POST" id="quickRequestForm">
                            <input type="hidden" name="quick_request" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">Item</label>
                                <select class="form-select" name="item_id" required onchange="updateAvailableQuantity(this)">
                                    <option value="">Select Item</option>
                                    <?php foreach ($available_items as $item): ?>
                                        <option value="<?php echo $item['id']; ?>" data-max="<?php echo $item['quantity']; ?>">
                                            <?php echo htmlspecialchars($item['item_name']); ?> 
                                            (<?php echo $item['quantity']; ?> available)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="adjustQuantity(-1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center" name="quantity" id="quickQuantity" min="1" value="1" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="adjustQuantity(1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted" id="quantityHelp">Maximum available: -</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Branch</label>
                                <select class="form-select" name="branch" required>
                                    <option value="">Select Branch</option>
                                    <option value="Hawassa Main Branch">Hawassa Main Branch</option>
                                    <option value="Hawassa Piassa Branch">Hawassa Piassa Branch</option>
                                    <option value="Hawassa University Branch">Hawassa University Branch</option>
                                    <option value="Shashemene Branch">Shashemene Branch</option>
                                    <option value="Dilla Branch">Dilla Branch</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Quick Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- My Top Items -->
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-star me-2"></i>My Most Requested Items
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_top_items)): ?>
                            <p class="text-muted text-center">No requests yet</p>
                        <?php else: ?>
                            <?php foreach ($user_top_items as $index => $item): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 <?php echo $index < count($user_top_items) - 1 ? 'border-bottom' : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo $item['total_quantity']; ?> units total</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-info"><?php echo $item['request_count']; ?> times</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Notifications Preview -->
                <div class="card mt-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bell me-2"></i>Recent Notifications
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="notifications-preview"></div>
                        <div class="text-center mt-3">
                            <a href="notifications.php" class="btn btn-outline-warning btn-sm">
                                View All Notifications <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Request History Chart -->
        <?php if (!empty($user_chart_data)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-area me-2"></i>My Request History
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="userChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
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
    <?php if (!empty($user_chart_data)): ?>
    // User Request History Chart
    const userCtx = document.getElementById('userChart').getContext('2d');
    new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo "'" . implode("','", array_reverse(array_column($user_chart_data, 'month_name'))) . "'"; ?>],
            datasets: [{
                label: 'Total Requests',
                data: [<?php echo implode(',', array_reverse(array_column($user_chart_data, 'total_requests'))); ?>],
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: '#28a745',
                borderWidth: 1
            }, {
                label: 'Approved',
                data: [<?php echo implode(',', array_reverse(array_column($user_chart_data, 'approved'))); ?>],
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                borderColor: '#007bff',
                borderWidth: 1
            }, {
                label: 'Rejected',
                data: [<?php echo implode(',', array_reverse(array_column($user_chart_data, 'rejected'))); ?>],
                backgroundColor: 'rgba(220, 53, 69, 0.8)',
                borderColor: '#dc3545',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
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
                }
            }
        }
    });
    <?php endif; ?>

// Quick request functionality
function updateAvailableQuantity(select) {
    const selectedOption = select.options[select.selectedIndex];
    const maxQuantity = selectedOption.getAttribute('data-max') || 0;
    const quantityInput = document.getElementById('quickQuantity');
    const helpText = document.getElementById('quantityHelp');
    
    if (maxQuantity > 0) {
        quantityInput.max = maxQuantity;
        helpText.textContent = `Maximum available: ${maxQuantity}`;
        helpText.className = 'form-text text-success';
    } else {
        quantityInput.max = 0;
        helpText.textContent = 'Item out of stock';
        helpText.className = 'form-text text-danger';
    }
}

function adjustQuantity(change) {
    const quantityInput = document.getElementById('quickQuantity');
    const currentValue = parseInt(quantityInput.value) || 1;
    const maxValue = parseInt(quantityInput.max) || 999;
    const minValue = parseInt(quantityInput.min) || 1;
    
    const newValue = Math.max(minValue, Math.min(maxValue, currentValue + change));
    quantityInput.value = newValue;
}

// Load recent notifications for preview
function loadNotificationsPreview() {
    $.ajax({
        url: '../ajax/notifications.php',
        method: 'GET',
        data: { limit: 3 },
        success: function(response) {
            if (response.success && response.notifications.length > 0) {
                let html = '';
                response.notifications.slice(0, 3).forEach(function(notification) {
                    html += `
                        <div class="d-flex align-items-start py-2 border-bottom">
                            <div class="flex-grow-1">
                                <p class="mb-1 small">${notification.message}</p>
                                <small class="text-muted">${formatDateTime(notification.created_at)}</small>
                            </div>
                            ${notification.status === 'unread' ? '<div class="text-primary"><i class="fas fa-circle" style="font-size: 6px;"></i></div>' : ''}
                        </div>
                    `;
                });
                $('#notifications-preview').html(html);
            } else {
                $('#notifications-preview').html('<p class="text-muted text-center small">No notifications</p>');
            }
        }
    });
}

// View request details
function viewRequestDetails(request) {
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

// Show quick request modal
function showQuickRequestModal() {
    // You can implement a modal version of quick request here
    window.location.href = 'request.php';
}

function formatDateTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleDateString() + ' - ' + date.toLocaleTimeString();
}

$(document).ready(function() {
    loadNotificationsPreview();
    setInterval(loadNotificationsPreview, 30000); // Refresh every 30 seconds
});
</script>

<?php include '../includes/footer.php'; ?>