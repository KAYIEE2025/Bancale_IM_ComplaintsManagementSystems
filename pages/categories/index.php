<?php
// pages/categories/index.php
$pageTitle = 'Categories';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/header.php';

$db         = getDB();
$search     = trim($_GET['search'] ?? '');
$params     = [];
$where      = '1=1';

if ($search !== '') {
    $where        = '(name LIKE :s OR description LIKE :s)';
    $params[':s'] = '%' . $search . '%';
}

$stmt       = $db->prepare("SELECT c.*, COUNT(comp.id) AS complaint_count
    FROM categories c
    LEFT JOIN complaints comp ON comp.category_id = c.id
    WHERE $where
    GROUP BY c.id
    ORDER BY c.name ASC");
$stmt->execute($params);
$categories = $stmt->fetchAll();
?>

<div class="page-header">
    <div><h1>Categories</h1><p><?= count($categories) ?> categor<?= count($categories) === 1 ? 'y' : 'ies' ?></p></div>
    <a href="create.php" class="btn btn-primary">+ New Category</a>
</div>

<div class="search-bar">
    <form method="get" style="display:contents">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search categories…">
        <button type="submit" class="btn btn-secondary">Search</button>
        <a href="index.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<?php if (empty($categories)): ?>
    <div class="empty-state"><div class="empty-icon">🏷️</div><p>No categories found.</p></div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr><th>#</th><th>Name</th><th>Description</th><th>Colour</th><th>Complaints</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
            <tr>
                <td style="color:var(--text-muted);font-size:.8rem">#<?= $cat['id'] ?></td>
                <td style="font-weight:600">
                    <span class="cat-dot" style="background:<?= e($cat['color']) ?>"></span>
                    <?= e($cat['name']) ?>
                </td>
                <td style="color:var(--text-muted);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?= e($cat['description']) ?>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:.5rem">
                        <span style="width:22px;height:22px;border-radius:4px;background:<?= e($cat['color']) ?>;display:inline-block;border:1px solid rgba(255,255,255,.12)"></span>
                        <code style="font-size:.78rem;color:var(--text-muted)"><?= e($cat['color']) ?></code>
                    </div>
                </td>
                <td>
                    <span class="badge badge-resolved"><?= (int)$cat['complaint_count'] ?></span>
                </td>
                <td style="color:var(--text-muted);font-size:.82rem"><?= formatDate($cat['created_at']) ?></td>
                <td>
                    <div class="actions">
                        <a href="edit.php?id=<?= $cat['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <button class="btn btn-danger btn-sm"
                            data-delete-url="delete.php?id=<?= $cat['id'] ?>"
                            data-delete-label="<?= e($cat['name']) ?>">Delete</button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>Confirm Delete</h3>
        <p>Delete category <strong id="deleteLabel"></strong>? This will fail if complaints are still assigned to it.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <a id="deleteConfirmBtn" href="#" class="btn btn-danger">Delete</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
