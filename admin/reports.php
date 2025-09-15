<?php
require_once '../config/config.php';
check_admin();

$page_title = 'Reports & Analytics';
$message = '';

// Handle report export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $report_type = $_GET['report_type'] ?? 'monthly';
    $month = $_GET['month'] ?? date('Y-m');
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="inventory_report_' . $report_type . '_' . $month . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Generate Excel content
    echo "<table border='1'>";
    echo "<tr><th colspan='6'>Commercial Bank of Ethiopia - Hawassa Branch</th></tr>";
    echo "<tr><th colspan='6'>Inventory Report - " . ucfirst($report_type) . " (" . date('F Y', strtotime($month . '-01')) . ")</th></tr>";
    echo "<tr><th>Date</th><th>User</th><th>Item</th><th>Quantity</th><th>Branch</th><th>Status</th></tr>";
    
    // Get report data
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            u.full_name,
            i.item_name
        FROM requests r
        JOIN users u ON r.user_id = u.id
        JOIN items i ON r.item_id = i.id
        WHERE DATE_FORMAT(r.created_at, '%Y-%m') = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$month]);
    $report_data = $stmt->fetchAll();
    
    foreach ($report_data as $row) {
        echo "<tr>";
        echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
        echo "<td>" . number_format($row['quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($row['branch']) . "</td>";
        echo "<td>" . ucfirst($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit();
}

// Get report parameters
$report_type = $_GET['report_type'] ?? 'monthly';
$month = $_GET['month'] ?? date('Y-m');
$quarter = $_GET['quarter'] ?? '';
$year = $_GET['year'] ?? date('Y');

// Monthly Report
if ($report_type === 'monthly') {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            u.full_name,
            i.item_name
        FROM requests r
        JOIN users u ON r.user_id = u.id
        JOIN items i ON r.item_id = i.id
        WHERE DATE_FORMAT(r.created_at, '%Y-%m') = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$month]);
    $report_data = $stmt->fetchAll();
    
    // Monthly statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM requests 
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
    ");
    $stmt->execute([$month]);
    $stats = $stmt->fetch();
}

// Quarterly Report
if ($report_type === 'quarterly') {
    $quarter_months = [
        '1' => ['01', '02', '03'],
        '2' => ['04', '05', '06'],
        '3' => ['07', '08', '09'],
        '4' => ['10', '11', '12']
    ];
    
    if (!$quarter) {
        $current_month = (int)date('n');
        $quarter = ceil($current_month / 3);
    }
    
    $months = $quarter_months[$quarter];
    $start_date = "$year-" . $months[0] . "-01";
    $end_date = "$year-" . $months[2] . "-31";
    
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            u.full_name,
            i.item_name
        FROM requests r
        JOIN users u ON r.user_id = u.id
        JOIN items i ON r.item_id = i.id
        WHERE r.created_at BETWEEN ? AND ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $report_data = $stmt->fetchAll();
    
    // Quarterly statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM requests 
        WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $stats = $stmt->fetch();
}

// Most requested items
$stmt = $pdo->prepare("
    SELECT 
        i.item_name,
        COUNT(r.id) as request_count,
        SUM(r.quantity) as total_quantity_requested
    FROM requests r
    JOIN items i ON r.item_id = i.id
    WHERE " . ($report_type === 'monthly' ? "DATE_FORMAT(r.created_at, '%Y-%m') = '$month'" : "r.created_at BETWEEN '$start_date' AND '$end_date'") . "
    GROUP BY r.item_id
    ORDER BY request_count DESC
    LIMIT 10
");
$stmt->execute();
$popular_items = $stmt->fetchAll();

// Top users by requests
$stmt = $pdo->prepare("
    SELECT 
        u.full_name,
        COUNT(r.id) as request_count
    FROM requests r
    JOIN users u ON r.user_id = u.id
    WHERE " . ($report_type === 'monthly' ? "DATE_FORMAT(r.created_at, '%Y-%m') = '$month'" : "r.created_at BETWEEN '$start_date' AND '$end_date'") . "
    GROUP BY r.user_id
    ORDER BY request_count DESC
    LIMIT 10
");
$stmt->execute();
$top_users = $stmt->fetchAll();

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Reports & Analytics</h1>
                <p class="text-muted mb-0">Generate and view detailed reports</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success" onclick="exportToExcel('reportTable', 'inventory-report-<?php echo $report_type; ?>-<?php echo $month ?? $quarter; ?>')">
                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                </button>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-outline-success">
                    <i class="fas fa-download me-2"></i>Download Excel
                </a>
                <button class="btn btn-outline-success" onclick="printPage()">
                    <i class="fas fa-print me-2"></i>Print Report
                </button>
            </div>
        </div>
    </div>
    
    <div class="content-body">
        <!-- Report Type Selection -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Report Type</label>
                        <select class="form-select" name="report_type" onchange="toggleDateInputs()">
                            <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                            <option value="quarterly" <?php echo $report_type === 'quarterly' ? 'selected' : ''; ?>>Quarterly Report</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3" id="monthly-input">
                        <label class="form-label">Month</label>
                        <input type="month" class="form-control" name="month" value="<?php echo $month; ?>">
                    </div>
                    
                    <div class="col-md-2" id="quarterly-input" style="display: none;">
                        <label class="form-label">Quarter</label>
                        <select class="form-select" name="quarter">
                            <option value="1" <?php echo $quarter == '1' ? 'selected' : ''; ?>>Q1 (Jan-Mar)</option>
                            <option value="2" <?php echo $quarter == '2' ? 'selected' : ''; ?>>Q2 (Apr-Jun)</option>
                            <option value="3" <?php echo $quarter == '3' ? 'selected' : ''; ?>>Q3 (Jul-Sep)</option>
                            <option value="4" <?php echo $quarter == '4' ? 'selected' : ''; ?>>Q4 (Oct-Dec)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2" id="year-input" style="display: none;">
                        <label class="form-label">Year</label>
                        <select class="form-select" name="year">
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-chart-bar me-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Print Header (Hidden on screen) -->
        <div class="print-header d-none">
            <h1><?php echo SITE_NAME; ?></h1>
            <h2><?php echo ucfirst($report_type); ?> Report</h2>
            <p><strong>Period:</strong> 
                <?php 
                if ($report_type === 'monthly') {
                    echo date('F Y', strtotime($month . '-01'));
                } else {
                    echo "Q$quarter $year";
                }
                ?>
            </p>
            <p><strong>Generated on:</strong> <?php echo date('F d, Y - h:i A'); ?></p>
        </div>
        
        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3><?php echo number_format($stats['total_requests']); ?></h3>
                    <p>Total Requests</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3><?php echo number_format($stats['approved']); ?></h3>
                    <p>Approved Requests</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h3><?php echo number_format($stats['rejected']); ?></h3>
                    <p>Rejected Requests</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3><?php echo number_format($stats['pending']); ?></h3>
                    <p>Pending Requests</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Detailed Report Table -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-table me-2"></i>Detailed Request Report
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="reportTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Branch</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($report_data)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                No data found for the selected period
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <td><?php echo format_date($row['created_at']); ?></td>
                                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                                <td><strong><?php echo number_format($row['quantity']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['branch']); ?></td>
                                                <td>
                                                    <span class="badge status-<?php echo $row['status']; ?>">
                                                        <?php echo ucfirst($row['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Analytics -->
            <div class="col-lg-4">
                <!-- Most Requested Items -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-trophy me-2"></i>Most Requested Items
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($popular_items)): ?>
                            <p class="text-muted text-center">No data available</p>
                        <?php else: ?>
                            <?php foreach ($popular_items as $index => $item): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 <?php echo $index < count($popular_items) - 1 ? 'border-bottom' : ''; ?>">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo number_format($item['total_quantity_requested']); ?> units total</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary"><?php echo $item['request_count']; ?> requests</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Top Users -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-friends me-2"></i>Top Requesting Users
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_users)): ?>
                            <p class="text-muted text-center">No data available</p>
                        <?php else: ?>
                            <?php foreach ($top_users as $index => $user): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 <?php echo $index < count($top_users) - 1 ? 'border-bottom' : ''; ?>">
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                    </div>
                                    <div>
                                        <span class="badge bg-secondary"><?php echo $user['request_count']; ?> requests</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDateInputs() {
    const reportType = document.querySelector('select[name="report_type"]').value;
    const monthlyInput = document.getElementById('monthly-input');
    const quarterlyInput = document.getElementById('quarterly-input');
    const yearInput = document.getElementById('year-input');
    
    if (reportType === 'monthly') {
        monthlyInput.style.display = 'block';
        quarterlyInput.style.display = 'none';
        yearInput.style.display = 'none';
    } else {
        monthlyInput.style.display = 'none';
        quarterlyInput.style.display = 'block';
        yearInput.style.display = 'block';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleDateInputs();
    
    // Auto-refresh report data every 30 seconds
    setInterval(function() {
        // Refresh page to get latest data
        if (document.hidden === false) {
            location.reload();
        }
    }, 30000);
});

// Enhanced export to Excel function
function exportToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    // Create workbook
    const wb = XLSX.utils.table_to_book(table, {sheet: "Report"});
    
    // Add metadata
    const ws = wb.Sheets["Report"];
    XLSX.utils.sheet_add_aoa(ws, [
        ["Commercial Bank of Ethiopia - Hawassa Branch"],
        ["Inventory Management Report"],
        ["Generated on: " + new Date().toLocaleString()],
        [""]
    ], {origin: "A1"});
    
    // Download file
    XLSX.writeFile(wb, filename + '_' + new Date().toISOString().slice(0,10) + '.xlsx');
}
</script>

<?php include '../includes/footer.php'; ?>