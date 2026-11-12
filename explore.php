<?php
require 'config.php';
require_login();

$pop = $pdo->query('SELECT MIN(id) AS id, MIN(title) AS title, MAX(cover_url) AS cover_url, MIN(genre) AS genre,
        COUNT(DISTINCT user_id) AS players, SUM(hours) AS total
    FROM games WHERE status <> "istek" GROUP BY LOWER(title) ORDER BY players DESC, total DESC LIMIT 8')->fetchAll();

$players = $pdo->query('SELECT u.username, u.avatar_url, COALESCE(SUM(g.hours),0) AS total, COUNT(g.id) AS cnt
    FROM users u LEFT JOIN games g ON g.user_id = u.id AND g.status <> "istek"
    GROUP BY u.id, u.username, u.avatar_url ORDER BY total DESC LIMIT 5')->fetchAll();

$newest = $pdo->query('SELECT username, avatar_url, created_at FROM users ORDER BY id DESC LIMIT 5')->fetchAll();

$recent = $pdo->query('SELECT g.id, g.title, g.cover_url, g.hours, u.username FROM games g JOIN users u ON u.id = g.user_id
    WHERE g.status <> "istek" ORDER BY g.created_at DESC LIMIT 6')->fetchAll();

$title = 'Keşfet';
include 'header.php';
?>
<h1>🧭 Keşfet</h1>
<p class="muted">Sitedeki herkesin kütüphanelerinden öne çıkanlar.</p>

<h2>🔥 En popüler oyunlar</h2>
<?php if (!$pop): ?><p class="muted">Henüz oyun yok.</p><?php endif; ?>
<div class="grid mini">
  <?php foreach ($pop as $g): ?>
    <a class="card" href="game_view.php?id=<?= (int)$g['id'] ?>">
      <?= cover_html($g) ?>
      <div class="info">
        <h3><?= e($g['title']) ?></h3>
        <div class="muted small"><?= (int)$g['players'] ?> oyuncu<?= $g['genre'] ? ' • ' . e($g['genre']) : '' ?></div>
        <div class="hours"><?= e(fmt_hours($g['total'])) ?></div>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<div class="two" style="margin-top:22px">
  <section class="panel">
    <h2>👑 En çok oynayanlar</h2>
    <?php foreach ($players as $i => $p): ?>
      <div class="urow"><b class="rank">#<?= $i + 1 ?></b><?= avatar_html($p, 38) ?>
        <div class="uname"><a href="profile.php?u=<?= urlencode($p['username']) ?>"><?= e($p['username']) ?></a>
          <div class="muted small"><?= (int)$p['cnt'] ?> oyun</div></div>
        <span class="hours"><?= e(fmt_hours($p['total'])) ?></span></div>
    <?php endforeach; ?>
  </section>
  <section class="panel">
    <h2>🌱 Yeni üyeler</h2>
    <?php foreach ($newest as $n): ?>
      <div class="urow"><?= avatar_html($n, 38) ?>
        <div class="uname"><a href="profile.php?u=<?= urlencode($n['username']) ?>"><?= e($n['username']) ?></a>
          <div class="muted small"><?= e(time_ago($n['created_at'])) ?></div></div></div>
    <?php endforeach; ?>
  </section>
</div>

<h2>🆕 Son eklenen oyunlar</h2>
<div class="grid mini">
  <?php foreach ($recent as $g): ?>
    <a class="card" href="game_view.php?id=<?= (int)$g['id'] ?>">
      <?= cover_html($g) ?>
      <div class="info"><h3><?= e($g['title']) ?></h3><div class="muted small"><?= e($g['username']) ?></div></div>
    </a>
  <?php endforeach; ?>
</div>
<?php include 'footer.php'; ?>
