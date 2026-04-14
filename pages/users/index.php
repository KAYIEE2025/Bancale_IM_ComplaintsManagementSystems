<?php
// pages/users/index.php
$pageTitle = 'Users';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/header.php';

$db     = getDB();
$search = trim($_GET['search'] ?? '');
$filterRole = $_GET['role'] ?? '';

$params = [];
$where  = ['1=1'];

if ($search !== '') {
    $where[]      = '(name LIKE :s OR email LIKE :s)';
    $params[':s'] = '%' . $search . '%';
}
if (in_array($filterRole, ['admin','staff','public'])) {
    $where[]          = 'role = :role';
    $params[':role']  = $filterRole;
}

$stmt  = $db->prepare('SELECT * FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC');
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="page-header">
    <div><h1>Users</h1><p><?= count($users) ?> registered user(s)</p></div>
    <a href="create.php" class="btn btn-primary">+ New User</a>
</div>

<div class="search-bar">
    <form method="get" style="display:contents">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name or email…">
        <select name="role">
            <option value="">All Roles</option>
            <option value="admin"  <?= $filterRole === 'admin'  ? 'selected' : '' ?>>Admin</option>
            <option value="staff"  <?= $filterRole === 'staff'  ? 'selected' : '' ?>>Staff</option>
            <option value="public" <?= $filterRole === 'public' ? 'selected' : '' ?>>Public</option>
        </select>
        <button type="submit" class="btn btn-secondary">Search</button>
        <a href="index.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<?php if (empty($users)): ?>
    <div class="empty-state"><div class="empty-icon">👤</div><p>No users found.</p></div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td style="color:var(--text-muted);font-size:.8rem">#<?= $u['id'] ?></td>
                <td style="font-weight:500"><?= e($u['name']) ?></td>
                <td style="color:var(--text-muted)"><?= e($u['email']) ?></td>
                <td><span class="role-pill role-<?= e($u['role']) ?>"><?= ucfirst(e($u['role'])) ?></span></td>
                <td style="color:var(--text-muted);font-size:.82rem"><?= formatDate($u['created_at']) ?></td>
                <td>
                    <div class="actions">
                        <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <button class="btn btn-danger btn-sm"
                            data-delete-url="delete.php?id=<?= $u['id'] ?>"
                            data-delete-label="<?= e($u['name']) ?>">Delete</button>
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
        <p>Delete user <strong id="deleteLabel"></strong>? Their complaints and responses will also be removed.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <a id="deleteConfirmBtn" href="#" class="btn btn-danger">Delete</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
