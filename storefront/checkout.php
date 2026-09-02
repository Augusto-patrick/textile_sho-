<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireCustomerLogin();

$customerId = $_SESSION['customer_id'];

$stmt = $conn->prepare(
    "SELECT ci.quantity, p.product_name, p.selling_price, p.stock_quantity
     FROM cart_items ci JOIN products p ON ci.product_id = p.product_id
     WHERE ci.customer_id = ?"
);
$stmt->bind_param('i', $customerId);
$stmt->execute();
$cartItems = $stmt->get_result();
$stmt->close();

$items = [];
$total = 0;
while ($row = $cartItems->fetch_assoc()) {
    $lineTotal = $row['selling_price'] * $row['quantity'];
    $total += $lineTotal;
    $items[] = $row;
}

if (empty($items)) {
    header('Location: cart.php');
    exit;
}

$stmt = $conn->prepare("SELECT full_name, address, city, pincode, phone FROM customers WHERE customer_id = ?");
$stmt->bind_param('i', $customerId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

$shippingFee = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout | Textile Shop</title>
<link rel="stylesheet" href="/textile_shop/assets/css/style.css">
</head>
<body>
<nav class="storefront-nav">
    <div><strong>🧵 Textile Shop</strong></div>
    <div>
        <a href="index.php">Shop</a>
        <a href="cart.php">Cart</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error" style="margin:20px 28px;"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card" style="max-width: 600px; margin: 28px auto;">
    <h2 style="margin-top:0;">Checkout</h2>

    <h4>Order Summary</h4>
    <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>₹<?= number_format($item['selling_price'] * $item['quantity'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div style="text-align:right; margin: 10px 0 20px;">
        <div>Subtotal: ₹<?= number_format($total, 2) ?></div>
        <div>Shipping: ₹<?= number_format($shippingFee, 2) ?></div>
        <strong style="font-size:1.2rem;">Total: ₹<?= number_format($total + $shippingFee, 2) ?></strong>
    </div>

    <form method="POST" action="place_order.php">
        <h4>Shipping Address</h4>
        <div class="form-group">
            <label>Full Address *</label>
            <textarea name="shipping_address" rows="3" required><?= htmlspecialchars(trim(($customer['address'] ?? '') . ', ' . ($customer['city'] ?? '') . ' ' . ($customer['pincode'] ?? ''), ', ')) ?></textarea>
        </div>

        <div class="form-group">
            <label>Payment Method</label>
            <select name="payment_method">
                <option value="cod">Cash on Delivery</option>
                <option value="upi">UPI</option>
                <option value="card">Card</option>
            </select>
        </div>

        <button type="submit" class="btn" style="width:100%;">Place Order</button>
    </form>
</div>
</body>
</html>
