<?php
// pages/responses/create.php
$pageTitle = 'New Response';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'staff']);
require_once __DIR__ . '/../../includes/header.php';

$db            = getDB();
$authUser      = currentUser();
$preComplaintId = (int)($_GET['complaint_id'] ?? 0);
$errors        = [];
$old           = ['complaint_id' => $preComplaintId, 'user_id' => $authUser['id'], 'message' => '', 'is_admin_reply' => '0'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId  = (int)($_POST['complaint_id']  ?? 0);
    $userId       = (int)($_POST['user_id']        ?? $authUser['id']);
    $message      = trim($_POST['message']         ?? '');
    $isAdminReply = isset($_POST['is_admin_reply']) ? 1 : 0;
    $old          = compact('complaintId', 'userId', 'message', 'isAdminReply');

    if ($complaintId <= 0)   $errors['complaint_id'] = 'Please select a complaint.';
    if (!required($message)) $errors['message']      = 'Message cannot be empty.';

    if (empty($errors)) {
        $stmt = $db->prepare(
            'INSERT INTO responses (complaint_id, user_id, message, is_admin_reply)
             VALUES (:cid, :uid, :msg, :admin)'
        );
        $stmt->execute([
            ':cid'   => $complaintId,
            ':uid'   => $userId,
            ':msg'   => $message,
            ':admin' => $isAdminReply,
        ]);

        setFlash('success', 'Response posted.');
        if ($preComplaintId > 0) {
            redirect('../complaints/view.php?id=' . $preComplaintId);
        } else {
            redirect('index.php');
        }
    }
}

$complaints = $db->query('SELECT id, title FROM complaints ORDER BY created_at DESC')->fetchAll();
$users      = $db->query('SELECT id, name, role FROM users ORDER BY name')->fetchAll();
?>

<div class="page-header">
    <div><h1>New Response</h1></div>
    <div class="actions">
        <?php if ($preComplaintId > 0): ?>
            <a href="../complaints/view.php?id=<?= $preComplaintId ?>" class="btn btn-secondary">← Back to Complaint</a>
        <?php else: ?>
            <a href="index.php" class="btn btn-secondary">← Back</a>
        <?php endif; ?>
    </div>
</div>

<div class="card fade-in">
    <form method="post">
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
                <label for="user_id">Responding As</label>
                <select id="user_id" name="user_id">
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"
                            <?= (int)$old['user_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?> (<?= e($u['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field" style="justify-content:flex-end">
                <label>&nbsp;</label>
                <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;text-transform:none;letter-spacing:0;font-size:.9rem;margin-top:.15rem">
                    <input type="checkbox" name="is_admin_reply" value="1"
                           <?= !empty($old['isAdminReply']) ? 'checked' : '' ?>
                           style="width:16px;height:16px;accent-color:var(--accent)">
                    Mark as Official Reply
                </label>
                <span class="field-hint">Official replies are highlighted gold in the complaint thread.</span>
            </div>

            <div class="field full">
                <label for="message">Message *</label>
                <textarea id="message" name="message" rows="5"
                          placeholder="Write your response here…"><?= e($old['message']) ?></textarea>
                <?php if (isset($errors['message'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['message']) ?></span>
                <?php endif; ?>
            </div>

        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Post Response</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
