<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$member_id = $_GET['id'] ?? 0;

// Get member details
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if(!$member) {
    header("Location: members.php");
    exit();
}

// Get savings summary
$savings = $pdo->prepare("SELECT SUM(amount) as total, COUNT(*) as count FROM savings WHERE member_id = ? AND transaction_type = 'deposit'");
$savings->execute([$member_id]);
$savings_data = $savings->fetch();

// Get loan summary
$loans = $pdo->prepare("SELECT * FROM loans WHERE member_id = ? ORDER BY id DESC");
$loans->execute([$member_id]);
$all_loans = $loans->fetchAll();

// Get current active loan
$active_loan = $pdo->prepare("SELECT * FROM loans WHERE member_id = ? AND status = 'disbursed' AND balance > 0");
$active_loan->execute([$member_id]);
$current_loan = $active_loan->fetch();
?>
<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-user-circle"></i> Member Details</h2>
        <div>
            <a href="members.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Members
            </a>
            <a href="?edit=<?= $member['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Member
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Personal Information -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr><th>Member Number</th><td><?= $member['member_number'] ?></a></td></tr>
                        <tr><th>Full Name</th><td><?= htmlspecialchars($member['full_name']) ?></a></td></tr>
                        <tr><th>Email</th><td><?= $member['email'] ?: 'Not provided' ?></a></td></tr>
                        <tr><th>Phone</th><td><?= $member['phone'] ?></a></td></tr>
                        <tr><th>Address</th><td><?= $member['address'] ?: 'Not provided' ?></a></td></tr>
                        <tr><th>Registration Date</th><td><?= date('d/m/Y', strtotime($member['registration_date'])) ?></a></td></tr>
                        <tr><th>Status</th>
                            <td>
                                <?php if($member['status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                             </a>
                         </a>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Financial Summary -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Financial Summary</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr><th>Total Savings</th>
                            <td class="text-end">UGX <?= number_format($savings_data['total'] ?? 0, 2) ?></a>
                         </a>
                        <tr><th>Number of Deposits</th>
                            <td class="text-end"><?= $savings_data['count'] ?? 0 ?></a>
                         </a>
                        <?php if($current_loan): ?>
                        <tr><th>Current Loan Balance</th>
                            <td class="text-end text-danger">UGX <?= number_format($current_loan['balance'], 2) ?></a>
                         </a>
                        <td><th>Monthly Payment</th>
                            <td class="text-end">UGX <?= number_format($current_loan['balance'] / 3, 2) ?></a>
                         </a>
                        <?php else: ?>
                        <tr><td colspan="2" class="text-center text-success">No Active Loan</a></a>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loan History -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Loan History</h5>
                </div>
                <div class="card-body">
                    <?php if(count($all_loans) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Loan Amount</th>
                                    <th>Total Payable</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_loans as $loan): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($loan['issue_date'])) ?></a>
                                    <td class="text-end">UGX <?= number_format($loan['loan_amount'], 2) ?></a>
                                    <td class="text-end">UGX <?= number_format($loan['total_payable'], 2) ?></a>
                                    <td class="text-end">UGX <?= number_format($loan['amount_paid'], 2) ?></a>
                                    <td class="text-end">UGX <?= number_format($loan['balance'], 2) ?></a>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $loan['status'] == 'completed' ? 'success' : 
                                            ($loan['status'] == 'disbursed' ? 'primary' : 
                                            ($loan['status'] == 'approved' ? 'info' : 
                                            ($loan['status'] == 'rejected' ? 'danger' : 'warning'))) ?>">
                                            <?= $loan['status'] ?>
                                        </span>
                                     </a>
                                 </a>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center">No loan history found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
