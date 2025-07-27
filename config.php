<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fishing_store');

// Create database connection
function getConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function formatPrice($price) {
    return number_format($price, 2) . ' บาท';
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}
?>
