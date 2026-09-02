<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Edit Customer';
$error = '';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['full_name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $city     = trim($_POST['city']);
    $pincode  = trim($_POST['pincode']);

    if ($name === '' || $email === '') {
        $error = 'Name and email are required.';
    } else {
        $stmt = $conn->prepare(
            "UPDATE customers SET full_name = ?, email = ?, phone = ?, address = ?, city = ?, pincode = ?
             WHERE customer_id = ?"
        );
        $stmt->bind_param('ssssssi', $name, $email, $phone, $address, $city, $pincode, $id);

        if ($stmt->execute()) {
            header('Location: list.php?msg=' . urlencode('Customer updated.'));
            exit;
        } else {
            $error = ($conn->errno === 1062)
                ? 'Another customer already uses that email.'
                : 'Error updating customer: ' . $conn->error;
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$customer) {
        header('Location: list.php');
        exit;
    }
} else {
    $customer = $_POST;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 500px;">
    <h3 style="margin-top:0;">Edit Customer</h3>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= $id ?>">
        <div class="form-group">
            <label for="full_name">Full Name *</label>
            <input type="text" id="full_name" name="full_name" required
                   value="<?= htmlspecialchars($customer['full_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone"
                   value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address"
                   value="<?= htmlspecialchars($customer['address'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city"
                   value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="pincode">Pincode</label>
            <input type="text" id="pincode" name="pincode"
                   value="<?= htmlspecialchars($customer['pincode'] ?? '') ?>">
        </div>

        <button type="submit" class="btn">Update Customer</button>
        <a href="list.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
