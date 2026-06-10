<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('cashier');

// Get all active members (removed guarantor_name from SELECT)
$members = $pdo->query("SELECT id, member_number, full_name FROM members WHERE status='active' ORDER BY full_name")->fetchAll();

// Handle deposit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_deposit'])) {
    $member_id = $_POST['member_id'];
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'] ?? '';
    
    if ($amount <= 0) {
        $error = "Please enter a valid amount greater than 0.";
    } else {
        // Insert savings record
        $stmt = $pdo->prepare("INSERT INTO savings (member_id, amount, transaction_date, transaction_type, description) VALUES (?,?,?,?,?)");
        $stmt->execute([$member_id, $amount, date('Y-m-d'), 'deposit', $description]);
        
        // Calculate new total savings
        $savings_total = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM savings WHERE member_id = ? AND transaction_type = 'deposit'");
        $savings_total->execute([$member_id]);
        $current_savings = $savings_total->fetch()['total'];
        
        // Check if member already has a ledger entry
        $existing = $pdo->prepare("SELECT * FROM ledger WHERE member_id = ?");
        $existing->execute([$member_id]);
        $ledger_exists = $existing->fetch();
        
        if ($ledger_exists) {
            // UPDATE existing ledger row
            $update = $pdo->prepare("UPDATE ledger SET 
                amount_saved = ?,
                total_amount = ?
                WHERE member_id = ?");
            $update->execute([$current_savings, $current_savings, $member_id]);
        } else {
            // Insert new ledger entry (without guarantor_name)
            $insert = $pdo->prepare("INSERT INTO ledger (member_id, amount_saved, total_amount, transaction_date, sign) 
                VALUES (?, ?, ?, ?, ?)");
            $insert->execute([
                $member_id,
                $current_savings,
                $current_savings,
                date('Y-m-d'),
                'Initial deposit: ' . number_format($amount, 2)
            ]);
        }
        
        logActivity($_SESSION['user_id'], "Recorded deposit of $amount for member ID: $member_id");
        $success = "Deposit of UGX " . number_format($amount, 2) . " recorded successfully! Total savings: UGX " . number_format($current_savings, 2);
        
        // Redirect to refresh
        header("Location: deposit.php?success=" . urlencode($success));
        exit();
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>
<?php include '../includes/header.php'; ?>
<h2><i class="fas fa-save"></i> Record Member Deposit</h2>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if(isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">New Deposit</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Select Member</label>
                        <select name="member_id" class="form-control" required>
                            <option value="">-- Select Member --</option>
                            <?php foreach($members as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= $m['member_number'] ?> - <?= htmlspecialchars($m['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Amount (UGX)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" placeholder="e.g., Monthly savings contribution"></textarea>
                    </div>
                    <button type="submit" name="submit_deposit" class="btn btn-success w-100">Record Deposit</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">Today's Transactions</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Member</th><th>Amount</th><th>Time</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $today = $pdo->query("
                                SELECT s.*, m.full_name 
                                FROM savings s 
                                JOIN members m ON s.member_id = m.id 
                                WHERE DATE(s.transaction_date) = CURRENT_DATE 
                                ORDER BY s.id DESC LIMIT 10
                            ")->fetchAll();
                            foreach($today as $t):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($t['full_name']) ?></td>
                                <td class="text-end">UGX <?= number_format($t['amount'], 2) ?></td>
                                <td><?= date('H:i', strtotime($t['transaction_date'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
