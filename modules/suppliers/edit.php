<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Edit Supplier';
$error = '';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name     = trim($_POST['supplier_name']);
    $contact  = trim($_POST['contact_person']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['email']);
    $address  = trim($_POST['address']);
    $gst      = trim($_POST['gst_number']);

    if ($name === '') {
        $error = 'Supplier name is required.';
    } else {
        $stmt = $conn->prepare(
            "UPDATE suppliers SET supplier_name = ?, contact_person = ?, phone = ?, email = ?, address = ?, gst_number = ?
             WHERE supplier_id = ?"
        );
        $stmt->bind_param('ssssssi', $name, $contact, $phone, $email, $address, $gst, $id);

        try {
            if ($stmt->execute()) {
                header('Location: list.php?msg=' . urlencode('Supplier updated.'));
                exit;
            } else {
                $error = 'Error updating supplier: ' . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            $error = 'Error updating supplier: ' . $e->getMessage();
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $supplier = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$supplier) {
        header('Location: list.php');
        exit;
    }
} else {
    $supplier = $_POST;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 560px;">
    <h3 style="margin-top:0;">Edit Supplier</h3>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= $id ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="supplier_name">Supplier Name *</label>
            <input type="text" id="supplier_name" name="supplier_name" required
                   value="<?= htmlspecialchars($supplier['supplier_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="contact_person">Contact Person</label>
            <input type="text" id="contact_person" name="contact_person"
                   value="<?= htmlspecialchars($supplier['contact_person'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone"
                   value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($supplier['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address"
                   value="<?= htmlspecialchars($supplier['address'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="gst_number">GST Number</label>
            <input type="text" id="gst_number" name="gst_number"
                   value="<?= htmlspecialchars($supplier['gst_number'] ?? '') ?>">
        </div>

        <button type="submit" class="btn">Update Supplier</button>
        <a href="list.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
