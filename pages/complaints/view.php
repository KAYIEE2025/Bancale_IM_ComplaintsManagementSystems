<?php
// pages/complaints/view.php
require_once __DIR__ . '/../../includes/auth.php';
requireAuth();
require_once __DIR__ . '/../../includes/header.php';

$db   = getDB();
$user = currentUser();
$id   = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare(
    'SELECT c.*, u.name AS user_name, u.email AS user_email,
            cat.name AS category_name, cat.color AS cat_color
     FROM complaints c
     JOIN users u        ON c.user_id     = u.id
     JOIN categories cat ON c.category_id = cat.id
     WHERE c.id = :id'
);
$stmt->execute([':id' => $id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('error', 'Complaint not found.');
    redirect('index.php');
}

// Public users may only view their own complaints
if ($user['role'] === 'public' && (int)$complaint['user_id'] !== (int)$user['id']) {
    setFlash('error', 'You do not have permission to view that complaint.');
    redirect('index.php');
}

// ── Handle inline reply POST (all roles) ────────────────────
$replyError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $message = trim($_POST['reply_message'] ?? '');

    if ($message === '') {
        $replyError = 'Reply cannot be empty.';
    } else {
        // Public users always post as regular reply (is_admin_reply = 0)
        // Staff/Admin can toggle official reply via checkbox
        $isAdminReply = 0;
        if (in_array($user['role'], ['admin', 'staff'])) {
            $isAdminReply = isset($_POST['is_official']) ? 1 : 0;
        }

        $ins = $db->prepare(
            'INSERT INTO responses (complaint_id, user_id, message, is_admin_reply)
             VALUES (:cid, :uid, :msg, :admin)'
        );
        $ins->execute([
            ':cid'   => $id,
            ':uid'   => $user['id'],
            ':msg'   => $message,
            ':admin' => $isAdminReply,
        ]);
        setFlash('success', 'Your reply has been posted.');
        redirect('view.php?id=' . $id);
    }
}

// Fetch responses
$rStmt = $db->prepare(
    'SELECT r.*, u.name AS user_name, u.role AS user_role
     FROM responses r
     JOIN users u ON r.user_id = u.id
     WHERE r.complaint_id = :id
     ORDER BY r.created_at ASC'
);
$rStmt->execute([':id' => $id]);
$responses = $rStmt->fetchAll();

$pageTitle = e($complaint['title']);
?>

<div class="page-header">
    <div>
        <h1 style="font-size:1.5rem"><?= e($complaint['title']) ?></h1>
        <p>Complaint #<?= $complaint['id'] ?> &mdash; <?= formatDate($complaint['created_at']) ?></p>
    </div>
    <div class="actions">
        <?php if (in_array($user['role'], ['admin','staff'])): ?>
            <a href="edit.php?id=<?= $complaint['id'] ?>" class="btn btn-secondary">Edit</a>
        <?php endif; ?>
        <?php if ($user['role'] === 'admin'): ?>
            <button class="btn btn-danger"
                data-delete-url="delete.php?id=<?= $complaint['id'] ?>"
                data-delete-label="this complaint">Delete</button>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary">← Back</a>
    </div>
</div>

<div class="detail-grid fade-in">

    <!-- Left: description + thread + reply form -->
    <div>

        <!-- Description -->
        <div class="card" style="margin-bottom:1.25rem">
            <h3 style="font-family:var(--font-head);margin-bottom:.75rem;font-size:1rem">Description</h3>
            <p style="color:var(--text-subtle);line-height:1.75"><?= nl2br(e($complaint['description'])) ?></p>
        </div>

        <!-- Response thread -->
        <div class="card" style="margin-bottom:1.25rem">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
                <h3 style="font-family:var(--font-head);font-size:1rem">
                    Conversation <span style="color:var(--text-muted);font-weight:400">(<?= count($responses) ?>)</span>
                </h3>
                <?php if (in_array($user['role'], ['admin','staff'])): ?>
                    <a href="../responses/create.php?complaint_id=<?= $complaint['id'] ?>"
                       class="btn btn-primary btn-sm">+ Add Response</a>
                <?php endif; ?>
            </div>

            <?php if (empty($responses)): ?>
                <div class="empty-state" style="padding:2rem">
                    <div class="empty-icon" style="font-size:2rem">💬</div>
                    <p>
                        <?= $user['role'] === 'public'
                            ? 'No replies yet. A staff member will respond soon. You can also add a comment below.'
                            : 'No responses yet.' ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="response-thread">
                <?php foreach ($responses as $r): ?>
                    <?php
                        $isOwn      = (int)$r['user_id'] === (int)$user['id'];
                        $isOfficial = (bool)$r['is_admin_reply'];
                        $roleLabel  = match($r['user_role']) {
                            'admin'  => 'Admin',
                            'staff'  => 'Staff',
                            default  => 'You',
                        };
                    ?>
                    <div class="response-item <?= $isOfficial ? 'admin-reply' : ($isOwn ? 'own-reply' : '') ?>">
                        <div class="response-meta">
                            <span class="response-author">
                                <?= $isOwn ? 'You' : e($r['user_name']) ?>
                            </span>
                            <?php if ($isOfficial): ?>
                                <span class="admin-tag"><?= $roleLabel ?></span>
                            <?php elseif ($r['user_role'] === 'public' && !$isOwn): ?>
                                <span class="user-tag">User</span>
                            <?php endif; ?>
                            <span><?= formatDate($r['created_at']) ?></span>
                        </div>
                        <p class="response-body"><?= nl2br(e($r['message'])) ?></p>

                        <?php if (in_array($user['role'], ['admin','staff'])): ?>
                        <div class="actions" style="margin-top:.6rem">
                            <a href="../responses/edit.php?id=<?= $r['id'] ?>"
                               class="btn btn-secondary btn-sm">Edit</a>
                            <button class="btn btn-danger btn-sm"
                                data-delete-url="../responses/delete.php?id=<?= $r['id'] ?>&redirect=<?= urlencode('view.php?id=' . $complaint['id']) ?>"
                                data-delete-label="this response">Delete</button>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Public reply form ── -->
        <?php if ($user['role'] === 'public'): ?>
        <div class="card reply-card">
            <h3 style="font-family:var(--font-head);font-size:1rem;margin-bottom:.75rem">
                💬 Add a Comment
            </h3>
            <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1rem">
                You can provide additional information or follow up on this complaint.
            </p>

            <?php if ($replyError): ?>
                <div class="alert alert-error" style="margin-bottom:1rem">
                    <?= e($replyError) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="field" style="margin-bottom:1rem">
                    <textarea name="reply_message"
                              rows="4"
                              placeholder="Write your comment or additional details here…"
                              style="width:100%"></textarea>
                </div>
                <div class="form-actions" style="margin-top:0">
                    <button type="submit" class="btn btn-primary">Post Comment</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Staff/admin inline quick-reply -->
        <?php if (in_array($user['role'], ['admin','staff'])): ?>
        <div class="card reply-card">
            <h3 style="font-family:var(--font-head);font-size:1rem;margin-bottom:.75rem">
                ✏️ Quick Reply
            </h3>

            <?php if ($replyError): ?>
                <div class="alert alert-error" style="margin-bottom:1rem">
                    <?= e($replyError) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="field" style="margin-bottom:.75rem">
                    <textarea name="reply_message"
                              rows="3"
                              placeholder="Type a quick response…"
                              style="width:100%"></textarea>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;
                                  text-transform:none;letter-spacing:0;font-size:.875rem;font-weight:400;color:var(--text-muted)">
                        <input type="checkbox" name="is_official" value="1"
                               style="width:15px;height:15px;accent-color:var(--accent)" checked>
                        Mark as official reply
                    </label>
                    <button type="submit" class="btn btn-primary btn-sm">Post Reply</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </div>

    <!-- Right: Details sidebar -->
    <div>
        <div class="card">
            <h3 style="font-family:var(--font-head);font-size:1rem;margin-bottom:1rem">Details</h3>
            <ul class="meta-list">
                <li>
                    <span class="key">Status</span>
                    <span class="val">
                        <span class="badge <?= statusBadge($complaint['status']) ?>">
                            <?= statusLabel($complaint['status']) ?>
                        </span>
                    </span>
                </li>
                <li>
                    <span class="key">Category</span>
                    <span class="val">
                        <span class="cat-dot" style="background:<?= e($complaint['cat_color']) ?>"></span>
                        <?= e($complaint['category_name']) ?>
                    </span>
                </li>
                <?php if ($user['role'] !== 'public'): ?>
                <li>
                    <span class="key">Submitted By</span>
                    <span class="val"><?= e($complaint['user_name']) ?></span>
                </li>
                <li>
                    <span class="key">Email</span>
                    <span class="val" style="font-size:.82rem"><?= e($complaint['user_email']) ?></span>
                </li>
                <?php endif; ?>
                <li>
                    <span class="key">Created</span>
                    <span class="val" style="font-size:.82rem"><?= formatDate($complaint['created_at']) ?></span>
                </li>
                <li>
                    <span class="key">Last Updated</span>
                    <span class="val" style="font-size:.82rem"><?= formatDate($complaint['updated_at']) ?></span>
                </li>
                <li>
                    <span class="key">Replies</span>
                    <span class="val"><?= count($responses) ?></span>
                </li>
            </ul>
        </div>

        <!-- Status legend for public -->
        <?php if ($user['role'] === 'public'): ?>
        <div class="card" style="margin-top:1rem">
            <h3 style="font-family:var(--font-head);font-size:.9rem;margin-bottom:.75rem;color:var(--text-muted)">
                Status Guide
            </h3>
            <ul class="meta-list">
                <li>
                    <span class="badge badge-open">Open</span>
                    <span style="font-size:.8rem;color:var(--text-muted)">Awaiting review</span>
                </li>
                <li>
                    <span class="badge badge-review">In Review</span>
                    <span style="font-size:.8rem;color:var(--text-muted)">Being handled</span>
                </li>
                <li>
                    <span class="badge badge-resolved">Resolved</span>
                    <span style="font-size:.8rem;color:var(--text-muted)">Issue addressed</span>
                </li>
                <li>
                    <span class="badge badge-closed">Closed</span>
                    <span style="font-size:.8rem;color:var(--text-muted)">No further action</span>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>

</div>

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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
