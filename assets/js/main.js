// Commercial Bank of Ethiopia - Hawassa Branch
// Inventory and Request Management System JavaScript

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-refresh notifications and real-time updates
    if (typeof userId !== 'undefined') {
        setInterval(loadNotifications, 5000); // Refresh every 5 seconds
        loadNotifications(); // Load initially
        
        // Real-time dashboard updates
        setInterval(updateDashboardStats, 15000); // Update every 15 seconds
        
        // Load header notifications
        setInterval(loadHeaderNotifications, 5000); // Update every 5 seconds
        loadHeaderNotifications(); // Load initially
    }

    // Multi-request form management
    let requestCount = 1;
    
    // Add new request item
    $(document).on('click', '.add-request-btn', function() {
        requestCount++;
        const requestItem = `
            <div class="request-item" id="request-${requestCount}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Request #${requestCount}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-request" data-id="${requestCount}">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Item Name <span class="text-danger">*</span></label>
                        <select class="form-select" name="items[${requestCount}][item_id]" required>
                            <option value="">Select Item</option>
                            ${getItemOptions()}
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="items[${requestCount}][quantity]" min="1" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select class="form-select" name="items[${requestCount}][branch]" required>
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
                        <textarea class="form-control" name="items[${requestCount}][remark]" rows="2" placeholder="Optional remarks..."></textarea>
                    </div>
                </div>
            </div>
        `;
        
        $('.add-request-btn').before(requestItem);
    });

    // Remove request item
    $(document).on('click', '.remove-request', function() {
        const id = $(this).data('id');
        $(`#request-${id}`).remove();
    });

    // Form validation
    $('form').on('submit', function(e) {
        const form = this;
        
        // Check if all required fields are filled
        $(form).find('input[required], select[required]').each(function() {
            if (!$(this).val()) {
                e.preventDefault();
                $(this).focus();
                showAlert('Please fill in all required fields.', 'warning');
                return false;
            }
        });
    });

    // Print functionality
    window.printPage = function() {
        window.print();
    };

    // Export to Excel functionality
    window.exportToExcel = function(tableId, filename) {
        const table = document.getElementById(tableId);
        const workbook = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
        XLSX.writeFile(workbook, filename + '.xlsx');
    };

    // Confirm delete
    $('.delete-btn').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    // Auto-save for settings
    $('.auto-save').on('change', function() {
        const field = $(this).attr('name');
        const value = $(this).val();
        
        $.ajax({
            url: '../ajax/save_setting.php',
            method: 'POST',
            data: { field: field, value: value },
            success: function(response) {
                if (response.success) {
                    showAlert('Setting saved successfully!', 'success');
                }
            }
        });
    });
});

// Load notifications
function loadNotifications() {
    if (typeof userId === 'undefined') return;
    
    $.ajax({
        url: '../ajax/real_time_updates.php?action=get_notifications',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateNotificationBadge(response.unread_count);
                updateNotificationList(response.notifications);
            }
        },
        error: function() {
            // Fallback to original notifications endpoint
            $.ajax({
                url: '../ajax/notifications.php',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateNotificationBadge(response.unread_count);
                        updateNotificationList(response.notifications);
                    }
                }
            });
        }
    });
}

// Update notification badge
function updateNotificationBadge(count) {
    const badge = $('.notification-badge');
    if (count > 0) {
        badge.text(count).show();
    } else {
        badge.hide();
    }
}

// Update notification list
function updateNotificationList(notifications) {
    const container = $('#notifications-list');
    if (container.length === 0) return;
    
    container.empty();
    
    if (notifications.length === 0) {
        container.html('<div class="text-center text-muted py-3">No notifications</div>');
        return;
    }
    
    notifications.forEach(function(notification) {
        const item = $(`
            <div class="notification-item ${notification.status === 'unread' ? 'unread' : ''}" data-id="${notification.id}">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="mb-1">${notification.message}</p>
                        <small class="text-muted">${formatDateTime(notification.created_at)}</small>
                    </div>
                    ${notification.status === 'unread' ? '<div class="unread-indicator"></div>' : ''}
                </div>
            </div>
        `);
        
        container.append(item);
    });
}

// Header notification functions
function loadHeaderNotifications() {
    if (typeof userId === 'undefined') return;
    
    $.ajax({
        url: '../ajax/notifications.php',
        method: 'GET',
        data: { limit: 5 },
        success: function(response) {
            if (response.success) {
                updateHeaderNotificationBadge(response.unread_count);
                updateHeaderNotificationList(response.notifications);
            }
        },
        error: function() {
            // Silently handle error - don't spam console
        }
    });
}

function updateHeaderNotificationBadge(count) {
    const badge = document.getElementById('headerNotificationBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-block';
            // Add pulsing animation for new notifications
            badge.classList.add('pulse-animation');
        } else {
            badge.style.display = 'none';
            badge.classList.remove('pulse-animation');
        }
    }
}

function updateHeaderNotificationList(notifications) {
    const container = document.getElementById('headerNotificationList');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (notifications.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-bell-slash mb-2 d-block"></i>No notifications</div>';
        return;
    }
    
    notifications.forEach(function(notification) {
        const item = document.createElement('div');
        item.className = `notification-item ${notification.status === 'unread' ? 'unread' : ''}`;
        item.onclick = function() {
            if (notification.status === 'unread') {
                markNotificationRead(notification.id);
            }
        };
        
        item.innerHTML = `
            <div class="d-flex">
                <div class="flex-grow-1">
                    <div class="notification-content">${notification.message}</div>
                    <div class="notification-time">${formatDateTime(notification.created_at)}</div>
                </div>
                ${notification.status === 'unread' ? '<div class="unread-indicator"></div>' : ''}
            </div>
        `;
        
        container.appendChild(item);
    });
}

function markAllNotificationsRead() {
    $.ajax({
        url: '../ajax/mark_notification_read.php',
        method: 'POST',
        data: { mark_all: 1 },
        success: function(response) {
            if (response.success) {
                loadHeaderNotifications();
                showNotificationToast('All notifications marked as read', 'success');
            }
        }
    });
}

// Real-time dashboard statistics update
function updateDashboardStats() {
    if (typeof userId === 'undefined') return;
    
    $.ajax({
        url: '../ajax/real_time_updates.php?action=get_dashboard_stats',
        method: 'GET',
        success: function(response) {
            if (response.success && response.stats) {
                updateStatCards(response.stats);
            }
        },
        error: function() {
            console.log('Dashboard stats update failed');
        }
    });
}

// Update stat cards with new data
function updateStatCards(stats) {
    Object.keys(stats).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            const currentValue = parseInt(element.textContent.replace(/,/g, ''));
            const newValue = stats[key];
            
            if (currentValue !== newValue) {
                // Animate the change
                element.style.transform = 'scale(1.1)';
                element.style.color = '#28a745';
                
                setTimeout(() => {
                    element.textContent = newValue.toLocaleString();
                    element.style.transform = 'scale(1)';
                    element.style.color = '';
                }, 200);
            }
        }
    });
}

// Approve request
function approveRequest(requestId) {
    if (!confirm('Are you sure you want to approve this request? This will update the inventory automatically.')) return;
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    $.ajax({
        url: '../ajax/approve_request.php',
        method: 'POST',
        data: { request_id: requestId },
        success: function(response) {
            if (response.success) {
                showAlert('Request approved successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(response.message || 'Error approving request', 'danger');
            }
        },
        error: function() {
            showAlert('Error processing request', 'danger');
        },
        complete: function() {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}

// Reject request
function rejectRequest(requestId) {
    const remark = prompt('Please provide a reason for rejection:');
    if (!remark) return;
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    $.ajax({
        url: '../ajax/reject_request.php',
        method: 'POST',
        data: { request_id: requestId, admin_remark: remark },
        success: function(response) {
            if (response.success) {
                showAlert('Request rejected successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(response.message || 'Error rejecting request', 'danger');
            }
        },
        error: function() {
            showAlert('Error processing request', 'danger');
        },
        complete: function() {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}

// Mark notification as read
function markNotificationRead(notificationId) {
    $.ajax({
        url: '../ajax/mark_notification_read.php',
        method: 'POST',
        data: { notification_id: notificationId },
        success: function(response) {
            if (response.success) {
                loadNotifications();
                loadHeaderNotifications();
            }
        }
    });
}

// Show alert
function showAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-danger';
    
    const alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('#alert-container').prepend(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        alert.alert('close');
    }, 5000);
}

// Enhanced notification system
function showNotificationToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Add to toast container
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    container.appendChild(toast);
    
    // Show toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove from DOM after hiding
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Get item options for dynamic forms
function getItemOptions() {
    let options = '';
    if (typeof itemOptions !== 'undefined') {
        itemOptions.forEach(function(item) {
            options += `<option value="${item.id}">${item.item_name} (Available: ${item.quantity})</option>`;
        });
    }
    return options;
}

// Format date time
function formatDateTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleDateString() + ' - ' + date.toLocaleTimeString();
}

// Mobile sidebar toggle
function toggleSidebar() {
    $('.sidebar').toggleClass('show');
}

// Search functionality
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        let show = false;
        const cells = rows[i].getElementsByTagName('td');
        
        for (let j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().includes(filter)) {
                show = true;
                break;
            }
        }
        
        rows[i].style.display = show ? '' : 'none';
    }
}

// Real-time clock
function updateClock() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
        hour12: true, 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    });
    const dateString = now.toLocaleDateString('en-US', { 
        weekday: 'short',
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
    
    // Update sidebar clock
    const sidebarTime = document.getElementById('current-time');
    const sidebarDate = document.getElementById('current-date');
    if (sidebarTime) sidebarTime.textContent = timeString;
    if (sidebarDate) sidebarDate.textContent = dateString;
    
    // Update header clock
    const headerTime = document.getElementById('header-time');
    const headerDate = document.getElementById('header-date');
    if (headerTime) headerTime.textContent = timeString;
    if (headerDate) headerDate.textContent = dateString;
}

// Update clock every second
setInterval(updateClock, 1000);
updateClock();