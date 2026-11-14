<?php
require 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $s = $pdo->prepare('SELECT cover_url FROM games WHERE id = ? AND user_id = ?');
    $s->execute([$id, $_SESSION['user_id']]);
    if ($row = $s->fetch()) {
        delete_upload($row['cover_url']);
        $pdo->prepare('DELETE FROM games WHERE id = ? AND user_id = ?')->execute([$id, $_SESSION['user_id']]);
        flash('Oyun silindi.');
    }
}
header('Location: library.php');
