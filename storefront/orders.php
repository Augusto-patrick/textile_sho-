<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireCustomerLogin();

$customerId = $_SESSION['customer_id'];

$stmt = $conn->prepare(
    "SELECT order_id, order_number, total_amount, order_status, payment_status, created_at
     FROM orders WHERE customer_id = ? ORDER BY created_at DESC"
);
$stmt->bind_param('i', $customerId);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders | Textile Shop</title>
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

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="margin:20px 28px;"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<div class="card" style="max-width: 700px; margin: 28px auto;">
    <h2 style="margin-top:0;">My Orders</h2>

    <table>
        <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php if ($orders->num_rows > 0): ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_number']) ?></td>
                    <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                    <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                    <td><span class="badge badge-success"><?= ucfirst(htmlspecialchars($order['order_status'])) ?></span></td>
                    <td><a href="order_detail.php?id=<?= $order['order_id'] ?>" class="btn-link">View</a></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">You haven't placed any orders yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
