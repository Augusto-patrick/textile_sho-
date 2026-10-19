<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Suppliers';

$sql = "SELECT s.supplier_id, s.supplier_name, s.contact_person, s.phone, s.email,
               s.address, s.gst_number, s.created_at,
               COUNT(p.product_id) AS product_count
        FROM suppliers s
        LEFT JOIN products p ON p.supplier_id = s.supplier_id
        GROUP BY s.supplier_id
        ORDER BY s.supplier_name";
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
        <h3 style="margin:0;">Suppliers</h3>
        <a href="add.php" class="btn">+ Add Supplier</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Supplier</th>
                <th>Contact Person</th>
                <th>Phone</th>
                <th>Email</th>
                <th>GST</th>
                <th>Products</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($row['supplier_name']) ?></strong>
                        <?php if ($row['address']): ?>
                            <div style="color:var(--text-muted); font-size:0.82rem;"><?= htmlspecialchars($row['address']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['contact_person'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['gst_number'] ?? '-') ?></td>
                    <td><?= (int)$row['product_count'] ?></td>
                    <td>
                        <a href="edit.php?id=<?= $row['supplier_id'] ?>" class="btn-link">Edit</a>
                        <?php if ((int)$row['product_count'] === 0): ?>
                            &nbsp;|&nbsp;
                            <a href="delete.php?id=<?= (int)$row['supplier_id'] ?>&amp;t=<?= urlencode(csrf_token()) ?>"
                               class="btn-link"
                               style="color: var(--danger);"
                               onclick="return confirm('Delete this supplier? This cannot be undone.');">Delete</a>
                        <?php else: ?>
                            <span style="color:var(--text-muted);">| in use</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center; color: var(--text-muted);">No suppliers yet. Add your first one.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
