<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireCustomerLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}
csrf_verify();

$customerId  = $_SESSION['customer_id'];
$cartItemId  = (int)($_POST['cart_item_id'] ?? 0);
$quantity    = max(1, (int)($_POST['quantity'] ?? 1));

$stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND customer_id = ?");
$stmt->bind_param('iii', $quantity, $cartItemId, $customerId);
$stmt->execute();
$stmt->close();

header('Location: cart.php');
exit;
