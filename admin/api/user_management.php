<?php
/**
 * User Management API Endpoint
 * 
 * Handles AJAX requests for user management operations
 * Enforces admin role requirement
 * Returns JSON responses
 */

require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/user_management.php';

// Set JSON header
header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action parameter required']);
    exit();
}

// Route actions
switch ($action) {
    case 'create':
        handleCreateUser();
        break;
    case 'update':
        handleUpdateUser();
        break;
    case 'delete':
        handleDeleteUser();
        break;
    case 'toggle_status':
        handleToggleStatus();
        break;
    case 'reset_password':
        handleResetPassword();
        break;
    case 'lock_account':
        handleLockAccount();
        break;
    case 'unlock_account':
        handleUnlockAccount();
        break;
    case 'get_all':
        handleGetAll();
        break;
    case 'get_logs':
        handleGetLogs();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
}

function handleCreateUser() {
    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;
    $full_name = $_POST['full_name'] ?? null;
    $role = $_POST['role'] ?? null;
    $email = $_POST['email'] ?? null;
    
    if (!$username || !$password || !$full_name || !$role) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    $result = createUser($username, $password, $full_name, $role, $email, $_SESSION['user_id']);
    echo json_encode($result);
}

function handleUpdateUser() {
    $user_id = $_POST['user_id'] ?? null;
    $full_name = $_POST['full_name'] ?? null;
    $email = $_POST['email'] ?? null;
    $role = $_POST['role'] ?? null;
    
    if (!$user_id || !$full_name || !$role) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    $result = updateUser($user_id, $full_name, $email, $role, $_SESSION['user_id']);
    echo json_encode($result);
}

function handleDeleteUser() {
    $user_id = $_POST['user_id'] ?? null;
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit();
    }
    
    $result = deleteUser($user_id, $_SESSION['user_id']);
    echo json_encode($result);
}

function handleToggleStatus() {
    $user_id = $_POST['user_id'] ?? null;
    $is_active = $_POST['is_active'] ?? null;
    
    if (!$user_id || $is_active === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    $is_active = filter_var($is_active, FILTER_VALIDATE_BOOLEAN);
    $result = toggleUserStatus($user_id, $is_active, $_SESSION['user_id']);
    echo json_encode($result);
}

function handleResetPassword() {
    $user_id = $_POST['user_id'] ?? null;
    $new_password = $_POST['new_password'] ?? null;
    
    if (!$user_id || !$new_password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    $result = resetPassword($user_id, $new_password, $_SESSION['user_id']);
    echo json_encode($result);
}

function handleLockAccount() {
    $user_id = $_POST['user_id'] ?? null;
    $minutes = $_POST['minutes'] ?? 30;
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit();
    }
    
    $minutes = max(1, min(1440, intval($minutes))); // 1-1440 minutes (1 day max)
    $result = lockUserAccount($user_id, $minutes, $_SESSION['user_id']);
    echo json_encode($result);
}

function handleUnlockAccount() {
    $user_id = $_POST['user_id'] ?? null;
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit();
    }
    
    $result = unlockUserAccount($user_id, $_SESSION['user_id']);
    echo json_encode($result);
}

function handleGetAll() {
    $result = getAllUsers();
    echo json_encode($result);
}

function handleGetLogs() {
    $user_id = $_GET['user_id'] ?? null;
    $limit = $_GET['limit'] ?? 20;
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit();
    }
    
    $limit = max(1, min(100, intval($limit)));
    $result = getUserActivityLogs($user_id, $limit);
    echo json_encode($result);
}
?>
