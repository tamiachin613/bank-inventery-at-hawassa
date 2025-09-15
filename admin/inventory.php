<?php
require_once '../config/config.php';
check_admin();

$page_title = 'Inventory Management';
$message = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $item_name = sanitize_input($_POST['item_name']);
                $item_code = sanitize_input($_POST['item_code']);
                $quantity = (int)$_POST['quantity'];
                $unit = sanitize_input($_POST['unit']);
                $description = sanitize_input($_POST['description']);
                
                // Check if item code already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE item_code = ?");
                $stmt->execute([$item_code]);
                
                if ($stmt->fetchColumn() > 0) {
                    $message = '<div class="alert alert-danger">Item code already exists!</div>';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO items (item_name, item_code, quantity, unit, description) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$item_name, $item_code, $quantity, $unit, $description])) {
                        $message = '<div class="alert alert-success">Item added successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error adding item!</div>';
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $item_name = sanitize_input($_POST['item_name']);
                $item_code = sanitize_input($_POST['item_code']);
                $quantity = (int)$_POST['quantity'];
                $unit = sanitize_input($_POST['unit']);
                $description = sanitize_input($_POST['description']);
                
                // Check if item code already exists for other items
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE item_code = ? AND id != ?");
                $stmt->execute([$item_code, $id]);
                
                if ($stmt->fetchColumn() > 0) {
                    $message = '<div class="alert alert-danger">Item code already exists!</div>';
                } else {
                    $stmt = $pdo->prepare("UPDATE items SET item_name = ?, item_code = ?, quantity = ?, unit = ?, description = ? WHERE id = ?");
                    if ($stmt->execute([$item_name, $item_code, $quantity, $unit, $description, $id])) {
                        $message = '<div class="alert alert-success">Item updated successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error updating item!</div>';
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Check if item is used in any requests
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE item_id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->fetchColumn() > 0) {
                    $message = '<div class="alert alert-warning">Cannot delete item: It has associated requests!</div>';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $message = '<div class="alert alert-success">Item deleted successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error deleting item!</div>';
                    }
                }
                break;
                
            case 'adjust_stock':
                $id = $_POST['id'];
                $adjustment = (int)$_POST['adjustment'];
                $adjustment_type = $_POST['adjustment_type'];
                
                if ($adjustment_type === 'add') {
                    $stmt = $pdo->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
                } else {
                    $stmt = $pdo->prepare("UPDATE items SET quantity = GREATEST(quantity - ?, 0) WHERE id = ?");
                }
                
                if ($stmt->execute([$adjustment, $id])) {
                    $message = '<div class="alert alert-success">Stock adjusted successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error adjusting stock!</div>';
                }
                break;
        }
    }
}

// Get all items
$stmt = $pdo->query("SELECT * FROM items ORDER BY item_name ASC");
$items = $stmt->fetchAll();

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Inventory Management</h1>
                <p class="text-muted mb-0">Manage inventory items and stock levels</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <i class="fas fa-plus me-2"></i>Add New Item
                </button>
                <button class="btn btn-outline-success" onclick="printPage()">
                    <i class="fas fa-print me-2"></i>Print
                </button>
            </div>
        </div>
    </div>
    
    <div class="content-body">
        <?php echo $message; ?>
        
        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3><?php echo count($items); ?></h3>
                    <p>Total Items</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <h3><?php echo number_format(array_sum(array_column($items, 'quantity'))); ?></h3>
                    <p>Total Stock Units</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3><?php echo count(array_filter($items, function($item) { return $item['quantity'] < 10; })); ?></h3>
                    <p>Low Stock Items</p>
                </div>
            </div>
        </div>
        
        <div class="card table-custom">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table data-table" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr class="<?php echo $item['quantity'] < 10 ? 'table-warning' : ''; ?>">
                                    <td><?php echo $item['id']; ?></td>
                                    <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td>
                                        <strong><?php echo number_format($item['quantity']); ?></strong>
                                        <?php if ($item['quantity'] < 10): ?>
                                            <i class="fas fa-exclamation-triangle text-warning ms-1" title="Low Stock"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td>
                                        <?php if ($item['quantity'] == 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php elseif ($item['quantity'] < 10): ?>
                                            <span class="badge bg-warning">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo format_datetime($item['updated_at']); ?></td>
                                    <td class="no-print">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="increaseStock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="decreaseStock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="item_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Item Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="item_code" required>
                        <small class="form-text text-muted">Unique identifier for the item</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="quantity" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select class="form-select" name="unit" required>
                                    <option value="">Select Unit</option>
                                    <option value="pcs">Pieces</option>
                                    <option value="box">Box</option>
                                    <option value="pack">Pack</option>
                                    <option value="ream">Ream</option>
                                    <option value="dozen">Dozen</option>
                                    <option value="kg">Kilogram</option>
                                    <option value="ltr">Liter</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="item_name" id="edit_item_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Item Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="item_code" id="edit_item_code" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="quantity" id="edit_quantity" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select class="form-select" name="unit" id="edit_unit" required>
                                    <option value="pcs">Pieces</option>
                                    <option value="box">Box</option>
                                    <option value="pack">Pack</option>
                                    <option value="ream">Ream</option>
                                    <option value="dozen">Dozen</option>
                                    <option value="kg">Kilogram</option>
                                    <option value="ltr">Liter</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Increase Stock Modal -->
<div class="modal fade" id="increaseStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Increase Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Item</label>
                    <input type="text" class="form-control" id="increase_item_name" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Amount to Add <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="increase_amount" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="updateInventory('increase')">
                    <i class="fas fa-plus me-2"></i>Increase Stock
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Decrease Stock Modal -->
<div class="modal fade" id="decreaseStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Decrease Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Item</label>
                    <input type="text" class="form-control" id="decrease_item_name" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Current Stock</label>
                    <input type="text" class="form-control" id="current_stock" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Amount to Remove <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="decrease_amount" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="updateInventory('decrease')">
                    <i class="fas fa-minus me-2"></i>Decrease Stock
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Old Stock Adjustment Modal (keeping for compatibility) -->
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="adjust_stock">
                <input type="hidden" name="id" id="adjust_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Item</label>
                        <input type="text" class="form-control" id="adjust_item_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="adjustment_type" required>
                            <option value="">Select Type</option>
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Adjustment Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="adjustment" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Adjust Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentItemId = null;

function editItem(item) {
    document.getElementById('edit_id').value = item.id;
    document.getElementById('edit_item_name').value = item.item_name;
    document.getElementById('edit_item_code').value = item.item_code;
    document.getElementById('edit_quantity').value = item.quantity;
    document.getElementById('edit_unit').value = item.unit;
    document.getElementById('edit_description').value = item.description;
    
    new bootstrap.Modal(document.getElementById('editItemModal')).show();
}

function increaseStock(id, itemName) {
    currentItemId = id;
    document.getElementById('increase_item_name').value = itemName;
    document.getElementById('increase_amount').value = '';
    
    new bootstrap.Modal(document.getElementById('increaseStockModal')).show();
}

function decreaseStock(id, itemName) {
    currentItemId = id;
    
    // Get current stock
    fetch('../ajax/real_time_updates.php?action=get_inventory_status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = data.items.find(i => i.id == id);
                if (item) {
                    document.getElementById('decrease_item_name').value = itemName;
                    document.getElementById('current_stock').value = item.quantity + ' units';
                    document.getElementById('decrease_amount').value = '';
                    document.getElementById('decrease_amount').max = item.quantity;
                    
                    new bootstrap.Modal(document.getElementById('decreaseStockModal')).show();
                }
            }
        });
}

function updateInventory(action) {
    const amount = document.getElementById(action + '_amount').value;
    
    if (!amount || amount <= 0) {
        alert('Please enter a valid amount');
        return;
    }
    
    // Show loading state
    const button = document.querySelector(`#${action}StockModal .btn-${action === 'increase' ? 'success' : 'warning'}`);
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
    button.disabled = true;
    
    fetch('../ajax/update_inventory.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `item_id=${currentItemId}&action=${action}&amount=${amount}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showAlert(data.message, 'success');
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById(action + 'StockModal')).hide();
            
            // Refresh page to show updated data
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error updating inventory', 'danger');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function adjustStock(id, itemName) {
    document.getElementById('adjust_id').value = id;
    document.getElementById('adjust_item_name').value = itemName;
    
    new bootstrap.Modal(document.getElementById('adjustStockModal')).show();
}

// Real-time inventory updates
setInterval(function() {
    if (!document.hidden) {
        fetch('../ajax/real_time_updates.php?action=get_inventory_status')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateInventoryTable(data.items);
                }
            })
            .catch(error => console.log('Real-time update error:', error));
    }
}, 10000); // Update every 10 seconds

function updateInventoryTable(items) {
    // Update quantity displays in real-time
    items.forEach(item => {
        const row = document.querySelector(`tr[data-item-id="${item.id}"]`);
        if (row) {
            const quantityCell = row.querySelector('.quantity-cell');
            if (quantityCell) {
                quantityCell.innerHTML = `<strong>${item.quantity.toLocaleString()}</strong>`;
                
                // Update status badge
                const statusCell = row.querySelector('.status-cell');
                if (statusCell) {
                    let statusClass = 'bg-success';
                    let statusText = 'In Stock';
                    
                    if (item.quantity === 0) {
                        statusClass = 'bg-danger';
                        statusText = 'Out of Stock';
                    } else if (item.quantity < 10) {
                        statusClass = 'bg-warning';
                        statusText = 'Low Stock';
                    }
                    
                    statusCell.innerHTML = `<span class="badge ${statusClass}">${statusText}</span>`;
                }
            }
        }
    });
}

function showAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-danger';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.content-body');
    container.insertBefore(alert, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}
</script>

<?php include '../includes/footer.php'; ?>