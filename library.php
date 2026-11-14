<?php
require 'config.php';
require_login();
$uid = $_SESSION['user_id'];

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'recent';
$fav = !empty($_GET['fav']);
$orders = [
    'recent' => 'created_at DESC',
    'hours'  => 'hours DESC',
    'name'   => 'title ASC',
    'rating' => 'rating DESC, title ASC',
    'played' => 'last_played DESC',
];
$order = $orders[$sort] ?? $orders['recent'];

$sql = 'SELECT * FROM games WHERE user_id = ?';
$params = [$uid];
if ($q !== '') { $sql .= ' AND title LIKE ?'; $params[] = "%$q%"; }
if (isset(STATUSES[$status])) { $sql .= ' AND status = ?'; $params[] = $status; }
if ($fav) { $sql .= ' AND favorite = 1'; }
$sql .= " ORDER BY $order";
$st = $pdo->prepare($sql);
$st->execute($params);
$games = $st->fetchAll();

$sum = $pdo->prepare('SELECT COUNT(*) c, COALESCE(SUM(hours),0) h FROM games WHERE user_id = ? AND status <> "istek"');
$sum->execute([$uid]);
$sum = $sum->fetch();

require_once 'gamify.php';
$gst = collect_stats($pdo, $uid);
award_new_badges($pdo, $uid, $gst);
$lv = level_info(calc_xp($gst));
if (isset($_SESSION['lvl']) && $lv['level'] > $_SESSION['lvl']) {
    $_SESSION['toast'] = trim(($_SESSION['toast'] ?? '') . ' ⬆️ Seviye atladın: Sv. ' . $lv['level'] . ' — ' . $lv['title'] . '!');
}
$_SESSION['lvl'] = $lv['level'];
$wk = $pdo->prepare('SELECT COALESCE(SUM(hours),0) FROM play_sessions WHERE user_id = ? AND played_on >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)');
$wk->execute([$uid]);
$week = (float)$wk->fetchColumn();
$gl = $pdo->prepare('SELECT weekly_goal FROM users WHERE id = ?');
$gl->execute([$uid]);
$goal = (float)$gl->fetchColumn();

$hs = $pdo->prepare('SELECT * FROM games WHERE user_id = ? AND last_played IS NOT NULL AND status <> "istek" ORDER BY last_played DESC, id DESC LIMIT 1');
$hs->execute([$uid]);
$hero = $hs->fetch();

$title = 'Kütüphane';
include 'header.php';
?>
<?php $tg = $hero['id'] ?? ($games[0]['id'] ?? 0); ?>
<section class="tour" id="tour" style="display:none">
  <button type="button" class="tourx" id="tourx" title="Gizle">×</button>
  <b>👋 Hoş geldin! Hızlı gezinti:</b>
  <div class="tchips">
    <?php if ($tg): ?><a href="game_view.php?id=<?= (int)$tg ?>">⏱️ Oyun sayacı</a><?php endif; ?>
    <a href="feed.php">📰 Akış</a>
    <a href="leaderboard.php">🏆 Sıralama</a>
    <a href="stats.php">📅 İstatistik</a>
    <a href="collections.php">🗂️ Koleksiyonlar</a>
    <a href="timeline.php">🕒 Zaman çizelgesi</a>
    <a href="wrapped.php">🎬 Oyun Özetim</a>
    <a href="settings.php">🎨 Arka plan</a>
  </div>
</section>
<script>
(function () {
  var t = document.getElementById('tour'), x = document.getElementById('tourx'), off = false;
  try { off = localStorage.getItem('tour_off') === '1'; } catch (e) {}
  if (!off) t.style.display = '';
  x.onclick = function () { t.style.display = 'none'; try { localStorage.setItem('tour_off', '1'); } catch (e) {} };
})();
</script>

<?php if ($hero): ?>
<section class="hero">
  <div class="hero-bg" style="<?= css_bg($hero['cover_url']) ?>"></div>
  <div class="hero-in">
    <a class="hero-cover" href="game_view.php?id=<?= (int)$hero['id'] ?>"><?= cover_html($hero) ?></a>
    <div class="hero-txt">
      <div class="eyebrow">KALDIĞIN YERDEN DEVAM ET</div>
      <h2><?= e($hero['title']) ?></h2>
      <div class="muted"><?= e(fmt_hours($hero['hours'])) ?> oynadın • son oynanma <?= e(date('d.m.Y', strtotime($hero['last_played']))) ?></div>
      <?php if ((int)$hero['progress'] > 0): ?>
        <div class="progress" style="max-width:320px"><div style="width:<?= (int)$hero['progress'] ?>%"></div></div>
        <div class="muted small">%<?= (int)$hero['progress'] ?> tamamlandı</div>
      <?php endif; ?>
      <div class="hero-act">
        <a class="btn" href="game_view.php?id=<?= (int)$hero['id'] ?>">▶ Oyuna git</a>
        <form method="post" action="game_hours.php" class="inl">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$hero['id'] ?>"><input type="hidden" name="hours" value="1">
          <button class="btn ghost">+1 saat</button>
        </form>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="summary">
  <div><b><?= (int)$sum['c'] ?></b><span>Oyun</span></div>
  <div><b><?= e(fmt_hours($sum['h'])) ?></b><span>Toplam Süre</span></div>
  <div><b>Sv. <?= $lv['level'] ?></b><span><?= e($lv['title']) ?></span></div>
</section>

<?php if ($goal > 0): $pct = min(100, round($week / $goal * 100)); ?>
<section class="goal">
  <div class="bl"><span>🎯 Haftalık hedef (son 7 gün)</span><span><?= e(fmt_hours($week)) ?> / <?= e(fmt_hours($goal)) ?><?= $week >= $goal ? ' ✅' : '' ?></span></div>
  <div class="progress"><div style="width:<?= $pct ?>%"></div></div>
</section>
<?php else: ?>
<p class="muted small">🎯 Haftalık oyun hedefi koymak için <a href="profile_edit.php">profilini düzenle</a>.</p>
<?php endif; ?>

<form class="filters" method="get">
  <input name="q" id="liveq" placeholder="Oyun ara...  ( / tuşu )" value="<?= e($q) ?>" autocomplete="off">
  <select name="status">
    <option value="">Tüm durumlar</option>
    <?php foreach (STATUSES as $k => $v): ?>
      <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
  <select name="sort">
    <option value="recent" <?= $sort==='recent'?'selected':'' ?>>Son eklenen</option>
    <option value="hours" <?= $sort==='hours'?'selected':'' ?>>En çok oynanan</option>
    <option value="played" <?= $sort==='played'?'selected':'' ?>>Son oynanan</option>
    <option value="rating" <?= $sort==='rating'?'selected':'' ?>>Puana göre</option>
    <option value="name" <?= $sort==='name'?'selected':'' ?>>İsme göre</option>
  </select>
  <label class="chk"><input type="checkbox" name="fav" value="1" <?= $fav ? 'checked' : '' ?>> ★ Favoriler</label>
  <button class="btn">Filtrele</button>
  <a class="btn ghost" href="export.php" title="Excel'de açılır">⬇ CSV</a>
</form>

<?php if (!$games): ?>
  <div class="empty">
    <p>Burada henüz oyun yok.</p>
    <a class="btn" href="game_form.php">İlk oyununu ekle</a>
  </div>
<?php else: ?>
<div class="grid">
<?php foreach ($games as $g): ?>
  <article class="card" data-title="<?= e(mb_strtolower($g['title'])) ?>">
    <a href="game_view.php?id=<?= (int)$g['id'] ?>"><?= cover_html($g) ?></a>
    <div class="info">
      <div class="titlerow">
        <h3><a href="game_view.php?id=<?= (int)$g['id'] ?>"><?= e($g['title']) ?></a></h3>
        <form class="inl" method="post" action="game_fav.php">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
          <button class="star <?= $g['favorite'] ? 'on' : '' ?>" title="Favori">★</button>
        </form>
      </div>
      <div class="meta">
        <span class="tag s-<?= e($g['status']) ?>"><?= e(STATUSES[$g['status']]) ?></span>
        <span class="muted"><?= e($g['platform']) ?><?= $g['genre'] ? ' • ' . e($g['genre']) : '' ?></span>
      </div>
      <div class="hours"><?= e(fmt_hours($g['hours'])) ?>
        <?php if ($g['rating']): ?><span class="rate">★ <?= (int)$g['rating'] ?>/10</span><?php endif; ?>
      </div>
      <?php if ((int)$g['progress'] > 0 && $g['status'] !== 'istek'): ?>
        <div class="progress sm" title="%<?= (int)$g['progress'] ?> tamamlandı"><div style="width:<?= (int)$g['progress'] ?>%"></div></div>
      <?php endif; ?>
      <?php if ($g['last_played']): ?><div class="muted small">Son oynanma: <?= e(date('d.m.Y', strtotime($g['last_played']))) ?></div><?php endif; ?>
      <?php if ($g['note']): ?><p class="note"><?= e(mb_strimwidth($g['note'], 0, 90, '…')) ?></p><?php endif; ?>
      <form class="quick" method="post" action="game_hours.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
        <input type="number" name="hours" step="0.5" min="0.5" max="24" placeholder="+ saat" required>
        <button class="btn small">Ekle</button>
      </form>
      <div class="actions">
        <a href="game_form.php?id=<?= (int)$g['id'] ?>">Düzenle</a>
        <form method="post" action="game_delete.php" onsubmit="return confirm('Bu oyun silinsin mi?')">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
          <button class="link danger">Sil</button>
        </form>
      </div>
    </div>
  </article>
<?php endforeach; ?>
</div>
<?php endif; ?>
<div class="empty" id="noresult" style="display:none"><p>Eşleşen oyun bulunamadı.</p></div>
<script>
(function () {
  var q = document.getElementById('liveq'); if (!q) return;
  var cards = document.querySelectorAll('.grid .card'), none = document.getElementById('noresult');
  function f() {
    var v = q.value.trim().toLowerCase(), shown = 0;
    cards.forEach(function (c) { var ok = !v || c.dataset.title.indexOf(v) !== -1; c.style.display = ok ? '' : 'none'; if (ok) shown++; });
    if (none) none.style.display = (cards.length && !shown) ? '' : 'none';
  }
  q.addEventListener('input', f); f();
  document.addEventListener('keydown', function (e) {
    if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) { e.preventDefault(); q.focus(); }
  });
})();
</script>
<?php include 'footer.php'; ?>
