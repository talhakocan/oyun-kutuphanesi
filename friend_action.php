<?php
require 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: friends.php'); exit; }
csrf_check();
$me = (int)$_SESSION['user_id'];
$target = (int)($_POST['target'] ?? 0);
$action = $_POST['action'] ?? '';

$ex = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$ex->execute([$target]);
if (!$ex->fetch() || $target === $me) { flash('Geçersiz kullanıcı.', 'err'); header('Location: friends.php'); exit; }

$rel = relation($pdo, $me, $target);

if ($action === 'add' && $rel === 'none') {
    $pdo->prepare('INSERT INTO friendships (requester_id, addressee_id) VALUES (?, ?)')->execute([$me, $target]);
    flash('Arkadaşlık isteği gönderildi.');
} elseif (($action === 'accept' || $action === 'add') && $rel === 'received') {
    $pdo->prepare('UPDATE friendships SET status = "accepted" WHERE requester_id = ? AND addressee_id = ?')->execute([$target, $me]);
    flash('Artık arkadaşsınız.');
} elseif ($action === 'remove' && in_array($rel, ['sent', 'received', 'friends'], true)) {
    $pdo->prepare('DELETE FROM friendships WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)')
        ->execute([$me, $target, $target, $me]);
    flash('İşlem tamamlandı.');
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'friends.php'));
