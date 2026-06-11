<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('cashier');

$statement_data = null;
$member_info = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = $_POST['member_id'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    
    // Get member info
    $member_stmt = $pdo->prepare("SELECT id, member_number, full_name, phone, email, address, registration_date, status FROM members WHERE id = ?");
    $member_stmt->execute([$member_id]);
    $member_info = $member_stmt->fetch();
    
    // ✅ FIXED: Use EXTRACT for PostgreSQL compatibility
    // Get savings for the month
    $savings_stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_savings, COUNT(*) as transactions 
        FROM savings 
        WHERE member_id = ? 
        AND EXTRACT(MONTH FROM transaction_date) = ? 
        AND EXTRACT(YEAR FROM transaction_date) = ? 
        AND transaction_type = 'deposit'
    ");
    $savings_stmt->execute([$member_id, $month, $year]);
    $savings_data = $savings_stmt->fetch();
    
    // ✅ FIXED: Use EXTRACT for PostgreSQL compatibility
    // Get loan payments for the month
    $payments_stmt = $pdo->prepare("
        SELECT COALESCE(SUM(lp.amount), 0) as total_paid, COALESCE(SUM(lp.interest_paid), 0) as interest_paid
        FROM loan_payments lp
        JOIN loans l ON lp.loan_id = l.id
        WHERE l.member_id = ? 
        AND EXTRACT(MONTH FROM lp.payment_date) = ? 
        AND EXTRACT(YEAR FROM lp.payment_date) = ?
    ");
    $payments_stmt->execute([$member_id, $month, $year]);
    $payments_data = $payments_stmt->fetch();
    
    // Get current balances
    $total_savings_all = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM savings WHERE member_id = ? AND transaction_type='deposit'");
    $total_savings_all->execute([$member_id]);
    $total_savings = $total_savings_all->fetch()['total'] ?? 0;
    
    $loan_balance = $pdo->prepare("SELECT COALESCE(balance, 0) as balance FROM loans WHERE member_id = ? AND status='disbursed' ORDER BY id DESC LIMIT 1");
    $loan_balance->execute([$member_id]);
    $current_loan_balance = $loan_balance->fetch()['balance'] ?? 0;
    
    // Get guarantor from ledger
    $guarantor_stmt = $pdo->prepare("SELECT guarantor_name FROM ledger WHERE member_id = ? AND guarantor_name IS NOT NULL AND guarantor_name != '' ORDER BY id DESC LIMIT 1");
    $guarantor_stmt->execute([$member_id]);
    $guarantor_data = $guarantor_stmt->fetch();
    $guarantor_name = $guarantor_data['guarantor_name'] ?? 'Not specified';
    
    $statement_data = [
        'savings' => $savings_data['total_savings'] ?? 0,
        'savings_count' => $savings_data['transactions'] ?? 0,
        'loan_payments' => $payments_data['total_paid'] ?? 0,
        'interest_paid' => $payments_data['interest_paid'] ?? 0,
        'total_savings' => $total_savings,
        'loan_balance' => $current_loan_balance,
        'guarantor' => $guarantor_name
    ];
}

// Get all members for dropdown
$members = $pdo->query("SELECT id, member_number, full_name FROM members WHERE status='active' ORDER BY full_name")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<h2><i class="fas fa-file-invoice"></i> Monthly Statement Generator</h2>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">Generate Statement</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Select Member</label>
                        <select name="member_id" class="form-control" required>
                            <option value="">-- Select Member --</option>
                            <?php foreach($members as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['member_number']) ?> - <?= htmlspecialchars($m['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Month</label>
                        <select name="month" class="form-control" required>
                            <option value="1" <?= ($_POST['month'] ?? '') == 1 ? 'selected' : '' ?>>January</option>
                            <option value="2" <?= ($_POST['month'] ?? '') == 2 ? 'selected' : '' ?>>February</option>
                            <option value="3" <?= ($_POST['month'] ?? '') == 3 ? 'selected' : '' ?>>March</option>
                            <option value="4" <?= ($_POST['month'] ?? '') == 4 ? 'selected' : '' ?>>April</option>
                            <option value="5" <?= ($_POST['month'] ?? '') == 5 ? 'selected' : '' ?>>May</option>
                            <option value="6" <?= ($_POST['month'] ?? '') == 6 ? 'selected' : '' ?>>June</option>
                            <option value="7" <?= ($_POST['month'] ?? '') == 7 ? 'selected' : '' ?>>July</option>
                            <option value="8" <?= ($_POST['month'] ?? '') == 8 ? 'selected' : '' ?>>August</option>
                            <option value="9" <?= ($_POST['month'] ?? '') == 9 ? 'selected' : '' ?>>September</option>
                            <option value="10" <?= ($_POST['month'] ?? '') == 10 ? 'selected' : '' ?>>October</option>
                            <option value="11" <?= ($_POST['month'] ?? '') == 11 ? 'selected' : '' ?>>November</option>
                            <option value="12" <?= ($_POST['month'] ?? '') == 12 ? 'selected' : '' ?>>December</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Year</label>
                        <select name="year" class="form-control" required>
                            <option value="2024" <?= ($_POST['year'] ?? '') == 2024 ? 'selected' : '' ?>>2024</option>
                            <option value="2025" <?= ($_POST['year'] ?? '') == 2025 ? 'selected' : '' ?>>2025</option>
                            <option value="2026" <?= ($_POST['year'] ?? '') == 2026 ? 'selected' : '' ?>>2026</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Generate Statement</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php if($statement_data && $member_info): ?>
        <div class="card" id="statement-card">
            <div class="card-header bg-success text-white text-center">
                <h4>Together-we-can SACCO</h4>
                <h5>Monthly Financial Statement</h5>
                <p><?= date('F Y', mktime(0,0,0,$_POST['month'],1,$_POST['year'])) ?></p>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <strong>Member Name:</strong> <?= htmlspecialchars($member_info['full_name']) ?><br>
                        <strong>Member Number:</strong> <?= htmlspecialchars($member_info['member_number']) ?><br>
                        <strong>Phone:</strong> <?= htmlspecialchars($member_info['phone'] ?? 'N/A') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Guarantor:</strong> <?= htmlspecialchars($statement_data['guarantor']) ?><br>
                        <strong>Registration Date:</strong> <?= date('d/m/Y', strtotime($member_info['registration_date'])) ?>
                    </div>
                </div>
                
                <table class="table table-bordered">
                    <tr class="table-info">
                        <th colspan="2">Savings Summary</th>
                    </tr>
                    <tr>
                        <td>Total Savings This Month</td>
                        <td class="text-end">UGX <?= number_format($statement_data['savings'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>Number of Deposits This Month</td>
                        <td class="text-end"><?= $statement_data['savings_count'] ?></td>
                    </tr>
                    <tr>
                        <td>Total Savings to Date</td>
                        <td class="text-end fw-bold">UGX <?= number_format($statement_data['total_savings'], 2) ?></td>
                    </tr>
                    
                    <tr class="table-warning">
                        <th colspan="2">Loan Summary</th>
                    </tr>
                    <tr>
                        <td>Loan Payments This Month</td>
                        <td class="text-end">UGX <?= number_format($statement_data['loan_payments'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>Interest Paid This Month</td>
                        <td class="text-end">UGX <?= number_format($statement_data['interest_paid'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>Outstanding Loan Balance</td>
                        <td class="text-end fw-bold text-danger">UGX <?= number_format($statement_data['loan_balance'], 2) ?></td>
                    </tr>
                    
                    <tr class="table-success">
                        <th>Net Position</th>
                        <th class="text-end">UGX <?= number_format($statement_data['total_savings'] - $statement_data['loan_balance'], 2) ?></th>
                    </tr>
                </table>
                
                <div class="text-muted mt-3 small text-center">
                    Generated on: <?= date('d/m/Y H:i:s') ?> | This is a computer-generated statement
                </div>
            </div>
            <div class="card-footer text-center">
                <button onclick="window.print()" class="btn btn-secondary">Print Statement</button>
                <button onclick="window.location.href='statement.php'" class="btn btn-primary">New Statement</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style media="print">
    .sidebar, .btn, .card-header .btn, form, .navbar {
        display: none !important;
    }
    .col-md-4 {
        display: none;
    }
    .col-md-8 {
        width: 100%;
    }
    .card {
        border: none;
    }
</style>

<?php include '../includes/footer.php'; ?>