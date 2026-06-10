<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('cashier');

// Get members with active loans (balance > 0)
$active_loans = $pdo->query("
    SELECT l.*, m.full_name, m.member_number 
    FROM loans l 
    JOIN members m ON l.member_id = m.id 
    WHERE l.status = 'disbursed' AND l.balance > 0
    ORDER BY m.full_name ASC
")->fetchAll();

// Handle loan payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_payment'])) {
    $loan_id = $_POST['loan_id'];
    $amount = floatval($_POST['amount']);
    
    if ($amount <= 0) {
        $error = "Please enter a valid amount greater than 0.";
    } else {
        // Get loan details
        $loan = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
        $loan->execute([$loan_id]);
        $loan_data = $loan->fetch();
        
        if (!$loan_data) {
            $error = "Loan not found.";
            } elseif ($amount > $loan_data['balance']) {
            $error = "Payment amount cannot exceed loan balance of UGX " . number_format($loan_data['balance'], 2);
        } else {
            // Calculate interest portion (10% of payment goes to interest)
            $interest_portion = $amount * 0.10;
            $principal_portion = $amount - $interest_portion;
            
            // Record payment in loan_payments table
            $stmt = $pdo->prepare("INSERT INTO loan_payments (loan_id, amount, payment_date, interest_paid, principal_paid) VALUES (?,?,?,?,?)");
            $stmt->execute([$loan_id, $amount, date('Y-m-d'), $interest_portion, $principal_portion]);
            
            // Update loan balance
            $new_balance = max(0, $loan_data['balance'] - $amount);
            $new_amount_paid = $loan_data['amount_paid'] + $amount;
            $update = $pdo->prepare("UPDATE loans SET amount_paid = ?, balance = ? WHERE id = ?");
            $update->execute([$new_amount_paid, $new_balance, $loan_id]);
            
            // Update loan status if fully paid
            if ($new_balance <= 0) {
                $pdo->prepare("UPDATE loans SET status = 'completed' WHERE id = ?")->execute([$loan_id]);
            }
            
            // Get member's current ledger entry
            $current_ledger = $pdo->prepare("SELECT * FROM ledger WHERE member_id = ? ORDER BY id DESC LIMIT 1");
            $current_ledger->execute([$loan_data['member_id']]);
            $current = $current_ledger->fetch();
            
            if ($current) {
                $new_loan_balance = max(0, ($current['loan_balance'] ?? 0) - $amount);
                $new_interest_paid = ($current['interest_paid'] ?? 0) + $interest_portion;
                $new_loan_payment = ($current['loan_payment'] ?? 0) + $amount;
                
                $update_ledger = $pdo->prepare("UPDATE ledger SET 
                    loan_balance = ?, 
                    interest_paid = ?, 
                    loan_payment = ?
                    WHERE member_id = ?");
                $update_ledger->execute([
                    $new_loan_balance,
                    $new_interest_paid,
                    $new_loan_payment,
                    $loan_data['member_id']
                ]);
            }
            
            logActivity($_SESSION['user_id'], "Received loan payment of $amount for loan ID: $loan_id");
            $success = "Payment of UGX " . number_format($amount, 2) . " recorded! New balance: UGX " . number_format($new_balance, 2);
            
            header("Location: loan_payment.php?success=" . urlencode($success));
            exit();
        }
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>
<?php include '../includes/header.php'; ?>
<h2><i class="fas fa-money-bill-wave"></i> Loan Payment</h2>

<?php if(isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Record Loan Repayment</h5>
            </div>
            <div class="card-body">
                <?php if(count($active_loans) > 0): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Select Member</label>
                        <select name="loan_id" class="form-control" required id="loan_select">
                            <option value="">-- Select Member --</option>
                            <?php foreach($active_loans as $loan): ?>
                            <option value="<?= $loan['id'] ?>" 
                                    data-balance="<?= $loan['balance'] ?>"
                                    data-loan-amount="<?= $loan['loan_amount'] ?>">
                                <?= htmlspecialchars($loan['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Outstanding Balance</label>
                        <input type="text" id="outstanding_balance" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Amount (UGX)</label>
                        <input type="number" step="0.01" name="amount" id="payment_amount" class="form-control" required>
                        <small class="text-muted">10% to interest, 90% to principal</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Breakdown</label>
                            <div class="alert alert-info">
                            <strong>Principal:</strong> <span id="principal_display">UGX 0.00</span><br>
                            <strong>Interest:</strong> <span id="interest_display">UGX 0.00</span>
                        </div>
                    </div>
                    
                    <button type="submit" name="submit_payment" class="btn btn-warning w-100">Record Payment</button>
                </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        No active loans with outstanding balance found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Recent Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Member</th><th>Amount</th><th>Interest</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent = $pdo->query("
                                SELECT lp.*, m.full_name, l.loan_amount
                                FROM loan_payments lp
                                JOIN loans l ON lp.loan_id = l.id
                                JOIN members m ON l.member_id = m.id
                                ORDER BY lp.id DESC LIMIT 10
                            ")->fetchAll();
                            foreach($recent as $r):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($r['full_name']) ?></td>
                                <td class="text-end">UGX <?= number_format($r['amount'], 2) ?></td>
                                <td class="text-end">UGX <?= number_format($r['interest_paid'], 2) ?></td>
                                <td><?= date('d/m/Y', strtotime($r['payment_date'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('loan_select').addEventListener('change', function() {
    var selectedOption = this.options[this.selectedIndex];
    var balance = selectedOption.getAttribute('data-balance');
    if (balance) {
        document.getElementById('outstanding_balance').value = 'UGX ' + parseFloat(balance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        document.getElementById('outstanding_balance').value = '';
    }
    updatePaymentBreakdown();
});

document.getElementById('payment_amount').addEventListener('input', function() {
    updatePaymentBreakdown();
});

function updatePaymentBreakdown() {
    var amount = parseFloat(document.getElementById('payment_amount').value) || 0;
    var interest = amount * 0.10;
    var principal = amount - interest;
    
    document.getElementById('principal_display').innerHTML = 'UGX ' + principal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('interest_display').innerHTML = 'UGX ' + interest.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php include '../includes/footer.php'; ?>
