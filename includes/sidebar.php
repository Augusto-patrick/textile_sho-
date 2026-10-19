<?php
// Compare against the full script path (e.g. /textile_shop/modules/inventory/list.php)
// so that three different "list.php" pages don't all light up as active at once.
$currentPage = $_SERVER['PHP_SELF'];
function navClass($path, $current) {
    return $path === $current ? 'nav-link active' : 'nav-link';
}
?>
<nav class="sidebar">
    <div class="sidebar-brand">🧵 Textile Shop</div>
    <a class="<?= navClass('/textile_shop/dashboard.php', $currentPage) ?>" href="/textile_shop/dashboard.php">Dashboard</a>

    <div class="nav-group-label">Inventory</div>
    <a class="<?= navClass('/textile_shop/modules/inventory/list.php', $currentPage) ?>" href="/textile_shop/modules/inventory/list.php">Products & Stock</a>
    <a class="<?= navClass('/textile_shop/modules/inventory/categories.php', $currentPage) ?>" href="/textile_shop/modules/inventory/categories.php">Categories</a>

    <div class="nav-group-label">Sales</div>
    <a class="<?= navClass('/textile_shop/modules/billing/pos.php', $currentPage) ?>" href="/textile_shop/modules/billing/pos.php">New Bill (POS)</a>
    <a class="<?= navClass('/textile_shop/modules/billing/history.php', $currentPage) ?>" href="/textile_shop/modules/billing/history.php">Bill History</a>
    <a class="<?= navClass('/textile_shop/modules/billing/online_orders.php', $currentPage) ?>" href="/textile_shop/modules/billing/online_orders.php">Online Orders</a>

    <div class="nav-group-label">People</div>
    <a class="<?= navClass('/textile_shop/modules/customers/list.php', $currentPage) ?>" href="/textile_shop/modules/customers/list.php">Customers</a>
    <a class="<?= navClass('/textile_shop/modules/suppliers/list.php', $currentPage) ?>" href="/textile_shop/modules/suppliers/list.php">Suppliers</a>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="nav-group-label">Admin</div>
    <a class="<?= navClass('/textile_shop/modules/admin/staff.php', $currentPage) ?>" href="/textile_shop/modules/admin/staff.php">Manage Staff</a>
    <?php endif; ?>

    <div class="nav-group-label">Storefront</div>
    <a class="nav-link" href="/textile_shop/storefront/index.php" target="_blank">View Online Store ↗</a>
</nav>
