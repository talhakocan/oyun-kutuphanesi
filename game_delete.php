<?php
require 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->prepare('DELETE FROM games WHERE id = ? AND user_id = ?')->execute([(int)$_POST['id'], $_SESSION['user_id']]);
    flash('Oyun silindi.');
}
header('Location: library.php');
