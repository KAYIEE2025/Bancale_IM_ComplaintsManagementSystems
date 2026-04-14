<?php
// pages/complaints/delete.php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $db   = getDB();
    $stmt = $db->prepare('SELECT title FROM complaints WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    if ($row) {
        // FK ON DELETE CASCADE removes responses automatically
        $del = $db->prepare('DELETE FROM complaints WHERE id = :id');
        $del->execute([':id' => $id]);
        setFlash('success', 'Complaint "' . $row['title'] . '" deleted.');
    } else {
        setFlash('error', 'Complaint not found.');
    }
} else {
    setFlash('error', 'Invalid complaint ID.');
}

redirect('index.php');
