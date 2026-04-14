<?php
// includes/auth.php  –  Session auth guard
// ============================================================
// Usage at top of any protected page:
//   require_once __DIR__ . '/../../includes/auth.php';
//   requireAuth();           // any logged-in role
//   requireRole('admin');    // admin only
//   requireRole(['admin','staff']); // admin or staff
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Return the currently logged-in user array, or null.
 */
function currentUser(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

/**
 * Check if anyone is logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['auth_user']['id']);
}

/**
 * Check the logged-in user's role.
 * $roles can be a string or array of strings.
 */
function hasRole(string|array $roles): bool
{
    $user = currentUser();
    if (!$user) return false;
    $roles = (array)$roles;
    return in_array($user['role'], $roles, true);
}

/**
 * Redirect to login if not authenticated.
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        // Save intended destination
        $_SESSION['intended'] = $_SERVER['REQUEST_URI'];
        $depth = substr_count($_SERVER['PHP_SELF'], '/') - 2;
        $base  = str_repeat('../', $depth);
        header('Location: ' . $base . 'auth/login.php');
        exit;
    }
}

/**
 * Redirect to dashboard with "Access Denied" if role does not match.
 */
function requireRole(string|array $roles): void
{
    requireAuth();
    if (!hasRole($roles)) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Access denied. You do not have permission to view that page.'];
        $depth = substr_count($_SERVER['PHP_SELF'], '/') - 2;
        $base  = str_repeat('../', $depth);
        header('Location: ' . $base . 'index.php');
        exit;
    }
}
