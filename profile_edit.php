<?php
require 'config.php';
require_login();
$uid = $_SESSION['user_id'];
$s = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$s->execute([$uid]);
$user = $s->fetch();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $bio = trim($_POST['bio'] ?? '');
    $av = trim($_POST['avatar_url'] ?? '');
    $goalRaw = trim(str_replace(',', '.', $_POST['weekly_goal'] ?? ''));
    $goal = $goalRaw === '' ? null : (float)$goalRaw;
    if (mb_strlen($bio) > 200) $err = 'Biyografi en fazla 200 karakter olabilir.';
    elseif ($av !== '' && !preg_match('#^https?://#i', $av)) $err = 'Profil resmi adresi http:// veya https:// ile başlamalı.';
    elseif ($goal !== null && ($goal < 1 || $goal > 168)) $err = 'Haftalık hedef 1 ile 168 saat arasında olmalı.';
    else {
        $pdo->prepare('UPDATE users SET bio = ?, avatar_url = ?, weekly_goal = ? WHERE id = ?')->execute([$bio ?: null, $av ?: null, $goal, $uid]);
        flash('Profil güncellendi.');
        header('Location: profile.php'); exit;
    }
    $user['bio'] = $bio; $user['avatar_url'] = $av; $user['weekly_goal'] = $goalRaw;
}

$title = 'Profili Düzenle';
include 'header.php';
?>
<div class="formbox">
  <h1>Profili Düzenle</h1>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Biyografi (en fazla 200 karakter)<textarea name="bio" rows="3" maxlength="200"><?= e($user['bio']) ?></textarea></label>
    <label>Profil resmi adresi (isteğe bağlı)<input name="avatar_url" value="<?= e($user['avatar_url']) ?>" placeholder="https://..."></label>
    <label>Haftalık oyun hedefi (saat, isteğe bağlı)<input type="number" name="weekly_goal" step="0.5" min="1" max="168" value="<?= e($user['weekly_goal'] !== null ? (float)$user['weekly_goal'] : '') ?>" placeholder="Örn: 10"></label>
    <div class="row">
      <button class="btn">Kaydet</button>
      <a class="btn ghost" href="profile.php">İptal</a>
    </div>
  </form>
  <p class="muted small" style="margin-top:16px"><a href="settings.php">🔒 Şifre değiştir</a></p>
</div>
<?php include 'footer.php'; ?>
