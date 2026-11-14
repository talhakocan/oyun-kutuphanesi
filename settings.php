<?php
require 'config.php';
require_login();
$uid = (int)$_SESSION['user_id'];
$errP = ''; $errA = '';
$presets = ['sakura' => '🌸 Sakura', 'neon' => '🌃 Neon Şehir', 'gece' => '🌌 Gece Mavisi', 'gunbatimi' => '🌇 Gün Batımı'];

$u = $pdo->prepare('SELECT password, bg_image, bg_dim, bg_blur FROM users WHERE id = ?');
$u->execute([$uid]);
$me = $u->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';

    if ($act === 'password') {
        $old = $_POST['old'] ?? ''; $new = $_POST['new'] ?? ''; $new2 = $_POST['new2'] ?? '';
        if (!password_verify($old, $me['password'])) $errP = 'Mevcut şifre yanlış.';
        elseif (strlen($new) < 6) $errP = 'Yeni şifre en az 6 karakter olmalı.';
        elseif ($new !== $new2) $errP = 'Yeni şifreler birbiriyle uyuşmuyor.';
        else {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            flash('Şifren güncellendi.');
            header('Location: settings.php'); exit;
        }
    }

    if ($act === 'appearance') {
        $cur = $me['bg_image'];
        $dim = max(0, min(90, (int)($_POST['bg_dim'] ?? 55)));
        $blur = max(0, min(20, (int)($_POST['bg_blur'] ?? 0)));
        $url = trim($_POST['bg_url'] ?? '');
        $preset = $_POST['bg_preset'] ?? '';
        $img = $cur;

        [$up, $upErr] = save_image_upload('bg_file', 'bg' . $uid);
        if ($upErr) $errA = $upErr;
        elseif (!empty($_POST['bg_remove'])) { $img = null; if ($up) delete_upload($up); }
        elseif ($up) $img = $up;
        elseif ($url !== '') {
            if (css_bg($url) === '' || strpos($url, 'uploads/') === 0) $errA = 'Resim adresi geçersiz. http:// veya https:// ile başlamalı, boşluk ve tırnak içermemeli.';
            else $img = $url;
        }
        elseif ($preset !== '' && isset($presets[$preset])) $img = 'preset:' . $preset;

        if (!$errA) {
            if ($img !== $cur) delete_upload($cur);
            $pdo->prepare('UPDATE users SET bg_image = ?, bg_dim = ?, bg_blur = ? WHERE id = ?')->execute([$img, $dim, $blur, $uid]);
            $_SESSION['bg'] = ['img' => $img ?? '', 'dim' => $dim, 'blur' => $blur];
            flash('Görünüm ayarların kaydedildi.');
            header('Location: settings.php'); exit;
        }
    }
}

$curImg = (string)($_SESSION['bg']['img'] ?? '');
$title = 'Ayarlar';
include 'header.php';
?>
<h1>⚙️ Ayarlar</h1>
<div class="two" style="grid-template-columns:3fr 2fr">
  <section class="panel">
    <h2>🎨 Görünüm — arka plan</h2>
    <?php if ($errA): ?><div class="flash err"><?= e($errA) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" id="appform">
      <?= csrf_field() ?><input type="hidden" name="action" value="appearance">

      <label>Kendi resmini yükle (JPG, PNG veya WEBP — en fazla 6 MB)
        <input type="file" name="bg_file" id="bg_file" accept="image/jpeg,image/png,image/webp"></label>

      <label>…veya resim adresi (https://...)
        <input name="bg_url" id="bg_url" placeholder="https://..."></label>

      <div class="small muted" style="margin-bottom:6px">…veya hazır arka plan seç:</div>
      <div class="presets">
        <label class="pset"><input type="radio" name="bg_preset" value="" checked> Değiştirme</label>
        <?php foreach ($presets as $k => $v): ?>
          <label class="pset <?= $curImg === 'preset:' . $k ? 'cur' : '' ?>"><input type="radio" name="bg_preset" value="<?= $k ?>"> <?= $v ?></label>
        <?php endforeach; ?>
      </div>

      <label>Karartma: <b id="dimv"><?= (int)$me['bg_dim'] ?></b>%
        <input type="range" name="bg_dim" id="bg_dim" min="0" max="90" value="<?= (int)$me['bg_dim'] ?>"></label>
      <label>Bulanıklık: <b id="blurv"><?= (int)$me['bg_blur'] ?></b> px
        <input type="range" name="bg_blur" id="bg_blur" min="0" max="20" value="<?= (int)$me['bg_blur'] ?>"></label>

      <?php if ($curImg !== ''): ?>
        <label class="chk"><input type="checkbox" name="bg_remove" value="1"> Mevcut arka planı kaldır</label>
      <?php endif; ?>

      <button class="btn">Kaydet</button>
      <p class="muted small" style="margin-top:12px">Ayarları değiştirirken arka plan <b>canlı önizlenir</b>. Kaydetmezsen eski haline döner. İpucu: yazılar okunsun diye karartmayı %50-70 arasında tut.</p>
    </form>
  </section>

  <section class="panel">
    <h2>🔒 Şifre değiştir</h2>
    <?php if ($errP): ?><div class="flash err"><?= e($errP) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <label>Mevcut şifre<input type="password" name="old" required></label>
      <label>Yeni şifre<input type="password" name="new" required></label>
      <label>Yeni şifre (tekrar)<input type="password" name="new2" required></label>
      <button class="btn">Şifreyi Güncelle</button>
    </form>
    <p class="muted small" style="margin-top:18px">Profil bilgileri ve avatar için <a href="profile_edit.php">profil düzenleme</a> sayfasına git.</p>
  </section>
</div>

<script>
(function () {
  var root = document.documentElement, layer = document.getElementById('bglayer'), body = document.body;
  var dim = document.getElementById('bg_dim'), blur = document.getElementById('bg_blur');
  function apply() {
    root.style.setProperty('--dim', (dim.value / 100).toFixed(2));
    root.style.setProperty('--blur', blur.value + 'px');
    document.getElementById('dimv').textContent = dim.value;
    document.getElementById('blurv').textContent = blur.value;
  }
  dim.oninput = apply; blur.oninput = apply;
  function clearPresets() { body.className = body.className.replace(/\bbgp-\w+/g, '').trim(); }
  document.getElementById('bg_file').onchange = function () {
    if (!this.files[0]) return;
    clearPresets(); layer.style.backgroundImage = 'url("' + URL.createObjectURL(this.files[0]) + '")'; body.classList.add('has-bg');
  };
  document.getElementById('bg_url').onchange = function () {
    var v = this.value.trim(); if (!/^https?:\/\//i.test(v)) return;
    clearPresets(); layer.style.backgroundImage = 'url("' + encodeURI(v) + '")'; body.classList.add('has-bg');
  };
  document.querySelectorAll('input[name=bg_preset]').forEach(function (r) {
    r.onchange = function () {
      if (!r.value) return;
      clearPresets(); layer.style.backgroundImage = ''; body.classList.add('has-bg', 'bgp-' + r.value);
    };
  });
})();
</script>
<?php include 'footer.php'; ?>
