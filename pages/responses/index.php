<?php
// pages/responses/index.php
$pageTitle = 'Responses';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','staff']);
require_once __DIR__ . '/../../includes/header.php';

$db         = getDB();
$search     = trim($_GET['search'] ?? '');
$filterType = $_GET['type'] ?? '';   // 'admin' | 'user' | ''

$params = [];
$where  = ['1=1'];

if ($search !== '') {
    $where[]      = 'r.message LIKE :s';
    $params[':s'] = '%' . $search . '%';
}
if ($filterType === 'admin') {
    $where[] = 'r.is_admin_reply = 1';
} elseif ($filterType === 'user') {
    $where[] = 'r.is_admin_reply = 0';
}

$sql = 'SELECT r.id, r.message, r.is_admin_reply, r.created_at,
               u.name AS user_name,
               c.id   AS complaint_id, c.title AS complaint_title
        FROM responses r
        JOIN users      u ON r.user_id      = u.id
        JOIN complaints c ON r.complaint_id = c.id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY r.created_at DESC';

$stmt      = $db->prepare($sql);
$stmt->execute($params);
$responses = $stmt->fetchAll();
?>

<div class="page-header">
    <div><h1>Responses</h1><p><?= count($responses) ?> response(s)</p></div>
    <a href="create.php" class="btn btn-primary">+ New Response</a>
</div>

<div class="search-bar">
    <form method="get" style="display:contents">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search message text…">
        <select name="type">
            <option value="">All Types</option>
            <option value="admin" <?= $filterType === 'admin' ? 'selected' : '' ?>>Admin Replies</option>
            <option value="user"  <?= $filterType === 'user'  ? 'selected' : '' ?>>User Replies</option>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="index.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<?php if (empty($responses)): ?>
    <div class="empty-state"><div class="empty-icon">💬</div><p>No responses found.</p></div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Complaint</th>
                <th>Responded By</th>
                <th>Type</th>
                <th>Message Preview</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($responses as $r): ?>
            <tr>
                <td style="color:var(--text-muted);font-size:.8rem">#<?= $r['id'] ?></td>
                <td>
                    <a href="../complaints/view.php?id=<?= $r['complaint_id'] ?>"
                       style="color:var(--accent);font-size:.875rem">
                        <?= e(mb_strimwidth($r['complaint_title'], 0, 40, '…')) ?>
                    </a>
                </td>
                <td><?= e($r['user_name']) ?></td>
                <td>
                    <?php if ($r['is_admin_reply']): ?>
                        <span class="badge badge-resolved">Admin</span>
                    <?php else: ?>
                        <span class="badge badge-open">User</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?= e(mb_strimwidth($r['message'], 0, 80, '…')) ?>
                </td>
                <td style="color:var(--text-muted);font-size:.82rem"><?= formatDate($r['created_at']) ?></td>
                <td>
                    <div class="actions">
                        <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <button class="btn btn-danger btn-sm"
                            data-delete-url="delete.php?id=<?= $r['id'] ?>"
                            data-delete-label="response #<?= $r['id'] ?>">Delete</button>
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
        <p>Delete <strong id="deleteLabel"></strong>? This cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <a id="deleteConfirmBtn" href="#" class="btn btn-danger">Delete</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
