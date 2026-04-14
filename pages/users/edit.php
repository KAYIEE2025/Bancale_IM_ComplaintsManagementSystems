<?php
// pages/users/edit.php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

if (!$user) { setFlash('error', 'User not found.'); redirect('index.php'); }

$pageTitle = 'Edit User';
$errors    = [];
$old       = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $role     = $_POST['role']          ?? 'public';
    $old      = array_merge($old, compact('name', 'email', 'role'));

    if (!required($name))                             $errors['name']  = 'Name is required.';
    if (!validEmail($email))                          $errors['email'] = 'Valid email is required.';
    if (!in_array($role, ['admin','staff','public'])) $errors['role']  = 'Invalid role.';
    if ($password !== '' && strlen($password) < 8)   $errors['password'] = 'Password must be at least 8 characters.';

    if (empty($errors['email'])) {
        $chk = $db->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $chk->execute([':email' => $email, ':id' => $id]);
        if ($chk->fetch()) $errors['email'] = 'This email is already used by another account.';
    }

    if (empty($errors)) {
        if ($password !== '') {
            // Update password too
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $upd  = $db->prepare('UPDATE users SET name=:name, email=:email, password=:pw, role=:role WHERE id=:id');
            $upd->execute([':name' => $name, ':email' => $email, ':pw' => $hash, ':role' => $role, ':id' => $id]);
        } else {
            // Leave password unchanged
            $upd = $db->prepare('UPDATE users SET name=:name, email=:email, role=:role WHERE id=:id');
            $upd->execute([':name' => $name, ':email' => $email, ':role' => $role, ':id' => $id]);
        }
        setFlash('success', 'User updated successfully.');
        redirect('index.php');
    }
}
?>

<div class="page-header">
    <div><h1>Edit User #<?= $id ?></h1></div>
    <a href="index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="card fade-in">
    <form method="post">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-grid">

            <div class="field">
                <label>Full Name *</label>
                <input type="text" name="name" value="<?= e($old['name']) ?>">
                <?php if (isset($errors['name'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label>Email *</label>
                <input type="email" name="email" value="<?= e($old['email']) ?>">
                <?php if (isset($errors['email'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label>New Password <span style="color:var(--text-muted);font-weight:400">(leave blank to keep current)</span></label>
                <input type="password" name="password" placeholder="Min. 8 characters">
                <?php if (isset($errors['password'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['password']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label>Role</label>
                <select name="role">
                    <option value="public" <?= $old['role'] === 'public' ? 'selected' : '' ?>>Public</option>
                    <option value="staff"  <?= $old['role'] === 'staff'  ? 'selected' : '' ?>>Staff</option>
                    <option value="admin"  <?= $old['role'] === 'admin'  ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
