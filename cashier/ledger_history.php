<?php
require_once '../includes/auth.php';
requireRole('cashier');

// Get all ledger entries with member details
$all_entries = $pdo->query("
    SELECT l.*, m.full_name, m.member_number
    FROM ledger l
    JOIN members m ON l.member_id = m.id
    ORDER BY l.id DESC
    LIMIT 200
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<h2>📜 Full Ledger Transaction History</h2>

<div class="table-responsive">
    <table class="table table-bordered table-striped" id="historyTable">
        <thead class="table-dark">
            <tr>
                <th>ID</th><th>Serial</th><th>Member</th><th>Amount Saved</th>
                <th>Total Amount</th><th>Loan Out</th><th>Interest Paid</th>
                <th>Loan Payment</th><th>Loan Balance</th><th>Date</th><th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($all_entries as $entry): ?>
            <tr>
                <td><?= $entry['id'] ?></td>
                <td><?= $entry['serial_number'] ?></td>
                <td><?= htmlspecialchars($entry['full_name']) ?></td>
                <td class="text-end"><?= number_format($entry['amount_saved'], 2) ?></td>
                <td class="text-end"><?= number_format($entry['total_amount'], 2) ?></td>
                <td class="text-end"><?= number_format($entry['loan_out'], 2) ?></td>
                <td class="text-end"><?= number_format($entry['interest_paid'], 2) ?></td>
                <td class="text-end"><?= number_format($entry['loan_payment'], 2) ?></td>
                <td class="text-end"><?= number_format($entry['loan_balance'], 2) ?></td>
                <td><?= $entry['transaction_date'] ?></td>
                <td><?= htmlspecialchars($entry['sign']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#historyTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 50
    });
});
</script>
<?php include '../includes/footer.php'; ?>