<?php
require_once '../config/config.php';
check_user();

$page_title = 'Submit Request';
$message = '';

// Handle form submission
if ($_POST) {
    if (isset($_POST['quick_request'])) {
        // Handle quick request from dashboard
        $item_id = $_POST['item_id'];
        $quantity = (int)$_POST['quantity'];
        $branch = sanitize_input($_POST['branch']);
        $remark = '';
        $request_date = date('Y-m-d');
        
        $stmt = $pdo->prepare("INSERT INTO requests (user_id, item_id, quantity, branch, remark, request_date) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $item_id, $quantity, $branch, $remark, $request_date])) {
            $message = '<div class="alert alert-success">Quick request submitted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error submitting request!</div>';
        }
    } elseif (isset($_POST['items'])) {
        // Handle multiple requests
        $request_date = sanitize_input($_POST['request_date']);
        $success_count = 0;
        $total_count = 0;
        
        foreach ($_POST['items'] as $item) {
            if (!empty($item['item_id']) && !empty($item['quantity']) && !empty($item['branch'])) {
                $total_count++;
                
                $stmt = $pdo->prepare("INSERT INTO requests (user_id, item_id, quantity, branch, remark, request_date) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([
                    $_SESSION['user_id'],
                    $item['item_id'],
                    (int)$item['quantity'],
                    sanitize_input($item['branch']),
                    sanitize_input($item['remark'] ?? ''),
                    $request_date
                ])) {
                    $success_count++;
                }
            }
        }
        
        if ($success_count === $total_count && $total_count > 0) {
            $message = "<div class='alert alert-success'>All $success_count requests submitted successfully!</div>";
        } elseif ($success_count > 0) {
            $message = "<div class='alert alert-warning'>$success_count of $total_count requests submitted successfully!</div>";
        } else {
            $message = '<div class="alert alert-danger">No requests were submitted. Please check your entries.</div>';
        }
    }
}

// Get available items
$stmt = $pdo->query("SELECT * FROM items WHERE quantity > 0 ORDER BY item_name ASC");
$items = $stmt->fetchAll();

// Convert to JavaScript for dynamic forms
$items_js = json_encode($items);

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Submit Request</h1>
                <p class="text-muted mb-0">Request inventory items for your branch</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary d-md-none" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="content-body">
        <?php echo $message; ?>
        
        <div class="card form-custom">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>New Request Form
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="requestForm">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Request Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="request_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Requested By</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div id="request-items">
                        <!-- First request item -->
                        <div class="request-item" id="request-1">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Request #1</h6>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Item Name <span class="text-danger">*</span></label>
                                    <select class="form-select" name="items[1][item_id]" required>
                                        <option value="">Select Item</option>
                                        <?php foreach ($items as $item): ?>
                                            <option value="<?php echo $item['id']; ?>">
                                                <?php echo htmlspecialchars($item['item_name']); ?> 
                                                (Available: <?php echo $item['quantity']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="items[1][quantity]" min="1" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                                    <select class="form-select" name="items[1][branch]" required>
                                        <option value="">Select Branch</option>
                                        <option value="Hawassa Main Branch">Hawassa Main Branch</option>
                                        <option value="Hawassa Piassa Branch">Hawassa Piassa Branch</option>
                                        <option value="Hawassa University Branch">Hawassa University Branch</option>
                                        <option value="Shashemene Branch">Shashemene Branch</option>
                                        <option value="Dilla Branch">Dilla Branch</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Remark</label>
                                    <textarea class="form-control" name="items[1][remark]" rows="2" placeholder="Optional remarks..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add more request button -->
                    <div class="add-request-btn text-center py-4 mb-4">
                        <i class="fas fa-plus fa-2x mb-2 d-block"></i>
                        <strong>Add Another Request</strong>
                        <p class="text-muted mb-0">Click to add more items to your request</p>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Submit Requests
                        </button>
                        <button type="reset" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-undo me-2"></i>Reset Form
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Help Section -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-question-circle me-2"></i>How to Submit Requests
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle me-2 text-info"></i>Tips for Better Requests</h6>
                        <ul>
                            <li>Check available quantities before requesting</li>
                            <li>Be specific with branch information</li>
                            <li>Add remarks for special requirements</li>
                            <li>Submit requests well in advance</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-clock me-2 text-warning"></i>Processing Time</h6>
                        <ul>
                            <li>Normal requests: 1-2 business days</li>
                            <li>Urgent requests: Same day (with remarks)</li>
                            <li>Bulk requests: 2-3 business days</li>
                            <li>You'll receive notifications on status updates</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Make items available globally for dynamic forms
const itemOptions = <?php echo $items_js; ?>;
</script>

<?php include '../includes/footer.php'; ?>