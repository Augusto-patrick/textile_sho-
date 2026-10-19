<?php
require_once __DIR__ . '/../includes/csrf.php';
session_unset();
session_destroy();
// Expire the hardened session cookie in the browser too.
setcookie(session_name(), '', [
    'expires'  => time() - 42000,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
header('Location: index.php');
exit;
