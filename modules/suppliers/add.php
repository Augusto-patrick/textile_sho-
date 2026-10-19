<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Add Supplier';
$error = '';

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
            "INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, gst_number)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('ssssss', $name, $contact, $phone, $email, $address, $gst);

        try {
            if ($stmt->execute()) {
                header('Location: list.php?msg=' . urlencode('Supplier added successfully.'));
                exit;
            } else {
                $error = 'Error saving supplier: ' . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            $error = 'Error saving supplier: ' . $e->getMessage();
        }
        $stmt->close();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 560px;">
    <h3 style="margin-top:0;">Add Supplier</h3>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="add.php">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="supplier_name">Supplier Name *</label>
            <input type="text" id="supplier_name" name="supplier_name" required
                   value="<?= htmlspecialchars($_POST['supplier_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="contact_person">Contact Person</label>
            <input type="text" id="contact_person" name="contact_person"
                   value="<?= htmlspecialchars($_POST['contact_person'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone"
                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address"
                   value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="gst_number">GST Number</label>
            <input type="text" id="gst_number" name="gst_number" placeholder="e.g. 29ABCDE1234F1Z5"
                   value="<?= htmlspecialchars($_POST['gst_number'] ?? '') ?>">
        </div>

        <button type="submit" class="btn">Save Supplier</button>
        <a href="list.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
