<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Edit Bill';
$error = '';

$validPayment = ['cash', 'card', 'upi'];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: history.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $walkinName   = trim($_POST['walkin_name'] ?? '');
    $walkinPhone  = trim($_POST['walkin_phone'] ?? '');
    $payment      = $_POST['payment_method'] ?? '';
    $discount     = (float)($_POST['discount'] ?? 0);
    $tax          = (float)($_POST['tax'] ?? 0);
    $quantities   = $_POST['qty'] ?? [];

    if (!in_array($payment, $validPayment, true)) {
        header('Location: edit_bill.php?id=' . $id . '&error=' . urlencode('Invalid payment method.'));
        exit;
    }
    if ($discount < 0 || $tax < 0) {
        header('Location: edit_bill.php?id=' . $id . '&error=' . urlencode('Discount and tax cannot be negative.'));
        exit;
    }

    $conn->begin_transaction();
    try {
        // Lock the bill header row while we edit it.
        $stmt = $conn->prepare("SELECT bill_id, bill_number FROM bills WHERE bill_id = ? FOR UPDATE");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $bill = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$bill) {
            throw new Exception('Bill not found.');
        }

        // Current line items, locked against concurrent edits.
        $stmt = $conn->prepare(
            "SELECT bill_item_id, product_id, quantity, unit_price FROM bill_items WHERE bill_id = ? FOR UPDATE"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $userId = (int)$_SESSION['user_id'];

        foreach ($rows as $row) {
            $itemId   = (int)$row['bill_item_id'];
            $newQty   = (int)($quantities[$itemId] ?? 0);
            $oldQty   = (int)$row['quantity'];
            $delta    = $newQty - $oldQty;

            if ($newQty < 1) {
                throw new Exception('Every item quantity must be at least 1.');
            }
            if ($delta === 0) {
                continue;
            }

            // Lock this product's stock row before touching it.
            $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ? FOR UPDATE");
            $stmt->bind_param('i', $row['product_id']);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($delta > 0 && (int)$product['stock_quantity'] < $delta) {
                $stmt = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
                $stmt->bind_param('i', $row['product_id']);
                $stmt->execute();
                $pname = $stmt->get_result()->fetch_assoc()['product_name'] ?? 'Item';
                $stmt->close();
                throw new Exception("Not enough stock for $pname to increase the quantity (only {$product['stock_quantity']} left).");
            }

            // Stock change is the opposite of the quantity change:
            // qty up => stock down, qty down => stock back.
            $stockChange = -$delta;
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
            $stmt->bind_param('ii', $stockChange, $row['product_id']);
            $stmt->execute();
            $stmt->close();

            $note = "Bill {$bill['bill_number']} edited";
            $stmt = $conn->prepare(
                "INSERT INTO stock_movements (product_id, change_type, quantity, reference_note, user_id)
                 VALUES (?, 'adjustment', ?, ?, ?)"
            );
            $stmt->bind_param('iisi', $row['product_id'], $stockChange, $note, $userId);
            $stmt->execute();
            $stmt->close();

            $lineTotal = $row['unit_price'] * $newQty;
            $stmt = $conn->prepare("UPDATE bill_items SET quantity = ?, line_total = ? WHERE bill_item_id = ?");
            $stmt->bind_param('idd', $newQty, $lineTotal, $itemId);
            $stmt->execute();
            $stmt->close();
        }

        // Recompute totals from the stored line items (never trust the browser).
        $stmt = $conn->prepare("SELECT COALESCE(SUM(line_total), 0) AS s FROM bill_items WHERE bill_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $subtotal = (float)$stmt->get_result()->fetch_assoc()['s'];
        $stmt->close();

        $total = max(0, $subtotal - $discount + $tax);

        // walkin_name(s), walkin_phone(s), payment_method(s),
        // discount(d), tax(d), subtotal(d), total_amount(d), bill_id(i)
        $stmt = $conn->prepare(
            "UPDATE bills SET walkin_name = ?, walkin_phone = ?, payment_method = ?,
                    discount = ?, tax = ?, subtotal = ?, total_amount = ?
             WHERE bill_id = ?"
        );
        $stmt->bind_param('sssddddi', $walkinName, $walkinPhone, $payment, $discount, $tax, $subtotal, $total, $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        header('Location: history.php?msg=' . urlencode("Bill {$bill['bill_number']} updated."));
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header('Location: edit_bill.php?id=' . $id . '&error=' . urlencode($e->getMessage()));
        exit;
    }
}

// ---- Load bill + items for the form ----
$stmt = $conn->prepare("SELECT * FROM bills WHERE bill_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bill) {
    header('Location: history.php');
    exit;
}

$stmt = $conn->prepare(
    "SELECT bi.bill_item_id, bi.product_id, bi.quantity, bi.unit_price, bi.line_total,
            p.product_name, p.stock_quantity
     FROM bill_items bi
     JOIN products p ON bi.product_id = p.product_id
     WHERE bi.bill_id = ?
     ORDER BY bi.bill_item_id"
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
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card" style="max-width: 720px;">
    <h3 style="margin-top:0;">Edit Bill <?= htmlspecialchars($bill['bill_number']) ?></h3>
    <p style="color: var(--text-muted); margin-top:-10px;">
        Created <?= date('d M Y, h:i A', strtotime($bill['created_at'])) ?>
    </p>

    <form method="POST" action="edit_bill.php?id=<?= $id ?>">
        <?= csrf_field() ?>

        <div style="display:flex; gap:16px; flex-wrap:wrap;">
            <div class="form-group" style="flex:1; min-width:200px;">
                <label for="walkin_name">Customer Name</label>
                <input type="text" id="walkin_name" name="walkin_name"
                       value="<?= htmlspecialchars($bill['walkin_name'] ?? '') ?>">
            </div>
            <div class="form-group" style="flex:1; min-width:160px;">
                <label for="walkin_phone">Customer Phone</label>
                <input type="text" id="walkin_phone" name="walkin_phone"
                       value="<?= htmlspecialchars($bill['walkin_phone'] ?? '') ?>">
            </div>
            <div class="form-group" style="min-width:150px;">
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method">
                    <?php foreach ($validPayment as $pm): ?>
                        <option value="<?= $pm ?>" <?= $bill['payment_method'] === $pm ? 'selected' : '' ?>><?= strtoupper($pm) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h4 style="margin-bottom:8px;">Items</h4>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="width:110px;">Qty</th>
                    <th>Unit Price</th>
                    <th>Line Total</th>
                    <th>In Stock</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($item = $items->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td>
                        <input type="number" name="qty[<?= (int)$item['bill_item_id'] ?>]" min="1"
                               value="<?= (int)$item['quantity'] ?>" required
                               style="width:80px; padding:6px; border:1px solid #d8d2c6; border-radius:5px;">
                    </td>
                    <td>₹<?= number_format($item['unit_price'], 2) ?></td>
                    <td>₹<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                    <td><?= (int)$item['stock_quantity'] ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <div style="display:flex; gap:16px; flex-wrap:wrap; margin-top:16px;">
            <div class="form-group">
                <label for="discount">Discount (₹)</label>
                <input type="number" step="0.01" min="0" id="discount" name="discount"
                       value="<?= htmlspecialchars(number_format((float)$bill['discount'], 2, '.', '')) ?>">
            </div>
            <div class="form-group">
                <label for="tax">Tax (₹)</label>
                <input type="number" step="0.01" min="0" id="tax" name="tax"
                       value="<?= htmlspecialchars(number_format((float)$bill['tax'], 2, '.', '')) ?>">
            </div>
        </div>

        <p style="color:var(--text-muted); font-size:0.85rem;">
            Quantities are checked against live stock. Reducing a quantity returns items to stock;
            increasing it uses stock. Totals are recalculated on the server as
            subtotal &minus; discount + tax.
        </p>

        <button type="submit" class="btn">Save Bill</button>
        <a href="history.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
