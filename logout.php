<?php
require_once __DIR__ . '/includes/auth.php';
session_unset();
session_destroy();
header('Location: /textile_shop/login.php');
exit;
