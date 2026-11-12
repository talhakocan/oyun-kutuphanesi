<?php
require 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: library.php'); exit; }
csrf_check();
$me = (int)$_SESSION['user_id'];
$cid = (int)($_POST['id'] ?? 0);

$s = $pdo->prepare('SELECT c.id, c.user_id, c.game_id, g.user_id AS owner FROM game_comments c JOIN games g ON g.id = c.game_id WHERE c.id = ?');
$s->execute([$cid]);
$c = $s->fetch();
if ($c && ((int)$c['user_id'] === $me || (int)$c['owner'] === $me)) {
    $pdo->prepare('DELETE FROM game_comments WHERE id = ?')->execute([$cid]);
    flash('Yorum silindi.');
    header('Location: game_view.php?id=' . (int)$c['game_id']); exit;
}
header('Location: library.php');
