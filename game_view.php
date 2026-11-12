<?php
require 'config.php';
require_login();
$me = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

$s = $pdo->prepare('SELECT g.*, u.username, u.avatar_url FROM games g JOIN users u ON u.id = g.user_id WHERE g.id = ?');
$s->execute([$id]);
$g = $s->fetch();
if (!$g) { flash('Oyun bulunamadı.', 'err'); header('Location: library.php'); exit; }

$owner = (int)$g['user_id'];
$rel = relation($pdo, $me, $owner);
$canComment = in_array($rel, ['self', 'friends'], true);

if ($rel === 'self') {
    require_once 'gamify.php';
    award_new_badges($pdo, $me, collect_stats($pdo, $me));
}

$fp = [];
$fid = friend_ids($pdo, $me);
if ($fid) {
    $inF = implode(',', array_fill(0, count($fid), '?'));
    $qf = $pdo->prepare("SELECT g.id, g.hours, u.username, u.avatar_url FROM games g JOIN users u ON u.id = g.user_id WHERE g.user_id IN ($inF) AND LOWER(g.title) = LOWER(?) AND g.user_id <> ? ORDER BY g.hours DESC");
    $qf->execute(array_merge($fid, [$g['title'], $owner]));
    $fp = $qf->fetchAll();
}

$ss = $pdo->prepare('SELECT played_on, hours FROM play_sessions WHERE game_id = ? ORDER BY played_on DESC, id DESC LIMIT 10');
$ss->execute([$id]);
$sessions = $ss->fetchAll();

$cm = $pdo->prepare('SELECT c.*, u.username, u.avatar_url FROM game_comments c JOIN users u ON u.id = c.user_id WHERE c.game_id = ? ORDER BY c.created_at DESC');
$cm->execute([$id]);
$comments = $cm->fetchAll();

$title = $g['title'];
include 'header.php';
?>
<div class="gamehead">
  <div class="bigcover"><?= cover_html($g) ?></div>
  <div class="gdetail">
    <h1><?= e($g['title']) ?></h1>
    <p>
      <span class="tag s-<?= e($g['status']) ?>"><?= e(STATUSES[$g['status']]) ?></span>
      <span class="muted"><?= e($g['platform']) ?><?= $g['genre'] ? ' • ' . e($g['genre']) : '' ?></span>
    </p>
    <div class="hours big"><?= e(fmt_hours($g['hours'])) ?>
      <?php if ($g['rating']): ?><span class="rate">★ <?= (int)$g['rating'] ?>/10</span><?php endif; ?>
    </div>
    <?php if ($g['last_played']): ?><div class="muted small">Son oynanma: <?= e(date('d.m.Y', strtotime($g['last_played']))) ?></div><?php endif; ?>
    <?php if ($g['note']): ?><p class="note"><?= nl2br(e($g['note'])) ?></p><?php endif; ?>
    <p class="muted small">Kütüphane sahibi: <a href="profile.php?u=<?= urlencode($g['username']) ?>"><?= e($g['username']) ?></a></p>
    <?php if ($rel === 'self'): ?>
      <a class="btn small" href="game_form.php?id=<?= $id ?>">Düzenle</a>
      <a class="btn small ghost" href="library.php">← Kütüphane</a>
    <?php else: ?>
      <a class="btn small ghost" href="profile.php?u=<?= urlencode($g['username']) ?>">← Profile dön</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($rel === 'self'): ?>
<section class="panel timerbox">
  <h2>⏱️ Oyun Sayacı</h2>
  <div class="timer" id="tdisp">00:00:00</div>
  <button class="btn" id="tstart" type="button">▶ Oyuna Başla</button>
  <button class="btn ghost" id="tstop" type="button" style="display:none">⏹ Bitir ve Kaydet</button>
  <p class="muted small">Sayaç sayfayı kapatsan bile çalışmaya devam eder (bu tarayıcıda). Bitirince süre otomatik eklenir.</p>
  <form id="tform" method="post" action="game_hours.php" style="display:none">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="hours" id="thours">
  </form>
</section>
<?php endif; ?>

<div class="two">
  <section class="panel">
    <h2>Yorumlar (<?= count($comments) ?>)</h2>
    <?php if ($canComment): ?>
    <form method="post" action="comment_add.php" class="commentform">
      <?= csrf_field() ?>
      <input type="hidden" name="game_id" value="<?= $id ?>">
      <textarea name="body" rows="2" maxlength="500" placeholder="Yorum yaz..." required></textarea>
      <button class="btn small">Gönder</button>
    </form>
    <?php else: ?>
      <p class="muted small">Yorum yapabilmek için <?= e($g['username']) ?> ile arkadaş olmalısın.</p>
    <?php endif; ?>

    <?php if (!$comments): ?><p class="muted">Henüz yorum yok.</p><?php endif; ?>
    <?php foreach ($comments as $c): ?>
      <div class="comment">
        <?= avatar_html($c, 36) ?>
        <div class="cbody">
          <div><a href="profile.php?u=<?= urlencode($c['username']) ?>"><b><?= e($c['username']) ?></b></a>
            <span class="muted small"><?= e(date('d.m.Y H:i', strtotime($c['created_at']))) ?></span></div>
          <div><?= nl2br(e($c['body'])) ?></div>
        </div>
        <?php if ((int)$c['user_id'] === $me || $owner === $me): ?>
          <form method="post" action="comment_delete.php" onsubmit="return confirm('Yorum silinsin mi?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="link danger">Sil</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="panel">
    <?php if ($fp): ?>
      <h2>👥 Arkadaşların da oynuyor</h2>
      <?php foreach ($fp as $f): ?>
        <div class="urow"><?= avatar_html($f, 32) ?>
          <div class="uname"><a href="profile.php?u=<?= urlencode($f['username']) ?>"><?= e($f['username']) ?></a></div>
          <span class="hours"><?= e(fmt_hours($f['hours'])) ?></span></div>
      <?php endforeach; ?>
      <hr class="sep">
    <?php endif; ?>
    <h2>Son oynama kayıtları</h2>
    <?php if (!$sessions): ?><p class="muted">Kayıt yok.</p><?php endif; ?>
    <?php foreach ($sessions as $x): ?>
      <div class="bl row2"><span><?= e(date('d.m.Y', strtotime($x['played_on']))) ?></span><span>+<?= e(fmt_hours($x['hours'])) ?></span></div>
    <?php endforeach; ?>
  </section>
</div>
<?php if ($rel === 'self'): ?>
<script>
(function () {
  var key = 'gtimer_<?= $id ?>', disp = document.getElementById('tdisp'),
      bs = document.getElementById('tstart'), be = document.getElementById('tstop'), iv = null;
  function get() { try { return parseInt(localStorage.getItem(key) || '0', 10); } catch (e) { return 0; } }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function render() {
    var st = get();
    if (!st) { disp.textContent = '00:00:00'; return; }
    var s = Math.max(0, Math.floor((Date.now() - st) / 1000));
    disp.textContent = pad(Math.floor(s / 3600)) + ':' + pad(Math.floor(s % 3600 / 60)) + ':' + pad(s % 60);
  }
  function ui() {
    var run = get() > 0;
    bs.style.display = run ? 'none' : '';
    be.style.display = run ? '' : 'none';
    render();
    if (run && !iv) iv = setInterval(render, 1000);
    if (!run && iv) { clearInterval(iv); iv = null; }
  }
  bs.onclick = function () {
    try { localStorage.setItem(key, String(Date.now())); } catch (e) { alert('Tarayıcı depolaması kapalı, sayaç kullanılamıyor.'); return; }
    ui();
  };
  be.onclick = function () {
    var h = Math.round((Date.now() - get()) / 360000) / 10;
    try { localStorage.removeItem(key); } catch (e) {}
    if (h < 0.1) { alert('Süre 0,1 saatten kısa olduğu için kaydedilmedi.'); ui(); return; }
    if (h > 24) h = 24;
    document.getElementById('thours').value = h;
    document.getElementById('tform').submit();
  };
  ui();
})();
</script>
<?php endif; ?>
<?php include 'footer.php'; ?>
