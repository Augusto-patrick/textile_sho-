<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireCustomerLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
csrf_verify();

$customerId = $_SESSION['customer_id'];
$productId  = (int)$_POST['product_id'];
$quantity   = max(1, (int)$_POST['quantity']);

$stmt = $conn->prepare("SELECT product_name, stock_quantity FROM products WHERE product_id = ? AND is_online = 1 AND status = 'active'");
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: index.php');
    exit;
}

$quantity = min($quantity, $product['stock_quantity']);

$stmt = $conn->prepare(
    "INSERT INTO cart_items (customer_id, product_id, quantity)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)"
);
$stmt->bind_param('iii', $customerId, $productId, $quantity);
$stmt->execute();
$stmt->close();

header('Location: cart.php?msg=' . urlencode($product['product_name'] . ' added to cart.'));
exit;
