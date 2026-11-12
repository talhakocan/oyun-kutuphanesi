<?php
require 'config.php';
require_once 'gamify.php';
require_login();
$me = (int)$_SESSION['user_id'];
$tab = ($_GET['t'] ?? 'week') === 'all' ? 'all' : 'week';
$ids = array_merge([$me], friend_ids($pdo, $me));
$in = implode(',', array_fill(0, count($ids), '?'));

$st = $pdo->prepare("SELECT u.id, u.username, u.avatar_url,
    COALESCE((SELECT SUM(hours) FROM games WHERE user_id = u.id AND status <> 'istek'),0) AS total,
    COALESCE((SELECT SUM(hours) FROM play_sessions WHERE user_id = u.id AND played_on >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)),0) AS week
    FROM users u WHERE u.id IN ($in)");
$st->execute($ids);
$rows = $st->fetchAll();
$key = $tab === 'all' ? 'total' : 'week';
usort($rows, function ($a, $b) use ($key) { return (float)$b[$key] <=> (float)$a[$key]; });

$title = 'Liderlik Tablosu';
include 'header.php';
$medals = ['🥇', '🥈', '🥉'];
?>
<h1>🏆 Liderlik Tablosu</h1>
<p class="muted">Sen ve arkadaşların arasındaki sıralama.</p>
<div class="tabs">
  <a class="<?= $tab === 'week' ? 'on' : '' ?>" href="leaderboard.php?t=week">Son 7 gün</a>
  <a class="<?= $tab === 'all' ? 'on' : '' ?>" href="leaderboard.php?t=all">Tüm zamanlar</a>
</div>

<?php if (count($rows) < 2): ?>
  <div class="empty"><p>Sıralama için arkadaş eklemelisin.</p><a class="btn" href="friends.php">Arkadaş bul</a></div>
<?php endif; ?>

<div class="podium">
  <?php
  $order = [1, 0, 2]; // 2. - 1. - 3. sıra düzeni
  foreach ($order as $i) { if (!isset($rows[$i])) continue; $r = $rows[$i]; ?>
    <div class="pod p<?= $i + 1 ?>">
      <div class="medal"><?= $medals[$i] ?></div>
      <?= avatar_html($r, 56) ?>
      <div><a href="profile.php?u=<?= urlencode($r['username']) ?>"><b><?= e($r['username']) ?></b></a></div>
      <div class="hours"><?= e(fmt_hours($r[$key])) ?></div>
    </div>
  <?php } ?>
</div>

<?php if (count($rows) > 3): ?>
<section class="panel">
  <?php foreach ($rows as $i => $r): if ($i < 3) continue; ?>
    <div class="urow">
      <b class="rank">#<?= $i + 1 ?></b><?= avatar_html($r, 38) ?>
      <div class="uname"><a href="profile.php?u=<?= urlencode($r['username']) ?>"><?= e($r['username']) ?></a></div>
      <div class="hours"><?= e(fmt_hours($r[$key])) ?></div>
    </div>
  <?php endforeach; ?>
</section>
<?php endif; ?>
<?php include 'footer.php'; ?>
