<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Online Orders';

$validStatuses = ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled'];
$filter = $_GET['status'] ?? '';
if (!in_array($filter, $validStatuses, true)) {
    $filter = '';
}

$sql = "SELECT o.order_id, o.order_number, o.total_amount, o.payment_method, o.payment_status,
               o.order_status, o.created_at, c.full_name AS customer_name,
               COUNT(oi.order_item_id) AS item_count
        FROM orders o
        JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN order_items oi ON oi.order_id = o.order_id";

if ($filter !== '') {
    $sql .= " WHERE o.order_status = ?";
}
$sql .= " GROUP BY o.order_id ORDER BY o.created_at DESC";

if ($filter !== '') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $filter);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query($sql);
}

function orderStatusBadge($s) {
    $map = ['placed' => 'badge-warning', 'confirmed' => 'badge-warning', 'shipped' => 'badge-success',
            'delivered' => 'badge-success', 'cancelled' => 'badge-danger'];
    $cls = $map[$s] ?? 'badge-warning';
    return '<span class="badge ' . $cls . '">' . ucfirst($s) . '</span>';
}
function payStatusBadge($s) {
    $map = ['paid' => 'badge-success', 'pending' => 'badge-warning', 'failed' => 'badge-danger'];
    $cls = $map[$s] ?? 'badge-warning';
    return '<span class="badge ' . $cls . '">' . ucfirst($s) . '</span>';
}

include __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
        <h3 style="margin:0;">Online Orders</h3>
        <div style="font-size:0.88rem;">
            <a href="online_orders.php" class="btn-link" style="<?= $filter === '' ? 'font-weight:700; text-decoration:underline;' : '' ?>">All</a>
            <?php foreach ($validStatuses as $s): ?>
                &nbsp;|&nbsp;
                <a href="online_orders.php?status=<?= $s ?>" class="btn-link"
                   style="<?= $filter === $s ? 'font-weight:700; text-decoration:underline;' : '' ?>"><?= ucfirst($s) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['order_number']) ?></strong></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><?= (int)$row['item_count'] ?></td>
                    <td>₹<?= number_format($row['total_amount'], 2) ?></td>
                    <td>
                        <?= strtoupper(htmlspecialchars($row['payment_method'])) ?>
                        <?= payStatusBadge($row['payment_status']) ?>
                    </td>
                    <td><?= orderStatusBadge($row['order_status']) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                    <td><a href="order_view.php?id=<?= $row['order_id'] ?>" class="btn-link">View</a></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center; color: var(--text-muted);"><?= $filter !== '' ? 'No ' . htmlspecialchars($filter) . ' orders.' : 'No online orders yet.' ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
