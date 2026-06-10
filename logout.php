<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in before logging activity
if (isset($_SESSION['user_id'])) {
    // Manually log the logout activity without using logActivity function
    try {
        require_once 'config/db.php';
        
        // Insert logout activity directly
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], "User logged out"]);
    } catch(Exception $e) {
        // Ignore errors during logout
    }
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>