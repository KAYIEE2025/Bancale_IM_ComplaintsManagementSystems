<?php
// auth/register.php  –  Public self-registration (role = 'public' always)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) { redirect('../index.php'); }

$errors = [];
$old    = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';
    $old      = compact('name', 'email');

    // Validation
    if (!required($name))   $errors['name']     = 'Full name is required.';
    if (!validEmail($email)) $errors['email']   = 'A valid email address is required.';
    if (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors['confirm']  = 'Passwords do not match.';

    // Unique email
    if (empty($errors['email'])) {
        $db  = getDB();
        $chk = $db->prepare('SELECT id FROM users WHERE email = :email');
        $chk->execute([':email' => $email]);
        if ($chk->fetch()) $errors['email'] = 'This email is already registered. Try logging in.';
    }

    if (empty($errors)) {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare(
            "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'public')"
        );
        $stmt->execute([':name' => $name, ':email' => $email, ':password' => $hash]);

        setFlash('success', 'Account created! Please log in.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — ClearVoice</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <style>
        body { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh;
            background: linear-gradient(135deg, #10b981 0%, #14b8a6 50%, #06b6d4 100%);
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.03"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        
        .login-wrap { 
            width:100%; 
            max-width:460px; 
            padding:1rem; 
            animation: slideUp .6s cubic-bezier(0.4, 0, 0.2, 1) both;
            position: relative;
            z-index: 1;
        }
        
        .login-brand {
            display:flex; 
            align-items:center; 
            justify-content:center; 
            gap:.75rem;
            font-family:var(--font-head); 
            font-size:2rem; 
            font-weight:700;
            margin-bottom:2.5rem; 
            color:white;
        }
        
        .login-brand .dot { 
            width:16px;height:16px;border-radius:50%;
            background: white;
            box-shadow: 0 4px 12px rgba(255,255,255,.3);
        }
        
        .login-box {
            background: rgba(255,255,255,.98);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255,255,255,.2);
            border-radius:var(--radius-xl); 
            padding:2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,.15);
            position: relative;
            overflow: hidden;
        }
        
        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #14b8a6, #06b6d4);
        }
        
        .login-box h2 { 
            font-family:var(--font-head); 
            font-size:1.5rem; 
            font-weight:700;
            margin-bottom:.5rem; 
            color: var(--text);
        }
        
        .login-box p  { 
            color:var(--text-muted); 
            font-size:.9rem; 
            margin-bottom:2rem;
            line-height: 1.5;
        }
        
        .login-box .field { margin-bottom:1.25rem; }
        .login-box .btn { 
            width:100%; 
            justify-content:center; 
            padding:.875rem; 
            font-size:1rem; 
            margin-top:.75rem;
            font-weight: 600;
        }
        
        .register-link { 
            text-align:center; 
            margin-top:2rem; 
            font-size:.875rem; 
            color:rgba(255,255,255,.8);
            padding: 1rem;
            background: rgba(255,255,255,.1);
            border-radius: var(--radius-lg);
            backdrop-filter: blur(10px);
        }
        
        .register-link a { 
            color: white; 
            font-weight: 600;
            text-decoration: none;
            transition: all .2s ease;
        }
        
        .register-link a:hover {
            text-shadow: 0 2px 8px rgba(255,255,255,.3);
        }
        
        .role-info {
            margin-top:1.5rem;
            background: rgba(249,115,22,.08);
            border: 1px solid rgba(249,115,22,.15);
            border-radius: var(--radius-lg);
            padding: 1rem 1.25rem;
            font-size: .85rem;
            color: var(--text-muted);
            backdrop-filter: blur(10px);
        }
        
        .role-info strong { color: var(--accent); font-weight: 600; }

        @keyframes slideUp {
            from { 
                opacity: 0; 
                transform: translateY(30px); 
            }
            to   { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-brand"><span class="dot"></span>ClearVoice</div>

    <div class="login-box">
        <h2>Create an account</h2>
        <p>Register to submit and track your complaints.</p>

        <form method="post">
            <div class="field">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name"
                       value="<?= e($old['name']) ?>" placeholder="Juan dela Cruz">
                <?php if (isset($errors['name'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= e($old['email']) ?>" placeholder="you@example.com">
                <?php if (isset($errors['email'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Min. 8 characters">
                <?php if (isset($errors['password'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['password']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm" placeholder="Repeat your password">
                <?php if (isset($errors['confirm'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['confirm']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <div class="register-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>

    <div class="role-info">
        ℹ️ Public accounts can <strong>submit complaints</strong> and track their own tickets.
        Staff and Admin accounts are created by the system administrator.
    </div>
</div>

</body>
</html>
