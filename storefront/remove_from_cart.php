<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireCustomerLogin();

$customerId = $_SESSION['customer_id'];
$cartItemId = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ? AND customer_id = ?");
$stmt->bind_param('ii', $cartItemId, $customerId);
$stmt->execute();
$stmt->close();

header('Location: cart.php');
exit;
