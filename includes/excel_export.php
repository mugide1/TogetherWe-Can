<?php
// Excel Export Helper Functions

function exportToExcel($data, $filename, $headers = []) {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Create output buffer
    $output = fopen('php://output', 'w');
    
    // Add headers if provided
    if (!empty($headers)) {
        fputcsv($output, $headers, "\t");
    }
    
    // Add data rows
    foreach ($data as $row) {
        fputcsv($output, $row, "\t");
    }
    
    fclose($output);
    exit();
}

function exportLedgerToExcel($ledger_data) {
    $headers = ['Serial No', 'Member No', 'Member Name', 'Amount Saved (UGX)', 'Total Amount (UGX)', 
                'Loan Out (UGX)', 'Interest Paid (UGX)', 'Loan Payment (UGX)', 'Loan Balance (UGX)', 
                'Guarantor', 'Status'];
    
    $data = [];
    foreach ($ledger_data as $row) {
        $data[] = [
            $row['serial_number'],
            $row['member_number'],
            $row['full_name'],
            number_format($row['amount_saved'], 2),
            number_format($row['total_amount'], 2),
            number_format($row['loan_out'], 2),
            number_format($row['interest_paid'], 2),
            number_format($row['loan_payment'], 2),
            number_format($row['loan_balance'], 2),
            $row['guarantor_name'] ?? 'Not specified',
            $row['loan_balance'] > 0 ? 'Active Loan' : 'Clean'
        ];
    }
    
    exportToExcel($data, 'ledger_book', $headers);
}

function exportMembersToExcel($members) {
    $headers = ['Member No', 'Full Name', 'Phone', 'Email', 'Address', 'Registration Date', 'Status'];
    
    $data = [];
    foreach ($members as $row) {
        $data[] = [
            $row['member_number'],
            $row['full_name'],
            $row['phone'],
            $row['email'],
            $row['address'],
            date('d/m/Y', strtotime($row['registration_date'])),
            $row['status']
        ];
    }
    
    exportToExcel($data, 'members_report', $headers);
}

function exportLoansToExcel($loans) {
    $headers = ['Member Name', 'Member No', 'Loan Amount (UGX)', 'Total Payable (UGX)', 
                'Amount Paid (UGX)', 'Balance (UGX)', 'Issue Date', 'Due Date', 'Status'];
    
    $data = [];
    foreach ($loans as $row) {
        $data[] = [
            $row['full_name'],
            $row['member_number'],
            number_format($row['loan_amount'], 2),
            number_format($row['total_payable'], 2),
            number_format($row['amount_paid'], 2),
            number_format($row['balance'], 2),
            date('d/m/Y', strtotime($row['issue_date'])),
            date('d/m/Y', strtotime($row['due_date'])),
            $row['status']
        ];
    }
    
    exportToExcel($data, 'loans_report', $headers);
}

function exportSavingsToExcel($savings) {
    $headers = ['Member Name', 'Member No', 'Amount (UGX)', 'Transaction Date', 'Type', 'Description'];
    
    $data = [];
    foreach ($savings as $row) {
        $data[] = [
            $row['full_name'],
            $row['member_number'],
            number_format($row['amount'], 2),
            date('d/m/Y', strtotime($row['transaction_date'])),
            $row['transaction_type'],
            $row['description']
        ];
    }
    
    exportToExcel($data, 'savings_report', $headers);
}
?>
