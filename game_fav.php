<?php
require 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->prepare('UPDATE games SET favorite = 1 - favorite WHERE id = ? AND user_id = ?')->execute([(int)$_POST['id'], $_SESSION['user_id']]);
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'library.php'));
