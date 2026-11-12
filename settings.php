<?php
require 'config.php';
require_login();
$uid = $_SESSION['user_id'];
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = $_POST['old'] ?? '';
    $new = $_POST['new'] ?? '';
    $new2 = $_POST['new2'] ?? '';
    $s = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $s->execute([$uid]);
    $hash = $s->fetchColumn();
    if (!password_verify($old, $hash)) $err = 'Mevcut şifre yanlış.';
    elseif (strlen($new) < 6) $err = 'Yeni şifre en az 6 karakter olmalı.';
    elseif ($new !== $new2) $err = 'Yeni şifreler birbiriyle uyuşmuyor.';
    else {
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
        flash('Şifren güncellendi.');
        header('Location: settings.php'); exit;
    }
}
$title = 'Ayarlar';
include 'header.php';
?>
<div class="formbox">
  <h1>⚙️ Ayarlar</h1>
  <h2>Şifre değiştir</h2>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Mevcut şifre<input type="password" name="old" required></label>
    <label>Yeni şifre<input type="password" name="new" required></label>
    <label>Yeni şifre (tekrar)<input type="password" name="new2" required></label>
    <button class="btn">Şifreyi Güncelle</button>
  </form>
  <p class="muted small" style="margin-top:18px">Profil bilgilerini ve haftalık hedefi <a href="profile_edit.php">profil düzenleme</a> sayfasından değiştirebilirsin.</p>
</div>
<?php include 'footer.php'; ?>
