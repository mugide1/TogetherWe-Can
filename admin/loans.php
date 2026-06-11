<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('admin');

// Process loan approval/rejection/disbursement
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $loan_id = $_POST['loan_id'];
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        // Check if member already has an active loan
        $loan = $pdo->prepare("SELECT member_id, loan_amount FROM loans WHERE id = ?");
        $loan->execute([$loan_id]);
        $loan_data = $loan->fetch();
        $member_id = $loan_data['member_id'];
        
        $active_check = $pdo->prepare("SELECT id, balance FROM loans WHERE member_id = ? AND status = 'disbursed' AND balance > 0");
        $active_check->execute([$member_id]);
        $existing_loan = $active_check->fetch();
        
        if($existing_loan) {
            $message = "Cannot approve. Member already has an active loan with balance of UGX " . number_format($existing_loan['balance'], 2);
        } else {
            $stmt = $pdo->prepare("UPDATE loans SET status = 'approved', approved_by = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $loan_id]);
            logActivity($_SESSION['user_id'], "Approved loan ID: $loan_id, Amount: " . $loan_data['loan_amount']);
            $message = "Loan approved successfully!";
        }
        
    } elseif ($action == 'disburse') {
        // Get loan details first
        $loan = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
        $loan->execute([$loan_id]);
        $loan_data = $loan->fetch();
        
        // Double check member doesn't have active loan before disbursing
        $active_check = $pdo->prepare("SELECT id, balance FROM loans WHERE member_id = ? AND status = 'disbursed' AND balance > 0 AND id != ?");
        $active_check->execute([$loan_data['member_id'], $loan_id]);
        
        if($active_check->fetch()) {
            $message = "Cannot disburse. Member already has an active loan.";
        } else {
            // Update loan status to disbursed
            $stmt = $pdo->prepare("UPDATE loans SET status = 'disbursed' WHERE id = ?");
            $stmt->execute([$loan_id]);
            
            // Get member's current ledger
            $current_ledger = $pdo->prepare("SELECT * FROM ledger WHERE member_id = ? ORDER BY id DESC LIMIT 1");
            $current_ledger->execute([$loan_data['member_id']]);
            $current = $current_ledger->fetch();
            
            if ($current) {
                // Update existing ledger with loan information
                $new_loan_out = ($current['loan_out'] ?? 0) + $loan_data['loan_amount'];
                $new_loan_balance = ($current['loan_balance'] ?? 0) + $loan_data['loan_amount'];
                
                $update = $pdo->prepare("UPDATE ledger SET 
                    loan_out = ?,
                    loan_balance = ?
                    WHERE member_id = ?");
                $update->execute([$new_loan_out, $new_loan_balance, $loan_data['member_id']]);
            } else {
                // Create new ledger entry
                $insert = $pdo->prepare("INSERT INTO ledger (member_id, loan_out, loan_balance, transaction_date, sign) 
                    VALUES (?, ?, ?, ?, ?)");
                $insert->execute([
                    $loan_data['member_id'],
                    $loan_data['loan_amount'],
                    $loan_data['loan_amount'],
                    date('Y-m-d'),
                    'Loan disbursed: ' . number_format($loan_data['loan_amount'], 2)
                ]);
            }
            
            logActivity($_SESSION['user_id'], "Disbursed loan ID: $loan_id, Amount: " . $loan_data['loan_amount']);
            $message = "Loan disbursed successfully!";
        }
        
    } elseif ($action == 'reject') {
        $stmt = $pdo->prepare("UPDATE loans SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$loan_id]);
        logActivity($_SESSION['user_id'], "Rejected loan ID: $loan_id");
        $message = "Loan rejected.";
    }
}

// Get pending loans
$pending_loans = $pdo->query("
    SELECT l.*, m.full_name, m.member_number
    FROM loans l 
    JOIN members m ON l.member_id = m.id 
    WHERE l.status = 'pending'
    ORDER BY l.id DESC
")->fetchAll();

// Get approved but not disbursed
$approved_loans = $pdo->query("
    SELECT l.*, m.full_name, m.member_number 
    FROM loans l 
    JOIN members m ON l.member_id = m.id 
    WHERE l.status = 'approved'
    ORDER BY l.id DESC
")->fetchAll();

// Get all loans for history
$all_loans = $pdo->query("
    SELECT l.*, m.full_name, m.member_number 
    FROM loans l 
    JOIN members m ON l.member_id = m.id 
    ORDER BY l.id DESC LIMIT 50
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<h2><i class="fas fa-clipboard-list"></i> Loan Management</h2>

<?php if(isset($message)): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<!-- Pending Loans -->
<div class="card mt-3">
    <div class="card-header bg-warning">
        <h5 class="mb-0">Pending Loan Applications</h5>
    </div>
    <div class="card-body">
        <?php if(count($pending_loans) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Member</th>
                        <th>Loan Amount (UGX)</th>
                        <th>Total Payable (UGX)</th>
                        <th>Interest (UGX)</th>
                        <th>Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pending_loans as $loan): ?>
                    <?php
                    // Check if member has active loan
                    $active_check = $pdo->prepare("SELECT balance FROM loans WHERE member_id = ? AND status = 'disbursed' AND balance > 0");
                    $active_check->execute([$loan['member_id']]);
                    $has_active = $active_check->fetch();
                    ?>
                    <tr class="<?= $has_active ? 'table-danger' : '' ?>">
                        <td>
                            <?= htmlspecialchars($loan['full_name']) ?><br>
                            <small><?= $loan['member_number'] ?></small>
                            <?php if($has_active): ?>
                                <br><span class="badge bg-danger">Has Active Loan</span>
                            <?php endif; ?>
                         </a>
                        <td>UGX <?= number_format($loan['loan_amount'], 2) ?></a>
                        <td>UGX <?= number_format($loan['total_payable'], 2) ?></a>
                        <td>UGX <?= number_format($loan['total_payable'] - $loan['loan_amount'], 2) ?></a>
                        <td><?= date('d/m/Y', strtotime($loan['due_date'])) ?></a>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                                <?php if($has_active): ?>
                                    <button type="button" class="btn btn-sm btn-secondary" disabled title="Member has active loan">
                                        <i class="fas fa-ban"></i> Cannot Approve
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" onclick="return confirm('Approve this loan?')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" onclick="return confirm('Reject this loan?')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                         </a>
                     </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">No pending loan applications.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Approved Loans Ready for Disbursement -->
<div class="card mt-3">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Approved - Ready for Disbursement</h5>
    </div>
    <div class="card-body">
        <?php if(count($approved_loans) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Member</th>
                        <th>Loan Amount (UGX)</th>
                        <th>Total Payable (UGX)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($approved_loans as $loan): ?>
                    <?php
                    // Double check member doesn't have active loan
                    $active_check = $pdo->prepare("SELECT balance FROM loans WHERE member_id = ? AND status = 'disbursed' AND balance > 0");
                    $active_check->execute([$loan['member_id']]);
                    $has_active = $active_check->fetch();
                    ?>
                    <tr class="<?= $has_active ? 'table-danger' : '' ?>">
                        <td>
                            <?= htmlspecialchars($loan['full_name']) ?><br>
                            <small><?= $loan['member_number'] ?></small>
                            <?php if($has_active): ?>
                                <br><span class="badge bg-danger">Has Active Loan (UGX <?= number_format($has_active['balance'], 2) ?>)</span>
                            <?php endif; ?>
                         </a>
                        <td>UGX <?= number_format($loan['loan_amount'], 2) ?></a>
                        <td>UGX <?= number_format($loan['total_payable'], 2) ?></a>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                                <?php if($has_active): ?>
                                    <button type="button" class="btn btn-sm btn-secondary" disabled>
                                        <i class="fas fa-ban"></i> Cannot Disburse
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="disburse" class="btn btn-sm btn-primary" onclick="return confirm('Disburse funds to member? This will update the ledger.')">
                                        <i class="fas fa-money-bill-wave"></i> Disburse
                                    </button>
                                <?php endif; ?>
                            </form>
                         </a>
                     </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">No approved loans waiting for disbursement.</p>
        <?php endif; ?>
    </div>
</div>

<!-- All Loans History -->
<div class="card mt-3">
    <div class="card-header">
        <h5 class="mb-0">Loan History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered" id="loanTable">
                <thead class="table-dark">
                    <tr>
                        <th>Member</th>
                        <th>Loan Amount (UGX)</th>
                        <th>Paid (UGX)</th>
                        <th>Balance (UGX)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_loans as $loan): ?>
                    <tr>
                        <td><?= htmlspecialchars($loan['full_name']) ?><br><small><?= $loan['member_number'] ?></small></td>
                        <td class="text-end">UGX <?= number_format($loan['loan_amount'], 2) ?></td>
                        <td class="text-end">UGX <?= number_format($loan['amount_paid'], 2) ?></td>
                        <td class="text-end <?= $loan['balance'] > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                            UGX <?= number_format($loan['balance'], 2) ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= 
                                $loan['status'] == 'completed' ? 'success' : 
                                ($loan['status'] == 'disbursed' ? 'primary' : 
                                ($loan['status'] == 'approved' ? 'info' : 
                                ($loan['status'] == 'rejected' ? 'danger' : 'warning'))) ?>">
                                <?= $loan['status'] ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($loan['issue_date'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#loanTable').DataTable({
        order: [[5, 'desc']],
        pageLength: 25
    });
});
</script>

<?php include '../includes/footer.php'; ?>
