<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$pageTitle = 'Manage Staff';

$sql = "SELECT user_id, full_name, username, email, role, phone, status, created_at
        FROM users
        ORDER BY created_at DESC";
$result = $conn->query($sql);

include __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="margin:0;">Staff &amp; Admins</h3>
        <a href="add.php" class="btn">+ Add Staff</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                    <td>
                        <?= $row['role'] === 'admin'
                            ? '<span class="badge badge-danger">Admin</span>'
                            : '<span class="badge badge-warning">Staff</span>' ?>
                    </td>
                    <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                    <td>
                        <?= $row['status'] === 'active'
                            ? '<span class="badge badge-success">Active</span>'
                            : '<span class="badge badge-danger">Inactive</span>' ?>
                    </td>
                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    <td><a href="edit.php?id=<?= $row['user_id'] ?>" class="btn-link">Edit</a></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center; color: var(--text-muted);">No staff accounts yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
