<?php
// ============================================================
// Database connection (MySQLi)
// Adjust these to match your XAMPP MySQL setup
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'textile_shop');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Consistent timezone for created_at comparisons in PHP-side logic
date_default_timezone_set('Asia/Kolkata');
