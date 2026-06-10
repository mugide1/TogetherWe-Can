<?php
require_once 'config/db.php';

echo "<h2>Cleaning Up Ledger Entries</h2>";

// Get all members
$members = $pdo->query("SELECT id, member_number, full_name, guarantor_name FROM members")->fetchAll();

foreach($members as $member) {
    $member_id = $member['id'];
    
    // Get all ledger entries for this member
    $entries = $pdo->prepare("SELECT * FROM ledger WHERE member_id = ? ORDER BY id ASC");
    $entries->execute([$member_id]);
    $all_entries = $entries->fetchAll();
    
    if (count($all_entries) > 0) {
        // Calculate totals from all entries
        $total_saved = 0;
        $total_loan_out = 0;
        $total_interest = 0;
        $total_payment = 0;
        $final_balance = 0;
        
        foreach($all_entries as $entry) {
            $total_saved += $entry['amount_saved'];
            $total_loan_out += $entry['loan_out'];
            $total_interest += $entry['interest_paid'];
            $total_payment += $entry['loan_payment'];
            if ($entry['loan_balance'] > 0) {
                $final_balance = $entry['loan_balance'];
            }
        }
        
        // Also get savings from savings table
        $savings_total = $pdo->prepare("SELECT SUM(amount) as total FROM savings WHERE member_id = ? AND transaction_type = 'deposit'");
        $savings_total->execute([$member_id]);
        $actual_savings = $savings_total->fetch()['total'] ?? 0;
        
        // Get loan info from loans table
        $loan_info = $pdo->prepare("SELECT SUM(loan_amount) as total_loan, SUM(amount_paid) as total_paid, SUM(balance) as current_balance FROM loans WHERE member_id = ? AND status != 'rejected'");
        $loan_info->execute([$member_id]);
        $loan_data = $loan_info->fetch();
        
        // Delete all old entries for this member
        $delete = $pdo->prepare("DELETE FROM ledger WHERE member_id = ?");
        $delete->execute([$member_id]);
        
        // Insert single clean entry
        $insert = $pdo->prepare("INSERT INTO ledger (member_id, amount_saved, total_amount, loan_out, interest_paid, loan_payment, loan_balance, guarantor_name, transaction_date, sign) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([
            $member_id,
            $actual_savings,
            $actual_savings,
            $loan_data['total_loan'] ?? 0,
            $total_interest,
            $loan_data['total_paid'] ?? 0,
            $loan_data['current_balance'] ?? 0,
            $member['guarantor_name'],
            date('Y-m-d'),
            'Consolidated entry - ' . date('Y-m-d H:i:s')
        ]);
        
        echo "<p>✓ Fixed member: <strong>" . htmlspecialchars($member['full_name']) . "</strong><br>";
        echo "&nbsp;&nbsp;Savings: " . number_format($actual_savings, 2) . " | ";
        echo "Loan Taken: " . number_format($loan_data['total_loan'] ?? 0, 2) . " | ";
        echo "Loan Balance: " . number_format($loan_data['current_balance'] ?? 0, 2) . "</p>";
    }
}

echo "<h3 style='color:green'>✅ Ledger cleanup complete!</h3>";
echo "<a href='cashier/ledger.php' class='btn btn-primary'>View Ledger</a>";
?>