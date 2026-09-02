<?php
$currentPage = basename($_SERVER['PHP_SELF']);
function navClass($page, $current) {
    return $page === $current ? 'nav-link active' : 'nav-link';
}
?>
<nav class="sidebar">
    <div class="sidebar-brand">🧵 Textile Shop</div>
    <a class="<?= navClass('dashboard.php', $currentPage) ?>" href="/textile_shop/dashboard.php">Dashboard</a>

    <div class="nav-group-label">Inventory</div>
    <a class="<?= navClass('list.php', $currentPage) ?>" href="/textile_shop/modules/inventory/list.php">Products & Stock</a>
    <a class="<?= navClass('categories.php', $currentPage) ?>" href="/textile_shop/modules/inventory/categories.php">Categories</a>

    <div class="nav-group-label">Sales</div>
    <a class="<?= navClass('pos.php', $currentPage) ?>" href="/textile_shop/modules/billing/pos.php">New Bill (POS)</a>
    <a class="<?= navClass('history.php', $currentPage) ?>" href="/textile_shop/modules/billing/history.php">Bill History</a>
    <a class="<?= navClass('online_orders.php', $currentPage) ?>" href="/textile_shop/modules/billing/online_orders.php">Online Orders</a>

    <div class="nav-group-label">People</div>
    <a class="<?= navClass('list.php', $currentPage) ?>" href="/textile_shop/modules/customers/list.php">Customers</a>
    <a class="<?= navClass('list.php', $currentPage) ?>" href="/textile_shop/modules/suppliers/list.php">Suppliers</a>

    <div class="nav-group-label">Insights</div>
    <a class="<?= navClass('dashboard.php', $currentPage) ?>" href="/textile_shop/modules/reports/dashboard.php">Reports</a>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="nav-group-label">Admin</div>
    <a class="<?= navClass('staff.php', $currentPage) ?>" href="/textile_shop/modules/admin/staff.php">Manage Staff</a>
    <?php endif; ?>

    <div class="nav-group-label">Storefront</div>
    <a class="nav-link" href="/textile_shop/storefront/index.php" target="_blank">View Online Store ↗</a>
</nav>
