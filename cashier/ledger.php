<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireRole('cashier');

// ✅ FIXED: PostgreSQL-compatible query to get latest ledger entry for each member
$stmt = $pdo->prepare("
    SELECT l.*, m.full_name, m.member_number
    FROM ledger l
    JOIN members m ON l.member_id = m.id
    WHERE l.id IN (
        SELECT MAX(id) 
        FROM ledger 
        GROUP BY member_id
    )
    ORDER BY m.full_name ASC
");
$stmt->execute();
$ledger_entries = $stmt->fetchAll();

// Alternative approach if the above still gives issues (more explicit):
// $stmt = $pdo->prepare("
//     SELECT l.*, m.full_name, m.member_number
//     FROM ledger l
//     JOIN members m ON l.member_id = m.id
//     JOIN (
//         SELECT member_id, MAX(id) as max_id
//         FROM ledger
//         GROUP BY member_id
//     ) latest ON l.id = latest.max_id
//     ORDER BY m.full_name ASC
// ");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ledger Book - SACCO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>📒 Ledger Book</h2>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
    <p>Current financial position for each member (latest transaction only)</p>
    
    <?php if(count($ledger_entries) == 0): ?>
        <div class="alert alert-info">No ledger entries found. Please record a deposit first.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="ledgerTable">
            <thead class="table-dark">
                <tr>
                    <th>Serial No</th>
                    <th>Member No</th>
                    <th>Member Name</th>
                    <th>Amount Saved (UGX)</th>
                    <th>Loan Out (UGX)</th>
                    <th>Interest Paid (UGX)</th>
                    <th>Loan Payment (UGX)</th>
                    <th>Loan Balance (UGX)</th>
                    <th>Guarantor</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $serial = 1;
                foreach($ledger_entries as $row): 
                ?>
                <?php
                $guarantor = (!empty($row['guarantor_name']) && $row['guarantor_name'] != 'Not specified') 
                    ? htmlspecialchars($row['guarantor_name']) 
                    : '<span class="text-muted">Not specified</span>';
                ?>
                <tr>
                    <td><?= $serial++ ?></td>
                    <td><?= htmlspecialchars($row['member_number']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td class="text-end"><?= number_format($row['amount_saved'], 2) ?></td>
                    <td class="text-end"><?= number_format($row['loan_out'], 2) ?></td>
                    <td class="text-end"><?= number_format($row['interest_paid'], 2) ?></td>
                    <td class="text-end"><?= number_format($row['loan_payment'], 2) ?></td>
                    <td class="text-end <?= $row['loan_balance'] > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                        <?= number_format($row['loan_balance'], 2) ?>
                    </td>
                    <td><?= $guarantor ?></td>
                    <td>
                        <?php if($row['loan_balance'] > 0): ?>
                            <span class="badge bg-warning">Active Loan</span>
                        <?php else: ?>
                            <span class="badge bg-success">Clean</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-secondary">
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">TOTALS:</td>
                    <td class="text-end"><?= number_format(array_sum(array_column($ledger_entries, 'amount_saved')), 2) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($ledger_entries, 'loan_out')), 2) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($ledger_entries, 'interest_paid')), 2) ?></td>
                    <td class="text-end"><?= number_format(array_sum(array_column($ledger_entries, 'loan_payment')), 2) ?></td>
                    <td class="text-end text-danger"><?= number_format(array_sum(array_column($ledger_entries, 'loan_balance')), 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#ledgerTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']]
    });
});
</script>

<?php include '../includes/footer.php'; ?>