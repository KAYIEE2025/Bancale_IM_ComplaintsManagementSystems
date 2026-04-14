<?php
// auth/logout.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/helpers.php';

$_SESSION = [];
session_destroy();

// Restart clean session to carry flash
session_start();
setFlash('success', 'You have been logged out successfully.');

redirect('../auth/login.php');
