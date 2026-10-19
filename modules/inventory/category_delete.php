<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

csrf_verify_get();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Refuse instead of letting the FK (ON DELETE SET NULL) silently
    // strip every product of its category.
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE category_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $count = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    if ($count > 0) {
        header('Location: categories.php?error=' . urlencode("Cannot delete: $count product(s) still use this category."));
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: categories.php?msg=' . urlencode('Category deleted.'));
exit;
