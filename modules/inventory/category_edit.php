<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Edit Category';
$error = '';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: categories.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name        = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if ($name === '') {
        $error = 'Category name is required.';
    } else {
        $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
        $stmt->bind_param('ssi', $name, $description, $id);

        try {
            if ($stmt->execute()) {
                header('Location: categories.php?msg=' . urlencode('Category updated.'));
                exit;
            } else {
                $error = 'Error updating category: ' . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            $error = ($e->getCode() === 1062)
                ? 'A category with that name already exists.'
                : 'Error updating category: ' . $e->getMessage();
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $category = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$category) {
        header('Location: categories.php');
        exit;
    }
} else {
    $category = $_POST;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 500px;">
    <h3 style="margin-top:0;">Edit Category</h3>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="category_edit.php?id=<?= $id ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="category_name">Category Name *</label>
            <input type="text" id="category_name" name="category_name" required
                   value="<?= htmlspecialchars($category['category_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description"
                   value="<?= htmlspecialchars($category['description'] ?? '') ?>">
        </div>

        <button type="submit" class="btn">Update Category</button>
        <a href="categories.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
