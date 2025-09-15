-- Commercial Bank of Ethiopia - Hawassa Branch
-- Inventory and Request Management System Database
-- Updated with Router, Switch, Cable, Blanket sample data

-- Create database
CREATE DATABASE IF NOT EXISTS hawassa_inventory;
USE hawassa_inventory;

-- Drop existing tables if they exist (for clean setup)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_username (username),
    INDEX idx_role (role)
);

-- Items table
CREATE TABLE items (
    id INT(11) NOT NULL AUTO_INCREMENT,
    item_name VARCHAR(100) NOT NULL,
    item_code VARCHAR(50) UNIQUE,
    quantity INT(11) NOT NULL DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'pcs',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_item_code (item_code),
    INDEX idx_quantity (quantity),
    INDEX idx_item_name (item_name)
);

-- Requests table
CREATE TABLE requests (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    item_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL,
    branch VARCHAR(100) NOT NULL,
    remark TEXT,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_remark TEXT,
    request_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_item_id (item_id),
    INDEX idx_request_date (request_date),
    INDEX idx_created_at (created_at)
);

-- Notifications table
CREATE TABLE notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
    type VARCHAR(20) DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_created_at (created_at)
);

-- Settings table
CREATE TABLE settings (
    id INT(11) NOT NULL AUTO_INCREMENT,
    admin_id INT(11) NOT NULL,
    setting_name VARCHAR(50) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_admin_setting (admin_id, setting_name)
);

-- Insert users with WORKING password hashes
-- Admin password: admin123
-- User password: user123
INSERT INTO users (username, password, role, email, full_name) VALUES
('admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHOgCS/3vFYbVzrKGnFVVqQMfHFe7UvNKG', 'admin', 'admin@cbe.et', 'System Administrator'),
('user1', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHOgCS/3vFYbVzrKGnFVVqQMfHFe7UvNKG', 'user', 'user1@cbe.et', 'John Doe'),
('user2', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHOgCS/3vFYbVzrKGnFVVqQMfHFe7UvNKG', 'user', 'user2@cbe.et', 'Jane Smith'),
('user3', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHOgCS/3vFYbVzrKGnFVVqQMfHFe7UvNKG', 'user', 'user3@cbe.et', 'Michael Johnson');

-- Insert your specified sample inventory items
INSERT INTO items (item_name, item_code, quantity, unit, description) VALUES
('Router', 'NET-001', 25, 'pcs', 'Network router for internet connectivity and branch networking'),
('Switch', 'NET-002', 40, 'pcs', 'Network switch for connecting multiple devices in branch network'),
('Cable', 'NET-003', 200, 'meter', 'Network cable for connecting computers and network devices'),
('Blanket', 'GEN-001', 15, 'pcs', 'Office blanket for staff comfort during cold weather');

-- Insert sample requests using the new items
INSERT INTO requests (user_id, item_id, quantity, branch, remark, status, request_date, admin_remark, created_at) VALUES
(2, 1, 2, 'Hawassa Main Branch', 'Need routers for new branch setup', 'approved', '2024-01-15', 'Approved - Network infrastructure upgrade', '2024-01-15 09:30:00'),
(2, 2, 5, 'Hawassa Main Branch', 'Network expansion for additional workstations', 'approved', '2024-01-15', 'Approved - IT department request', '2024-01-15 10:15:00'),
(3, 3, 50, 'Hawassa Piassa Branch', 'Cable installation for new office layout', 'pending', '2024-01-16', '', '2024-01-16 08:45:00'),
(3, 4, 3, 'Hawassa Piassa Branch', 'Staff comfort during winter season', 'approved', '2024-01-14', 'Approved - Staff welfare', '2024-01-14 14:20:00'),
(4, 1, 1, 'Hawassa University Branch', 'Backup router for main connection', 'rejected', '2024-01-13', 'Budget constraints - defer to next quarter', '2024-01-13 11:00:00'),
(2, 2, 3, 'Hawassa Main Branch', 'Additional switches for server room', 'approved', '2024-01-12', 'Approved - Critical infrastructure', '2024-01-12 16:30:00'),
(4, 3, 30, 'Hawassa University Branch', 'Network cable for student service area', 'pending', '2024-01-17', '', '2024-01-17 09:00:00'),
(3, 4, 2, 'Hawassa Piassa Branch', 'Additional blankets for staff break room', 'approved', '2024-01-11', 'Approved - Staff comfort', '2024-01-11 13:45:00');

-- Insert sample notifications related to new items
INSERT INTO notifications (user_id, message, type, created_at) VALUES
(2, 'Your request for Router (2 units) has been approved. Admin note: Approved - Network infrastructure upgrade', 'success', '2024-01-15 09:35:00'),
(2, 'Your request for Switch (5 units) has been approved. Admin note: Approved - IT department request', 'success', '2024-01-15 10:20:00'),
(3, 'Your request for Blanket (3 units) has been approved. Admin note: Approved - Staff welfare', 'success', '2024-01-14 14:25:00'),
(4, 'Your request for Router (1 unit) has been rejected. Reason: Budget constraints - defer to next quarter', 'error', '2024-01-13 11:05:00'),
(2, 'Your request for Switch (3 units) has been approved. Admin note: Approved - Critical infrastructure', 'success', '2024-01-12 16:35:00'),
(3, 'Your request for Blanket (2 units) has been approved. Admin note: Approved - Staff comfort', 'success', '2024-01-11 13:50:00'),
(1, 'System initialized successfully. Welcome to CBE Hawassa Branch Inventory Management System!', 'info', '2024-01-01 00:00:00'),
(1, 'Low Stock Alert: Blanket is running low (15 units remaining)', 'warning', '2024-01-18 09:00:00'),
(1, 'New Request Submitted: John Doe requested 2 units of Router for Hawassa Main Branch', 'info', '2024-01-15 09:30:00'),
(1, 'New Request Submitted: Jane Smith requested 50 meters of Cable for Hawassa Piassa Branch', 'info', '2024-01-16 08:45:00');

-- Insert comprehensive admin settings
INSERT INTO settings (admin_id, setting_name, setting_value) VALUES
(1, 'admin_email', 'admin@cbe.et'),
(1, 'low_stock_threshold', '10'),
(1, 'auto_notifications', 'enabled'),
(1, 'email_notifications', 'enabled'),
(1, 'backup_frequency', 'weekly'),
(1, 'system_timezone', 'Africa/Addis_Ababa'),
(1, 'notification_frequency', '5'),
(1, 'dashboard_refresh', '15'),
(1, 'max_request_quantity', '1000'),
(1, 'system_maintenance_mode', 'disabled');

-- Create triggers for automatic notifications and real-time updates
DELIMITER //

CREATE TRIGGER after_inventory_update
AFTER UPDATE ON items
FOR EACH ROW
BEGIN
    -- Notify admin of inventory changes
    IF OLD.quantity != NEW.quantity THEN
        INSERT INTO notifications (user_id, message, type)
        SELECT 1, 
               CONCAT('Inventory Update: ', NEW.item_name, ' quantity changed from ', OLD.quantity, ' to ', NEW.quantity, ' units'),
               'info';
    END IF;
    
    -- Check for low stock and notify admin
    IF NEW.quantity <= 10 AND OLD.quantity > 10 THEN
        INSERT INTO notifications (user_id, message, type)
        SELECT 1,
               CONCAT('Low Stock Alert: ', NEW.item_name, ' is running low (', NEW.quantity, ' units remaining)'),
               'warning';
    END IF;
    
    -- Check for out of stock
    IF NEW.quantity = 0 AND OLD.quantity > 0 THEN
        INSERT INTO notifications (user_id, message, type)
        SELECT 1,
               CONCAT('Out of Stock Alert: ', NEW.item_name, ' is now out of stock!'),
               'error';
    END IF;
END//

CREATE TRIGGER after_request_insert
AFTER INSERT ON requests
FOR EACH ROW
BEGIN
    -- Notify admin of new request
    INSERT INTO notifications (user_id, message, type)
    SELECT 1,
           CONCAT('New Request: ', (SELECT full_name FROM users WHERE id = NEW.user_id), 
                  ' requested ', NEW.quantity, ' units of ', 
                  (SELECT item_name FROM items WHERE id = NEW.item_id),
                  ' for ', NEW.branch),
           'info';
    
    -- Notify user that request was submitted
    INSERT INTO notifications (user_id, message, type) VALUES 
    (NEW.user_id, 
     CONCAT('Your request for ', (SELECT item_name FROM items WHERE id = NEW.item_id), 
            ' (', NEW.quantity, ' units) has been submitted and is pending approval.'), 
     'info');
END//

CREATE TRIGGER after_request_status_change
AFTER UPDATE ON requests
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO notifications (user_id, message, type)
        VALUES (NEW.user_id,
                CONCAT('Request Status Update: Your request for ', 
                       (SELECT item_name FROM items WHERE id = NEW.item_id),
                       ' (', NEW.quantity, ' units) is now ', UPPER(NEW.status),
                       CASE WHEN NEW.admin_remark IS NOT NULL AND NEW.admin_remark != '' 
                            THEN CONCAT('. Admin Note: ', NEW.admin_remark) 
                            ELSE '' END),
                CASE NEW.status 
                    WHEN 'approved' THEN 'success'
                    WHEN 'rejected' THEN 'error'
                    ELSE 'info'
                END);
    END IF;
END//

DELIMITER ;

-- Create views for reporting and analytics
CREATE VIEW monthly_request_summary AS
SELECT 
    DATE_FORMAT(r.created_at, '%Y-%m') as month,
    DATE_FORMAT(r.created_at, '%M %Y') as month_name,
    COUNT(*) as total_requests,
    SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
    COUNT(DISTINCT r.user_id) as unique_users,
    COUNT(DISTINCT r.item_id) as unique_items
FROM requests r
GROUP BY DATE_FORMAT(r.created_at, '%Y-%m')
ORDER BY month DESC;

CREATE VIEW inventory_status_view AS
SELECT 
    i.*,
    CASE 
        WHEN i.quantity = 0 THEN 'Out of Stock'
        WHEN i.quantity < 10 THEN 'Low Stock'
        WHEN i.quantity < 50 THEN 'Medium Stock'
        ELSE 'Well Stocked'
    END as stock_status,
    COALESCE(req_stats.total_requests, 0) as total_requests,
    COALESCE(req_stats.total_approved, 0) as total_approved,
    COALESCE(req_stats.total_quantity_issued, 0) as total_quantity_issued
FROM items i
LEFT JOIN (
    SELECT 
        item_id,
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as total_approved,
        SUM(CASE WHEN status = 'approved' THEN quantity ELSE 0 END) as total_quantity_issued
    FROM requests
    GROUP BY item_id
) req_stats ON i.id = req_stats.item_id
ORDER BY i.item_name;

-- Display setup completion message
SELECT 'Database setup completed successfully!' as message,
       'Admin Login: admin / admin123' as admin_credentials,
       'User Login: user1, user2, user3 / user123' as user_credentials,
       'System URL: http://localhost/hawassa_inventory/' as access_url;