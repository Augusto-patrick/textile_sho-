<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Categories';

// Count products per category so we can warn before deleting one in use.
$sql = "SELECT c.category_id, c.category_name, c.description,
               COUNT(p.product_id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.category_id
        GROUP BY c.category_id
        ORDER BY c.category_name";
$result = $conn->query($sql);

include __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="margin:0;">Categories</h3>
        <a href="category_add.php" class="btn">+ Add Category</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Description</th>
                <th>Products</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['category_name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
                    <td><?= (int)$row['product_count'] ?></td>
                    <td>
                        <a href="category_edit.php?id=<?= $row['category_id'] ?>" class="btn-link">Edit</a>
                        <?php if ((int)$row['product_count'] === 0): ?>
                            &nbsp;|&nbsp;
                            <a href="category_delete.php?id=<?= (int)$row['category_id'] ?>&amp;t=<?= urlencode(csrf_token()) ?>"
                               class="btn-link"
                               style="color: var(--danger);"
                               onclick="return confirm('Delete this category?');">Delete</a>
                        <?php else: ?>
                            <span style="color:var(--text-muted);">| in use</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" style="text-align:center; color: var(--text-muted);">No categories yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
