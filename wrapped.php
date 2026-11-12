<?php
require 'config.php';
require_once 'gamify.php';
require_login();
$uid = $_SESSION['user_id'];

$st = collect_stats($pdo, $uid);
$xp = calc_xp($st);
$lv = level_info($xp);
$badges = get_badges($st);
$earned = 0;
foreach ($badges as $b) if ($b['ok']) $earned++;

$q = function ($sql, $params = null) use ($pdo, $uid) {
    $s = $pdo->prepare($sql);
    $s->execute($params ?? [$uid]);
    return $s;
};

$top3 = $q('SELECT * FROM games WHERE user_id = ? AND status <> "istek" AND hours > 0 ORDER BY hours DESC LIMIT 3')->fetchAll();
$genre = $q('SELECT genre, SUM(hours) h FROM games WHERE user_id = ? AND genre IS NOT NULL AND genre <> "" AND status <> "istek" GROUP BY genre ORDER BY h DESC LIMIT 1')->fetch();
$longest = $q('SELECT ps.hours, ps.played_on, g.title FROM play_sessions ps JOIN games g ON g.id = ps.game_id WHERE ps.user_id = ? ORDER BY ps.hours DESC LIMIT 1')->fetch();
$bestday = $q('SELECT played_on d, SUM(hours) h FROM play_sessions WHERE user_id = ? GROUP BY played_on ORDER BY h DESC LIMIT 1')->fetch();
$wd = $q('SELECT DAYOFWEEK(played_on) w, SUM(hours) h FROM play_sessions WHERE user_id = ? GROUP BY w ORDER BY h DESC LIMIT 1')->fetch();
$m30 = (float)$q('SELECT COALESCE(SUM(hours),0) FROM play_sessions WHERE user_id = ? AND played_on >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)')->fetchColumn();
$dayNames = [1 => 'Pazar', 2 => 'Pazartesi', 3 => 'Salı', 4 => 'Çarşamba', 5 => 'Perşembe', 6 => 'Cuma', 7 => 'Cumartesi'];

// Oyuncu tipi
if ($st['maxsession'] >= 5)       $persona = ['🏃', 'Maratoncu', 'Bir oturuşta saatlerce bırakmayan tipsin.'];
elseif ($st['games'] > 0 && $st['finished'] >= 3 && $st['finished'] / $st['games'] >= 0.4) $persona = ['🏁', 'Bitirici', 'Başladığın oyunu sonuna kadar götürürsün.'];
elseif ($st['genres'] >= 4)       $persona = ['🧭', 'Kaşif', 'Her türden oyuna şans verirsin.'];
elseif ($st['friends'] >= 3)      $persona = ['🤝', 'Sosyal Oyuncu', 'Oyun senin için arkadaşlarla daha güzel.'];
elseif ($st['days'] >= 7)         $persona = ['📅', 'Düzenli Oyuncu', 'Her gün biraz, istikrar sende.'];
else                              $persona = ['😎', 'Rahat Oyuncu', 'Keyfine göre oynarsın, acelen yok.'];

$films = (int)floor($st['hours'] / 2);
$days = round($st['hours'] / 24, 1);

$title = 'Oyun Özetim';
include 'header.php';
?>
<div class="wrapped">
  <div class="wcard w1">
    <h2>🎬 <?= e($_SESSION['username']) ?></h2>
    <div class="big">Oyun Özetin</div>
    <div class="sub">Hazırsan başlayalım. Aşağı kaydır ↓</div>
  </div>

  <div class="wcard w2">
    <h2>Toplam oyun süren</h2>
    <div class="big"><?= e(fmt_hours($st['hours'])) ?></div>
    <div class="sub">Bu, yaklaşık <b><?= $films ?></b> film izlemeye ya da tam <b><?= str_replace('.', ',', (string)$days) ?></b> güne denk geliyor.</div>
    <div class="sub">Son 30 günde: <b><?= e(fmt_hours($m30)) ?></b></div>
  </div>

  <div class="wcard w3">
    <h2>En çok vakit harcadığın oyunlar</h2>
    <?php if (!$top3): ?><div class="sub">Henüz süre girmedin.</div><?php endif; ?>
    <?php foreach ($top3 as $i => $g): ?>
      <div class="<?= $i === 0 ? 'big' : 'sub' ?>"><?= ['🥇', '🥈', '🥉'][$i] ?> <?= e($g['title']) ?>
        <span class="small">(<?= e(fmt_hours($g['hours'])) ?>)</span></div>
    <?php endforeach; ?>
  </div>

  <div class="wcard w4">
    <h2>Favori türün</h2>
    <?php if ($genre): ?>
      <div class="big"><?= e($genre['genre']) ?></div>
      <div class="sub">Bu türde toplam <b><?= e(fmt_hours($genre['h'])) ?></b> oynadın.</div>
    <?php else: ?><div class="sub">Oyunlarına tür eklersen burada görünür.</div><?php endif; ?>
  </div>

  <div class="wcard w5">
    <h2>Rekorların</h2>
    <?php if ($longest): ?>
      <div class="sub">En uzun oturum: <b><?= e(fmt_hours($longest['hours'])) ?></b> — <?= e($longest['title']) ?> (<?= e(date('d.m.Y', strtotime($longest['played_on']))) ?>)</div>
    <?php endif; ?>
    <?php if ($bestday): ?>
      <div class="sub">En yoğun gün: <b><?= e(date('d.m.Y', strtotime($bestday['d']))) ?></b> — <?= e(fmt_hours($bestday['h'])) ?></div>
    <?php endif; ?>
    <?php if ($wd): ?>
      <div class="sub">En çok oynadığın gün: <b><?= e($dayNames[(int)$wd['w']]) ?></b></div>
    <?php endif; ?>
    <?php if (!$longest && !$bestday): ?><div class="sub">“+ saat” veya oyun sayacıyla kayıt girince rekorların burada çıkar.</div><?php endif; ?>
  </div>

  <div class="wcard w6">
    <h2>Seviyen</h2>
    <div class="big">Sv. <?= $lv['level'] ?></div>
    <div class="sub"><?= e($lv['title']) ?> • <?= $lv['xp'] ?> XP • <?= $earned ?>/<?= count($badges) ?> rozet</div>
    <div class="wbadges">
      <?php foreach ($badges as $b) if ($b['ok']) echo '<span title="' . e($b['name']) . '">' . $b['ic'] . '</span>'; ?>
    </div>
  </div>

  <div class="wcard w7">
    <h2>Oyuncu tipin</h2>
    <div class="big"><?= $persona[0] ?> <?= e($persona[1]) ?></div>
    <div class="sub"><?= e($persona[2]) ?></div>
    <div style="margin-top:18px"><a class="btn" href="profile.php">Profilime git</a></div>
  </div>
</div>
<script>
(function () {
  var cards = document.querySelectorAll('.wcard');
  if (!('IntersectionObserver' in window)) { cards.forEach(function (c) { c.classList.add('in'); }); return; }
  var io = new IntersectionObserver(function (es) {
    es.forEach(function (e) { if (e.isIntersecting) e.target.classList.add('in'); });
  }, { threshold: 0.25 });
  cards.forEach(function (c) { io.observe(c); });
})();
</script>
<?php include 'footer.php'; ?>
