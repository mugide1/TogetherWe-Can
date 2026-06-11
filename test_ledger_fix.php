<?php
/**
 * Test script to verify ledger balance calculations
 * Scenario: Loan 2,000 → Payment 1,120 → Balance should be 880
 */

require_once 'config/db.php';

echo "=== Ledger Balance Calculation Test ===\n\n";

// Find a member with active loan
$active_loans = $pdo->query("
    SELECT l.id, l.member_id, l.loan_amount, l.total_payable, l.balance, 
           m.full_name, le.loan_out, le.loan_balance, le.interest_paid, le.loan_payment
    FROM loans l
    JOIN members m ON l.member_id = m.id
    LEFT JOIN ledger le ON l.member_id = le.member_id
    WHERE l.status = 'disbursed' AND l.balance > 0
    ORDER BY l.id DESC LIMIT 5
")->fetchAll();

if (empty($active_loans)) {
    echo "❌ No active loans found. Cannot test.\n";
} else {
    echo "Active Loans Found:\n";
    echo str_repeat("-", 120) . "\n";
    
    foreach ($active_loans as $loan) {
        echo "Member: {$loan['full_name']}\n";
        echo "Loan Amount (Principal): UGX " . number_format($loan['loan_amount'], 2) . "\n";
        echo "Total Payable (Principal + Interest): UGX " . number_format($loan['total_payable'], 2) . "\n";
        echo "Loan Balance in loans table: UGX " . number_format($loan['balance'], 2) . "\n";
        echo "\nLedger Entry:\n";
        echo "  loan_out: UGX " . number_format($loan['loan_out'] ?? 0, 2) . "\n";
        echo "  loan_balance: UGX " . number_format($loan['loan_balance'] ?? 0, 2) . "\n";
        echo "  interest_paid: UGX " . number_format($loan['interest_paid'] ?? 0, 2) . "\n";
        echo "  loan_payment: UGX " . number_format($loan['loan_payment'] ?? 0, 2) . "\n";
        
        // Expected calculation:
        // loan_balance should = loan_amount - total_payments
        $expected_balance = $loan['loan_amount'] - ($loan['loan_payment'] ?? 0);
        echo "\n✓ Expected loan_balance: UGX " . number_format($expected_balance, 2) . "\n";
        echo "✓ Actual loan_balance: UGX " . number_format($loan['loan_balance'] ?? 0, 2) . "\n";
        
        if (abs($expected_balance - ($loan['loan_balance'] ?? 0)) < 0.01) {
            echo "✅ PASS - Balance calculation is correct\n";
        } else {
            echo "❌ FAIL - Balance calculation is incorrect\n";
        }
        echo str_repeat("-", 120) . "\n";
    }
}

echo "\n=== Test Complete ===\n";
?>
