<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Edit Product';
$error = '';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: list.php');
    exit;
}

$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$suppliers  = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");

// ------------------------------------------------------------
// UPDATE: runs only when the form is submitted (POST)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['product_name']);
    $categoryId    = $_POST['category_id'] ?: null;
    $supplierId    = $_POST['supplier_id'] ?: null;
    $sku           = trim($_POST['sku']);
    $unit          = $_POST['unit'];
    $costPrice     = (float)$_POST['cost_price'];
    $sellingPrice  = (float)$_POST['selling_price'];
    $stockQty      = (int)$_POST['stock_quantity'];
    $reorderLevel  = (int)$_POST['reorder_level'];
    $isOnline      = isset($_POST['is_online']) ? 1 : 0;
    $status        = $_POST['status'];

    if ($name === '') {
        $error = 'Product name is required.';
    } else {
        $stmt = $conn->prepare(
            "UPDATE products SET
                product_name = ?, category_id = ?, supplier_id = ?, sku = ?, unit = ?,
                cost_price = ?, selling_price = ?, stock_quantity = ?, reorder_level = ?,
                is_online = ?, status = ?
             WHERE product_id = ?"
        );
        $stmt->bind_param(
            'siisiddiiisi',
            $name, $categoryId, $supplierId, $sku, $unit,
            $costPrice, $sellingPrice, $stockQty, $reorderLevel,
            $isOnline, $status, $id
        );

        if ($stmt->execute()) {
            header('Location: list.php?msg=' . urlencode('Product updated.'));
            exit;
        } else {
            $error = 'Error updating product: ' . $conn->error;
        }
        $stmt->close();
    }
}

// ------------------------------------------------------------
// Load the existing product so the form can be pre-filled.
// We only do this on the initial GET load — if the form was just
// submitted with bad data, we want to keep showing what the user typed,
// not overwrite it with the old DB values.
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        header('Location: list.php');
        exit;
    }
} else {
    // Keep whatever was typed in the failed submission
    $product = $_POST;
    $product['product_id'] = $id;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 640px;">
    <h3 style="margin-top:0;">Edit Product</h3>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= $id ?>">
        <div class="form-group">
            <label for="product_name">Product Name *</label>
            <input type="text" id="product_name" name="product_name" required
                   value="<?= htmlspecialchars($product['product_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="sku">SKU</label>
            <input type="text" id="sku" name="sku"
                   value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">-- None --</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['category_id'] ?>" <?= ($product['category_id'] ?? '') == $cat['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="supplier_id">Supplier</label>
            <select id="supplier_id" name="supplier_id">
                <option value="">-- None --</option>
                <?php while ($sup = $suppliers->fetch_assoc()): ?>
                    <option value="<?= $sup['supplier_id'] ?>" <?= ($product['supplier_id'] ?? '') == $sup['supplier_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sup['supplier_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="unit">Unit</label>
            <select id="unit" name="unit">
                <?php foreach (['piece', 'meter', 'set'] as $u): ?>
                    <option value="<?= $u ?>" <?= ($product['unit'] ?? '') === $u ? 'selected' : '' ?>><?= ucfirst($u) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="cost_price">Cost Price (₹)</label>
            <input type="number" step="0.01" id="cost_price" name="cost_price" value="<?= htmlspecialchars($product['cost_price'] ?? 0) ?>">
        </div>

        <div class="form-group">
            <label for="selling_price">Selling Price (₹) *</label>
            <input type="number" step="0.01" id="selling_price" name="selling_price" required value="<?= htmlspecialchars($product['selling_price'] ?? 0) ?>">
        </div>

        <div class="form-group">
            <label for="stock_quantity">Stock Quantity</label>
            <input type="number" id="stock_quantity" name="stock_quantity" value="<?= htmlspecialchars($product['stock_quantity'] ?? 0) ?>">
        </div>

        <div class="form-group">
            <label for="reorder_level">Reorder Level</label>
            <input type="number" id="reorder_level" name="reorder_level" value="<?= htmlspecialchars($product['reorder_level'] ?? 5) ?>">
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="active" <?= ($product['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="discontinued" <?= ($product['status'] ?? '') === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_online" style="width:auto; display:inline-block;" <?= !empty($product['is_online']) ? 'checked' : '' ?>>
                List this product on the online store
            </label>
        </div>

        <button type="submit" class="btn">Update Product</button>
        <a href="list.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
