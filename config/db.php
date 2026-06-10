<?php

// Set timezone to East African Time
date_default_timezone_set('Africa/Nairobi');
// Alternative: 'Africa/Kampala' also works

$host = 'localhost';
$dbname = 'together_sacco';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Only start session if not already active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>