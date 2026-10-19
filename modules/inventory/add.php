<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Add Product';
$error = '';

// ------------------------------------------------------------
// Load categories and suppliers to populate the dropdowns.
// ------------------------------------------------------------
$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$suppliers  = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");

// ------------------------------------------------------------
// CREATE: this block only runs when the form below is submitted
// (i.e. when the request method is POST, not when the page first loads as GET).
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
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

    if ($name === '') {
        $error = 'Product name is required.';
    } else {
        // Prepared statement: the "?" placeholders keep user input
        // separate from the SQL command itself. This is what stops
        // SQL injection — never build queries by gluing strings together.
        $stmt = $conn->prepare(
            "INSERT INTO products
                (product_name, category_id, supplier_id, sku, unit, cost_price, selling_price, stock_quantity, reorder_level, is_online)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        // Type string: s=string, i=integer, d=decimal(double)
        $stmt->bind_param(
            'siisiddiii',
            $name, $categoryId, $supplierId, $sku, $unit,
            $costPrice, $sellingPrice, $stockQty, $reorderLevel, $isOnline
        );

        // PHP 8.1+ mysqli throws exceptions on errors (e.g. duplicate SKU),
        // so the old `if ($stmt->execute())` alone would 500 on failure.
        try {
            if ($stmt->execute()) {
                header('Location: list.php?msg=' . urlencode('Product added successfully.'));
                exit;
            } else {
                $error = 'Error saving product: ' . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            $error = ($e->getCode() === 1062)
                ? 'A product with that SKU already exists.'
                : 'Error saving product: ' . $e->getMessage();
        }
        $stmt->close();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 640px;">
    <h3 style="margin-top:0;">Add Product</h3>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="add.php">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="product_name">Product Name *</label>
            <input type="text" id="product_name" name="product_name" required
                   value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="sku">SKU (unique code)</label>
            <input type="text" id="sku" name="sku" placeholder="e.g. SAR-001"
                   value="<?= htmlspecialchars($_POST['sku'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">-- None --</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="supplier_id">Supplier</label>
            <select id="supplier_id" name="supplier_id">
                <option value="">-- None --</option>
                <?php while ($sup = $suppliers->fetch_assoc()): ?>
                    <option value="<?= $sup['supplier_id'] ?>"><?= htmlspecialchars($sup['supplier_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="unit">Unit</label>
            <select id="unit" name="unit">
                <option value="piece">Piece</option>
                <option value="meter">Meter</option>
                <option value="set">Set</option>
            </select>
        </div>

        <div class="form-group">
            <label for="cost_price">Cost Price (₹)</label>
            <input type="number" step="0.01" id="cost_price" name="cost_price" value="0">
        </div>

        <div class="form-group">
            <label for="selling_price">Selling Price (₹) *</label>
            <input type="number" step="0.01" id="selling_price" name="selling_price" required value="0">
        </div>

        <div class="form-group">
            <label for="stock_quantity">Opening Stock Quantity</label>
            <input type="number" id="stock_quantity" name="stock_quantity" value="0">
        </div>

        <div class="form-group">
            <label for="reorder_level">Reorder Level (low-stock alert threshold)</label>
            <input type="number" id="reorder_level" name="reorder_level" value="5">
        </div>

        <div class="form-group">
            <label><input type="checkbox" name="is_online" style="width:auto; display:inline-block;"> List this product on the online store</label>
        </div>

        <button type="submit" class="btn">Save Product</button>
        <a href="list.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
