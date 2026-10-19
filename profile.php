<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle = 'My Account';
$error = '';
$userId = (int)$_SESSION['user_id'];

// ------------------------------------------------------------
// POST: change my own password.
// Anyone logged in (staff or admin) can reach this page — each user
// can only ever change the password of the account they are
// logged in as (the WHERE clause uses the session user_id, never
// a user id from the URL or form).
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($current === '' || $new === '' || $confirm === '') {
        $error = 'All three password fields are required.';
    } elseif (!password_verify($current, $row['password'] ?? '')) {
        $error = 'Your current password is incorrect.';
    } elseif ($new !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new === $current) {
        $error = 'New password must be different from your current one.';
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param('si', $hashed, $userId);
        $stmt->execute();
        $stmt->close();

        // New session id after the credential change (session fixation defence).
        session_regenerate_id(true);

        header('Location: profile.php?msg=' . urlencode('Password updated.'));
        exit;
    }
}

// Current account details for display.
$stmt = $conn->prepare("SELECT full_name, username, email, role, phone, created_at FROM users WHERE user_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width: 560px;">
    <h3 style="margin-top:0;">My Account</h3>

    <table style="margin-bottom:20px;">
        <tr><th style="text-align:left; padding:6px 0;">Name</th><td><?= htmlspecialchars($me['full_name']) ?></td></tr>
        <tr><th style="text-align:left; padding:6px 0;">Username</th><td><?= htmlspecialchars($me['username']) ?></td></tr>
        <tr><th style="text-align:left; padding:6px 0;">Email</th><td><?= htmlspecialchars($me['email'] ?? '-') ?></td></tr>
        <tr><th style="text-align:left; padding:6px 0;">Phone</th><td><?= htmlspecialchars($me['phone'] ?? '-') ?></td></tr>
        <tr><th style="text-align:left; padding:6px 0;">Role</th>
            <td><?= $me['role'] === 'admin' ? '<span class="badge badge-danger">Admin</span>' : '<span class="badge badge-warning">Staff</span>' ?></td></tr>
        <tr><th style="text-align:left; padding:6px 0;">Member since</th><td><?= date('d M Y', strtotime($me['created_at'])) ?></td></tr>
    </table>

    <h4 style="margin-bottom:10px;">Change Password</h4>
    <form method="POST" action="profile.php">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="current_password">Current Password *</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
        </div>

        <div class="form-group">
            <label for="new_password">New Password * <span style="color:var(--text-muted); font-weight:normal;">(min 6 characters)</span></label>
            <input type="password" id="new_password" name="new_password" required autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password *</label>
            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn">Update Password</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
