<?php
// pages/responses/edit.php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','staff']);
require_once __DIR__ . '/../../includes/header.php';

$db  = getDB();
$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM responses WHERE id = :id');
$stmt->execute([':id' => $id]);
$response = $stmt->fetch();

if (!$response) { setFlash('error', 'Response not found.'); redirect('index.php'); }

$pageTitle = 'Edit Response';
$errors    = [];
$old       = $response;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId  = (int)($_POST['complaint_id'] ?? 0);
    $userId       = (int)($_POST['user_id']       ?? 0);
    $message      = trim($_POST['message']        ?? '');
    $isAdminReply = isset($_POST['is_admin_reply']) ? 1 : 0;
    $old          = array_merge($old, compact('complaintId', 'userId', 'message', 'isAdminReply'));

    if ($complaintId <= 0) $errors['complaint_id'] = 'Please select a complaint.';
    if ($userId      <= 0) $errors['user_id']      = 'Please select a user.';
    if (!required($message)) $errors['message']    = 'Message is required.';

    if (empty($errors)) {
        $upd = $db->prepare(
            'UPDATE responses
             SET complaint_id = :cid, user_id = :uid, message = :msg, is_admin_reply = :admin
             WHERE id = :id'
        );
        $upd->execute([
            ':cid'   => $complaintId,
            ':uid'   => $userId,
            ':msg'   => $message,
            ':admin' => $isAdminReply,
            ':id'    => $id,
        ]);

        setFlash('success', 'Response updated successfully.');
        redirect('index.php');
    }
}

$complaints = $db->query('SELECT id, title FROM complaints ORDER BY created_at DESC')->fetchAll();
$users      = $db->query('SELECT id, name, role FROM users ORDER BY name')->fetchAll();
?>

<div class="page-header">
    <div><h1>Edit Response #<?= $id ?></h1></div>
    <a href="index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="card fade-in">
    <form method="post">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-grid">

            <div class="field full">
                <label for="complaint_id">Complaint *</label>
                <select id="complaint_id" name="complaint_id">
                    <option value="">— Select Complaint —</option>
                    <?php foreach ($complaints as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            <?= (int)$old['complaint_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                            #<?= $c['id'] ?> — <?= e($c['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['complaint_id'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['complaint_id']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="user_id">Responded By *</label>
                <select id="user_id" name="user_id">
                    <option value="">— Select User —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"
                            <?= (int)$old['user_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?> (<?= e($u['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['user_id'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['user_id']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field" style="justify-content:center">
                <label>&nbsp;</label>
                <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;text-transform:none;letter-spacing:0;font-size:.9rem;margin-top:.2rem">
                    <input type="checkbox" name="is_admin_reply" value="1"
                           <?= $old['is_admin_reply'] ? 'checked' : '' ?>
                           style="width:16px;height:16px;accent-color:var(--accent)">
                    Mark as Admin Reply
                </label>
            </div>

            <div class="field full">
                <label for="message">Message *</label>
                <textarea id="message" name="message" rows="5"><?= e($old['message']) ?></textarea>
                <?php if (isset($errors['message'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['message']) ?></span>
                <?php endif; ?>
            </div>

        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
