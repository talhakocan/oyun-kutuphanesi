<?php
require 'config.php';
require_login();
$uid = $_SESSION['user_id'];

$top = $pdo->prepare('SELECT * FROM games WHERE user_id = ? AND hours > 0 ORDER BY hours DESC LIMIT 10');
$top->execute([$uid]);
$top = $top->fetchAll();
$max = $top ? max(array_column($top, 'hours')) : 1;

$bySt = $pdo->prepare('SELECT status, COUNT(*) c, SUM(hours) h FROM games WHERE user_id = ? GROUP BY status');
$bySt->execute([$uid]);
$bySt = array_column($bySt->fetchAll(), null, 'status');

$byGenre = $pdo->prepare('SELECT genre, SUM(hours) h FROM games WHERE user_id = ? AND genre IS NOT NULL GROUP BY genre ORDER BY h DESC LIMIT 5');
$byGenre->execute([$uid]);
$byGenre = $byGenre->fetchAll();

$week = $pdo->prepare('SELECT COALESCE(SUM(hours),0) FROM play_sessions WHERE user_id = ? AND played_on >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)');
$week->execute([$uid]);
$week = $week->fetchColumn();

// ---- ısı haritası (son 26 hafta) ----
$startTs = strtotime('monday this week');
$startTs = strtotime('-25 weeks', $startTs);
$ds = $pdo->prepare('SELECT played_on d, SUM(hours) h FROM play_sessions WHERE user_id = ? AND played_on >= ? GROUP BY played_on');
$ds->execute([$uid, date('Y-m-d', $startTs)]);
$map = array_column($ds->fetchAll(), 'h', 'd');
$today = date('Y-m-d');

$cells = [];
for ($i = 0; $i < 182; $i++) {
    $key = date('Y-m-d', strtotime("+$i day", $startTs));
    $h = (float)($map[$key] ?? 0);
    if ($key > $today) $cls = 'fut';
    elseif ($h <= 0) $cls = 'l0';
    elseif ($h < 1) $cls = 'l1';
    elseif ($h < 3) $cls = 'l2';
    elseif ($h < 6) $cls = 'l3';
    else $cls = 'l4';
    $cells[] = [$cls, date('d.m.Y', strtotime($key)) . ': ' . ($h > 0 ? fmt_hours($h) : 'oynanmadı')];
}

// ---- günlük seri ----
$streak = 0;
$day = isset($map[$today]) ? $today : date('Y-m-d', strtotime('-1 day'));
while (isset($map[$day]) && (float)$map[$day] > 0) {
    $streak++;
    $day = date('Y-m-d', strtotime("$day -1 day"));
}

// ---- aylık grafik (son 6 ay) ----
$trMonths = ['', 'Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
$mo = $pdo->prepare('SELECT DATE_FORMAT(played_on, "%Y-%m") m, SUM(hours) h FROM play_sessions WHERE user_id = ? AND played_on >= ? GROUP BY m');
$mo->execute([$uid, date('Y-m-01', strtotime('first day of -5 months'))]);
$mo = array_column($mo->fetchAll(), 'h', 'm');
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("first day of -$i months");
    $months[] = ['label' => $trMonths[(int)date('n', $ts)], 'h' => (float)($mo[date('Y-m', $ts)] ?? 0)];
}
$mmax = max(1, max(array_column($months, 'h')));

$title = 'İstatistikler';
include 'header.php';
?>
<h1>İstatistikler</h1>
<section class="summary">
  <?php foreach (STATUSES as $k => $v): ?>
    <div><b><?= (int)($bySt[$k]['c'] ?? 0) ?></b><span><?= $v ?></span></div>
  <?php endforeach; ?>
  <div><b><?= e(fmt_hours($week)) ?></b><span>Son 7 gün</span></div>
  <div><b>🔥 <?= $streak ?></b><span>Günlük seri</span></div>
</section>

<section class="panel">
  <h2>Oyun takvimi (son 26 hafta)</h2>
  <div class="heat">
    <?php foreach ($cells as $c): ?><i class="<?= $c[0] ?>" title="<?= e($c[1]) ?>"></i><?php endforeach; ?>
  </div>
  <div class="muted small legend">Az <i class="l0"></i><i class="l1"></i><i class="l2"></i><i class="l3"></i><i class="l4"></i> Çok
    &nbsp;•&nbsp; Takvim, “+ saat” ve oyun sayacı kayıtlarından oluşur.</div>
</section>

<section class="panel">
  <h2>Aylık oyun süresi</h2>
  <div class="mbars">
    <?php foreach ($months as $m): ?>
      <div class="mbar"><span><?= e(fmt_hours($m['h'])) ?></span>
        <div style="height:<?= max(2, round($m['h'] / $mmax * 100)) ?>%"></div>
        <span class="muted"><?= e($m['label']) ?></span></div>
    <?php endforeach; ?>
  </div>
</section>

<div class="two">
  <section class="panel">
    <h2>En çok oynanan oyunlar</h2>
    <?php if (!$top): ?><p class="muted">Henüz süre girilmemiş.</p><?php endif; ?>
    <?php foreach ($top as $g): ?>
      <div class="bar">
        <div class="bl"><span><?= e($g['title']) ?></span><span><?= e(fmt_hours($g['hours'])) ?></span></div>
        <div class="track"><div class="fill" style="width:<?= round($g['hours'] / $max * 100) ?>%"></div></div>
      </div>
    <?php endforeach; ?>
  </section>
  <section class="panel">
    <h2>Türlere göre süre</h2>
    <?php if (!$byGenre): ?><p class="muted">Oyunlara tür ekleyince burada görünür.</p><?php endif; ?>
    <?php foreach ($byGenre as $r): ?>
      <div class="bl row2"><span><?= e($r['genre']) ?></span><span><?= e(fmt_hours($r['h'])) ?></span></div>
    <?php endforeach; ?>
  </section>
</div>
<?php include 'footer.php'; ?>
