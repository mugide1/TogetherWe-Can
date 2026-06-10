<?php
require_once 'config/db.php';

echo "<h2>Fixing Missing Guarantors</h2>";

// Get all members with loans but no guarantor in ledger
$members_to_fix = $pdo->query("
    SELECT DISTINCT m.id, m.full_name 
    FROM members m
    JOIN ledger l ON m.id = l.member_id
    WHERE l.loan_out > 0 AND (l.guarantor_name IS NULL OR l.guarantor_name = '' OR l.guarantor_name = 'Not specified')
")->fetchAll();

echo "<table border='1' cellpadding='8'>";
echo "<tr><th>Member ID</th><th>Member Name</th><th>Current Guarantor</th><th>Action</th></tr>";

foreach($members_to_fix as $member) {
    echo "<tr>";
    echo "<form method='POST'>";
    echo "<td>{$member['id']}</td>";
    echo "<td>" . htmlspecialchars($member['full_name']) . "</td>";
    echo "<td><input type='text' name='guarantor_name' placeholder='Enter guarantor name' required></td>";
    echo "<td><button type='submit' name='update' value='{$member['id']}'>Update</button></td>";
    echo "</form>";
    echo "</tr>";
}

echo "</table>";

// Process update
if(isset($_POST['update'])) {
    $member_id = $_POST['update'];
    $guarantor = $_POST['guarantor_name'];
    
    $update = $pdo->prepare("UPDATE ledger SET guarantor_name = ? WHERE member_id = ? AND loan_out > 0");
    $update->execute([$guarantor, $member_id]);
    
    echo "<p style='color:green'>✓ Updated guarantor for member ID: $member_id to '$guarantor'</p>";
    echo "<meta http-equiv='refresh' content='2'>";
}
?>