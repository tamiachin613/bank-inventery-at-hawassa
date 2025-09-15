<?php
require_once '../config/config.php';
check_admin();

if (isset($_GET['request_id'])) {
    $request_id = (int)$_GET['request_id'];
    
    // Get request details
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name, u.email, i.item_name, i.item_code, i.unit
        FROM requests r
        JOIN users u ON r.user_id = u.id
        JOIN items i ON r.item_id = i.id
        WHERE r.id = ?
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        die('Request not found');
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Approval - <?php echo $request['item_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
        
        .print-header {
            text-align: center;
            border-bottom: 3px solid #228b22;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .print-header h1 {
            color: #228b22;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .print-header h2 {
            color: #666;
            font-size: 18px;
            margin-bottom: 0;
        }
        
        .request-details {
            margin: 30px 0;
        }
        
        .detail-row {
            margin-bottom: 15px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: bold;
            color: #333;
            width: 200px;
            display: inline-block;
        }
        
        .detail-value {
            color: #666;
        }
        
        .status-approved { color: #28a745; font-weight: bold; }
        .status-rejected { color: #dc3545; font-weight: bold; }
        .status-pending { color: #ffc107; font-weight: bold; }
        
        .signature-section {
            margin-top: 60px;
            page-break-inside: avoid;
        }
        
        .signature-box {
            border-bottom: 2px solid #333;
            width: 250px;
            height: 50px;
            margin: 20px auto;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print me-2"></i>Print Document
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>Close
            </button>
        </div>
        
        <div class="print-header">
            <h1>INVENTORY REQUEST APPROVAL</h1>
            <h2>Commercial Bank of Ethiopia - Hawassa Branch</h2>
        </div>
        
        <div class="request-details">
            <div class="detail-row">
                <span class="detail-label">Request ID:</span>
                <span class="detail-value">#<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Requested By:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request['full_name']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request['email']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Item Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request['item_name']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Item Code:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request['item_code']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Quantity Requested:</span>
                <span class="detail-value"><?php echo number_format($request['quantity']); ?> <?php echo htmlspecialchars($request['unit']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Branch:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request['branch']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Request Date:</span>
                <span class="detail-value"><?php echo date('F d, Y', strtotime($request['request_date'])); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value status-<?php echo $request['status']; ?>">
                    <?php echo strtoupper($request['status']); ?>
                </span>
            </div>
            
            <?php if ($request['remark']): ?>
            <div class="detail-row">
                <span class="detail-label">User Remark:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request['remark']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($request['admin_remark']): ?>
            <div class="detail-row">
                <span class="detail-label">Admin Remark:</span>
                <span class="detail-value"><?php echo htmlspecialchars($request['admin_remark']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="signature-section">
            <div class="row">
                <div class="col-6 text-center">
                    <div class="signature-box"></div>
                    <div><strong>Requested By</strong></div>
                    <div><?php echo htmlspecialchars($request['full_name']); ?></div>
                    <div>Date: <?php echo date('M d, Y', strtotime($request['created_at'])); ?></div>
                </div>
                <div class="col-6 text-center">
                    <div class="signature-box"></div>
                    <div><strong>Approved By</strong></div>
                    <div>Administrator</div>
                    <div>Date: <?php echo date('M d, Y'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>This document was generated automatically by the CBE Hawassa Branch Inventory Management System</p>
            <p>Generated on: <?php echo date('F d, Y - h:i A'); ?></p>
        </div>
    </div>
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>

<?php
} else {
    header('Location: ../admin/requests.php');
    exit();
}
?>