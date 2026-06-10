<?php

require_once '../includes/auth.php';
requireRole('auditor');  // This should allow auditor role, not admin
?>
<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-chart-bar"></i> Auditor Reports - Together-we-can SACCO</h2>

<div class="row mt-4">
    <?php
    // Get summary statistics (same as admin but read-only)
    $total_members = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
    $active_members = $pdo->query("SELECT COUNT(*) FROM members WHERE status='active'")->fetchColumn();
    
    $total_savings = $pdo->query("SELECT SUM(amount) as total FROM savings WHERE transaction_type='deposit'")->fetch()['total'] ?? 0;
    $total_loans_disbursed = $pdo->query("SELECT SUM(loan_amount) as total FROM loans WHERE status='disbursed'")->fetch()['total'] ?? 0;
    $total_interest_collected = $pdo->query("SELECT SUM(interest_paid) as total FROM loan_payments")->fetch()['total'] ?? 0;
    $outstanding_loans = $pdo->query("SELECT SUM(balance) as total FROM loans WHERE status='disbursed'")->fetch()['total'] ?? 0;
    $total_repayments = $pdo->query("SELECT SUM(amount) as total FROM loan_payments")->fetch()['total'] ?? 0;
    ?>
    
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5>Total Members</h5>
                <h2><?= $total_members ?></h2>
                <small>Active: <?= $active_members ?></small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5>Total Savings</h5>
                <h2>UGX <?= number_format($total_savings, 2) ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <h5>Total Loans Disbursed</h5>
                <h2>UGX <?= number_format($total_loans_disbursed, 2) ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <h5>Total Repayments</h5>
                <h2>UGX <?= number_format($total_repayments, 2) ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-white bg-danger mb-3">
            <div class="card-body">
                <h5>Outstanding Loans</h5>
                <h2>UGX <?= number_format($outstanding_loans, 2) ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-white bg-secondary mb-3">
            <div class="card-body">
                <h5>Interest Collected</h5>
                <h2>UGX <?= number_format($total_interest_collected, 2) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-history"></i> Recent Activity Log
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="logTable">
                        <thead class="table-light">
                            <tr><th>User</th><th>Action</th><th>Timestamp</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $logs = $pdo->query("
                                SELECT l.*, u.username 
                                FROM activity_logs l 
                                JOIN users u ON l.user_id = u.id 
                                ORDER BY l.id DESC LIMIT 50
                            ")->fetchAll();
                            foreach($logs as $log):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($log['username']) ?></td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($log['timestamp'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-hand-holding-usd"></i> Loan Performance
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr><th>Member</th><th>Loan Amount</th><th>Paid</th><th>Balance</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $loans = $pdo->query("
                                SELECT l.*, m.full_name 
                                FROM loans l 
                                JOIN members m ON l.member_id = m.id 
                                WHERE l.status IN ('disbursed', 'approved')
                                ORDER BY l.id DESC LIMIT 20
                            ")->fetchAll();
                            foreach($loans as $loan):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($loan['full_name']) ?></td>
                                <td class="text-end">UGX <?= number_format($loan['loan_amount'], 2) ?></td>
                                <td class="text-end">UGX <?= number_format($loan['amount_paid'], 2) ?></td>
                                <td class="text-end">UGX <?= number_format($loan['balance'], 2) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $loan['balance'] > 0 ? 'warning' : 'success' ?>">
                                        <?= $loan['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-users"></i> Top Savers
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr><th>Member</th><th>Total Savings</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $top_savers = $pdo->query("
                                SELECT m.full_name, SUM(s.amount) as total_savings
                                FROM savings s
                                JOIN members m ON s.member_id = m.id
                                WHERE s.transaction_type = 'deposit'
                                GROUP BY s.member_id
                                ORDER BY total_savings DESC
                                LIMIT 10
                            ")->fetchAll();
                            foreach($top_savers as $saver):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($saver['full_name']) ?></td>
                                <td class="text-end">UGX <?= number_format($saver['total_savings'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <button onclick="window.print()" class="btn btn-secondary">
        <i class="fas fa-print"></i> Print Report
    </button>
</div>

<!-- Add DataTables for sorting -->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#logTable').DataTable({
        pageLength: 25,
        order: [[2, 'desc']]
    });
});
</script>

<style media="print">
    .sidebar, .card-header .btn, .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate, .btn {
        display: none !important;
    }
    .col-md-2 {
        display: none;
    }
    .col-md-10, .col-md-4, .col-md-6, .col-md-12 {
        width: 100%;
    }
    .card {
        border: none;
        break-inside: avoid;
    }
</style>

<?php include '../includes/footer.php'; ?>
