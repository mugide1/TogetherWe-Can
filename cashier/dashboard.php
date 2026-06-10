<?php
require_once '../includes/auth.php';
requireRole('cashier');

// Get today's stats
$today_deposits = $pdo->query("SELECT SUM(amount) as total FROM savings WHERE DATE(transaction_date) = CURRENT_DATE AND transaction_type='deposit'")->fetch()['total'] ?? 0;
$today_payments = $pdo->query("SELECT SUM(amount) as total FROM loan_payments WHERE DATE(payment_date) = CURRENT_DATE")->fetch()['total'] ?? 0;
$pending_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='pending'")->fetchColumn();

// Get recent transactions (limited to 5)
$recent_deposits = $pdo->query("
    SELECT s.*, m.full_name 
    FROM savings s 
    JOIN members m ON s.member_id = m.id 
    WHERE s.transaction_type = 'deposit'
    ORDER BY s.id DESC LIMIT 5
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<style>
    .stat-card {
        border-radius: 12px;
        transition: transform 0.2s;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-3px);
    }
    .stat-number {
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 0;
    }
    .stat-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
    }
    .action-btn {
        padding: 12px;
        border-radius: 10px;
        transition: all 0.2s;
    }
    .action-btn:hover {
        transform: translateY(-2px);
    }
    .welcome-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 20px;
        color: white;
        margin-bottom: 25px;
    }
</style>

<div class="container-fluid">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-hand-peace me-2"></i>Hello, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?>!
                </h4>
                <p class="mb-0 opacity-75"><?= date('l, F j, Y') ?></p>
            </div>
            <div>
                <i class="fas fa-clock fa-2x opacity-50"></i>
                <div class="small"><?= date('h:i A') ?></div>
            </div>
        </div>
    </div>

    <!-- Stats Row - Only 3 cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stat-label text-white-50 mb-0">Today's Deposits</p>
                            <p class="stat-number">UGX <?= number_format($today_deposits, 0) ?></p>
                        </div>
                        <i class="fas fa-save fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stat-label text-white-50 mb-0">Today's Payments</p>
                            <p class="stat-number">UGX <?= number_format($today_payments, 0) ?></p>
                        </div>
                        <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stat-label text-dark-50 mb-0">Pending Approvals</p>
                            <p class="stat-number"><?= $pending_loans ?></p>
                        </div>
                        <i class="fas fa-hourglass-half fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-3 col-sm-6">
                            <a href="deposit.php" class="btn btn-success action-btn w-100">
                                <i class="fas fa-save me-2"></i> Record Deposit
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="loan_payment.php" class="btn btn-warning action-btn w-100 text-dark">
                                <i class="fas fa-money-bill-wave me-2"></i> Loan Payment
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="loan_application.php" class="btn btn-info action-btn w-100 text-white">
                                <i class="fas fa-hand-holding-usd me-2"></i> Apply Loan
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="ledger.php" class="btn btn-secondary action-btn w-100">
                                <i class="fas fa-book me-2"></i> Ledger
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity - Only one section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Recent Deposits
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if(count($recent_deposits) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Member</th>
                                        <th>Amount</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_deposits as $deposit): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($deposit['full_name']) ?></td>
                                        <td class="text-success fw-bold">+ UGX <?= number_format($deposit['amount'], 2) ?></td>
                                        <td><?= date('H:i', strtotime($deposit['transaction_date'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>No deposits recorded today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
