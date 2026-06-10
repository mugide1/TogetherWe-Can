<?php
// Simple PDF Export using HTML2PDF (built-in browser print to PDF)
// This uses the browser's print functionality to save as PDF

function exportToPDF($html_content, $filename) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?= $filename ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                padding: 20px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            .header h1 {
                margin: 0;
                color: #2c3e50;
            }
            .header p {
                margin: 5px 0;
                color: #7f8c8d;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
            }
            th {
                background-color: #2c3e50;
                color: white;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                font-size: 12px;
                color: #7f8c8d;
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
            .text-end {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 4px;
                font-size: 11px;
            }
            .badge-success { background: #27ae60; color: white; }
            .badge-danger { background: #e74c3c; color: white; }
            .badge-warning { background: #f39c12; color: white; }
            .badge-info { background: #3498db; color: white; }
        </style>
    </head>
    <body>
        <?= $html_content ?>
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.close();
                }, 1000);
            };
        </script>
    </body>
    </html>
    <?php
    exit();
}

function generatePDFReport($data, $type, $start_date, $end_date) {
    ob_start();
    ?>
    <div class="header">
        <h1>Together-we-can SACCO</h1>
        <p><?= ucfirst($type) ?> Report</p>
        <p>Period: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
        <p>Generated on: <?= date('d/m/Y H:i:s') ?></p>
    </div>
    
    <?php if($type == 'summary'): ?>
        <h3>Summary Statistics</h3>
        <table>
            <tr><th>Metric</th><th>Value</th></tr>
            <?php foreach($data as $key => $value): ?>
            <tr>
                <td><?= str_replace('_', ' ', ucfirst($key)) ?></td>
                <td class="text-end"><?= $value ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    
    <?php elseif($type == 'members'): ?>
        <h3>Members Report</h3>
        <table>
            <thead>
                <tr><th>Member No</th><th>Name</th><th>Phone</th><th>Savings</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach($data as $row): ?>
                <tr>
                    <td><?= $row['member_number'] ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= $row['phone'] ?></td>
                    <td class="text-end">UGX <?= number_format($row['total_savings'] ?? 0, 2) ?></td>
                    <td class="text-center"><?= $row['status'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    
    <?php elseif($type == 'loans'): ?>
        <h3>Loans Report</h3>
        <table>
            <thead>
                <tr><th>Member</th><th>Loan Amount</th><th>Paid</th><th>Balance</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach($data as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td class="text-end">UGX <?= number_format($row['loan_amount'], 2) ?></td>
                    <td class="text-end">UGX <?= number_format($row['amount_paid'], 2) ?></td>
                    <td class="text-end">UGX <?= number_format($row['balance'], 2) ?></td>
                    <td class="text-center"><?= $row['status'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    
    <?php elseif($type == 'savings'): ?>
        <h3>Savings Report</h3>
        <table>
            <thead>
                <tr><th>Member</th><th>Amount Saved</th><th>Transactions</th></tr>
            </thead>
            <tbody>
                <?php foreach($data as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td class="text-end">UGX <?= number_format($row['total_saved'], 2) ?></td>
                    <td class="text-center"><?= $row['transactions'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div class="footer">
        <p>This is a computer-generated report. For official use only.</p>
    </div>
    <?php
    $html = ob_get_clean();
    exportToPDF($html, $type . '_report');
}
?>
