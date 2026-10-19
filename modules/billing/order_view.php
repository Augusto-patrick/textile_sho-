<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Order Detail';
$error = '';

$validStatuses   = ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled'];
$validPayStatus  = ['pending', 'paid', 'failed'];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: online_orders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $newStatus  = $_POST['order_status'] ?? '';
    $newPayment = $_POST['payment_status'] ?? '';

    if (!in_array($newStatus, $validStatuses, true) || !in_array($newPayment, $validPayStatus, true)) {
        header('Location: order_view.php?id=' . $id . '&error=' . urlencode('Invalid status value.'));
        exit;
    }
    // Current state (for transition rules)
    $stmt = $conn->prepare("SELECT order_status, order_number FROM orders WHERE order_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$current) {
        header('Location: online_orders.php');
        exit;
    }

    $oldStatus = $current['order_status'];
    $statusChanged = ($newStatus !== $oldStatus);
    $invalid = null;

    if ($statusChanged && $oldStatus === 'cancelled') {
        $invalid = 'Cancelled orders cannot be reactivated.';
    } elseif ($statusChanged && $newStatus === 'cancelled' && !in_array($oldStatus, ['placed', 'confirmed', 'shipped'], true)) {
        $invalid = 'A delivered order cannot be cancelled.';
    }

    if ($invalid !== null) {
        header('Location: order_view.php?id=' . $id . '&error=' . urlencode($invalid));
        exit;
    }

    $conn->begin_transaction();
    try {
                if ($statusChanged && $newStatus === 'cancelled') {
                    // Stock was deducted when the order was placed — give it back.
                    $stmt = $conn->prepare(
                        "SELECT oi.product_id, oi.quantity FROM order_items oi
                         WHERE oi.order_id = ? FOR UPDATE"
                    );
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $items = $stmt->get_result();
                    $stmt->close();

                    while ($item = $items->fetch_assoc()) {
                        $stmt = $conn->prepare(
                            "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?"
                        );
                        $stmt->bind_param('ii', $item['quantity'], $item['product_id']);
                        $stmt->execute();
                        $stmt->close();

                        $note = "Order {$current['order_number']} cancelled";
                        $userId = (int)$_SESSION['user_id'];
                        $stmt = $conn->prepare(
                            "INSERT INTO stock_movements (product_id, change_type, quantity, reference_note, user_id)
                             VALUES (?, 'return_in', ?, ?, ?)"
                        );
                        $stmt->bind_param('iisi', $item['product_id'], $item['quantity'], $note, $userId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }

                $stmt = $conn->prepare(
                    "UPDATE orders SET order_status = ?, payment_status = ? WHERE order_id = ?"
                );
                $stmt->bind_param('ssi', $newStatus, $newPayment, $id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                header('Location: order_view.php?id=' . $id . '&msg=' . urlencode('Order updated.'));
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Update failed: ' . $e->getMessage();
            }
}

// ---- Load order for display ----
$stmt = $conn->prepare(
    "SELECT o.*, c.full_name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
     FROM orders o
     JOIN customers c ON o.customer_id = c.customer_id
     WHERE o.order_id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: online_orders.php');
    exit;
}

$stmt = $conn->prepare(
    "SELECT oi.quantity, oi.unit_price, oi.line_total, p.product_name
     FROM order_items oi
     JOIN products p ON oi.product_id = p.product_id
     WHERE oi.order_id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width: 680px;">
    <h3 style="margin-top:0;">Order <?= htmlspecialchars($order['order_number']) ?></h3>
    <p style="color: var(--text-muted); margin-top:-10px;">
        Placed <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
    </p>

    <p>
        <strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?>
        <?php if ($order['customer_phone']): ?> &middot; <?= htmlspecialchars($order['customer_phone']) ?><?php endif; ?>
        <?php if ($order['customer_email']): ?> &middot; <?= htmlspecialchars($order['customer_email']) ?><?php endif; ?>
    </p>
    <p><strong>Ship to:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>

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
        <div>Subtotal: ₹<?= number_format($order['subtotal'], 2) ?></div>
        <div>Shipping: ₹<?= number_format($order['shipping_fee'], 2) ?></div>
        <div style="font-size:1.2rem; font-weight:700;">Total: ₹<?= number_format($order['total_amount'], 2) ?></div>
    </div>

    <form method="POST" action="order_view.php?id=<?= $id ?>" style="margin-top:20px; border-top:1px solid var(--border, #e5e0d8); padding-top:16px;">
        <?= csrf_field() ?>
        <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end;">
            <div class="form-group" style="margin-bottom:0;">
                <label for="order_status">Order Status</label>
                <select id="order_status" name="order_status">
                    <?php foreach ($validStatuses as $s): ?>
                        <option value="<?= $s ?>" <?= $order['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label for="payment_status">Payment Status</label>
                <select id="payment_status" name="payment_status">
                    <?php foreach ($validPayStatus as $s): ?>
                        <option value="<?= $s ?>" <?= $order['payment_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn">Save Changes</button>
            </div>
        </div>
        <p style="color:var(--text-muted); font-size:0.82rem; margin-bottom:0;">
            Cancelling an order returns its items to stock. Cancelled and delivered orders are final.
        </p>
    </form>

    <a href="online_orders.php" class="btn btn-secondary" style="margin-top:16px;">Back to Orders</a>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
