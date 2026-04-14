<?php
// auth/login.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// Already logged in → go to dashboard
if (isLoggedIn()) { redirect('../index.php'); }

$errors = [];
$old    = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $old      = ['email' => $email];

    if (!validEmail($email))     $errors['email']    = 'Please enter a valid email address.';
    if (!required($password))    $errors['password'] = 'Password is required.';

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            $_SESSION['auth_user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];

            // Redirect to intended page or dashboard
            $intended = $_SESSION['intended'] ?? null;
            unset($_SESSION['intended']);
            redirect($intended ?? '../index.php');
        } else {
            $errors['form'] = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ClearVoice</title>
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
            width: 100%;
            max-width: 440px;
            padding: 1rem;
            animation: slideUp .6s cubic-bezier(0.4, 0, 0.2, 1) both;
            position: relative;
            z-index: 1;
        }
        
        .login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .75rem;
            font-family: var(--font-head);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2.5rem;
            color: white;
            text-align: center;
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
            border-radius: var(--radius-xl);
            padding: 2.5rem;
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
            font-family: var(--font-head);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: .5rem;
            color: var(--text);
        }
        
        .login-box p { 
            color: var(--text-muted); 
            font-size: .9rem; 
            margin-bottom: 2rem;
            line-height: 1.5;
        }
        
        .login-box .field { margin-bottom: 1.25rem; }
        .login-box .btn { 
            width: 100%; 
            justify-content: center; 
            padding: .875rem; 
            font-size: 1rem;
            margin-top: .75rem;
            font-weight: 600;
        }
        
        .register-link { 
            display: block !important;
            visibility: visible !important;
            text-align: center; 
            margin-top: 2rem; 
            font-size: .9rem; 
            font-weight: 700;
            color: #064e3b !important;
            padding: 1.25rem;
            background: rgba(255,255,255,.35);
            border: 1px solid rgba(255,255,255,.3);
            border-radius: var(--radius-lg);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 16px rgba(0,0,0,.1);
            position: relative;
            z-index: 10;
            text-shadow: 0 1px 3px rgba(255,255,255,.3);
        }
        
        .register-link a { 
            color: #064e3b !important; /* Dark forest green - same as main text */
            font-weight: 700;
            text-decoration: none;
            transition: all .2s ease;
            text-shadow: 0 1px 3px rgba(255,255,255,.3);
        }
        
        .register-link a:hover {
            color: #7c2d12 !important; /* Dark orange on hover for contrast */
            text-shadow: 0 2px 8px rgba(255,255,255,.5);
            transform: translateY(-1px);
        }

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
    <div class="login-brand">
        <span class="dot"></span>ClearVoice
    </div>

    <div class="login-box">
        <h2>Welcome back</h2>
        <p>Sign in to your account to continue.</p>

        <?php if (isset($errors['form'])): ?>
            <div class="alert alert-error" style="margin-bottom:1.25rem">
                <?= e($errors['form']) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= e($old['email']) ?>"
                       placeholder="you@example.com"
                       autocomplete="email">
                <?php if (isset($errors['email'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="••••••••"
                       autocomplete="current-password">
                <?php if (isset($errors['password'])): ?>
                    <span style="color:var(--danger);font-size:.8rem"><?= e($errors['password']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Create one</a>
        </div>
    </div>

</div>

</body>
</html>
