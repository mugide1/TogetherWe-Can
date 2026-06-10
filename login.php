<?php
require_once 'config/db.php';
require_once 'includes/user_management.php';

if (isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'admin') header('Location: admin/dashboard.php');
    elseif($_SESSION['role'] == 'cashier') header('Location: cashier/dashboard.php');
    elseif($_SESSION['role'] == 'auditor') header('Location: auditor/reports.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    // Check if account is locked
    if ($user) {
        $lock_check = checkAccountLocked($username);
        if ($lock_check['locked']) {
            $error = "Account is locked. Try again in " . $lock_check['minutes_remaining'] . " minutes.";
        } else {
            // Check if user is inactive
            if (!$user['is_active']) {
                $error = "Your account has been deactivated. Contact an administrator.";
            } else {
                // Try both verification methods
                $valid = false;
                if (password_verify($password, $user['password'])) {
                    $valid = true;
                } elseif (md5($password) == $user['password']) {
                    $valid = true;
                }
                
                if ($valid) {
                    // Reset failed login attempts
                    resetFailedLogins($username);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // Update last login time
                    $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->execute([$user['id']]);
                    
                    // Log activity
                    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
                    $log->execute([$user['id'], "User logged in"]);
                    
                    // Redirect based on role
                    switch($user['role']) {
                        case 'admin': header('Location: admin/dashboard.php'); break;
                        case 'cashier': header('Location: cashier/dashboard.php'); break;
                        case 'auditor': header('Location: auditor/reports.php'); break;
                    }
                    exit();
                } else {
                    // Increment failed login attempts
                    incrementFailedLogins($username);
                    $error = "Invalid username or password";
                }
            }
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SACCO Login - Together-we-can</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Together-we-can SACCO</h4>
                        <small>Management System Login</small>
                    </div>
                    <div class="card-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <strong>Demo Credentials:</strong><br>
                                Username: admin | Password: admin123
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>