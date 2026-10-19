<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

csrf_verify_get();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Refuse instead of letting the FK (ON DELETE SET NULL) silently
    // unlink every product from its supplier.
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE supplier_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $count = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    if ($count > 0) {
        header('Location: list.php?error=' . urlencode("Cannot delete: $count product(s) still use this supplier."));
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: list.php?msg=' . urlencode('Supplier deleted.'));
exit;
