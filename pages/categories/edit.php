<?php
// pages/categories/edit.php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/header.php';

$db  = getDB();
$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM categories WHERE id = :id');
$stmt->execute([':id' => $id]);
$category = $stmt->fetch();

if (!$category) { setFlash('error', 'Category not found.'); redirect('index.php'); }

$pageTitle = 'Edit Category';
$errors    = [];
$old       = $category;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $color       = trim($_POST['color']       ?? '#6366f1');
    $old         = array_merge($old, compact('name', 'description', 'color'));

    if (!required($name)) $errors['name'] = 'Category name is required.';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        $color           = '#6366f1';
        $errors['color'] = 'Invalid colour format.';
    }

    if (empty($errors['name'])) {
        $chk = $db->prepare('SELECT id FROM categories WHERE name = :name AND id != :id');
        $chk->execute([':name' => $name, ':id' => $id]);
        if ($chk->fetch()) $errors['name'] = 'Another category already has this name.';
    }

    if (empty($errors)) {
        $upd = $db->prepare(
            'UPDATE categories SET name = :name, description = :desc, color = :color WHERE id = :id'
        );
        $upd->execute([':name' => $name, ':desc' => $description, ':color' => $color, ':id' => $id]);
        setFlash('success', 'Category updated.');
        redirect('index.php');
    }
}
?>

<div class="page-header">
    <div><h1>Edit Category #<?= $id ?></h1></div>
    <a href="index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="card fade-in">
    <form method="post">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-grid">

            <div class="field">
                <label for="name">Category Name *</label>
                <input type="text" id="name" name="name" value="<?= e($old['name']) ?>">
                <?php if (isset($errors['name'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="color">Badge Colour</label>
                <div style="display:flex;gap:.6rem;align-items:center">
                    <input type="color" id="color" name="color"
                           value="<?= e($old['color']) ?>"
                           style="width:3rem;flex-shrink:0">
                    <span id="colorHex" style="font-family:monospace;font-size:.85rem;color:var(--text-muted)">
                        <?= e($old['color']) ?>
                    </span>
                </div>
            </div>

            <div class="field full">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?= e($old['description']) ?></textarea>
            </div>

        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('color').addEventListener('input', function () {
    document.getElementById('colorHex').textContent = this.value;
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
