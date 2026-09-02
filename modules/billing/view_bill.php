<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Bill Detail';
$billId = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT b.*, u.full_name AS staff_name FROM bills b
     LEFT JOIN users u ON b.user_id = u.user_id WHERE b.bill_id = ?"
);
$stmt->bind_param('i', $billId);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bill) {
    header('Location: history.php');
    exit;
}

$stmt = $conn->prepare(
    "SELECT bi.*, p.product_name FROM bill_items bi
     JOIN products p ON bi.product_id = p.product_id WHERE bi.bill_id = ?"
);
$stmt->bind_param('i', $billId);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 600px;">
    <h3 style="margin-top:0;">Bill <?= htmlspecialchars($bill['bill_number']) ?></h3>
    <p style="color: var(--text-muted); margin-top:-10px;">
        <?= date('d M Y, h:i A', strtotime($bill['created_at'])) ?> &middot; Billed by <?= htmlspecialchars($bill['staff_name'] ?? '-') ?>
    </p>
    <p><strong>Customer:</strong> <?= htmlspecialchars($bill['walkin_name'] ?: 'Walk-in') ?> <?= $bill['walkin_phone'] ? '(' . htmlspecialchars($bill['walkin_phone']) . ')' : '' ?></p>

    <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr></thead>
        <tbody>
        <?php while ($item = $items->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= (int)$item['quantity'] ?></td>
                <td>₹<?= number_format($item['unit_price'], 2) ?></td>
                <td>₹<?= number_format($item['line_total'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div style="text-align:right; margin-top:14px;">
        <div>Subtotal: ₹<?= number_format($bill['subtotal'], 2) ?></div>
        <div style="font-size:1.2rem; font-weight:700;">Total: ₹<?= number_format($bill['total_amount'], 2) ?></div>
        <div style="color:var(--text-muted); font-size:0.85rem;">Paid via <?= strtoupper(htmlspecialchars($bill['payment_method'])) ?></div>
    </div>

    <a href="history.php" class="btn btn-secondary" style="margin-top:16px;">Back to History</a>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
