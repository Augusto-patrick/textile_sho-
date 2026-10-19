<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Bill History';

// ------------------------------------------------------------
// For each bill, count how many items it has and confirm the total.
// We JOIN to users to show which staff member created the bill.
// ------------------------------------------------------------
$sql = "SELECT b.bill_id, b.bill_number, b.customer_id, b.walkin_name, b.total_amount,
               b.payment_method, b.created_at, u.full_name AS staff_name,
               (SELECT COUNT(*) FROM bill_items bi WHERE bi.bill_id = b.bill_id) AS item_count
        FROM bills b
        LEFT JOIN users u ON b.user_id = u.user_id
        ORDER BY b.created_at DESC";
$result = $conn->query($sql);

include __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-top:0;">Bill History</h3>
    <table>
        <thead>
            <tr>
                <th>Bill #</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Billed By</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['bill_number']) ?></td>
                    <td><?= htmlspecialchars($row['walkin_name'] ?: 'Walk-in') ?></td>
                    <td><?= (int)$row['item_count'] ?></td>
                    <td>₹<?= number_format($row['total_amount'], 2) ?></td>
                    <td><?= strtoupper(htmlspecialchars($row['payment_method'])) ?></td>
                    <td><?= htmlspecialchars($row['staff_name'] ?? '-') ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                    <td>
                        <a href="view_bill.php?id=<?= $row['bill_id'] ?>" class="btn-link">View</a>
                        &nbsp;|&nbsp;
                        <a href="edit_bill.php?id=<?= $row['bill_id'] ?>" class="btn-link">Edit</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center; color:var(--text-muted);">No bills yet. Create one from "New Bill (POS)".</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
