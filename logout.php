<?php
require_once __DIR__ . '/includes/auth.php';
session_unset();
session_destroy();
// Also expire the session cookie in the browser.
setcookie(session_name(), '', [
    'expires'  => time() - 42000,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
header('Location: /textile_shop/login.php');
exit;
