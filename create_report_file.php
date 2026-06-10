<?php
$content = '<?php
require_once \'../includes/auth.php\';
requireRole(\'admin\');
?>
<?php include \'../includes/header.php\'; ?>
<h2>Reports Page</h2>
<p>Reports are working!</p>
<?php include \'../includes/footer.php\'; ?>';

$file_path = __DIR__ . '/admin/reports.php';

if (file_put_contents($file_path, $content)) {
    echo "<h2 style='color:green'>✓ reports.php created successfully!</h2>";
    echo "<p>Path: " . $file_path . "</p>";
    echo "<a href='admin/reports.php'>Click here to view reports.php</a>";
} else {
    echo "<h2 style='color:red'>✗ Failed to create reports.php</h2>";
    echo "<p>Please check folder permissions.</p>";
}
?>