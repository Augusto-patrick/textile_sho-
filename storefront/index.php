<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

$products = [];
$result = $conn->query("SELECT product_id, product_name, selling_price, image_path FROM products WHERE is_online = 1 AND status = 'active' ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop | Textile Shop</title>
<link rel="stylesheet" href="/textile_shop/assets/css/style.css">
</head>
<body>
<nav class="storefront-nav">
    <div><strong>🧵 Textile Shop</strong></div>
    <div>
        <?php if (isset($_SESSION['customer_id'])): ?>
            <a href="/textile_shop/storefront/cart.php">Cart</a>
            <a href="/textile_shop/storefront/orders.php">My Orders</a>
            <a href="/textile_shop/storefront/logout.php">Logout</a>
        <?php else: ?>
            <a href="/textile_shop/storefront/login.php">Login</a>
            <a href="/textile_shop/storefront/register.php">Sign Up</a>
        <?php endif; ?>
    </div>
</nav>

<?php if (empty($products)): ?>
    <div class="card" style="margin:28px;">
        <p style="color: var(--text-muted); margin:0;">No products are listed online yet. Once the inventory module marks items as "online", they'll show up here.</p>
    </div>
<?php else: ?>
<div class="product-grid">
    <?php foreach ($products as $p): ?>
    <div class="product-card">
        <img src="<?= $p['image_path'] ? htmlspecialchars($p['image_path']) : '/textile_shop/assets/img/placeholder.png' ?>" alt="<?= htmlspecialchars($p['product_name']) ?>">
        <div class="p-body">
            <div><?= htmlspecialchars($p['product_name']) ?></div>
            <div class="p-price">₹<?= number_format($p['selling_price'], 2) ?></div>
            <a href="/textile_shop/storefront/product.php?id=<?= $p['product_id'] ?>" class="btn">View</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</body>
</html>
