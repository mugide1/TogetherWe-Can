<?php
/**
 * User Management Utility Functions
 * 
 * Provides centralized functions for:
 * - CRUD operations on users
 * - Password management
 * - Account status and locking
 * - Input validation
 * - Activity logging
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Response format: ['success' => bool, 'message' => string, 'data' => mixed]
 */

/**
 * Validate username format: alphanumeric only (3-20 chars)
 */
function validateUsername($username) {
    if (empty($username)) {
        return ['valid' => false, 'error' => 'Username is required'];
    }
    if (strlen($username) < 3) {
        return ['valid' => false, 'error' => 'Username must be at least 3 characters'];
    }
    if (strlen($username) > 20) {
        return ['valid' => false, 'error' => 'Username must not exceed 20 characters'];
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['valid' => false, 'error' => 'Username can only contain letters, numbers, and underscores'];
    }
    return ['valid' => true];
}

/**
 * Check if username already exists
 */
function usernameExists($username, $exclude_user_id = null) {
    global $pdo;
    
    if ($exclude_user_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $exclude_user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
        $stmt->execute([$username]);
    }
    
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

/**
 * Validate email format (optional field)
 */
function validateEmail($email) {
    if (empty($email)) {
        return ['valid' => true]; // Optional field
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'error' => 'Invalid email format'];
    }
    if (emailExists($email)) {
        return ['valid' => false, 'error' => 'Email already registered'];
    }
    return ['valid' => true];
}

/**
 * Check if email already exists
 */
function emailExists($email, $exclude_user_id = null) {
    global $pdo;
    
    if (empty($email)) return false;
    
    if ($exclude_user_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $exclude_user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
        $stmt->execute([$email]);
    }
    
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

/**
 * Validate full name (required, not empty)
 */
function validateFullName($full_name) {
    if (empty($full_name) || strlen(trim($full_name)) === 0) {
        return ['valid' => false, 'error' => 'Full name is required'];
    }
    if (strlen($full_name) > 100) {
        return ['valid' => false, 'error' => 'Full name must not exceed 100 characters'];
    }
    return ['valid' => true];
}

/**
 * Validate role (must be admin, cashier, or auditor)
 */
function validateRole($role) {
    $allowed_roles = ['admin', 'cashier', 'auditor'];
    if (!in_array($role, $allowed_roles)) {
        return ['valid' => false, 'error' => 'Invalid role. Allowed: admin, cashier, auditor'];
    }
    return ['valid' => true];
}

/**
 * Create a new user
 */
function createUser($username, $password, $full_name, $role, $email = null, $requester_id = null) {
    global $pdo;
    
    // Validate all inputs
    $username_validation = validateUsername($username);
    if (!$username_validation['valid']) {
        return ['success' => false, 'message' => $username_validation['error']];
    }
    
    if (usernameExists($username)) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    $fullname_validation = validateFullName($full_name);
    if (!$fullname_validation['valid']) {
        return ['success' => false, 'message' => $fullname_validation['error']];
    }
    
    $email_validation = validateEmail($email);
    if (!$email_validation['valid']) {
        return ['success' => false, 'message' => $email_validation['error']];
    }
    
    $role_validation = validateRole($role);
    if (!$role_validation['valid']) {
        return ['success' => false, 'message' => $role_validation['error']];
    }
    
    if (empty($password) || strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, full_name, role, email, is_active, failed_login_attempts, password_changed_at)
            VALUES (?, ?, ?, ?, ?, 1, 0, NOW())
        ");
        
        $stmt->execute([$username, $password_hash, $full_name, $role, $email]);
        $user_id = $pdo->lastInsertId();
        
        // Log activity
        if ($requester_id) {
            logActivity($requester_id, "Created user: $username (ID: $user_id, Role: $role)");
        }
        
        return [
            'success' => true,
            'message' => 'User created successfully',
            'data' => ['user_id' => $user_id, 'username' => $username]
        ];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Get user by ID
 */
function getUserById($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, username, full_name, email, role, is_active, last_login, password_changed_at, locked_until FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    return ['success' => true, 'data' => $user];
}

/**
 * Get all users
 */
function getAllUsers() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT id, username, full_name, email, role, is_active, last_login, password_changed_at, locked_until, failed_login_attempts FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll();
    
    return ['success' => true, 'data' => $users];
}

/**
 * Update user details (excludes username)
 */
function updateUser($user_id, $full_name, $email, $role, $requester_id = null) {
    global $pdo;
    
    // Prevent self-modification of critical fields
    if ($user_id == $requester_id && $role !== $_SESSION['role']) {
        return ['success' => false, 'message' => 'You cannot change your own role'];
    }
    
    // Validate inputs
    $fullname_validation = validateFullName($full_name);
    if (!$fullname_validation['valid']) {
        return ['success' => false, 'message' => $fullname_validation['error']];
    }
    
    $email_validation = validateEmail($email);
    if (!$email_validation['valid']) {
        return ['success' => false, 'message' => $email_validation['error']];
    }
    
    $role_validation = validateRole($role);
    if (!$role_validation['valid']) {
        return ['success' => false, 'message' => $role_validation['error']];
    }
    
    // Check if user exists
    $user = getUserById($user_id);
    if (!$user['success']) {
        return $user;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $role, $user_id]);
        
        // Log activity
        if ($requester_id) {
            logActivity($requester_id, "Updated user: ID $user_id (role changed to $role)");
        }
        
        return ['success' => true, 'message' => 'User updated successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Delete user (soft delete using is_active flag)
 */
function deleteUser($user_id, $requester_id = null) {
    global $pdo;
    
    // Prevent self-deletion
    if ($user_id == $requester_id) {
        return ['success' => false, 'message' => 'You cannot delete your own account'];
    }
    
    // Check if user exists
    $user = getUserById($user_id);
    if (!$user['success']) {
        return $user;
    }
    
    try {
        // Soft delete: mark as inactive instead of removing from DB
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Log activity
        if ($requester_id) {
            logActivity($requester_id, "Deleted (deactivated) user: ID $user_id ({$user['data']['username']})");
        }
        
        return ['success' => true, 'message' => 'User deleted successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Reset user password
 */
function resetPassword($user_id, $new_password, $requester_id = null) {
    global $pdo;
    
    // Validate password
    if (empty($new_password) || strlen($new_password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }
    
    // Check if user exists
    $user = getUserById($user_id);
    if (!$user['success']) {
        return $user;
    }
    
    // Hash new password
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?");
        $stmt->execute([$password_hash, $user_id]);
        
        // Reset failed login attempts
        $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Log activity
        if ($requester_id) {
            logActivity($requester_id, "Reset password for user: ID $user_id ({$user['data']['username']})");
        }
        
        return ['success' => true, 'message' => 'Password reset successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Toggle user account status (enable/disable)
 */
function toggleUserStatus($user_id, $is_active, $requester_id = null) {
    global $pdo;
    
    // Check if user exists
    $user = getUserById($user_id);
    if (!$user['success']) {
        return $user;
    }
    
    // Prevent disabling own account
    if ($user_id == $requester_id && !$is_active) {
        return ['success' => false, 'message' => 'You cannot disable your own account'];
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$is_active ? 1 : 0, $user_id]);
        
        $status_text = $is_active ? 'enabled' : 'disabled';
        
        // Log activity
        if ($requester_id) {
            logActivity($requester_id, "User $status_text: ID $user_id ({$user['data']['username']})");
        }
        
        return ['success' => true, 'message' => "User $status_text successfully"];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Lock user account for specified duration
 */
function lockUserAccount($user_id, $minutes = 30, $requester_id = null) {
    global $pdo;
    
    // Check if user exists
    $user = getUserById($user_id);
    if (!$user['success']) {
        return $user;
    }
    
    $locked_until = date('Y-m-d H:i:s', strtotime("+$minutes minutes"));
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET locked_until = ? WHERE id = ?");
        $stmt->execute([$locked_until, $user_id]);
        
        // Log activity
        if ($requester_id) {
            logActivity($requester_id, "Locked account: ID $user_id ({$user['data']['username']}) for $minutes minutes");
        }
        
        return ['success' => true, 'message' => "User account locked for $minutes minutes"];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Unlock user account
 */
function unlockUserAccount($user_id, $requester_id = null) {
    global $pdo;
    
    // Check if user exists
    $user = getUserById($user_id);
    if (!$user['success']) {
        return $user;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET locked_until = NULL, failed_login_attempts = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Log activity
        if ($requester_id) {
            logActivity($requester_id, "Unlocked account: ID $user_id ({$user['data']['username']})");
        }
        
        return ['success' => true, 'message' => 'User account unlocked'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Increment failed login attempts
 */
function incrementFailedLogins($username) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE username = ?");
        $stmt->execute([$username]);
        
        // Check if we've reached 3 failed attempts
        $stmt = $pdo->prepare("SELECT failed_login_attempts FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        
        if ($result && $result['failed_login_attempts'] >= 3) {
            // Auto-lock for 30 minutes
            lockAccountByUsername($username, 30);
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if account is locked
 */
function checkAccountLocked($username) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT locked_until FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return ['locked' => false];
        }
        
        if ($result['locked_until'] && strtotime($result['locked_until']) > time()) {
            $minutes_remaining = ceil((strtotime($result['locked_until']) - time()) / 60);
            return ['locked' => true, 'until' => $result['locked_until'], 'minutes_remaining' => $minutes_remaining];
        }
        
        // Unlock if time has passed
        $stmt = $pdo->prepare("UPDATE users SET locked_until = NULL WHERE username = ? AND locked_until < NOW()");
        $stmt->execute([$username]);
        
        return ['locked' => false];
    } catch (PDOException $e) {
        return ['locked' => false];
    }
}

/**
 * Reset failed login attempts
 */
function resetFailedLogins($username) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0 WHERE username = ?");
        $stmt->execute([$username]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Lock account by username (used internally)
 */
function lockAccountByUsername($username, $minutes = 30) {
    global $pdo;
    
    $locked_until = date('Y-m-d H:i:s', strtotime("+$minutes minutes"));
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET locked_until = ? WHERE username = ?");
        $stmt->execute([$locked_until, $username]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get user activity logs
 */
function getUserActivityLogs($user_id, $limit = 20) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT al.id, al.action, al.timestamp 
            FROM activity_logs al 
            WHERE al.user_id = ? 
            ORDER BY al.timestamp DESC 
            LIMIT ?
        ");
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $logs = $stmt->fetchAll();
        return ['success' => true, 'data' => $logs];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Generate random password
 */
function generateTempPassword($length = 12) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}
?>
