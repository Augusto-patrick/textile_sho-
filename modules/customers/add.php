<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Add Customer';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name     = trim($_POST['full_name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $city     = trim($_POST['city']);
    $pincode  = trim($_POST['pincode']);
    $password = $_POST['password'];

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Name, email, and password are required.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO customers (full_name, email, password, phone, address, city, pincode)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssss', $name, $email, $hashedPassword, $phone, $address, $city, $pincode);

        // PHP 8.1+ mysqli throws exceptions on errors (e.g. duplicate email),
        // so duplicate-key handling must catch, not check errno afterwards.
        try {
            if ($stmt->execute()) {
                header('Location: list.php?msg=' . urlencode('Customer added successfully.'));
                exit;
            } else {
                $error = 'Error saving customer: ' . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            $error = ($e->getCode() === 1062)
                ? 'A customer with that email already exists.'
                : 'Error saving customer: ' . $e->getMessage();
        }
        $stmt->close();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 500px;">
    <h3 style="margin-top:0;">Add Customer</h3>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="add.php">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="full_name">Full Name *</label>
            <input type="text" id="full_name" name="full_name" required
                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone"
                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address"
                   value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city"
                   value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="pincode">Pincode</label>
            <input type="text" id="pincode" name="pincode"
                   value="<?= htmlspecialchars($_POST['pincode'] ?? '') ?>">
        </div>

        <button type="submit" class="btn">Save Customer</button>
        <a href="list.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
