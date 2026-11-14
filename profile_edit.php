<?php
require 'config.php';
require_login();
$uid = (int)$_SESSION['user_id'];
$s = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$s->execute([$uid]);
$user = $s->fetch();
$oldAv = $user['avatar_url'];
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $bio = trim($_POST['bio'] ?? '');
    $avText = trim($_POST['avatar_url'] ?? '');
    $goalRaw = trim(str_replace(',', '.', $_POST['weekly_goal'] ?? ''));
    $goal = $goalRaw === '' ? null : (float)$goalRaw;
    if (mb_strlen($bio) > 200) $err = 'Biyografi en fazla 200 karakter olabilir.';
    elseif ($avText !== '' && css_bg($avText) === '') $err = 'Profil resmi adresi geçersiz (http:// veya https:// ile başlamalı).';
    elseif ($goal !== null && ($goal < 1 || $goal > 168)) $err = 'Haftalık hedef 1 ile 168 saat arasında olmalı.';
    else {
        [$up, $upErr] = save_image_upload('avatar_file', 'av' . $uid, 4194304);
        if ($upErr) $err = $upErr;
        else {
            $final = null;
            if (!empty($_POST['avatar_remove'])) { if ($up) delete_upload($up); }
            elseif ($up) $final = $up;
            elseif ($avText !== '') $final = $avText;
            elseif ($oldAv) $final = $oldAv;
            if ($final !== $oldAv) delete_upload($oldAv);
            $pdo->prepare('UPDATE users SET bio = ?, avatar_url = ?, weekly_goal = ? WHERE id = ?')->execute([$bio ?: null, $final, $goal, $uid]);
            flash('Profil güncellendi.');
            header('Location: profile.php'); exit;
        }
    }
    $user['bio'] = $bio; $user['weekly_goal'] = $goalRaw;
}

$shownAv = (strpos((string)$oldAv, 'http') === 0) ? $oldAv : '';
$title = 'Profili Düzenle';
include 'header.php';
?>
<div class="formbox">
  <h1>Profili Düzenle</h1>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row" style="align-items:center">
      <?= avatar_html($user, 72) ?>
      <label>Profil resmi yükle (JPG, PNG, WEBP — en fazla 4 MB)<input type="file" name="avatar_file" accept="image/jpeg,image/png,image/webp"></label>
    </div>
    <label>…veya resim adresi<input name="avatar_url" value="<?= e($shownAv) ?>" placeholder="https://..."></label>
    <?php if ($oldAv): ?><label class="chk"><input type="checkbox" name="avatar_remove" value="1"> Profil resmini kaldır</label><?php endif; ?>
    <label>Biyografi (en fazla 200 karakter)<textarea name="bio" rows="3" maxlength="200"><?= e($user['bio']) ?></textarea></label>
    <label>Haftalık oyun hedefi (saat, isteğe bağlı)<input type="number" name="weekly_goal" step="0.5" min="1" max="168" value="<?= e($user['weekly_goal'] !== null && $user['weekly_goal'] !== '' ? (float)$user['weekly_goal'] : '') ?>" placeholder="Örn: 10"></label>
    <div class="row">
      <button class="btn">Kaydet</button>
      <a class="btn ghost" href="profile.php">İptal</a>
    </div>
  </form>
  <p class="muted small" style="margin-top:16px"><a href="settings.php">🎨 Arka plan ve şifre ayarları</a></p>
</div>
<?php include 'footer.php'; ?>
