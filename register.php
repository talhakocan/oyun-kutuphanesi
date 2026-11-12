<?php
require 'config.php';
if (!empty($_SESSION['user_id'])) { header('Location: library.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = trim($_POST['username'] ?? '');
    $m = trim($_POST['email'] ?? '');
    $p = $_POST['password'] ?? '';
    if (mb_strlen($u) < 3 || mb_strlen($u) > 30) $err = 'Kullanıcı adı 3-30 karakter olmalı.';
    elseif (!filter_var($m, FILTER_VALIDATE_EMAIL)) $err = 'Geçerli bir e-posta gir.';
    elseif (strlen($p) < 6) $err = 'Şifre en az 6 karakter olmalı.';
    else {
        $s = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $s->execute([$u, $m]);
        if ($s->fetch()) $err = 'Bu kullanıcı adı veya e-posta zaten kayıtlı.';
        else {
            $isFirst = ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) ? 1 : 0; // ilk kullanıcı yönetici
            $pdo->prepare('INSERT INTO users (username, email, password, is_admin) VALUES (?,?,?,?)')
                ->execute([$u, $m, password_hash($p, PASSWORD_DEFAULT), $isFirst]);
            flash('Kayıt başarılı, giriş yapabilirsin.');
            header('Location: login.php'); exit;
        }
    }
}
$title = 'Kayıt Ol';
include 'header.php';
?>
<div class="authbox">
  <h1>Kayıt Ol</h1>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Kullanıcı adı<input name="username" value="<?= e($_POST['username'] ?? '') ?>" required></label>
    <label>E-posta<input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></label>
    <label>Şifre<input type="password" name="password" required></label>
    <button class="btn">Kayıt Ol</button>
  </form>
  <p class="muted">Hesabın var mı? <a href="login.php">Giriş yap</a></p>
</div>
<?php include 'footer.php'; ?>
