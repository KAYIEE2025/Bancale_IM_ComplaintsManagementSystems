<?php
// pages/complaints/create.php
$pageTitle = 'New Complaint';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth();
require_once __DIR__ . '/../../includes/header.php';

$db   = getDB();
$user = currentUser();
$errors = [];
$old    = ['title' => '', 'description' => '', 'user_id' => $user['id'], 'category_id' => '', 'status' => 'open'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = $_POST['status'] ?? 'open';
    $categoryId  = (int)($_POST['category_id'] ?? 0);

    // Public users can only submit as themselves; admin/staff may pick any user
    $userId = ($user['role'] === 'public')
        ? $user['id']
        : (int)($_POST['user_id'] ?? $user['id']);

    $old = compact('title', 'description', 'userId', 'categoryId', 'status');

    if (!required($title))       $errors['title']       = 'Title is required.';
    if (!required($description)) $errors['description'] = 'Description is required.';
    if ($categoryId <= 0)        $errors['category_id'] = 'Please select a category.';
    if (!in_array($status, ['open','in_review','resolved','closed'])) {
        $errors['status'] = 'Invalid status.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare(
            'INSERT INTO complaints (user_id, category_id, title, description, status)
             VALUES (:uid, :cid, :title, :desc, :status)'
        );
        $stmt->execute([
            ':uid'    => $userId,
            ':cid'    => $categoryId,
            ':title'  => $title,
            ':desc'   => $description,
            ':status' => $status,
        ]);

        setFlash('success', 'Complaint submitted successfully.');
        redirect('index.php');
    }
}

$categories = $db->query('SELECT id, name, color FROM categories ORDER BY name')->fetchAll();
$statuses   = ['open', 'in_review', 'resolved', 'closed'];
// Admin/staff can assign to any user
$users = [];
if (in_array($user['role'], ['admin','staff'])) {
    $users = $db->query('SELECT id, name FROM users ORDER BY name')->fetchAll();
}
?>

<div class="page-header">
    <div>
        <h1>New Complaint</h1>
        <p>Submit a new complaint or feedback.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">← Back to List</a>
</div>

<div class="card fade-in">
    <form method="post">
        <div class="form-grid">

            <div class="field full">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title"
                       value="<?= e($old['title']) ?>"
                       placeholder="Brief summary of your complaint">
                <?php if (isset($errors['title'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['title']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field full">
                <label for="description">Description *</label>
                <textarea id="description" name="description" rows="5"
                          placeholder="Describe your complaint in detail…"><?= e($old['description']) ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['description']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id">
                    <option value="">— Select Category —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"
                            <?= (int)$old['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category_id'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['category_id']) ?></span>
                <?php endif; ?>
            </div>

            <?php if (in_array($user['role'], ['admin','staff']) && !empty($users)): ?>
            <div class="field">
                <label for="user_id">Submit On Behalf Of</label>
                <select id="user_id" name="user_id">
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"
                            <?= (int)$old['user_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= $old['status'] === $s ? 'selected' : '' ?>><?= statusLabel($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
                <!-- Public: hidden fixed values -->
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <input type="hidden" name="status"  value="open">
            <?php endif; ?>

        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Submit Complaint</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
