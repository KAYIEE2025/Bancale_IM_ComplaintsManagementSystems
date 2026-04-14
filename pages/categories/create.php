<?php
// pages/categories/create.php
$pageTitle = 'New Category';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/header.php';

$db     = getDB();
$errors = [];
$old    = ['name' => '', 'description' => '', 'color' => '#6366f1'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $color       = trim($_POST['color']       ?? '#6366f1');
    $old         = compact('name', 'description', 'color');

    if (!required($name)) $errors['name'] = 'Category name is required.';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        $color            = '#6366f1';
        $errors['color']  = 'Invalid colour format.';
    }

    // Unique name check
    if (empty($errors['name'])) {
        $chk = $db->prepare('SELECT id FROM categories WHERE name = :name');
        $chk->execute([':name' => $name]);
        if ($chk->fetch()) $errors['name'] = 'A category with this name already exists.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare(
            'INSERT INTO categories (name, description, color) VALUES (:name, :desc, :color)'
        );
        $stmt->execute([':name' => $name, ':desc' => $description, ':color' => $color]);
        setFlash('success', 'Category created successfully.');
        redirect('index.php');
    }
}
?>

<div class="page-header">
    <div><h1>New Category</h1></div>
    <a href="index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="card fade-in">
    <form method="post">
        <div class="form-grid">

            <div class="field">
                <label for="name">Category Name *</label>
                <input type="text" id="name" name="name"
                       value="<?= e($old['name']) ?>"
                       placeholder="e.g. Technical Issue">
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
                <?php if (isset($errors['color'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['color']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field full">
                <label for="description">Description</label>
                <textarea id="description" name="description"
                          rows="3"
                          placeholder="Brief description of what belongs in this category…"><?= e($old['description']) ?></textarea>
            </div>

        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Category</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
// Live hex preview next to colour picker
document.getElementById('color').addEventListener('input', function () {
    document.getElementById('colorHex').textContent = this.value;
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
