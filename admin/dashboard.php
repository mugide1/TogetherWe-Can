<?php
require_once '../config/db.php';      // 1. Database connection FIRST
require_once '../includes/auth.php';  // 2. Authentication
requireRole('admin');                  // 3. Role check

// Rest of your code...
require_once '../includes/auth.php';
requireRole('admin');

// Get summary data
$totalMembers = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$totalSavings = $pdo->query("SELECT SUM(amount) FROM savings WHERE transaction_type='deposit'")->fetchColumn();
$totalSavings = $totalSavings ?: 0;
$totalLoans = $pdo->query("SELECT SUM(loan_amount) FROM loans WHERE status='disbursed'")->fetchColumn();
$totalLoans = $totalLoans ?: 0;
$totalLoanBalance = $pdo->query("SELECT SUM(balance) FROM loans WHERE status='disbursed'")->fetchColumn();
$totalLoanBalance = $totalLoanBalance ?: 0;
$pendingLoans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='pending'")->fetchColumn();
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
        color: rgba(255,255,255,0.8);
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
        background: linear-gradient(135deg, #1e2a3a 0%, #0f1724 100%);
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
                    <i class="fas fa-crown me-2"></i>Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?>!
                </h4>
                <p class="mb-0 opacity-75"><?= date('l, F j, Y') ?></p>
            </div>
            <div>
                <i class="fas fa-calendar-alt fa-2x opacity-50"></i>
                <div class="small"><?= date('h:i A') ?></div>
            </div>
        </div>
    </div>

    <!-- Stats Row - Only 4 main cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stat-label">Total Members</p>
                            <p class="stat-number"><?= $totalMembers ?></p>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stat-label">Total Savings</p>
                            <p class="stat-number">UGX <?= number_format($totalSavings, 0) ?></p>
                        </div>
                        <i class="fas fa-piggy-bank fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stat-label">Loans Disbursed</p>
                            <p class="stat-number">UGX <?= number_format($totalLoans, 0) ?></p>
                        </div>
                        <i class="fas fa-hand-holding-usd fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stat-label text-dark-50">Outstanding Balance</p>
                            <p class="stat-number">UGX <?= number_format($totalLoanBalance, 0) ?></p>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row - Only 2 cards -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stat-label">Pending Loan Approvals</p>
                            <p class="stat-number"><?= $pendingLoans ?></p>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card stat-card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stat-label">Loan Recovery Rate</p>
                            <p class="stat-number">
                                <?php 
                                $recoveryRate = $totalLoans > 0 ? (($totalLoans - $totalLoanBalance) / $totalLoans) * 100 : 0;
                                echo number_format($recoveryRate, 1) . '%';
                                ?>
                            </p>
                        </div>
                        <i class="fas fa-percent fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4 col-sm-6">
                            <a href="members.php" class="btn btn-primary action-btn w-100">
                                <i class="fas fa-users me-2"></i> Manage Members
                            </a>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <a href="loans.php" class="btn btn-success action-btn w-100">
                                <i class="fas fa-hand-holding-usd me-2"></i> Approve Loans
                            </a>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <a href="reports.php" class="btn btn-info action-btn w-100 text-white">
                                <i class="fas fa-chart-line me-2"></i> View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
