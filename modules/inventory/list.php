<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Products & Stock';

// ------------------------------------------------------------
// READ: fetch every product, joined with its category name.
// We use a JOIN because the "products" table only stores a
// category_id number — the actual category name lives in the
// "categories" table. JOIN lets us pull both in one query.
// ------------------------------------------------------------
$sql = "SELECT p.product_id, p.product_name, p.sku, c.category_name,
               p.selling_price, p.stock_quantity, p.reorder_level,
               p.is_online, p.status
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);

include __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="margin:0;">Products & Stock</h3>
        <a href="add.php" class="btn">+ Add Product</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Online?</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                    <td><?= htmlspecialchars($row['sku'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                    <td>₹<?= number_format($row['selling_price'], 2) ?></td>
                    <td>
                        <?= (int)$row['stock_quantity'] ?>
                        <?php if ($row['stock_quantity'] <= $row['reorder_level']): ?>
                            <span class="badge badge-danger">Low</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['is_online'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>' ?></td>
                    <td><?= $row['status'] === 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Discontinued</span>' ?></td>
                    <td>
                        <a href="edit.php?id=<?= $row['product_id'] ?>" class="btn-link">Edit</a>
                        &nbsp;|&nbsp;
                        <a href="delete.php?id=<?= (int)$row['product_id'] ?>&amp;t=<?= urlencode(csrf_token()) ?>"
                           class="btn-link"
                           style="color: var(--danger);"
                           onclick="return confirm('Delete this product? This cannot be undone.');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center; color: var(--text-muted);">No products yet. Click "+ Add Product" to create your first one.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
