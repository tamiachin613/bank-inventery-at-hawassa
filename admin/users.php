<?php
require_once '../config/config.php';
check_admin();

$page_title = 'User Management';
$message = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = sanitize_input($_POST['username']);
                $password = $_POST['password'];
                $email = sanitize_input($_POST['email']);
                $full_name = sanitize_input($_POST['full_name']);
                $role = $_POST['role'];
                
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $stmt->execute([$username]);
                
                if ($stmt->fetchColumn() > 0) {
                    $message = '<div class="alert alert-danger">Username already exists!</div>';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$username, $hashed_password, $email, $full_name, $role])) {
                        $message = '<div class="alert alert-success">User added successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error adding user!</div>';
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $username = sanitize_input($_POST['username']);
                $email = sanitize_input($_POST['email']);
                $full_name = sanitize_input($_POST['full_name']);
                $role = $_POST['role'];
                
                // Check if username already exists for other users
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $id]);
                
                if ($stmt->fetchColumn() > 0) {
                    $message = '<div class="alert alert-danger">Username already exists!</div>';
                } else {
                    if (!empty($_POST['password'])) {
                        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, email = ?, full_name = ?, role = ? WHERE id = ?");
                        $success = $stmt->execute([$username, $hashed_password, $email, $full_name, $role, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ? WHERE id = ?");
                        $success = $stmt->execute([$username, $email, $full_name, $role, $id]);
                    }
                    
                    if ($success) {
                        $message = '<div class="alert alert-success">User updated successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error updating user!</div>';
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
                if ($stmt->execute([$id, $_SESSION['user_id']])) {
                    $message = '<div class="alert alert-success">User deleted successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error deleting user!</div>';
                }
                break;
        }
    }
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">User Management</h1>
                <p class="text-muted mb-0">Manage system users and their permissions</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus me-2"></i>Add New User
                </button>
            </div>
        </div>
    </div>
    
    <div class="content-body">
        <?php echo $message; ?>
        
        <div class="card table-custom">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo format_datetime($user['created_at']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" required>
                            <option value="">Select Role</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" id="edit_username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password <small class="text-muted">(leave blank to keep current password)</small></label>
                        <input type="password" class="form-control" name="password">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" id="edit_role" required>
                            <option value="">Select Role</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>