<?php
require_once '../config/db.php';  // This MUST be first - gives us $pdo
require_once '../includes/auth.php';
requireRole('admin');

// Handle Excel export (MUST come before any HTML output)
if(isset($_GET['export']) && $_GET['export'] == 'excel') {
    require_once '../includes/excel_export.php';
    $members = $pdo->query("SELECT * FROM members ORDER BY id DESC")->fetchAll();
    exportMembersToExcel($members);
    exit(); // Stop execution after export
}

// Handle member registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $member_number = 'M' . date('Ymd') . rand(100,999);
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $registration_date = date('Y-m-d');
    
    $stmt = $pdo->prepare("INSERT INTO members (member_number, full_name, email, phone, address, registration_date, status) VALUES (?,?,?,?,?,?, 'active')");
    $stmt->execute([$member_number, $full_name, $email, $phone, $address, $registration_date]);
    
    $member_id = $pdo->lastInsertId();
    $ledger_stmt = $pdo->prepare("INSERT INTO ledger (member_id, transaction_date, sign) VALUES (?,?,?)");
    $ledger_stmt->execute([$member_id, date('Y-m-d'), 'Member registered']);
    
    logActivity($_SESSION['user_id'], "Registered new member: $full_name");
    $success = "Member registered successfully! Member Number: $member_number";
}

// Handle member update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_member'])) {
    $member_id = $_POST['member_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    $stmt = $pdo->prepare("UPDATE members SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->execute([$full_name, $email, $phone, $address, $member_id]);
    
    logActivity($_SESSION['user_id'], "Updated member ID: $member_id");
    $update_success = "Member details updated successfully!";
    
    // Clear edit mode
    $edit_id = null;
}

// Deactivate member
if(isset($_GET['deactivate'])) {
    $id = $_GET['deactivate'];
    $stmt = $pdo->prepare("UPDATE members SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$id]);
    logActivity($_SESSION['user_id'], "Deactivated member ID: $id");
    $success = "Member has been deactivated.";
    header("Location: members.php?msg=" . urlencode($success));
    exit();
}

// Activate member
if(isset($_GET['activate'])) {
    $id = $_GET['activate'];
    $stmt = $pdo->prepare("UPDATE members SET status = 'active' WHERE id = ?");
    $stmt->execute([$id]);
    logActivity($_SESSION['user_id'], "Activated member ID: $id");
    $success = "Member has been reactivated.";
    header("Location: members.php?msg=" . urlencode($success));
    exit();
}

// Get member for editing
$edit_member = null;
if(isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_member = $stmt->fetch();
}

// Fetch all members
$members = $pdo->query("SELECT * FROM members ORDER BY id DESC")->fetchAll();
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Member Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modal-backdrop {
            z-index: 1040;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-users"></i> Member Management</h2>

<?php if($msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if(isset($update_success)): ?>
    <div class="alert alert-success"><?= $update_success ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <!-- Registration Form -->
        <div class="card">
            <div class="card-header bg-primary text-white">Register New Member</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-2">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Phone *</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Address</label>
                        <textarea name="address" class="form-control"></textarea>
                    </div>
                    <button type="submit" name="register" class="btn btn-primary w-100">Register Member</button>
                </form>
                <div class="mt-2 small text-muted">
                    <i class="fas fa-info-circle"></i> Guarantor will be added during loan application
                </div>
            </div>
        </div>
        
        <!-- Edit Form (shows when editing) -->
        <?php if($edit_member): ?>
        <div class="card mt-3">
            <div class="card-header bg-warning text-dark">Edit Member</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="member_id" value="<?= $edit_member['id'] ?>">
                    <div class="mb-2">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($edit_member['full_name']) ?>" required>
                    </div>
                    <div class="mb-2">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_member['email']) ?>">
                    </div>
                    <div class="mb-2">
                        <label>Phone *</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($edit_member['phone']) ?>" required>
                    </div>
                    <div class="mb-2">
                        <label>Address</label>
                        <textarea name="address" class="form-control"><?= htmlspecialchars($edit_member['address']) ?></textarea>
                    </div>
                    <button type="submit" name="update_member" class="btn btn-warning w-100">Update Member</button>
                    <a href="members.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list"></i> All Members</span>
                <a href="?export=excel" class="btn btn-sm btn-light">
                    <i class="fas fa-file-excel text-success"></i> Export to Excel
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="memberTable" class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Member No</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($members as $m): ?>
                            <tr>
                                <td><?= $m['member_number'] ?></a></td>
                                <td><?= htmlspecialchars($m['full_name']) ?></a></td>
                                <td><?= $m['phone'] ?></a></a></td>
                                <td><?= $m['email'] ?></a></a></a></td>
                                <td>
                                    <?php if($m['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                 </a>
                                <td>
                                    <a href="member_details.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?edit=<?= $m['id'] ?>" class="btn btn-sm btn-primary" title="Edit Member">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if($m['status'] == 'active'): ?>
                                        <a href="?deactivate=<?= $m['id'] ?>" class="btn btn-sm btn-warning" title="Deactivate" onclick="return confirm('Deactivate this member?')">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?activate=<?= $m['id'] ?>" class="btn btn-sm btn-success" title="Activate" onclick="return confirm('Reactivate this member?')">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                 </a>
                              </a>
                            <?php endforeach; ?>
                        </tbody>
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#memberTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']]
    });
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>