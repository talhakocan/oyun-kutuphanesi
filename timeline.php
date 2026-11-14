<?php
require 'config.php';
require_login();
$uid = (int)$_SESSION['user_id'];
$tr = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

$gs = $pdo->prepare('SELECT g.*, DATE_FORMAT(g.created_at, "%Y-%m") AS ym FROM games g WHERE g.user_id = ? ORDER BY g.created_at DESC');
$gs->execute([$uid]);
$byMonth = [];
foreach ($gs->fetchAll() as $g) $byMonth[$g['ym']]['games'][] = $g;

$ss = $pdo->prepare('SELECT DATE_FORMAT(played_on, "%Y-%m") AS ym, SUM(hours) AS h, COUNT(DISTINCT game_id) AS n FROM play_sessions WHERE user_id = ? GROUP BY ym');
$ss->execute([$uid]);
foreach ($ss->fetchAll() as $r) {
    $byMonth[$r['ym']]['h'] = (float)$r['h'];
    $byMonth[$r['ym']]['n'] = (int)$r['n'];
}
krsort($byMonth);

$title = 'Zaman Çizelgesi';
include 'header.php';
?>
<h1>🕒 Zaman Çizelgesi</h1>
<p class="muted">Ay ay oyun yolculuğun: eklediğin oyunlar ve oynadığın süre.</p>
<?php if (!$byMonth): ?><div class="empty"><p>Henüz kayıt yok.</p></div><?php endif; ?>
<div class="tline">
<?php foreach ($byMonth as $ym => $d):
    [$y, $m] = explode('-', $ym); ?>
  <div class="tl-item">
    <div class="tl-dot"></div>
    <div class="tl-body">
      <h2><?= e($tr[(int)$m] . ' ' . $y) ?></h2>
      <div class="muted small">
        <?php if (!empty($d['h'])): ?>⏱️ <?= e(fmt_hours($d['h'])) ?> oynandı • <?= (int)($d['n'] ?? 0) ?> farklı oyun<?php endif; ?>
        <?php if (!empty($d['games'])): ?> • 🆕 <?= count($d['games']) ?> oyun eklendi<?php endif; ?>
      </div>
      <?php if (!empty($d['games'])): ?>
      <div class="grid mini" style="margin-top:12px">
        <?php foreach ($d['games'] as $g): ?>
          <a class="card" href="game_view.php?id=<?= (int)$g['id'] ?>"><?= cover_html($g) ?>
            <div class="info"><h3><?= e($g['title']) ?></h3><div class="muted small"><?= e(STATUSES[$g['status']]) ?></div></div></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php include 'footer.php'; ?>
