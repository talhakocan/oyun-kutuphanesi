<?php
require 'config.php';
require_login();
$uid = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$g = ['title'=>'','platform'=>'PC','genre'=>'','cover_url'=>'','status'=>'oynuyorum','hours'=>0,'rating'=>'','note'=>'','last_played'=>'','progress'=>0];
$oldCover = null;

if ($id) {
    $s = $pdo->prepare('SELECT * FROM games WHERE id = ? AND user_id = ?');
    $s->execute([$id, $uid]);
    $row = $s->fetch();
    if (!$row) { flash('Oyun bulunamadı.', 'err'); header('Location: library.php'); exit; }
    $g = $row;
    $oldCover = $row['cover_url'];
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $g['title'] = trim($_POST['title'] ?? '');
    $g['platform'] = trim($_POST['platform'] ?? 'PC');
    $g['genre'] = trim($_POST['genre'] ?? '');
    $coverText = trim($_POST['cover_url'] ?? '');
    $g['cover_url'] = $coverText;
    $g['status'] = $_POST['status'] ?? 'oynuyorum';
    $g['hours'] = max(0, (float)str_replace(',', '.', $_POST['hours'] ?? 0));
    $g['rating'] = $_POST['rating'] !== '' ? (int)$_POST['rating'] : null;
    $g['note'] = trim($_POST['note'] ?? '');
    $g['last_played'] = $_POST['last_played'] ?: null;
    $g['progress'] = max(0, min(100, (int)($_POST['progress'] ?? 0)));

    if ($g['title'] === '' || mb_strlen($g['title']) > 120) $err = 'Oyun adı zorunlu (en fazla 120 karakter).';
    elseif (!isset(STATUSES[$g['status']])) $err = 'Geçersiz durum.';
    elseif ($g['rating'] !== null && ($g['rating'] < 1 || $g['rating'] > 10)) $err = 'Puan 1 ile 10 arasında olmalı.';
    elseif ($coverText !== '' && css_bg($coverText) === '') $err = 'Kapak adresi geçersiz (http:// veya https:// ile başlamalı, boşluk ve tırnak içermemeli).';
    elseif ($g['hours'] > 99999) $err = 'Saat değeri çok büyük.';
    else {
        [$upc, $upe] = save_image_upload('cover_file', 'cover' . $uid);
        if ($upe) $err = $upe;
        else {
            $final = null;
            if (!empty($_POST['cover_remove'])) { if ($upc) delete_upload($upc); }
            elseif ($upc) $final = $upc;
            elseif ($coverText !== '') $final = $coverText;
            elseif ($oldCover && strpos($oldCover, 'uploads/') === 0) $final = $oldCover;
            if ($final !== $oldCover) delete_upload($oldCover);
            if ($g['status'] === 'bitirdim' && $g['progress'] === 0) $g['progress'] = 100;

            $vals = [$g['title'], $g['platform'], $g['genre'] ?: null, $final, $g['status'],
                     $g['hours'], $g['rating'], $g['note'], $g['last_played'], $g['progress']];
            if ($id) {
                $pdo->prepare('UPDATE games SET title=?, platform=?, genre=?, cover_url=?, status=?, hours=?, rating=?, note=?, last_played=?, progress=? WHERE id=? AND user_id=?')
                    ->execute(array_merge($vals, [$id, $uid]));
                flash('Oyun güncellendi.');
            } else {
                $pdo->prepare('INSERT INTO games (title, platform, genre, cover_url, status, hours, rating, note, last_played, progress, user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute(array_merge($vals, [$uid]));
                flash('Oyun kütüphaneye eklendi.');
            }
            header('Location: library.php'); exit;
        }
    }
}

$shownCover = (strpos((string)$g['cover_url'], 'http') === 0) ? $g['cover_url'] : '';
$hasUpCover = $oldCover && strpos($oldCover, 'uploads/') === 0;
$title = $id ? 'Oyunu Düzenle' : 'Oyun Ekle';
include 'header.php';
?>
<div class="formbox">
  <h1><?= $id ? 'Oyunu Düzenle' : 'Yeni Oyun Ekle' ?></h1>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <label>Oyun adı *<input name="title" value="<?= e($g['title']) ?>" required maxlength="120"></label>
    <div class="row">
      <label>Platform
        <select name="platform">
          <?php foreach (['PC','PlayStation','Xbox','Nintendo','Mobil','Diğer'] as $p): ?>
            <option <?= $g['platform']===$p?'selected':'' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Tür<input name="genre" value="<?= e($g['genre']) ?>" placeholder="FPS, RPG, Strateji..."></label>
    </div>
    <div class="row">
      <label>Durum
        <select name="status">
          <?php foreach (STATUSES as $k => $v): ?>
            <option value="<?= $k ?>" <?= $g['status']===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Oynama süresi (saat)<input type="number" name="hours" step="0.1" min="0" value="<?= e($g['hours']) ?>"></label>
    </div>
    <label>Tamamlama: <b id="pv"><?= (int)$g['progress'] ?></b>%
      <input type="range" name="progress" min="0" max="100" step="5" value="<?= (int)$g['progress'] ?>" oninput="document.getElementById('pv').textContent=this.value"></label>
    <div class="row">
      <label>Puan (1-10)<input type="number" name="rating" min="1" max="10" value="<?= e($g['rating']) ?>"></label>
      <label>Son oynanma tarihi<input type="date" name="last_played" value="<?= e($g['last_played']) ?>"></label>
    </div>

    <label>Kapak resmi yükle (JPG, PNG, WEBP — en fazla 6 MB)<input type="file" name="cover_file" accept="image/jpeg,image/png,image/webp"></label>
    <label>…veya kapak adresi<input name="cover_url" id="cover_url" value="<?= e($shownCover) ?>" placeholder="https://..."></label>
    <div class="coverfind">
      <button type="button" class="btn small ghost" id="findcover">🔍 Steam'de kapak bul</button>
      <span class="muted small" id="covermsg">Oyun adını yazıp tıkla, çıkan resimlerden birini seç.</span>
      <div class="coverres" id="coverres"></div>
    </div>
    <?php if ($hasUpCover): ?><label class="chk"><input type="checkbox" name="cover_remove" value="1"> Yüklü kapağı kaldır</label><?php endif; ?>

    <label>Not<textarea name="note" rows="3"><?= e($g['note']) ?></textarea></label>
    <div class="row">
      <button class="btn">Kaydet</button>
      <a class="btn ghost" href="library.php">İptal</a>
    </div>
  </form>
</div>
<script>
(function () {
  var btn = document.getElementById('findcover'), res = document.getElementById('coverres'),
      msg = document.getElementById('covermsg'), inp = document.getElementById('cover_url'),
      title = document.querySelector('input[name=title]');
  btn.onclick = function () {
    var q = title.value.trim();
    if (!q) { msg.textContent = 'Önce oyun adını yaz.'; return; }
    msg.textContent = 'Aranıyor...'; res.innerHTML = '';
    fetch('cover_search.php?q=' + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.ok) { msg.textContent = 'Steam’e ulaşılamadı. Kapak adresini elle yapıştırabilirsin.'; return; }
        if (!d.items.length) { msg.textContent = 'Sonuç bulunamadı. Başka bir ad dene.'; return; }
        msg.textContent = 'Birini seç:';
        d.items.forEach(function (it) {
          var b = document.createElement('button');
          b.type = 'button'; b.className = 'cvopt'; b.title = it.name;
          var im = document.createElement('img'); im.src = it.thumb || it.cover; im.alt = '';
          var sp = document.createElement('span'); sp.textContent = it.name;
          b.appendChild(im); b.appendChild(sp);
          b.onclick = function () { inp.value = it.cover; msg.textContent = 'Seçildi: ' + it.name; res.innerHTML = ''; };
          res.appendChild(b);
        });
      })
      .catch(function () { msg.textContent = 'Arama yapılamadı. Kapak adresini elle yapıştırabilirsin.'; });
  };
})();
</script>
<?php include 'footer.php'; ?>
