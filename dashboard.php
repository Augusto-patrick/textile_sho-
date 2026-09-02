<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle = 'Dashboard';

// ---- Stats (each wrapped so a missing table during early setup won't crash the page) ----
function safeCount($conn, $sql) {
    $res = $conn->query($sql);
    return $res ? (int)$res->fetch_row()[0] : 0;
}
function safeSum($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) return 0;
    $val = $res->fetch_row()[0];
    return $val ? (float)$val : 0;
}

$totalProducts   = safeCount($conn, "SELECT COUNT(*) FROM products WHERE status = 'active'");
$lowStockCount   = safeCount($conn, "SELECT COUNT(*) FROM products WHERE stock_quantity <= reorder_level AND status = 'active'");
$todaySales      = safeSum($conn, "SELECT SUM(total_amount) FROM bills WHERE DATE(created_at) = CURDATE()");
$pendingOrders   = safeCount($conn, "SELECT COUNT(*) FROM orders WHERE order_status IN ('placed','confirmed')");
$totalCustomers  = safeCount($conn, "SELECT COUNT(*) FROM customers");
$totalSuppliers  = safeCount($conn, "SELECT COUNT(*) FROM suppliers");

include __DIR__ . '/includes/header.php';
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Active Products</div>
        <div class="stat-value"><?= $totalProducts ?></div>
    </div>
    <div class="stat-card" style="border-left-color: var(--danger);">
        <div class="stat-label">Low Stock Items</div>
        <div class="stat-value"><?= $lowStockCount ?></div>
    </div>
    <div class="stat-card" style="border-left-color: var(--success);">
        <div class="stat-label">Today's Sales</div>
        <div class="stat-value">₹<?= number_format($todaySales, 2) ?></div>
    </div>
    <div class="stat-card" style="border-left-color: var(--accent);">
        <div class="stat-label">Pending Online Orders</div>
        <div class="stat-value"><?= $pendingOrders ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Customers</div>
        <div class="stat-value"><?= $totalCustomers ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Suppliers</div>
        <div class="stat-value"><?= $totalSuppliers ?></div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Quick Actions</h3>
    <a href="/textile_shop/modules/billing/pos.php" class="btn">+ New Bill</a>
    <a href="/textile_shop/modules/inventory/list.php" class="btn btn-secondary">Manage Inventory</a>
    <a href="/textile_shop/modules/billing/online_orders.php" class="btn btn-secondary">View Online Orders</a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
