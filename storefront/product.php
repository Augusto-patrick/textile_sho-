<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$productId = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT product_id, product_name, description, selling_price, stock_quantity, image_path
     FROM products WHERE product_id = ? AND is_online = 1 AND status = 'active'"
);
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['product_name']) ?> | Textile Shop</title>
<link rel="stylesheet" href="/textile_shop/assets/css/style.css">
</head>
<body>
<nav class="storefront-nav">
    <div><strong>🧵 Textile Shop</strong></div>
    <div>
        <a href="index.php">Shop</a>
        <?php if (isset($_SESSION['customer_id'])): ?>
            <a href="cart.php">Cart</a>
            <a href="orders.php">My Orders</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Sign Up</a>
        <?php endif; ?>
    </div>
</nav>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="margin:20px 28px;"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<div style="max-width: 700px; margin: 28px auto; display:grid; grid-template-columns: 280px 1fr; gap: 28px; min-width:0;">
    <img src="<?= $product['image_path'] ? htmlspecialchars($product['image_path']) : '/textile_shop/assets/img/placeholder.png' ?>"
         alt="<?= htmlspecialchars($product['product_name']) ?>"
         style="width:100%; border-radius:10px; background:#eee;">

    <div>
        <h2><?= htmlspecialchars($product['product_name']) ?></h2>
        <p class="p-price" style="font-size:1.4rem;">₹<?= number_format($product['selling_price'], 2) ?></p>
        <p style="color: var(--text-muted);"><?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?></p>
        <p><?= $product['stock_quantity'] > 0 ? "In stock ({$product['stock_quantity']} available)" : '<span style="color:var(--danger);">Out of stock</span>' ?></p>

        <?php if (isset($_SESSION['customer_id'])): ?>
            <?php if ($product['stock_quantity'] > 0): ?>
                <form method="POST" action="add_to_cart.php" style="display:flex; gap:10px; align-items:center; margin-top:16px;">
                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                    <label for="quantity" style="margin:0;">Qty:</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?>" style="width:80px;">
                    <button type="submit" class="btn">Add to Cart</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <p><a href="login.php" class="btn">Login to purchase</a></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
