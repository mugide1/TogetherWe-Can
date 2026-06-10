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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SACCO Login - Together-we-can</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1f3c88 0%, #0f224f 55%, #0b1330 100%);
            color: #fff;
        }
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 1rem;
            overflow: hidden;
        }
        .login-card .card-header {
            background: linear-gradient(135deg, #0066cc, #004a9f);
            border: none;
            padding: 1.6rem 1.25rem;
        }
        .login-card .card-header h4 {
            margin-bottom: 0.35rem;
            font-size: 1.45rem;
            letter-spacing: 0.04em;
        }
        .login-card .card-body {
            background: #ffffff;
            color: #202124;
            padding: 1.75rem;
        }
        .login-card .form-control {
            border-radius: 0.75rem;
            padding: 0.95rem 1rem;
            box-shadow: none;
        }
        .login-btn {
            border-radius: 0.85rem;
            padding: 0.95rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            box-shadow: 0 12px 24px rgba(0, 102, 204, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(0, 102, 204, 0.24);
        }
        .login-card .alert {
            margin-bottom: 1rem;
        }
        .login-footer-text {
            font-size: 0.92rem;
            color: #6c757d;
        }
        @media (max-width: 575.98px) {
            .login-wrapper {
                padding: 1.25rem 0;
            }
            .login-card {
                border-radius: 1rem;
            }
            .login-card .card-body {
                padding: 1.5rem;
            }
            .login-card .card-header {
                padding: 1.25rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card card shadow">
            <div class="card-header text-center text-white">
                <h4>Together-we-can SACCO</h4>
                <small>Management System Login</small>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 login-btn">Login</button>
                </form>
                <div class="mt-3 text-center login-footer-text">
                    <strong>Demo Credentials:</strong><br>
                    Username: admin | Password: admin123
                </div>
            </div>
        </div>
    </div>
</body>
</html>