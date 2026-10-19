<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name     = trim($_POST['full_name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $city     = trim($_POST['city']);
    $pincode  = trim($_POST['pincode']);

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Name, email, and password are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO customers (full_name, email, password, phone, address, city, pincode)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssss', $name, $email, $hashedPassword, $phone, $address, $city, $pincode);

        if ($stmt->execute()) {
            session_regenerate_id(true);
            $_SESSION['customer_id']   = $conn->insert_id;
            $_SESSION['customer_name'] = $name;
            header('Location: index.php');
            exit;
        } else {
            $error = ($conn->errno === 1062)
                ? 'An account with that email already exists. Try logging in instead.'
                : 'Error creating account: ' . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up | Textile Shop</title>
<link rel="stylesheet" href="/textile_shop/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Create Account</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password * (min 6 characters)</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Pincode</label>
                <input type="text" name="pincode" value="<?= htmlspecialchars($_POST['pincode'] ?? '') ?>">
            </div>
            <button type="submit" class="btn">Create Account</button>
        </form>
        <p style="text-align:center; margin-top:16px; font-size:0.85rem;">
            Already have an account? <a href="login.php" class="btn-link">Log in</a>
        </p>
    </div>
</div>
</body>
</html>
