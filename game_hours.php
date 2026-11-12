<?php
require 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: library.php'); exit; }
csrf_check();
$uid = $_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);
$h = round((float)str_replace(',', '.', $_POST['hours'] ?? 0), 1);

if ($h <= 0 || $h > 24) {
    flash('Saat 0 ile 24 arasında olmalı.', 'err');
} else {
    $s = $pdo->prepare('SELECT id, status FROM games WHERE id = ? AND user_id = ?');
    $s->execute([$id, $uid]);
    if ($g = $s->fetch()) {
        $pdo->prepare('INSERT INTO play_sessions (game_id, user_id, hours, played_on) VALUES (?,?,?,CURDATE())')->execute([$id, $uid, $h]);
        $newStatus = $g['status'] === 'istek' ? 'oynuyorum' : $g['status'];
        $pdo->prepare('UPDATE games SET hours = hours + ?, last_played = CURDATE(), status = ? WHERE id = ?')->execute([$h, $newStatus, $id]);
        flash("$h saat eklendi.");
    }
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'library.php'));
