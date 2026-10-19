<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Add Category';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name        = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if ($name === '') {
        $error = 'Category name is required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
        $stmt->bind_param('ss', $name, $description);

        try {
            if ($stmt->execute()) {
                header('Location: categories.php?msg=' . urlencode('Category added successfully.'));
                exit;
            } else {
                $error = 'Error saving category: ' . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            $error = ($e->getCode() === 1062)
                ? 'A category with that name already exists.'
                : 'Error saving category: ' . $e->getMessage();
        }
        $stmt->close();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 500px;">
    <h3 style="margin-top:0;">Add Category</h3>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="category_add.php">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="category_name">Category Name *</label>
            <input type="text" id="category_name" name="category_name" required
                   placeholder="e.g. Sarees, Shirts, Bedsheets"
                   value="<?= htmlspecialchars($_POST['category_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description"
                   value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
        </div>

        <button type="submit" class="btn">Save Category</button>
        <a href="categories.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
