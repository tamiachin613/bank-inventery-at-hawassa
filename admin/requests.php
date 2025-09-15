<?php
require_once '../config/config.php';
check_admin();

$page_title = 'Request Management';
$message = '';

// Handle request actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve':
                $request_id = $_POST['request_id'];
                $admin_remark = sanitize_input($_POST['admin_remark'] ?? '');
                
                // Get request details
                $stmt = $pdo->prepare("
                    SELECT r.*, i.quantity as current_stock 
                    FROM requests r
                    JOIN items i ON r.item_id = i.id
                    WHERE r.id = ? AND r.status = 'pending'
                ");
                $stmt->execute([$request_id]);
                $request = $stmt->fetch();
                
                if ($request) {
                    if ($request['current_stock'] >= $request['quantity']) {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        try {
                            // Update request status
                            $stmt = $pdo->prepare("UPDATE requests SET status = 'approved', admin_remark = ? WHERE id = ?");
                            $stmt->execute([$admin_remark, $request_id]);
                            
                            // Update inventory
                            $stmt = $pdo->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
                            $stmt->execute([$request['quantity'], $request['item_id']]);
                            
                            // Add notification
                            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')");
                            $message_text = "Your request has been approved. " . ($admin_remark ? "Admin note: " . $admin_remark : "");
                            $stmt->execute([$request['user_id'], $message_text]);
                            
                            $pdo->commit();
                            $message = '<div class="alert alert-success">Request approved successfully!</div>';
                        } catch (Exception $e) {
                            $pdo->rollback();
                            $message = '<div class="alert alert-danger">Error approving request!</div>';
                        }
                    } else {
                        $message = '<div class="alert alert-warning">Insufficient stock to approve this request!</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger">Request not found or already processed!</div>';
                }
                break;
                
            case 'reject':
                $request_id = $_POST['request_id'];
                $admin_remark = sanitize_input($_POST['admin_remark']);
                
                // Update request status
                $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected', admin_remark = ? WHERE id = ? AND status = 'pending'");
                $stmt->execute([$admin_remark, $request_id]);
                
                if ($stmt->rowCount() > 0) {
                    // Get user_id for notification
                    $stmt = $pdo->prepare("SELECT user_id FROM requests WHERE id = ?");
                    $stmt->execute([$request_id]);
                    $user_id = $stmt->fetchColumn();
                    
                    // Add notification
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'error')");
                    $message_text = "Your request has been rejected. Reason: " . $admin_remark;
                    $stmt->execute([$user_id, $message_text]);
                    
                    $message = '<div class="alert alert-success">Request rejected successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Request not found or already processed!</div>';
                }
                break;
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query based on filters
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(r.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(r.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get requests with filters
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, i.item_name, i.quantity as current_stock
    FROM requests r
    JOIN users u ON r.user_id = u.id
    JOIN items i ON r.item_id = i.id
    $where_clause
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Request Management</h1>
                <p class="text-muted mb-0">Review and process inventory requests</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success" onclick="printPage()">
                    <i class="fas fa-print me-2"></i>Print Report
                </button>
            </div>
        </div>
    </div>
    
    <div class="content-body">
        <?php echo $message; ?>
        
        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php
            $stats = [];
            foreach ($requests as $request) {
                $stats[$request['status']] = ($stats[$request['status']] ?? 0) + 1;
            }
            ?>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3><?php echo $stats['pending'] ?? 0; ?></h3>
                    <p>Pending Requests</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3><?php echo $stats['approved'] ?? 0; ?></h3>
                    <p>Approved Requests</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h3><?php echo $stats['rejected'] ?? 0; ?></h3>
                    <p>Rejected Requests</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="fas fa-list"></i>
                    </div>
                    <h3><?php echo count($requests); ?></h3>
                    <p>Total Requests</p>
                </div>
            </div>
        </div>
        
        <div class="card table-custom">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table data-table" id="requestsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Item</th>
                                <th>Requested Qty</th>
                                <th>Available Stock</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Request Date</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['item_name']); ?></td>
                                    <td><?php echo number_format($request['quantity']); ?></td>
                                    <td>
                                        <?php echo number_format($request['current_stock']); ?>
                                        <?php if ($request['current_stock'] < $request['quantity'] && $request['status'] === 'pending'): ?>
                                            <i class="fas fa-exclamation-triangle text-warning ms-1" title="Insufficient Stock"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['branch']); ?></td>
                                    <td>
                                        <span class="badge status-<?php echo $request['status']; ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo format_datetime($request['created_at']); ?></td>
                                    <td class="no-print">
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success" onclick="approveRequest(<?php echo $request['id']; ?>)">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?php echo $request['id']; ?>)">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewRequest(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <?php if ($request['status'] === 'approved'): ?>
                                                <a href="../ajax/print_request_pdf.php?request_id=<?php echo $request['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-print"></i> Print
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Request Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="request_id" id="approve_request_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Are you sure you want to approve this request? The inventory will be automatically updated.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Admin Remark (Optional)</label>
                        <textarea class="form-control" name="admin_remark" rows="3" placeholder="Add any additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Request Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" id="reject_request_id">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Please provide a reason for rejecting this request.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="admin_remark" rows="3" placeholder="Explain why this request is being rejected..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Request Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-6"><strong>User:</strong></div>
                    <div class="col-sm-6" id="view_user"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-6"><strong>Item:</strong></div>
                    <div class="col-sm-6" id="view_item"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-6"><strong>Quantity:</strong></div>
                    <div class="col-sm-6" id="view_quantity"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-6"><strong>Branch:</strong></div>
                    <div class="col-sm-6" id="view_branch"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-6"><strong>Status:</strong></div>
                    <div class="col-sm-6" id="view_status"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-6"><strong>Request Date:</strong></div>
                    <div class="col-sm-6" id="view_date"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-6"><strong>User Remark:</strong></div>
                    <div class="col-sm-6" id="view_remark"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-6"><strong>Admin Remark:</strong></div>
                    <div class="col-sm-6" id="view_admin_remark"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function approveRequest(requestId) {
    document.getElementById('approve_request_id').value = requestId;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function rejectRequest(requestId) {
    document.getElementById('reject_request_id').value = requestId;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function viewRequest(request) {
    document.getElementById('view_user').textContent = request.full_name;
    document.getElementById('view_item').textContent = request.item_name;
    document.getElementById('view_quantity').textContent = new Intl.NumberFormat().format(request.quantity);
    document.getElementById('view_branch').textContent = request.branch;
    document.getElementById('view_status').innerHTML = `<span class="badge status-${request.status}">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span>`;
    document.getElementById('view_date').textContent = formatDateTime(request.created_at);
    document.getElementById('view_remark').textContent = request.remark || 'None';
    document.getElementById('view_admin_remark').textContent = request.admin_remark || 'None';
    
    new bootstrap.Modal(document.getElementById('viewModal')).show();
}

// Real-time updates for request management
setInterval(function() {
    if (!document.hidden) {
        fetch('../ajax/real_time_updates.php?action=get_pending_requests')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePendingCount(data.pending_count);
                }
            })
            .catch(error => console.log('Real-time update error:', error));
    }
}, 5000); // Update every 5 seconds

function updatePendingCount(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}

function formatDateTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleDateString() + ' - ' + date.toLocaleTimeString();
}
</script>

<?php include '../includes/footer.php'; ?>