<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireCustomerLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$customerId       = $_SESSION['customer_id'];
$shippingAddress  = trim($_POST['shipping_address']);
$paymentMethod    = $_POST['payment_method'] ?? 'cod';

if ($shippingAddress === '') {
    header('Location: checkout.php?error=' . urlencode('Shipping address is required.'));
    exit;
}

$conn->begin_transaction();

try {
    // FOR UPDATE locks these product rows until we commit/rollback —
    // stops two customers from both "succeeding" in buying the last
    // item at the exact same moment (a race condition).
    $stmt = $conn->prepare(
        "SELECT ci.cart_item_id, ci.quantity, p.product_id, p.product_name, p.selling_price, p.stock_quantity
         FROM cart_items ci JOIN products p ON ci.product_id = p.product_id
         WHERE ci.customer_id = ? FOR UPDATE"
    );
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $cartResult = $stmt->get_result();
    $stmt->close();

    $items = [];
    $subtotal = 0;
    while ($row = $cartResult->fetch_assoc()) {
        if ($row['quantity'] > $row['stock_quantity']) {
            throw new Exception("Sorry, only {$row['stock_quantity']} of {$row['product_name']} left in stock.");
        }
        $lineTotal = $row['selling_price'] * $row['quantity'];
        $subtotal += $lineTotal;
        $items[] = $row + ['line_total' => $lineTotal];
    }

    if (empty($items)) {
        throw new Exception('Your cart is empty.');
    }

    $shippingFee = 0;
    $totalAmount = $subtotal + $shippingFee;
    $orderNumber = 'ORD-' . date('YmdHis') . '-' . random_int(100, 999);

    // Type string: customerId(i), orderNumber(s), subtotal(d), shippingFee(d),
    // totalAmount(d), paymentMethod(s), shippingAddress(s) => "isdddss"
    $stmt = $conn->prepare(
        "INSERT INTO orders (customer_id, order_number, subtotal, shipping_fee, total_amount, payment_method, shipping_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isdddss', $customerId, $orderNumber, $subtotal, $shippingFee, $totalAmount, $paymentMethod, $shippingAddress);
    $stmt->execute();
    $orderId = $conn->insert_id;
    $stmt->close();

    foreach ($items as $item) {
        $stmt = $conn->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iiidd', $orderId, $item['product_id'], $item['quantity'], $item['selling_price'], $item['line_total']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
        $stmt->bind_param('ii', $item['quantity'], $item['product_id']);
        $stmt->execute();
        $stmt->close();

        $note = "Sold via online order $orderNumber";
        $negQty = -$item['quantity'];
        $stmt = $conn->prepare(
            "INSERT INTO stock_movements (product_id, change_type, quantity, reference_note) VALUES (?, 'sale_out', ?, ?)"
        );
        $stmt->bind_param('iis', $item['product_id'], $negQty, $note);
        $stmt->execute();
        $stmt->close();
    }

    // Order placed successfully — empty the cart.
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE customer_id = ?");
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header('Location: orders.php?msg=' . urlencode("Order $orderNumber placed successfully!"));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    header('Location: checkout.php?error=' . urlencode($e->getMessage()));
    exit;
}
