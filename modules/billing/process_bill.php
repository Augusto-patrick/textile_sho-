<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pos.php');
    exit;
}
csrf_verify();

$cartJson     = $_POST['cart_json'] ?? '';
$walkinName   = trim($_POST['walkin_name'] ?? '');
$walkinPhone  = trim($_POST['walkin_phone'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? 'cash';
$userId       = $_SESSION['user_id'];

// json_decode turns the JSON text (sent from JavaScript) back into
// a PHP array we can loop over. "true" makes it an associative array
// instead of a generic object.
$cart = json_decode($cartJson, true);

if (empty($cart)) {
    header('Location: pos.php?error=' . urlencode('Cart was empty.'));
    exit;
}

// ------------------------------------------------------------
// WHY A TRANSACTION?
// A single "sale" actually needs several database writes:
//   1. one row in `bills`
//   2. one row in `bill_items` per product
//   3. a stock reduction on each product row
//   4. an audit entry in `stock_movements` per product
// If step 3 failed halfway through (e.g. power cut, or a bug) after
// step 1 and 2 already succeeded, you'd have a bill on record for
// items whose stock was never actually reduced — your data would be
// inconsistent forever.
//
// begin_transaction() says "treat everything until commit() as one
// all-or-nothing unit." If anything goes wrong, rollback() undoes
// every write made since the transaction started, as if none of it
// happened.
// ------------------------------------------------------------
$conn->begin_transaction();

try {
    // Re-check stock and prices from the DATABASE, not from the browser.
    // Never trust quantities/prices sent from JavaScript — a user could
    // edit them in devtools. We only trust the product_id, then look up
    // the real price and stock ourselves.
    $subtotal = 0;
    $lineItems = [];

    foreach ($cart as $productId => $item) {
        $productId = (int)$productId;
        $qty = (int)$item['qty'];

        $stmt = $conn->prepare("SELECT product_name, selling_price, stock_quantity FROM products WHERE product_id = ? FOR UPDATE");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            throw new Exception("Product #$productId no longer exists.");
        }
        if ($qty > $product['stock_quantity']) {
            throw new Exception("Not enough stock for {$product['product_name']} (only {$product['stock_quantity']} left).");
        }

        $lineTotal = $product['selling_price'] * $qty;
        $subtotal += $lineTotal;

        $lineItems[] = [
            'product_id' => $productId,
            'name' => $product['product_name'],
            'qty' => $qty,
            'unit_price' => $product['selling_price'],
            'line_total' => $lineTotal,
        ];
    }

    $totalAmount = $subtotal; // no discount/tax logic yet — kept simple for now
    $billNumber = 'BILL-' . date('YmdHis') . '-' . random_int(100, 999);

    // 1. Create the bill header
    $stmt = $conn->prepare(
        "INSERT INTO bills (bill_number, walkin_name, walkin_phone, user_id, subtotal, discount, tax, total_amount, payment_method)
         VALUES (?, ?, ?, ?, ?, 0, 0, ?, ?)"
    );
    $stmt->bind_param('sssidds', $billNumber, $walkinName, $walkinPhone, $userId, $subtotal, $totalAmount, $paymentMethod);
    $stmt->execute();
    $billId = $conn->insert_id; // the auto-generated bill_id we just created
    $stmt->close();

    // 2, 3, 4. For each line item: insert bill_items, reduce stock, log movement
    foreach ($lineItems as $item) {
        $stmt = $conn->prepare(
            "INSERT INTO bill_items (bill_id, product_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iiidd', $billId, $item['product_id'], $item['qty'], $item['unit_price'], $item['line_total']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
        $stmt->bind_param('ii', $item['qty'], $item['product_id']);
        $stmt->execute();
        $stmt->close();

        $note = "Sold via bill $billNumber";
        $negQty = -$item['qty']; // negative because stock is going down
        $stmt = $conn->prepare(
            "INSERT INTO stock_movements (product_id, change_type, quantity, reference_note, user_id) VALUES (?, 'sale_out', ?, ?, ?)"
        );
        $stmt->bind_param('iisi', $item['product_id'], $negQty, $note, $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Everything above succeeded — make it permanent.
    $conn->commit();

    header('Location: history.php?msg=' . urlencode("Bill $billNumber created successfully."));
    exit;

} catch (Exception $e) {
    // Something failed — undo every write made since begin_transaction().
    $conn->rollback();
    header('Location: pos.php?error=' . urlencode($e->getMessage()));
    exit;
}
