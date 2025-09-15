# Commercial Bank of Ethiopia - Hawassa Branch
## Inventory and Request Management System

### Overview
A comprehensive web-based inventory management system designed specifically for the Commercial Bank of Ethiopia - Hawassa Branch. The system enables efficient management of office supplies, stationery, and equipment through a role-based interface supporting both administrators and regular users.

### Features

#### Admin Capabilities
- **User Management**: Add, edit, and delete user accounts
- **Inventory Management**: Complete CRUD operations for inventory items
- **Request Processing**: Approve or reject user requests with real-time inventory updates
- **Reporting**: Generate monthly and quarterly reports with export functionality
- **Settings Management**: Configure system settings and admin notifications
- **Dashboard Analytics**: Visual statistics and charts for system overview

#### User Capabilities
- **Multi-Request Submission**: Submit multiple inventory requests simultaneously
- **Real-time Notifications**: Receive instant updates on request status
- **Request Tracking**: Monitor status of all submitted requests
- **Dashboard Overview**: Personal statistics and recent activity

#### System Features
- **Role-based Authentication**: Secure login system with password hashing
- **Real-time Updates**: AJAX-powered notifications and inventory updates
- **Responsive Design**: Mobile-friendly interface with Bootstrap 5
- **Print Functionality**: Print requests and reports
- **Export Capabilities**: Export data to Excel format
- **Professional UI**: Ethiopian Commercial Bank branding

### Technology Stack
- **Backend**: PHP 7.4+ with PDO MySQL
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Database**: MySQL 5.7+
- **Libraries**: jQuery, Chart.js, Font Awesome, DataTables
- **Server**: Apache/XAMPP compatible

### Installation Instructions

#### Prerequisites
- XAMPP (Apache + PHP 7.4+ + MySQL)
- Web browser (Chrome, Firefox, Safari, Edge)

#### Step 1: Setup Files
1. Download and extract the project files
2. Copy the entire `hawassa_inventory` folder to your XAMPP `htdocs` directory
   ```
   C:\xampp\htdocs\hawassa_inventory\
   ```

#### Step 2: Database Setup
1. Start XAMPP and ensure Apache and MySQL are running
2. Open phpMyAdmin (`http://localhost/phpmyadmin`)
3. Create a new database named `hawassa_inventory`
4. Import the `hawassa_inventory.sql` file:
   - Click on the database name
   - Go to "Import" tab
   - Choose the SQL file and click "Go"

#### Step 3: Configuration
The database configuration is already set in `config/database.php`:
```php
$host = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP MySQL password
$database = 'hawassa_inventory';
```

#### Step 4: Access the System
Navigate to: `http://localhost/hawassa_inventory/`

### Default Login Credentials

#### Administrator Account
- **Username**: `admin`
- **Password**: `admin123`
- **Role**: Administrator

#### User Accounts
- **Username**: `user1` / **Password**: `user123`
- **Username**: `user2` / **Password**: `user123`
- **Username**: `user3` / **Password**: `user123`
- **Role**: Regular User

### Database Structure

#### Tables Overview
- **users**: User accounts and authentication
- **items**: Inventory items and stock levels
- **requests**: User requests and their status
- **notifications**: System notifications
- **settings**: Admin configuration settings

#### Key Relationships
- Users → Requests (One-to-Many)
- Items → Requests (One-to-Many)
- Users → Notifications (One-to-Many)

### File Structure
```
hawassa_inventory/
├── admin/                 # Admin panel pages
│   ├── dashboard.php
│   ├── users.php
│   ├── inventory.php
│   ├── requests.php
│   ├── reports.php
│   └── settings.php
├── user/                  # User panel pages
│   ├── dashboard.php
│   ├── request.php
│   └── notifications.php
├── config/                # Configuration files
│   ├── config.php
│   └── database.php
├── includes/              # Shared components
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── assets/                # Static assets
│   ├── css/style.css
│   └── js/main.js
├── ajax/                  # AJAX endpoints
│   ├── notifications.php
│   ├── approve_request.php
│   └── reject_request.php
├── auth/
│   └── logout.php
├── index.php             # Login page
├── hawassa_inventory.sql # Database structure
└── README.md            # Documentation
```

### Usage Instructions

#### For Administrators
1. **Login** with admin credentials
2. **Manage Users**: Add new users, edit existing accounts
3. **Manage Inventory**: Add items, update stock levels
4. **Process Requests**: Approve/reject user requests
5. **Generate Reports**: Create monthly/quarterly reports
6. **Configure Settings**: Update system preferences

#### For Users
1. **Login** with user credentials
2. **Submit Requests**: Create new inventory requests
3. **Track Status**: Monitor request progress
4. **View Notifications**: Check status updates
5. **Quick Requests**: Use dashboard quick request form

### Key Features Explained

#### Real-time Inventory Updates
- When requests are approved, inventory quantities automatically decrease
- Low stock alerts trigger when items fall below threshold
- Admin dashboard shows real-time stock levels

#### Multi-Request System
- Users can submit multiple items in a single request
- Dynamic form allows adding/removing request items
- Batch processing for efficient workflow

#### Notification System
- Real-time AJAX notifications
- Email-style notification interface
- Mark as read/unread functionality
- Auto-refresh every 30 seconds

#### Reporting System
- Monthly and quarterly reports
- Excel export functionality
- Print-friendly formats
- Statistical charts and graphs

### Security Features
- Password hashing (PHP password_hash())
- SQL injection prevention (PDO prepared statements)
- XSS protection (htmlspecialchars())
- CSRF protection on forms
- Role-based access control
- Session management

### Customization Options

#### Branding
- Colors defined in CSS variables
- Logo replaceable in assets
- Bank-specific styling throughout

#### Configuration
- Database settings in `config/database.php`
- System constants in `config/config.php`
- Email settings in admin panel

#### Extensions
- Additional item categories
- More user roles
- Extended reporting features
- API endpoints for mobile apps

### Troubleshooting

#### Common Issues
1. **Database Connection Error**
   - Verify MySQL is running in XAMPP
   - Check database credentials in `config/database.php`

2. **Permission Errors**
   - Ensure proper file permissions on server
   - Check XAMPP directory access rights

3. **Login Issues**
   - Verify database import was successful
   - Use default credentials listed above

4. **AJAX Not Working**
   - Check browser console for JavaScript errors
   - Verify jQuery is loading properly

#### Support
For technical support or customization requests, contact the development team or refer to the inline code documentation.

### License
This system is developed specifically for the Commercial Bank of Ethiopia - Hawassa Branch. All rights reserved.

### Version Information
- **Version**: 1.0.0
- **Release Date**: January 2024
- **PHP Version**: 7.4+
- **MySQL Version**: 5.7+
- **Bootstrap Version**: 5.1.3"# bank-inventery-at-hawassa" 
