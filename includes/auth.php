<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

// Check User Login Status
function is_user_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Enforce User Login
function require_user() {
    if (!is_user_logged_in()) {
        set_toast('Please log in to access your customer dashboard.', 'warning');
        redirect('/hotel_website/user/login.php');
    }
}

// Get Logged User Details from DB
function get_logged_user() {
    if (!is_user_logged_in()) return null;
    $pdo = getDBConnection();
    if (!$pdo) {
        return [
            'customer_id' => $_SESSION['user_id'] ?? 1,
            'name' => $_SESSION['user_name'] ?? 'Mihir Mashru',
            'email' => $_SESSION['user_email'] ?? 'mashrumihir15@gmail.com',
            'mobile' => '+91 9876543210',
            'gender' => 'Male'
        ];
    }
    $stmt = $pdo->prepare("SELECT * FROM `customers` WHERE `customer_id` = :id LIMIT 1");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        return [
            'customer_id' => $_SESSION['user_id'] ?? 1,
            'name' => $_SESSION['user_name'] ?? 'Mihir Mashru',
            'email' => $_SESSION['user_email'] ?? 'mashrumihir15@gmail.com',
            'mobile' => '+91 9876543210',
            'gender' => 'Male'
        ];
    }
    return $user;
}

// Check Admin Login Status
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Enforce Admin Login
function require_admin() {
    if (!is_admin_logged_in()) {
        set_toast('Administrator credentials required.', 'warning');
        redirect('/hotel_website/admin/login.php');
    }
}

// Get Logged Admin Details from DB
function get_logged_admin() {
    if (!is_admin_logged_in()) return null;
    $pdo = getDBConnection();
    if (!$pdo) {
        return [
            'admin_id' => $_SESSION['admin_id'] ?? 1,
            'username' => $_SESSION['admin_username'] ?? 'admin',
            'email' => $_SESSION['admin_email'] ?? 'admin@hotelease.com'
        ];
    }
    $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `admin_id` = :id LIMIT 1");
    $stmt->execute(['id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    if (!$admin) {
        return [
            'admin_id' => $_SESSION['admin_id'] ?? 1,
            'username' => $_SESSION['admin_username'] ?? 'admin',
            'email' => $_SESSION['admin_email'] ?? 'admin@hotelease.com'
        ];
    }
    return $admin;
}
?>
