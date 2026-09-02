<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

// ------------------------------------------------------------
// DELETE: triggered by clicking the "Delete" link in list.php,
// which passes the product's id in the URL, e.g. delete.php?id=7
// ------------------------------------------------------------
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: list.php?msg=' . urlencode('Product deleted.'));
exit;
