<?php
// ============================================================
// Staff/Admin authentication helpers
// Include this at the top of any protected staff page.
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// Call at the top of any page that requires staff/admin login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /textile_shop/login.php');
        exit;
    }
}

// Call at the top of any page that requires admin-only access
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        die('Access denied: admin privileges required.');
    }
}

// Call at the top of any customer-only storefront page
function requireCustomerLogin() {
    if (!isset($_SESSION['customer_id'])) {
        header('Location: /textile_shop/storefront/login.php');
        exit;
    }
}
