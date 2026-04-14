<?php
// index.php  –  Dashboard / home page
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
require_once __DIR__ . '/includes/header.php';

$db   = getDB();
$user = currentUser();

// Stats visible to admin/staff only
$totalComplaints    = $db->query('SELECT COUNT(*) FROM complaints')->fetchColumn();
$openComplaints     = $db->query("SELECT COUNT(*) FROM complaints WHERE status='open'")->fetchColumn();
$inReviewComplaints = $db->query("SELECT COUNT(*) FROM complaints WHERE status='in_review'")->fetchColumn();
$resolvedComplaints = $db->query("SELECT COUNT(*) FROM complaints WHERE status='resolved'")->fetchColumn();
$totalUsers         = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalResponses     = $db->query('SELECT COUNT(*) FROM responses')->fetchColumn();

// Recent complaints — public sees only their own
if ($user['role'] === 'public') {
    $recentStmt = $db->prepare(
        'SELECT c.id, c.title, c.status, c.created_at,
                u.name AS user_name, cat.name AS category_name, cat.color AS cat_color
         FROM complaints c
         JOIN users      u   ON c.user_id     = u.id
         JOIN categories cat ON c.category_id = cat.id
         WHERE c.user_id = :uid
         ORDER BY c.created_at DESC LIMIT 8'
    );
    $recentStmt->execute([':uid' => $user['id']]);
} else {
    $recentStmt = $db->query(
        'SELECT c.id, c.title, c.status, c.created_at,
                u.name AS user_name, cat.name AS category_name, cat.color AS cat_color
         FROM complaints c
         JOIN users      u   ON c.user_id     = u.id
         JOIN categories cat ON c.category_id = cat.id
         ORDER BY c.created_at DESC LIMIT 8'
    );
}
$recentComplaints = $recentStmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1>
            <?php if ($user['role'] === 'public'): ?>
                My Complaints
            <?php else: ?>
                Dashboard
            <?php endif; ?>
        </h1>
        <p>
            Welcome back, <strong><?= e($user['name']) ?></strong>
            <span class="role-pill role-<?= e($user['role']) ?>" style="margin-left:.4rem"><?= ucfirst(e($user['role'])) ?></span>
        </p>
    </div>
    <a href="pages/complaints/create.php" class="btn btn-primary">+ New Complaint</a>
</div>

<!-- Stats (admin + staff only) -->
<?php if (in_array($user['role'], ['admin','staff'])): ?>
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-number"><?= $totalComplaints ?></div>
        <div class="stat-label">Total Complaints</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color:#f87171"><?= $openComplaints ?></div>
        <div class="stat-label">Open</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color:#fb923c"><?= $inReviewComplaints ?></div>
        <div class="stat-label">In Review</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color:#4ade80"><?= $resolvedComplaints ?></div>
        <div class="stat-label">Resolved</div>
    </div>
    <?php if ($user['role'] === 'admin'): ?>
    <div class="stat-card">
        <div class="stat-number"><?= $totalUsers ?></div>
        <div class="stat-label">Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $totalResponses ?></div>
        <div class="stat-label">Responses</div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Public user quick status banner -->
<?php if ($user['role'] === 'public'): ?>
<div class="stats-row">
    <?php
    $myTotal    = $db->prepare('SELECT COUNT(*) FROM complaints WHERE user_id=:uid'); $myTotal->execute([':uid'=>$user['id']]);
    $myOpen     = $db->prepare("SELECT COUNT(*) FROM complaints WHERE user_id=:uid AND status='open'"); $myOpen->execute([':uid'=>$user['id']]);
    $myResolved = $db->prepare("SELECT COUNT(*) FROM complaints WHERE user_id=:uid AND status='resolved'"); $myResolved->execute([':uid'=>$user['id']]);
    ?>
    <div class="stat-card">
        <div class="stat-number"><?= $myTotal->fetchColumn() ?></div>
        <div class="stat-label">My Complaints</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color:#f87171"><?= $myOpen->fetchColumn() ?></div>
        <div class="stat-label">Open</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color:#4ade80"><?= $myResolved->fetchColumn() ?></div>
        <div class="stat-label">Resolved</div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Complaints Table -->
<div class="card fade-in">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
        <h2 style="font-family:var(--font-head);font-size:1.1rem;font-weight:700">
            <?= $user['role'] === 'public' ? 'My Recent Complaints' : 'Recent Complaints' ?>
        </h2>
        <a href="pages/complaints/index.php" class="btn btn-secondary btn-sm">View All →</a>
    </div>

    <?php if (empty($recentComplaints)): ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <p>No complaints yet. <a href="pages/complaints/create.php">Submit one now</a>.</p>
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
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentComplaints as $c): ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:.8rem">#<?= $c['id'] ?></td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= e($c['title']) ?>
                    </td>
                    <td>
                        <span class="cat-dot" style="background:<?= e($c['cat_color']) ?>"></span>
                        <?= e($c['category_name']) ?>
                    </td>
                    <?php if ($user['role'] !== 'public'): ?>
                    <td><?= e($c['user_name']) ?></td>
                    <?php endif; ?>
                    <td><span class="badge <?= statusBadge($c['status']) ?>"><?= statusLabel($c['status']) ?></span></td>
                    <td style="color:var(--text-muted);font-size:.82rem"><?= formatDate($c['created_at']) ?></td>
                    <td><a href="pages/complaints/view.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Role permission info card for public users -->
<?php if ($user['role'] === 'public'): ?>
<div class="card fade-in" style="margin-top:1.25rem;border-color:rgba(240,192,64,.2);background:rgba(240,192,64,.04)">
    <h3 style="font-family:var(--font-head);font-size:.95rem;margin-bottom:.5rem;color:var(--accent)">
        ℹ️ What you can do
    </h3>
    <p style="color:var(--text-muted);font-size:.875rem;line-height:1.7">
        As a <strong style="color:var(--text)">Public</strong> user you can <strong style="color:var(--text)">submit complaints</strong> and
        <strong style="color:var(--text)">track their status</strong>. A staff member will review and respond to your complaints.
        You can view full details and staff responses on each complaint page.
    </p>
</div>
<?php endif; ?>

<!-- Delete Modal -->
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
