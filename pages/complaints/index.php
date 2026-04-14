<?php
// pages/complaints/index.php  –  List complaints (role-filtered)
$pageTitle = 'Complaints';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth();
require_once __DIR__ . '/../../includes/header.php';

$db   = getDB();
$user = currentUser();

$search       = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status']   ?? '';
$filterCat    = (int)($_GET['category'] ?? 0);

$params = [];
$where  = ['1=1'];

// Public users only see their own complaints
if ($user['role'] === 'public') {
    $where[]        = 'c.user_id = :self_id';
    $params[':self_id'] = $user['id'];
}

if ($search !== '') {
    $where[]         = '(c.title LIKE :search OR c.description LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($filterStatus !== '') {
    $where[]           = 'c.status = :status';
    $params[':status'] = $filterStatus;
}
if ($filterCat > 0) {
    $where[]       = 'c.category_id = :cat';
    $params[':cat'] = $filterCat;
}

$sql = 'SELECT c.id, c.title, c.status, c.created_at,
               u.name AS user_name, cat.name AS category_name, cat.color AS cat_color
        FROM complaints c
        JOIN users      u   ON c.user_id     = u.id
        JOIN categories cat ON c.category_id = cat.id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY c.created_at DESC';

$stmt       = $db->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
?>

<div class="page-header">
    <div>
        <h1>Complaints</h1>
        <p>
            <?php if ($user['role'] === 'public'): ?>
                Your submitted complaints — <?= count($complaints) ?> record(s)
            <?php else: ?>
                All complaints — <?= count($complaints) ?> record(s)
            <?php endif; ?>
        </p>
    </div>
    <a href="create.php" class="btn btn-primary">+ New Complaint</a>
</div>

<!-- Search & Filter -->
<div class="search-bar">
    <form method="get" style="display:contents">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search title or description…">
        <select name="status">
            <option value="">All Statuses</option>
            <?php foreach (['open','in_review','resolved','closed'] as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= statusLabel($s) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $filterCat === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="index.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<?php if (empty($complaints)): ?>
    <div class="empty-state">
        <div class="empty-icon">🗂️</div>
        <p>No complaints found. <a href="create.php">Submit one</a>.</p>
    </div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Category</th>
                <?php if ($user['role'] !== 'public'): ?><th>Submitted By</th><?php endif; ?>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($complaints as $c): ?>
            <tr>
                <td style="color:var(--text-muted);font-size:.8rem">#<?= $c['id'] ?></td>
                <td><?= e($c['title']) ?></td>
                <td>
                    <span class="cat-dot" style="background:<?= e($c['cat_color']) ?>"></span>
                    <?= e($c['category_name']) ?>
                </td>
                <?php if ($user['role'] !== 'public'): ?>
                <td><?= e($c['user_name']) ?></td>
                <?php endif; ?>
                <td><span class="badge <?= statusBadge($c['status']) ?>"><?= statusLabel($c['status']) ?></span></td>
                <td style="color:var(--text-muted);font-size:.82rem"><?= formatDate($c['created_at']) ?></td>
                <td>
                    <div class="actions">
                        <a href="view.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                        <?php if (in_array($user['role'], ['admin','staff'])): ?>
                        <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <?php endif; ?>
                        <?php if ($user['role'] === 'admin'): ?>
                        <button class="btn btn-danger btn-sm"
                            data-delete-url="delete.php?id=<?= $c['id'] ?>"
                            data-delete-label="<?= e($c['title']) ?>">Delete</button>
                        <?php endif; ?>
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
        <p>Delete <strong id="deleteLabel"></strong>? All linked responses will also be removed.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <a id="deleteConfirmBtn" href="#" class="btn btn-danger">Delete</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
