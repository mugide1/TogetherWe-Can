<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('cashier');

// Get all active members for dropdown
$members = $pdo->query("SELECT id, member_number, full_name FROM members WHERE status='active'")->fetchAll();

// Calculate maximum loan amount based on savings (3x savings)
$max_loan = 0;
$selected_member = null;
$current_savings = 0;
$has_active_loan = false;
$existing_loan_balance = 0;

if(isset($_POST['check_eligibility'])) {
    $member_id = $_POST['member_id'];
    
    // Check if member has an active loan (disbursed and balance > 0)
    $loan_check = $pdo->prepare("SELECT balance, loan_amount FROM loans WHERE member_id = ? AND status = 'disbursed' AND balance > 0");
    $loan_check->execute([$member_id]);
    $active_loan = $loan_check->fetch();
    
    if($active_loan) {
        $has_active_loan = true;
        $existing_loan_balance = $active_loan['balance'];
    }
    
    // Get savings
    $stmt = $pdo->prepare("SELECT SUM(amount) as total_savings FROM savings WHERE member_id = ? AND transaction_type='deposit'");
    $stmt->execute([$member_id]);
    $current_savings = $stmt->fetch()['total_savings'] ?? 0;
    $max_loan = $current_savings * 3;
    
    $member_stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $member_stmt->execute([$member_id]);
    $selected_member = $member_stmt->fetch();
}

// Process loan application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_loan'])) {
    $member_id = $_POST['member_id'];
    $loan_amount = $_POST['loan_amount'];
    $interest_rate = 12.00;
    $months = $_POST['repayment_months'];
    $guarantor_name = $_POST['guarantor_name'];
    
    // DOUBLE CHECK: Verify no active loan before saving
    $loan_check = $pdo->prepare("SELECT balance FROM loans WHERE member_id = ? AND status = 'disbursed' AND balance > 0");
    $loan_check->execute([$member_id]);
    if($loan_check->fetch()) {
        $error = "Cannot apply for a new loan. Member has an outstanding loan balance.";
    } else {
        // Calculate interest and total payable
        $interest = ($loan_amount * $interest_rate / 100);
        $total_payable = $loan_amount + $interest;
        $issue_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime("+$months months"));
        
        $stmt = $pdo->prepare("INSERT INTO loans (member_id, loan_amount, interest_rate, total_payable, balance, issue_date, due_date, status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$member_id, $loan_amount, $interest_rate, $total_payable, $total_payable, $issue_date, $due_date, 'pending']);
        
        $loan_id = $pdo->lastInsertId();
        
        // Update ledger with guarantor information
        $existing = $pdo->prepare("SELECT * FROM ledger WHERE member_id = ? ORDER BY id DESC LIMIT 1");
        $existing->execute([$member_id]);
        $current = $existing->fetch();
        
        if ($current) {
            $update = $pdo->prepare("UPDATE ledger SET guarantor_name = ?, loan_out = loan_out + ?, loan_balance = loan_balance + ?, sign = ? WHERE member_id = ?");
            $update->execute([$guarantor_name, $loan_amount, $total_payable, "Loan application: UGX " . number_format($loan_amount, 2), $member_id]);
        } else {
            $insert = $pdo->prepare("INSERT INTO ledger (member_id, loan_out, loan_balance, guarantor_name, transaction_date, sign) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([$member_id, $loan_amount, $total_payable, $guarantor_name, date('Y-m-d'), "Loan application: UGX " . number_format($loan_amount, 2)]);
        }
        
        logActivity($_SESSION['user_id'], "Loan application submitted for member ID: $member_id, Amount: $loan_amount, Guarantor: $guarantor_name");
        $success = "Loan application submitted for approval! Amount: UGX " . number_format($loan_amount, 2);
    }
}
?>
<?php include '../includes/header.php'; ?>
<h2><i class="fas fa-hand-holding-usd"></i> Loan Application</h2>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if(isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-info text-white">Step 1: Check Eligibility</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Select Member</label>
                        <select name="member_id" class="form-control" required onchange="this.form.submit()">
                            <option value="">-- Select Member --</option>
                            <?php foreach($members as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= (isset($_POST['member_id']) && $_POST['member_id'] == $m['id']) ? 'selected' : '' ?>>
                                <?= $m['member_number'] ?> - <?= htmlspecialchars($m['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="check_eligibility" value="1">
                </form>
                
                <?php if($selected_member): ?>
                <hr>
                <h6>Member Details:</h6>
                <p>
                    <strong>Name:</strong> <?= htmlspecialchars($selected_member['full_name']) ?><br>
                    <strong>Member No:</strong> <?= $selected_member['member_number'] ?><br>
                    <strong>Phone:</strong> <?= $selected_member['phone'] ?><br>
                    <strong>Total Savings:</strong> UGX <?= number_format($current_savings, 2) ?><br>
                    <strong>Max Loan Eligible:</strong> <span class="text-success fw-bold">UGX <?= number_format($max_loan, 2) ?></span><br>
                    
                    <?php if($has_active_loan): ?>
                        <div class="alert alert-danger mt-2">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Cannot apply for new loan!</strong><br>
                            Member has an existing loan balance of <strong>UGX <?= number_format($existing_loan_balance, 2) ?></strong>.<br>
                            Complete the current loan first.
                        </div>
                    <?php endif; ?>
                    
                    <small class="text-muted">*Loan amount cannot exceed 3x total savings</small>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-primary text-white">Step 2: Apply for Loan</div>
            <div class="card-body">
                <?php if($selected_member && $max_loan > 0 && !$has_active_loan): ?>
                <form method="POST">
                    <input type="hidden" name="member_id" value="<?= $_POST['member_id'] ?>">
                    
                    <div class="mb-3">
                        <label>Loan Amount (UGX)</label>
                        <input type="number" step="0.01" name="loan_amount" class="form-control" max="<?= $max_loan ?>" required>
                        <small>Maximum: UGX <?= number_format($max_loan, 2) ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label>Repayment Period (Months)</label>
                        <select name="repayment_months" class="form-control" required>
                            <option value="3">3 months</option>
                            <option value="6">6 months</option>
                            <option value="9">9 months</option>
                            <option value="12">12 months</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label>Guarantor Name *</label>
                        <input type="text" name="guarantor_name" class="form-control" required placeholder="Enter guarantor's full name">
                        <small class="text-muted">The guarantor will be responsible if the member defaults</small>
                    </div>
                    
                    <div class="mb-3">
                        <label>Guarantor Phone (Optional)</label>
                        <input type="text" name="guarantor_phone" class="form-control" placeholder="Enter guarantor's phone number">
                    </div>
                    
                    <div class="mb-3 alert alert-info">
                        <strong>Loan Terms:</strong><br>
                        Interest Rate: 12% per annum<br>
                        Total Payable: UGX <span id="total_payable">0.00</span><br>
                        Monthly Payment: UGX <span id="monthly_payment">0.00</span>
                    </div>
                    
                    <button type="submit" name="apply_loan" class="btn btn-primary w-100">Submit Application</button>
                </form>
                <?php elseif($selected_member && $max_loan == 0 && !$has_active_loan): ?>
                <div class="alert alert-warning">
                    Member has no savings yet. Minimum savings required before applying for a loan.
                </div>
                <?php elseif($selected_member && $has_active_loan): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-ban"></i> 
                    <strong>Loan Restriction!</strong><br>
                    This member already has an active loan with balance <strong>UGX <?= number_format($existing_loan_balance, 2) ?></strong>.<br>
                    They must complete repaying the current loan before applying for a new one.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate total payable and monthly payment
document.querySelector('input[name="loan_amount"]').addEventListener('input', calculateLoan);
document.querySelector('select[name="repayment_months"]').addEventListener('change', calculateLoan);

function calculateLoan() {
    let amount = parseFloat(document.querySelector('input[name="loan_amount"]').value) || 0;
    let months = parseInt(document.querySelector('select[name="repayment_months"]').value) || 3;
    let interest = amount * 0.12; // 12% per annum
    let total = amount + interest;
    let monthly = total / months;
    
    document.getElementById('total_payable').innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('monthly_payment').innerText = monthly.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php include '../includes/footer.php'; ?>
