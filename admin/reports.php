<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/excel_export.php';
require_once '../includes/pdf_export.php';  // Add this line
requireRole('admin');

// Check for PDF export
if(isset($_GET['pdf']) && isset($_GET['type'])) {
    $export_type = $_GET['type'];
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    
    if($export_type == 'members') {
        $data = $pdo->query("
            SELECT m.*, 
                   (SELECT SUM(amount) FROM savings WHERE member_id = m.id AND transaction_type='deposit') as total_savings
            FROM members m
            ORDER BY m.id DESC
        ")->fetchAll();
        generatePDFReport($data, 'members', $start_date, $end_date);
    }
    elseif($export_type == 'loans') {
        $data = $pdo->query("
            SELECT l.*, m.full_name 
            FROM loans l 
            JOIN members m ON l.member_id = m.id 
            ORDER BY l.id DESC
        ")->fetchAll();
        generatePDFReport($data, 'loans', $start_date, $end_date);
    }
    elseif($export_type == 'savings') {
        $data = $pdo->query("
            SELECT m.full_name, SUM(s.amount) as total_saved, COUNT(s.id) as transactions
            FROM savings s
            JOIN members m ON s.member_id = m.id
            WHERE s.transaction_type = 'deposit'
            GROUP BY s.member_id
            ORDER BY total_saved DESC
        ")->fetchAll();
        generatePDFReport($data, 'savings', $start_date, $end_date);
    }
    elseif($export_type == 'summary') {
        $total_members = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
        $total_savings = $pdo->query("SELECT SUM(amount) FROM savings WHERE transaction_type='deposit'")->fetchColumn() ?? 0;
        $total_loans = $pdo->query("SELECT SUM(loan_amount) FROM loans WHERE status='disbursed'")->fetchColumn() ?? 0;
        $total_loan_balance = $pdo->query("SELECT SUM(balance) FROM loans WHERE status='disbursed'")->fetchColumn() ?? 0;
        $pending_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='pending'")->fetchColumn();
        
        $data = [
            'Total Members' => number_format($total_members),
            'Total Savings' => 'UGX ' . number_format($total_savings, 2),
            'Total Loans Disbursed' => 'UGX ' . number_format($total_loans, 2),
            'Outstanding Loan Balance' => 'UGX ' . number_format($total_loan_balance, 2),
            'Pending Approvals' => $pending_loans
        ];
        generatePDFReport($data, 'summary', $start_date, $end_date);
    }
}

// Get date filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'summary';
?>
<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-chart-line"></i> Financial Reports</h2>

<!-- Report Filters -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-filter"></i> Filter Reports
    </div>
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-3">
                <label>Report Type</label>
                <select name="report_type" class="form-control">
                    <option value="summary" <?= $report_type == 'summary' ? 'selected' : '' ?>>Summary Report</option>
                    <option value="savings" <?= $report_type == 'savings' ? 'selected' : '' ?>>Savings Report</option>
                    <option value="loans" <?= $report_type == 'loans' ? 'selected' : '' ?>>Loans Report</option>
                    <option value="members" <?= $report_type == 'members' ? 'selected' : '' ?>>Members Report</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
            </div>
            <div class="col-md-3">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Report Content with Export Buttons -->
<?php if($report_type == 'summary'): ?>
    <?php
    $total_members = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
    $active_members = $pdo->query("SELECT COUNT(*) FROM members WHERE status='active'")->fetchColumn();
    $total_savings = $pdo->query("SELECT SUM(amount) as total FROM savings WHERE transaction_type='deposit' AND transaction_date BETWEEN '$start_date' AND '$end_date'")->fetch()['total'] ?? 0;
    $total_loans = $pdo->query("SELECT SUM(loan_amount) as total FROM loans WHERE status='disbursed' AND issue_date BETWEEN '$start_date' AND '$end_date'")->fetch()['total'] ?? 0;
    $total_repayments = $pdo->query("SELECT SUM(amount) as total FROM loan_payments WHERE payment_date BETWEEN '$start_date' AND '$end_date'")->fetch()['total'] ?? 0;
    $outstanding_loans = $pdo->query("SELECT SUM(balance) as total FROM loans WHERE status='disbursed'")->fetch()['total'] ?? 0;
    ?>
    
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span>Summary Report</span>
            <div>
                <a href="?pdf=1&type=summary&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-danger me-2">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="?export=excel&type=summary" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5>Membership</h5>
                            <p>Total: <?= $total_members ?> | Active: <?= $active_members ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5>Savings</h5>
                            <p>Total: UGX <?= number_format($total_savings, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mt-2">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5>Loans</h5>
                            <p>Disbursed: UGX <?= number_format($total_loans, 2) ?></p>
                            <p>Repayments: UGX <?= number_format($total_repayments, 2) ?></p>
                            <p>Outstanding: UGX <?= number_format($outstanding_loans, 2) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif($report_type == 'members'): ?>
    <?php
    $members_data = $pdo->query("
        SELECT m.*, 
               (SELECT SUM(amount) FROM savings WHERE member_id = m.id AND transaction_type='deposit') as total_savings,
               (SELECT SUM(balance) FROM loans WHERE member_id = m.id AND status='disbursed') as loan_balance
        FROM members m
        ORDER BY m.registration_date DESC
    ")->fetchAll();
    ?>
    
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span>Members Report</span>
            <div>
                <a href="?pdf=1&type=members&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-danger me-2">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="?export=excel&type=members" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="reportTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Member No</th><th>Name</th><th>Phone</th>
                            <th>Total Savings</th><th>Loan Balance</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($members_data as $row): ?>
                        <tr>
                            <td><?= $row['member_number'] ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= $row['phone'] ?></td>
                            <td class="text-end">UGX <?= number_format($row['total_savings'] ?? 0, 2) ?></td>
                            <td class="text-end">UGX <?= number_format($row['loan_balance'] ?? 0, 2) ?></td>
                            <td><?= $row['status'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif($report_type == 'loans'): ?>
    <?php
    $loans_data = $pdo->query("
        SELECT m.full_name, m.member_number, l.loan_amount, l.amount_paid, l.balance, l.status, l.issue_date
        FROM loans l
        JOIN members m ON l.member_id = m.id
        WHERE l.issue_date BETWEEN '$start_date' AND '$end_date'
        ORDER BY l.issue_date DESC
    ")->fetchAll();
    ?>
    
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span>Loans Report</span>
            <div>
                <a href="?pdf=1&type=loans&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-danger me-2">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="?export=excel&type=loans" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="reportTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Member</th><th>Loan Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Issue Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($loans_data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name']) ?> <br><small><?= $row['member_number'] ?></small></td>
                            <td class="text-end">UGX <?= number_format($row['loan_amount'], 2) ?></td>
                            <td class="text-end">UGX <?= number_format($row['amount_paid'], 2) ?></td>
                            <td class="text-end">UGX <?= number_format($row['balance'], 2) ?></td>
                            <td><?= $row['status'] ?></td>
                            <td><?= date('d/m/Y', strtotime($row['issue_date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif($report_type == 'savings'): ?>
    <?php
    $savings_data = $pdo->query("
        SELECT m.full_name, m.member_number, SUM(s.amount) as total_saved, COUNT(s.id) as transactions
        FROM savings s
        JOIN members m ON s.member_id = m.id
        WHERE s.transaction_type = 'deposit' AND s.transaction_date BETWEEN '$start_date' AND '$end_date'
        GROUP BY s.member_id
        ORDER BY total_saved DESC
    ")->fetchAll();
    ?>
    
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span>Savings Report</span>
            <div>
                <a href="?pdf=1&type=savings&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-danger me-2">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="?export=excel&type=savings" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="reportTable">
                    <thead class="table-dark">
                        <tr><th>Member</th><th>Total Saved</th><th>Transactions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($savings_data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name']) ?> <br><small><?= $row['member_number'] ?></small></td>
                            <td class="text-end">UGX <?= number_format($row['total_saved'], 2) ?></td>
                            <td class="text-center"><?= $row['transactions'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Add DataTables for sorting -->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#reportTable').DataTable({
        pageLength: 25,
        order: []
    });
});
</script>

<style media="print">
    .sidebar, .card-header .btn, form, .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate, .btn {
        display: none !important;
    }
    .col-md-2, .col-md-3 {
        display: none;
    }
    .col-md-10, .col-md-6, .col-md-12 {
        width: 100%;
    }
    .card {
        border: none;
        break-inside: avoid;
    }
</style>

<?php include '../includes/footer.php'; ?>
