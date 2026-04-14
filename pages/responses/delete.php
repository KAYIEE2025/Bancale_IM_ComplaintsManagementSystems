<?php
// pages/responses/delete.php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','staff']);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$id          = (int)($_GET['id']       ?? 0);
$redirectUrl = $_GET['redirect']       ?? 'index.php';

// Whitelist redirect to prevent open redirect
$allowedPrefixes = ['index.php', '../complaints/view.php'];
$safeRedirect    = 'index.php';
foreach ($allowedPrefixes as $prefix) {
    if (str_starts_with($redirectUrl, $prefix)) {
        $safeRedirect = $redirectUrl;
        break;
    }
}

if ($id > 0) {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM responses WHERE id = :id');
    $stmt->execute([':id' => $id]);

    if ($stmt->fetch()) {
        $db->prepare('DELETE FROM responses WHERE id = :id')->execute([':id' => $id]);
        setFlash('success', 'Response deleted.');
    } else {
        setFlash('error', 'Response not found.');
    }
} else {
    setFlash('error', 'Invalid ID.');
}

redirect($safeRedirect);
