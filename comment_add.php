<?php
require 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: library.php'); exit; }
csrf_check();
$me = (int)$_SESSION['user_id'];
$gid = (int)($_POST['game_id'] ?? 0);
$body = trim($_POST['body'] ?? '');

$s = $pdo->prepare('SELECT user_id FROM games WHERE id = ?');
$s->execute([$gid]);
$g = $s->fetch();

if (!$g) {
    flash('Oyun bulunamadı.', 'err'); header('Location: library.php'); exit;
}
if (!in_array(relation($pdo, $me, $g['user_id']), ['self', 'friends'], true)) {
    flash('Sadece arkadaşların oyunlarına yorum yapabilirsin.', 'err');
} elseif ($body === '' || mb_strlen($body) > 500) {
    flash('Yorum 1-500 karakter olmalı.', 'err');
} else {
    $pdo->prepare('INSERT INTO game_comments (game_id, user_id, body) VALUES (?,?,?)')->execute([$gid, $me, $body]);
    flash('Yorum eklendi.');
}
header('Location: game_view.php?id=' . $gid);
