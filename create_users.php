<?php
require_once 'config/db.php';

try {
    echo "<h2>Creating Users for SACCO System</h2>";
    
    // Clear existing users
    $pdo->exec("DELETE FROM users");
    echo "<p>✓ Cleared existing users</p>";
    
    // Create password hash for 'admin123'
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    echo "<p>Generated password hash: <code>" . $password_hash . "</code></p>";
    
    // Insert users
    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
    
    $users = [
        ['admin', $password_hash, 'System Admin', 'admin'],
        ['cashier1', $password_hash, 'Main Cashier', 'cashier'],
        ['auditor1', $password_hash, 'Internal Auditor', 'auditor']
    ];
    
    foreach($users as $user) {
        $stmt->execute($user);
        echo "<p>✓ Inserted user: <strong>" . $user[0] . "</strong> (Role: " . $user[3] . ")</p>";
    }
    
    // Verify
    $verify = $pdo->query("SELECT username, role FROM users");
    echo "<h3>Users in database now:</h3>";
    echo "<ul>";
    while($row = $verify->fetch()) {
        echo "<li>" . $row['username'] . " - " . $row['role'] . "</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<a href='login.php' class='btn btn-primary'>Go to Login Page</a>";
    
} catch(PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>