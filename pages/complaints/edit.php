<?php
// pages/complaints/edit.php  –  Admin & Staff can edit
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'staff']);
require_once __DIR__ . '/../../includes/header.php';

$db        = getDB();
$authUser  = currentUser();
$id        = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM complaints WHERE id = :id');
$stmt->execute([':id' => $id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('error', 'Complaint not found.');
    redirect('index.php');
}

$pageTitle = 'Edit Complaint';
$errors    = [];
$old       = $complaint;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = $_POST['status']           ?? 'open';
    // Only admin can reassign user/category
    $userId      = ($authUser['role'] === 'admin') ? (int)($_POST['user_id'] ?? $complaint['user_id']) : (int)$complaint['user_id'];
    $categoryId  = ($authUser['role'] === 'admin') ? (int)($_POST['category_id'] ?? $complaint['category_id']) : (int)$complaint['category_id'];

    $old = array_merge($old, compact('title', 'description', 'status', 'userId', 'categoryId'));

    if (!required($title))       $errors['title']       = 'Title is required.';
    if (!required($description)) $errors['description'] = 'Description is required.';
    if (!in_array($status, ['open','in_review','resolved','closed'])) $errors['status'] = 'Invalid status.';

    if (empty($errors)) {
        $upd = $db->prepare(
            'UPDATE complaints
             SET user_id=:uid, category_id=:cid, title=:title, description=:desc, status=:status
             WHERE id=:id'
        );
        $upd->execute([
            ':uid'    => $userId,
            ':cid'    => $categoryId,
            ':title'  => $title,
            ':desc'   => $description,
            ':status' => $status,
            ':id'     => $id,
        ]);
        setFlash('success', 'Complaint updated.');
        redirect('view.php?id=' . $id);
    }
}

$users      = $db->query('SELECT id, name FROM users ORDER BY name')->fetchAll();
$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$statuses   = ['open', 'in_review', 'resolved', 'closed'];
?>

<div class="page-header">
    <div><h1>Edit Complaint #<?= $id ?></h1></div>
    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">← Back</a>
</div>

<div class="card fade-in">
    <form method="post">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-grid">

            <div class="field full">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" value="<?= e($old['title']) ?>">
                <?php if (isset($errors['title'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['title']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field full">
                <label for="description">Description *</label>
                <textarea id="description" name="description" rows="5"><?= e($old['description']) ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['description']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Admin-only: reassign user & category -->
            <?php if ($authUser['role'] === 'admin'): ?>
            <div class="field">
                <label for="user_id">Submitted By</label>
                <select id="user_id" name="user_id">
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (int)$old['user_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)$old['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= $old['status'] === $s ? 'selected' : '' ?>><?= statusLabel($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($authUser['role'] === 'staff'): ?>
                    <span class="field-hint">As staff you can update the status. Contact admin to reassign category or submitter.</span>
                <?php endif; ?>
            </div>

        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
