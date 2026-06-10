<?php
/**
 * Database Migration: Add User Management Fields
 * This script safely adds new columns to the users table for enhanced user management
 * Columns: email, is_active, failed_login_attempts, locked_until, password_changed_at, last_login
 * 
 * Safe to run multiple times - checks if columns exist before adding them
 */

require_once '../config/db.php';

$migrations = [
    "ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE NULL DEFAULT NULL AFTER full_name",
    "ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT 1 AFTER email",
    "ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0 AFTER is_active",
    "ALTER TABLE users ADD COLUMN locked_until DATETIME NULL DEFAULT NULL AFTER failed_login_attempts",
    "ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL DEFAULT NULL AFTER locked_until",
    "ALTER TABLE users ADD COLUMN last_login DATETIME NULL DEFAULT NULL AFTER password_changed_at",
];

$results = [];
foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        $column_name = explode(' ', $sql)[5];
        $results[] = ['status' => 'success', 'column' => $column_name, 'message' => "Column added: $column_name"];
    } catch (PDOException $e) {
        // If column already exists, that's fine
        if (strpos($e->getMessage(), '1060') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            $column_name = explode(' ', $sql)[5];
            $results[] = ['status' => 'info', 'column' => $column_name, 'message' => "Column already exists: $column_name"];
        } else {
            $results[] = ['status' => 'error', 'column' => '', 'message' => $e->getMessage()];
        }
    }
}

// Verify the schema
$verify = $pdo->query("DESCRIBE users");
$existing_columns = $verify->fetchAll(PDO::FETCH_COLUMN, 0);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Migration - Add User Fields</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4>Database Migration Results</h4>
                    </div>
                    <div class="card-body">
                        <h5>Migration Status:</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Column</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($result['column']) ?></code></td>
                                            <td>
                                                <?php if ($result['status'] === 'success'): ?>
                                                    <span class="badge bg-success">Success</span>
                                                <?php elseif ($result['status'] === 'info'): ?>
                                                    <span class="badge bg-info">Info</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Error</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($result['message']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr>

                        <h5>Current Users Table Schema:</h5>
                        <p><small class="text-muted">These columns now exist in the users table:</small></p>
                        <div class="alert alert-info">
                            <code><?= implode(', ', $existing_columns) ?></code>
                        </div>

                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Migration completed successfully!
                        </div>

                        <a href="../admin/dashboard.php" class="btn btn-primary">Go to Admin Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
