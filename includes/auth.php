<?php
require_once __DIR__ . '/../config/db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
        die("Access denied. $role privileges required.");
    }
}

function logActivity($user_id, $action) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
    $stmt->execute([$user_id, $action]);
}
?>