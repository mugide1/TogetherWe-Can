<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

// Fetch all users directly from database
$stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC");
$users = $stmt->fetchAll();

// Handle Create User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $username = $_POST['username'];
        $full_name = $_POST['full_name'];
        $role = $_POST['role'];
        $email = $_POST['email'] ?? '';
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if($check->fetch()) {
            $error = "Username already exists!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, email, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$username, $password, $full_name, $role, $email]);
            logActivity($_SESSION['user_id'], "Added new user: $username ($role)");
            $success = "User added successfully! Password: " . $_POST['password'];
            header("Location: users.php?success=" . urlencode($success));
            exit();
        }
    }
    
    if ($action == 'update') {
        $user_id = $_POST['user_id'];
        $full_name = $_POST['full_name'];
        $role = $_POST['role'];
        $email = $_POST['email'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, email = ? WHERE id = ?");
        $stmt->execute([$full_name, $role, $email, $user_id]);
        logActivity($_SESSION['user_id'], "Updated user ID: $user_id");
        $success = "User updated successfully!";
        header("Location: users.php?success=" . urlencode($success));
        exit();
    }
    
    if ($action == 'reset_password') {
        $user_id = $_POST['user_id'];
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_password, $user_id]);
        logActivity($_SESSION['user_id'], "Reset password for user ID: $user_id");
        $success = "Password reset successfully! New password: " . $_POST['new_password'];
        header("Location: users.php?success=" . urlencode($success));
        exit();
    }
    
    if ($action == 'toggle_status') {
        $user_id = $_POST['user_id'];
        $is_active = $_POST['is_active'];
        
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$is_active, $user_id]);
        logActivity($_SESSION['user_id'], ($is_active == 1 ? "Activated" : "Deactivated") . " user ID: $user_id");
        $success = "User " . ($is_active == 1 ? "activated" : "deactivated") . " successfully!";
        header("Location: users.php?success=" . urlencode($success));
        exit();
    }
    
    if ($action == 'delete') {
        $user_id = $_POST['user_id'];
        
        if($user_id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            logActivity($_SESSION['user_id'], "Deleted user ID: $user_id");
            $success = "User deleted successfully!";
            header("Location: users.php?success=" . urlencode($success));
            exit();
        }
    }
}

// Handle simple GET actions
if(isset($_GET['deactivate'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $stmt->execute([$_GET['deactivate']]);
    header("Location: users.php?success=User deactivated");
    exit();
}
if(isset($_GET['activate'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
    $stmt->execute([$_GET['activate']]);
    header("Location: users.php?success=User activated");
    exit();
}
if(isset($_GET['delete'])) {
    if($_GET['delete'] != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header("Location: users.php?success=User deleted");
        exit();
    }
}

// Get user for editing
$edit_user = null;
if(isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
}

$msg = $_GET['success'] ?? '';
$error = $error ?? '';
?>
<?php include '../includes/header.php'; ?>

<style>
    /* Responsive Styles */
    .main-content {
        margin-left: 0;
        padding: 15px;
        transition: all 0.3s;
    }

    .page-header {
        margin-bottom: 15px;
    }

    .page-header h1 {
        color: #1e2a3a;
        font-weight: 700;
        margin-bottom: 5px;
        font-size: 1.4rem;
    }

    .page-header p {
        color: #718096;
        margin-bottom: 0;
        font-size: 0.8rem;
    }

    .form-card {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    .form-card h5 {
        color: #1e2a3a;
        font-weight: 600;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.95rem;
    }

    .form-group {
        margin-bottom: 10px;
    }

    .form-label {
        font-weight: 500;
        color: #2d3748;
        margin-bottom: 4px;
        font-size: 0.8rem;
    }

    .form-control, .form-select {
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 6px 10px;
        font-size: 12px;
        width: 100%;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        outline: none;
    }

    .users-table {
        background: white;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    .users-table h5 {
        color: #1e2a3a;
        font-weight: 600;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.95rem;
    }

    /* Table Responsive */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table {
        margin-bottom: 0;
        font-size: 12px;
        min-width: 600px;
        width: 100%;
    }

    .table thead th {
        background: #2c3e50;
        color: white;
        font-weight: 600;
        border: none;
        padding: 10px 8px;
        font-size: 12px;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 8px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        word-break: break-word;
    }

    .table tbody tr:hover {
        background: #f7fafc;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 500;
        white-space: nowrap;
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .action-buttons .btn-sm {
        padding: 4px 8px;
        font-size: 11px;
    }

    .alert-custom {
        padding: 10px 12px;
        border-radius: 5px;
        margin-bottom: 12px;
        font-size: 12px;
    }

    .alert-success-custom {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger-custom {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* Desktop Layout */
    @media (min-width: 992px) {
        .row {
            display: flex;
            flex-wrap: wrap;
        }
        .col-md-4 {
            width: 33.333%;
            padding-right: 15px;
        }
        .col-md-8 {
            width: 66.666%;
            padding-left: 15px;
        }
    }

    /* Tablet Layout */
    @media (max-width: 991px) and (min-width: 768px) {
        .main-content {
            padding: 12px;
        }
        .table {
            font-size: 11px;
        }
        .table thead th {
            padding: 8px 6px;
            font-size: 11px;
        }
        .table tbody td {
            padding: 6px;
        }
        .action-buttons .btn-sm {
            padding: 3px 6px;
            font-size: 10px;
        }
    }

    /* Mobile Layout */
    @media (max-width: 767px) {
        .main-content {
            padding: 10px;
        }
        
        .page-header h1 {
            font-size: 1.2rem;
        }
        
        .page-header p {
            font-size: 0.7rem;
        }
        
        .form-card, .users-table {
            padding: 10px;
        }
        
        .form-card h5, .users-table h5 {
            font-size: 0.85rem;
        }
        
        .table {
            font-size: 10px;
            min-width: 500px;
        }
        
        .table thead th {
            padding: 6px 4px;
            font-size: 10px;
        }
        
        .table tbody td {
            padding: 5px;
        }
        
        .badge {
            padding: 2px 5px;
            font-size: 9px;
        }
        
        .action-buttons {
            gap: 3px;
        }
        
        .action-buttons .btn-sm {
            padding: 2px 5px;
            font-size: 9px;
        }
        
        .btn {
            font-size: 11px;
            padding: 6px 10px;
        }
        
        .form-control, .form-select {
            font-size: 11px;
            padding: 5px 8px;
        }
        
        .form-label {
            font-size: 0.75rem;
        }
    }

    /* Small Mobile */
    @media (max-width: 480px) {
        .main-content {
            padding: 8px;
        }
        
        .table {
            min-width: 450px;
            font-size: 9px;
        }
        
        .table thead th {
            padding: 5px 3px;
            font-size: 9px;
        }
        
        .table tbody td {
            padding: 4px;
        }
        
        .badge {
            padding: 2px 4px;
            font-size: 8px;
        }
        
        .action-buttons .btn-sm {
            padding: 2px 4px;
            font-size: 8px;
        }
    }
</style>

<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-users-cog"></i> User Management</h1>
        <p>Create, edit, and manage system users with various roles and permissions</p>
    </div>

    <!-- Alert Messages -->
    <?php if($msg): ?>
        <div class="alert alert-success-custom alert-custom">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger-custom alert-custom">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Add User Form -->
        <div class="col-md-4">
            <div class="form-card">
                <h5><i class="fas fa-user-plus"></i> Add New User</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="6" value="admin123">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="cashier">Cashier</option>
                            <option value="auditor">Auditor</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create User</button>
                </form>
            </div>
            
            <!-- Edit User Form -->
            <?php if($edit_user): ?>
            <div class="form-card mt-3">
                <h5><i class="fas fa-edit"></i> Edit User</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($edit_user['username']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($edit_user['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_user['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="cashier" <?= $edit_user['role'] == 'cashier' ? 'selected' : '' ?>>Cashier</option>
                            <option value="auditor" <?= $edit_user['role'] == 'auditor' ? 'selected' : '' ?>>Auditor</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Update User</button>
                    <a href="users.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Users List -->
        <div class="col-md-8">
            <div class="users-table">
                <h5><i class="fas fa-list"></i> All Users</h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($users && count($users) > 0): ?>
                                <?php foreach($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?> </a>
                                    <td>
                                        <?= htmlspecialchars($user['username']) ?>
                                        <?php if($user['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-primary">You</span>
                                        <?php endif; ?>
                                     </a>
                                    <td><?= htmlspecialchars($user['full_name']) ?> </a>
                                    <td>
                                        <?php if($user['role'] == 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php elseif($user['role'] == 'cashier'): ?>
                                            <span class="badge bg-success">Cashier</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Auditor</span>
                                        <?php endif; ?>
                                     </a>
                                    <td>
                                        <?php if($user['is_active'] == 1): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                     </a>
                                    <td>
                                        <?php if($user['last_login']): ?>
                                            <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                     </a>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if($user['role'] != 'admin'): ?>
                                                <a href="?edit=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-warning reset-pwd-btn" data-id="<?= $user['id'] ?>" data-name="<?= htmlspecialchars($user['username']) ?>" title="Reset Password">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php if($user['is_active'] == 1): ?>
                                                    <a href="?deactivate=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Deactivate" onclick="return confirm('Deactivate this user?')">
                                                        <i class="fas fa-ban"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="?activate=<?= $user['id'] ?>" class="btn btn-sm btn-outline-success" title="Activate" onclick="return confirm('Activate this user?')">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this user?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">System</span>
                                            <?php endif; ?>
                                        </div>
                                     </a>
                                 </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No users found</a>
                                 </a>
                            <?php endif; ?>
                        </tbody>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <div class="alert alert-info">
                        Reset password for user: <strong id="reset_username"></strong>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="text" name="new_password" class="form-control" value="admin123" required>
                        <small class="text-muted">Default: admin123</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        pageLength: 10,
        order: [[0, 'asc']],
        responsive: true,
        autoWidth: false,
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                previous: "Previous",
                next: "Next"
            }
        }
    });
    
    // Reset password modal
    $('.reset-pwd-btn').click(function() {
        var userId = $(this).data('id');
        var userName = $(this).data('name');
        $('#reset_user_id').val(userId);
        $('#reset_username').text(userName);
        $('#resetPasswordModal').modal('show');
    });
});
</script>

<?php include '../includes/footer.php'; ?>