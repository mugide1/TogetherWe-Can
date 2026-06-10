<?php
if (!isset($_SESSION)) session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Together-we-can SACCO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Wrapper for flex layout */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #1e2a3a 0%, #0f1724 100%);
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 100;
        }

        /* Collapsed Sidebar - Icons Only */
        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar.collapsed .sidebar-text {
            display: none;
        }

        .sidebar.collapsed .logo-text {
            display: none;
        }

        .sidebar.collapsed .user-info {
            display: none;
        }

        .sidebar.collapsed .sidebar-item {
            justify-content: center;
            padding: 12px 0;
        }

        .sidebar.collapsed .sidebar-item i {
            margin: 0;
            font-size: 1.3rem;
        }

        .sidebar.collapsed .toggle-btn {
            justify-content: center;
        }

        .sidebar.collapsed .sidebar-item span {
            display: none;
        }

        /* Sidebar Items Container */
        .sidebar-menu {
            flex: 1;
        }

        /* Sidebar Items */
        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #e2e8f0;
            text-decoration: none;
            transition: all 0.2s ease;
            margin: 4px 8px;
            border-radius: 10px;
            gap: 12px;
        }

        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-item i {
            width: 24px;
            font-size: 1.1rem;
            text-align: center;
        }

        .sidebar-item span {
            font-size: 14px;
            font-weight: 500;
        }

        /* Active menu item */
        .sidebar-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* Toggle Button */
        .toggle-btn {
            display: flex;
            justify-content: flex-end;
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 10px;
        }

        .toggle-btn i {
            font-size: 1.2rem;
            color: white;
            transition: transform 0.3s ease;
        }

        .toggle-btn:hover i {
            transform: scale(1.1);
        }

        /* Rotate arrow when collapsed */
        .sidebar.collapsed .toggle-btn i {
            transform: rotate(180deg);
        }

        /* Logo Area */
        .logo-area {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .logo-area h4 {
            font-size: 1.3rem;
            margin: 0;
            background: linear-gradient(135deg, #fff 0%, #a0aec0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-area small {
            font-size: 0.7rem;
            color: #a0aec0;
        }

        /* User Info */
        .user-info {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .user-name {
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .user-role {
            font-size: 0.7rem;
            color: #a0aec0;
        }

        .user-role i {
            margin-right: 5px;
        }

        /* Logout Section - Separated at Bottom */
        .sidebar-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
            padding: 15px 0;
        }

        .logout-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #f56565;
            text-decoration: none;
            transition: all 0.2s ease;
            margin: 4px 8px;
            border-radius: 10px;
            gap: 12px;
        }

        .logout-item:hover {
            background: rgba(245, 101, 101, 0.2);
            color: #ff6b6b;
            transform: translateX(5px);
        }

        .logout-item i {
            width: 24px;
            font-size: 1.1rem;
            text-align: center;
        }

        .logout-item span {
            font-size: 14px;
            font-weight: 500;
        }

        /* Main Content Area - Expands with sidebar */
        .main-content {
            flex: 1;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 20px;
            background: #f4f6f9;
            min-height: 100vh;
        }

        /* Mobile Top Bar */
        .mobile-top-bar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(135deg, #1e2a3a 0%, #0f1724 100%);
            color: white;
            z-index: 1001;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            align-items: center;
            padding: 0 15px;
        }

        .mobile-top-bar .menu-icon {
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 15px;
        }

        .mobile-top-bar .logo-text {
            font-size: 1rem;
            font-weight: bold;
            flex: 1;
        }

        .mobile-top-bar .user-icon {
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Mobile Overlay */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            cursor: pointer;
        }

        /* Scrollbar Styling */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 5px;
        }

        /* ===== MOBILE STYLES ===== */
        @media (max-width: 768px) {
            .app-wrapper {
                display: block;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                height: 100vh;
                z-index: 1002;
                transition: left 0.3s ease;
            }
            
            .sidebar.mobile-visible {
                left: 0;
            }
            
            .main-content {
                padding-top: 70px;
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .mobile-top-bar {
                display: flex;
            }
            
            .toggle-btn {
                display: none;
            }
        }
        
        /* Desktop Styles */
        @media (min-width: 769px) {
            .mobile-top-bar {
                display: none !important;
            }
            
            .mobile-overlay {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<?php if(isset($_SESSION['user_id'])): ?>

<!-- Mobile Top Bar -->
<div class="mobile-top-bar">
    <i class="fas fa-bars menu-icon" onclick="toggleMobileSidebar()"></i>
    <div class="logo-text">
        <strong>Together-we-can</strong>
        <small style="font-size: 0.7rem;">SACCO</small>
    </div>
    <div class="user-icon" onclick="window.location.href='../logout.php'">
        <i class="fas fa-sign-out-alt"></i>
    </div>
</div>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>

<div class="app-wrapper">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-chevron-left"></i>
        </div>
        
        <div class="logo-area">
            <h4 class="logo-text">Together-we-can</h4>
            <small class="logo-text">SACCO System</small>
        </div>
        
        <div class="user-info">
            <div class="user-name">
                <i class="fas fa-user-circle"></i>
                <span class="sidebar-text"> <?= $_SESSION['full_name'] ?? $_SESSION['username'] ?></span>
            </div>
            <div class="user-role">
                <i class="fas fa-shield-alt"></i>
                <span class="sidebar-text"> <?= ucfirst($_SESSION['role'] ?? 'User') ?></span>
            </div>
        </div>
        
        <!-- Menu Items Container -->
        <div class="sidebar-menu">
            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="../admin/dashboard.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
                <a href="../admin/members.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span class="sidebar-text">Members</span>
                </a>
                <a href="../admin/loans.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'loans.php' ? 'active' : '' ?>">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span class="sidebar-text">Loan Approvals</span>
                </a>
                <a href="../admin/reports.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span class="sidebar-text">Reports</span>
                </a>
                <a href="../admin/users.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i>
                    <span class="sidebar-text">User Management</span>
                </a>
            <?php elseif($_SESSION['role'] == 'cashier'): ?>
                <a href="../cashier/dashboard.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
                <a href="../cashier/deposit.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'deposit.php' ? 'active' : '' ?>">
                    <i class="fas fa-save"></i>
                    <span class="sidebar-text">Record Deposit</span>
                </a>
                <a href="../cashier/loan_payment.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'loan_payment.php' ? 'active' : '' ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span class="sidebar-text">Loan Payment</span>
                </a>
                <a href="../cashier/loan_application.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'loan_application.php' ? 'active' : '' ?>">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span class="sidebar-text">Loan Application</span>
                </a>
                <a href="../cashier/ledger.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'ledger.php' ? 'active' : '' ?>">
                    <i class="fas fa-book"></i>
                    <span class="sidebar-text">Ledger Book</span>
                </a>
                <a href="../cashier/statement.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'statement.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span class="sidebar-text">Monthly Statement</span>
                </a>
            <?php elseif($_SESSION['role'] == 'auditor'): ?>
                <a href="../auditor/reports.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span class="sidebar-text">Audit Reports</span>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Logout Separated at Bottom -->
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-item">
                <i class="fas fa-sign-out-alt"></i>
                <span class="sidebar-text">Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">

<?php endif; ?>