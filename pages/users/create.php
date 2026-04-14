<?php
// pages/users/create.php  –  Admin creates users (any role)
$pageTitle = 'New User';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/header.php';

$db     = getDB();
$errors = [];
$old    = ['name' => '', 'email' => '', 'role' => 'public'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $role     = $_POST['role']          ?? 'public';
    $old      = compact('name', 'email', 'role');

    if (!required($name))                              $errors['name']     = 'Name is required.';
    if (!validEmail($email))                           $errors['email']    = 'Valid email is required.';
    if (strlen($password) < 8)                         $errors['password'] = 'Password must be at least 8 characters.';
    if (!in_array($role, ['admin','staff','public']))  $errors['role']     = 'Invalid role.';

    if (empty($errors['email'])) {
        $chk = $db->prepare('SELECT id FROM users WHERE email = :email');
        $chk->execute([':email' => $email]);
        if ($chk->fetch()) $errors['email'] = 'This email is already registered.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $stmt->execute([':name' => $name, ':email' => $email, ':password' => $hash, ':role' => $role]);
        setFlash('success', 'User "' . $name . '" created successfully.');
        redirect('index.php');
    }
}
?>

<div class="page-header">
    <div><h1>New User</h1><p>Create an Admin, Staff, or Public account.</p></div>
    <a href="index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="card fade-in">
    <form method="post">
        <div class="form-grid">
            <div class="field">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" placeholder="e.g. Maria Santos">
                <?php if (isset($errors['name'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['name']) ?></span>
                <?php endif; ?>
            </div>
            <div class="field">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" placeholder="user@example.com">
                <?php if (isset($errors['email'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>
            <div class="field">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" placeholder="Min. 8 characters">
                <?php if (isset($errors['password'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['password']) ?></span>
                <?php endif; ?>
            </div>
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="public" <?= $old['role'] === 'public' ? 'selected' : '' ?>>Public (Self-service)</option>
                    <option value="staff"  <?= $old['role'] === 'staff'  ? 'selected' : '' ?>>Staff (Can respond)</option>
                    <option value="admin"  <?= $old['role'] === 'admin'  ? 'selected' : '' ?>>Admin (Full access)</option>
                </select>
                <span class="field-hint">Public users can submit complaints. Staff can respond. Admins manage everything.</span>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
