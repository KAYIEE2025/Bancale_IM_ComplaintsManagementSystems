<?php
// pages/categories/delete.php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $db   = getDB();
    $stmt = $db->prepare('SELECT name FROM categories WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row  = $stmt->fetch();

    if ($row) {
        try {
            // FK RESTRICT will throw if complaints still reference this category
            $db->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $id]);
            setFlash('success', 'Category "' . $row['name'] . '" deleted.');
        } catch (PDOException $e) {
            setFlash('error', 'Cannot delete "' . $row['name'] . '" — it still has complaints assigned to it. Reassign or delete those complaints first.');
        }
    } else {
        setFlash('error', 'Category not found.');
    }
} else {
    setFlash('error', 'Invalid ID.');
}

redirect('index.php');
