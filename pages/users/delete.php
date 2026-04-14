<?php
// pages/users/delete.php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $db   = getDB();
    $stmt = $db->prepare('SELECT name FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    if ($row) {
        $db->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $id]);
        setFlash('success', 'User "' . $row['name'] . '" deleted.');
    } else {
        setFlash('error', 'User not found.');
    }
} else {
    setFlash('error', 'Invalid ID.');
}

redirect('index.php');
