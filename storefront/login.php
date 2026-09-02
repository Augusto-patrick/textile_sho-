<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT customer_id, full_name, password FROM customers WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($customer && password_verify($password, $customer['password'])) {
        $_SESSION['customer_id']   = $customer['customer_id'];
        $_SESSION['customer_name'] = $customer['full_name'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Textile Shop</title>
<link rel="stylesheet" href="/textile_shop/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <p style="text-align:center; margin-top:16px; font-size:0.85rem;">
            New here? <a href="register.php" class="btn-link">Create an account</a>
        </p>
    </div>
</div>
</body>
</html>
