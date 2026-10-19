<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireCustomerLogin();

$customerId = $_SESSION['customer_id'];

$stmt = $conn->prepare(
    "SELECT ci.cart_item_id, ci.quantity, p.product_id, p.product_name, p.selling_price, p.stock_quantity
     FROM cart_items ci
     JOIN products p ON ci.product_id = p.product_id
     WHERE ci.customer_id = ?"
);
$stmt->bind_param('i', $customerId);
$stmt->execute();
$cartItems = $stmt->get_result();
$stmt->close();

$items = [];
$total = 0;
while ($row = $cartItems->fetch_assoc()) {
    $row['line_total'] = $row['selling_price'] * $row['quantity'];
    $total += $row['line_total'];
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Cart | Textile Shop</title>
<link rel="stylesheet" href="/textile_shop/assets/css/style.css">
</head>
<body>
<nav class="storefront-nav">
    <div><strong>🧵 Textile Shop</strong></div>
    <div>
        <a href="index.php">Shop</a>
        <a href="orders.php">My Orders</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="margin:20px 28px;"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<div class="card" style="max-width: 700px; margin: 28px auto;">
    <h2 style="margin-top:0;">Your Cart</h2>

    <?php if (empty($items)): ?>
        <p style="color: var(--text-muted);">Your cart is empty. <a href="index.php">Go shopping</a>.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td>₹<?= number_format($item['selling_price'], 2) ?></td>
                    <td>
                        <form method="POST" action="update_cart.php" style="display:flex; gap:6px; align-items:center;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="cart_item_id" value="<?= (int)$item['cart_item_id'] ?>">
                            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_quantity'] ?>" style="width:60px;">
                            <button type="submit" class="btn-link">Update</button>
                        </form>
                    </td>
                    <td>₹<?= number_format($item['line_total'], 2) ?></td>
                    <td>
                        <a href="remove_from_cart.php?id=<?= (int)$item['cart_item_id'] ?>&amp;t=<?= urlencode(csrf_token()) ?>" style="color:var(--danger);"
                           onclick="return confirm('Remove this item?');">✕</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div style="text-align:right; margin-top:14px;">
            <strong style="font-size:1.2rem;">Total: ₹<?= number_format($total, 2) ?></strong>
        </div>

        <a href="checkout.php" class="btn" style="width:100%; text-align:center; margin-top:16px; display:block;">Proceed to Checkout</a>
    <?php endif; ?>
</div>
</body>
</html>
