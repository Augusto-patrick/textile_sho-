<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$pageTitle = 'Edit Staff Member';
$error = '';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: staff.php');
    exit;
}

$isSelf = ($id === (int)($_SESSION['user_id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name     = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $phone    = trim($_POST['phone']);
    $role     = ($_POST['role'] ?? 'staff') === 'admin' ? 'admin' : 'staff';
    $status   = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    // Safety rule: an admin must never be able to demote or lock out
    // their OWN account — otherwise one misclick can lock everyone out.
    if ($isSelf) {
        $stmt = $conn->prepare("SELECT role, status FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $role   = $current['role'];
        $status = $current['status'];
    }

    if ($name === '' || $username === '') {
        $error = 'Name and username are required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {
        $error = 'Username may only contain letters, numbers, dots, and underscores.';
    } else {
        if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "UPDATE users SET full_name = ?, username = ?, email = ?, password = ?, role = ?, phone = ?, status = ?
                 WHERE user_id = ?"
            );
            $stmt->bind_param('sssssssi', $name, $username, $email, $hashed, $role, $phone, $status, $id);
        } else {
            $stmt = $conn->prepare(
                "UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, phone = ?, status = ?
                 WHERE user_id = ?"
            );
            $stmt->bind_param('ssssssi', $name, $username, $email, $role, $phone, $status, $id);
        }

        try {
            if ($stmt->execute()) {
                // Keep the live session in sync when editing yourself.
                if ($isSelf) {
                    $_SESSION['full_name'] = $name;
                    $_SESSION['username']  = $username;
                }
                header('Location: staff.php?msg=' . urlencode('Staff member updated.'));
                exit;
            } else {
                $error = 'Error updating staff member: ' . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            $error = ($e->getCode() === 1062)
                ? 'That username or email is already taken.'
                : 'Error updating staff member: ' . $e->getMessage();
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        header('Location: staff.php');
        exit;
    }
} else {
    $user = $_POST;
    $user['role']   = $role;
    $user['status'] = $status;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 500px;">
    <h3 style="margin-top:0;">Edit Staff Member</h3>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($isSelf): ?>
        <div class="alert" style="background:#eef3fb; color:#2a4a70;">
            You are editing your own account — role and status are locked to prevent locking yourself out.
        </div>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= $id ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="full_name">Full Name *</label>
            <input type="text" id="full_name" name="full_name" required
                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" required
                   value="<?= htmlspecialchars($user['username'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="password">New Password <span style="color:var(--text-muted); font-weight:normal;">(leave blank to keep current)</span></label>
            <input type="password" id="password" name="password" autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" <?= $isSelf ? 'disabled' : '' ?>>
                <option value="staff" <?= ($user['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <?php if ($isSelf): ?>
                <!-- disabled selects are not submitted, so keep the value via a hidden field -->
                <input type="hidden" name="role" value="<?= htmlspecialchars($user['role'] ?? 'staff') ?>">
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone"
                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" <?= $isSelf ? 'disabled' : '' ?>>
                <option value="active" <?= ($user['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active (can log in)</option>
                <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (blocked)</option>
            </select>
            <?php if ($isSelf): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($user['status'] ?? 'active') ?>">
            <?php endif; ?>
        </div>

        <button type="submit" class="btn">Update Staff Member</button>
        <a href="staff.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
