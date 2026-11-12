<?php
require 'config.php';
if (!empty($_SESSION['user_id'])) { header('Location: library.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = trim($_POST['username'] ?? '');
    $s = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
    $s->execute([$u, $u]);
    $user = $s->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = (int)($user['is_admin'] ?? 0);
        header('Location: library.php'); exit;
    }
    $err = 'Kullanıcı adı veya şifre hatalı.';
}
$title = 'Giriş';
include 'header.php';
?>
<div class="authbox">
  <h1>Giriş Yap</h1>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Kullanıcı adı veya e-posta<input name="username" required></label>
    <label>Şifre<input type="password" name="password" required></label>
    <button class="btn">Giriş Yap</button>
  </form>
  <p class="muted">Hesabın yok mu? <a href="register.php">Kayıt ol</a></p>
</div>
<?php include 'footer.php'; ?>
