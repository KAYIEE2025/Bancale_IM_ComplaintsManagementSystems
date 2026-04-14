<?php
// includes/header.php  –  Shared HTML <head> + role-aware navbar
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$currentUser = currentUser();
$flash       = getFlash();

// Depth-aware base path (works from any subdirectory)
$depth = substr_count($_SERVER['PHP_SELF'], '/') - 2;
$base  = str_repeat('../', $depth);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'ClearVoice') ?> — ClearVoice</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css?v=<?= time(); ?>">
</head>
<body>

<nav class="navbar">
    <a class="brand" href="<?= $base ?>index.php">
        <span class="brand-dot"></span>ClearVoice
    </a>

    <ul class="nav-links">
        <li>
            <a href="<?= $base ?>pages/complaints/index.php"
               class="<?= str_contains($_SERVER['PHP_SELF'], '/complaints/') ? 'active' : '' ?>">
                Complaints
            </a>
        </li>
        <?php if ($currentUser && in_array($currentUser['role'], ['admin','staff'])): ?>
        <li>
            <a href="<?= $base ?>pages/responses/index.php"
               class="<?= str_contains($_SERVER['PHP_SELF'], '/responses/') ? 'active' : '' ?>">
                Responses
            </a>
        </li>
        <?php endif; ?>
        <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
        <li>
            <a href="<?= $base ?>pages/users/index.php"
               class="<?= str_contains($_SERVER['PHP_SELF'], '/users/') ? 'active' : '' ?>">
                Users
            </a>
        </li>
        <li>
            <a href="<?= $base ?>pages/categories/index.php"
               class="<?= str_contains($_SERVER['PHP_SELF'], '/categories/') ? 'active' : '' ?>">
                Categories
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="user-menu">
        <?php if ($currentUser): ?>
            <div class="user-info">
                <span class="user-avatar"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></span>
                <div class="user-details">
                    <span class="user-name"><?= e($currentUser['name']) ?></span>
                    <span class="role-pill role-<?= e($currentUser['role']) ?>"><?= ucfirst(e($currentUser['role'])) ?></span>
                </div>
            </div>
            <a href="<?= $base ?>auth/logout.php" class="btn btn-secondary btn-sm">Logout</a>
        <?php else: ?>
            <a href="<?= $base ?>auth/login.php" class="btn btn-primary btn-sm">Sign In</a>
        <?php endif; ?>
    </div>
</nav>

<main class="container">
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
    </div>
<?php endif; ?>
