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
            body { margin: 0; font-size: 12pt; }
            .no-print { display: none !important; }
            .container { max-width: none !important; }
        }
        
        .print-header {
            text-align: center;
            border-bottom: 3px solid #228b22;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .print-header h1 {
            color: #228b22;
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .print-header h2 {
            color: #666;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .bank-info {
            color: #999;
            font-size: 14px;
        }
        
        .request-details {
            margin: 30px 0;
        }
        
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .detail-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .detail-label {
            font-weight: bold;
            color: #333;
            width: 200px;
            background: #f8f9fa;
        }
        
        .detail-value {
            color: #666;
        }
        
        .status-approved { 
            color: #28a745; 
            font-weight: bold; 
            text-transform: uppercase;
            background: #d4edda;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .status-rejected { 
            color: #dc3545; 
            font-weight: bold; 
            text-transform: uppercase;
            background: #f8d7da;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .status-pending { 
            color: #ffc107; 
            font-weight: bold; 
            text-transform: uppercase;
            background: #fff3cd;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .signature-section {
            margin-top: 60px;
            page-break-inside: avoid;
        }
        
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .signature-table td {
            text-align: center;
            padding: 20px;
            vertical-align: top;
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
        
        .official-seal {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            border: 2px solid #228b22;
            border-radius: 10px;
            background: #f8f9fa;
        }
        
        .request-id {
            font-size: 24px;
            font-weight: bold;
            color: #228b22;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background: #f0f8f0;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="no-print mb-3 text-center">
            <button onclick="window.print()" class="btn btn-success btn-lg me-3">
                <i class="fas fa-print me-2"></i>Print Document
            </button>
            <button onclick="downloadPDF()" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-download me-2"></i>Download PDF
            </button>
            <button onclick="window.close()" class="btn btn-secondary btn-lg">
                <i class="fas fa-times me-2"></i>Close
            </button>
        </div>
        
        <div class="print-header">
            <h1>INVENTORY REQUEST APPROVAL</h1>
            <h2>Commercial Bank of Ethiopia</h2>
            <div class="bank-info">Hawassa Branch - Inventory Management Department</div>
        </div>
        
        <div class="request-id">
            REQUEST ID: #<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?>
        </div>
        
        <div class="request-details">
            <table class="detail-table">
                <tr>
                    <td class="detail-label">Requested By:</td>
                    <td class="detail-value"><?php echo htmlspecialchars($request['full_name']); ?></td>
                </tr>
                <tr>
                    <td class="detail-label">Email Address:</td>
                    <td class="detail-value"><?php echo htmlspecialchars($request['email']); ?></td>
                </tr>
                <tr>
                    <td class="detail-label">Item Name:</td>
                    <td class="detail-value"><?php echo htmlspecialchars($request['item_name']); ?></td>
                </tr>
                <tr>
                    <td class="detail-label">Item Code:</td>
                    <td class="detail-value"><?php echo htmlspecialchars($request['item_code']); ?></td>
                </tr>
                <tr>
                    <td class="detail-label">Quantity Requested:</td>
                    <td class="detail-value"><strong><?php echo number_format($request['quantity']); ?> <?php echo htmlspecialchars($request['unit']); ?></strong></td>
                </tr>
                <tr>
                    <td class="detail-label">Branch/Department:</td>
                    <td class="detail-value"><?php echo htmlspecialchars($request['branch']); ?></td>
                </tr>
                <tr>
                    <td class="detail-label">Request Date:</td>
                    <td class="detail-value"><?php echo date('F d, Y', strtotime($request['request_date'])); ?></td>
                </tr>
                <tr>
                    <td class="detail-label">Submission Date:</td>
                    <td class="detail-value"><?php echo date('F d, Y - h:i A', strtotime($request['created_at'])); ?></td>
                </tr>
                <tr>
                    <td class="detail-label">Current Status:</td>
                    <td class="detail-value">
                        <span class="status-<?php echo $request['status']; ?>">
                            <?php echo strtoupper($request['status']); ?>
                        </span>
                    </td>
                </tr>
                
                <?php if ($request['remark']): ?>
                <tr>
                    <td class="detail-label">User Remarks:</td>
                    <td class="detail-value"><?php echo htmlspecialchars($request['remark']); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if ($request['admin_remark']): ?>
                <tr>
                    <td class="detail-label">Admin Remarks:</td>
                    <td class="detail-value"><?php echo htmlspecialchars($request['admin_remark']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <?php if ($request['status'] === 'approved'): ?>
        <div class="official-seal">
            <h4 style="color: #228b22; margin-bottom: 10px;">
                <i class="fas fa-stamp me-2"></i>OFFICIALLY APPROVED
            </h4>
            <p style="margin: 0; color: #666;">
                This request has been approved by the authorized administrator<br>
                and the inventory has been updated accordingly.
            </p>
        </div>
        <?php endif; ?>
        
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td style="width: 50%;">
                        <div class="signature-box"></div>
                        <div><strong>Requested By</strong></div>
                        <div><?php echo htmlspecialchars($request['full_name']); ?></div>
                        <div><small>Date: <?php echo date('M d, Y', strtotime($request['created_at'])); ?></small></div>
                    </td>
                    <td style="width: 50%;">
                        <div class="signature-box"></div>
                        <div><strong>Approved By</strong></div>
                        <div>Administrator</div>
                        <div><small>Date: <?php echo date('M d, Y'); ?></small></div>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="footer">
            <p><strong>Commercial Bank of Ethiopia - Hawassa Branch</strong></p>
            <p>Inventory Management System | Generated on: <?php echo date('F d, Y - h:i A'); ?></p>
            <p style="font-size: 10px;">This is an official document generated by the CBE Inventory Management System</p>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function downloadPDF() {
            // Simple PDF generation using browser print to PDF
            window.print();
        }
        
        // Auto-print when page loads (optional)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 500);
        // };
    </script>
</body>
</html>

<?php
} else {
    header('Location: ../admin/requests.php');
    exit();
}
?>