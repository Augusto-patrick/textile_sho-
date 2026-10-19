<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$pageTitle = 'Add Staff Member';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name     = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = ($_POST['role'] ?? 'staff') === 'admin' ? 'admin' : 'staff';
    $phone    = trim($_POST['phone']);
    $status   = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($name === '' || $username === '' || $password === '') {
        $error = 'Name, username, and password are required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {
        $error = 'Username may only contain letters, numbers, dots, and underscores.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, username, email, password, role, phone, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssss', $name, $username, $email, $hashedPassword, $role, $phone, $status);

        // PHP 8.1+ mysqli throws exceptions by default, so duplicate-key
        // errors arrive as mysqli_sql_exception instead of a false return.
        try {
            if ($stmt->execute()) {
                header('Location: staff.php?msg=' . urlencode('Staff member added successfully.'));
                exit;
            } else {
                $error = 'Error saving staff member: ' . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            $error = ($e->getCode() === 1062)
                ? 'That username or email is already taken.'
                : 'Error saving staff member: ' . $e->getMessage();
        }
        $stmt->close();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 500px;">
    <h3 style="margin-top:0;">Add Staff Member</h3>

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
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" required
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="staff">Staff</option>
                <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone"
                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="active">Active (can log in)</option>
                <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (blocked)</option>
            </select>
        </div>

        <button type="submit" class="btn">Save Staff Member</button>
        <a href="staff.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
