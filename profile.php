<?php
require 'config.php';
require_once 'gamify.php';
require_login();
$me = $_SESSION['user_id'];
$name = trim($_GET['u'] ?? $_SESSION['username']);

$s = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$s->execute([$name]);
$user = $s->fetch();
if (!$user) { flash('Kullanıcı bulunamadı.', 'err'); header('Location: library.php'); exit; }
$uid = (int)$user['id'];
$rel = relation($pdo, $me, $uid);

$gst = collect_stats($pdo, $uid);
$lv = level_info(calc_xp($gst));
$badges = get_badges($gst);
$earned = 0;
foreach ($badges as $b) if ($b['ok']) $earned++;

$gs = $pdo->prepare('SELECT * FROM games WHERE user_id = ? AND status <> "istek" ORDER BY hours DESC LIMIT 24');
$gs->execute([$uid]);
$games = $gs->fetchAll();

$fv = $pdo->prepare('SELECT * FROM games WHERE user_id = ? AND favorite = 1 ORDER BY hours DESC LIMIT 6');
$fv->execute([$uid]);
$favs = $fv->fetchAll();

$lp = $pdo->prepare('SELECT * FROM games WHERE user_id = ? AND last_played IS NOT NULL ORDER BY last_played DESC, id DESC LIMIT 1');
$lp->execute([$uid]);
$last = $lp->fetch();

$title = $user['username'] . ' - Profil';
include 'header.php';
?>
<section class="profilehead">
  <?= avatar_html($user, 96) ?>
  <div class="pinfo">
    <h1><?= e($user['username']) ?> <span class="tag lvltag">Sv. <?= $lv['level'] ?> • <?= e($lv['title']) ?></span></h1>
    <p class="muted"><?= e($user['bio'] ?: 'Henüz biyografi yazılmamış.') ?></p>
    <?php if ($last): ?><div class="small muted">🎮 Son oynadığı: <a href="game_view.php?id=<?= (int)$last['id'] ?>"><?= e($last['title']) ?></a> (<?= e(date('d.m.Y', strtotime($last['last_played']))) ?>)</div><?php endif; ?>
    <div class="small muted">Üyelik: <?= e(date('d.m.Y', strtotime($user['created_at']))) ?></div>
  </div>
  <div class="pact">
    <?php
    if ($rel === 'self') echo '<a class="btn small" href="profile_edit.php">Profili Düzenle</a>';
    elseif ($rel === 'none') echo friend_form('add', $uid, '+ Arkadaş Ekle');
    elseif ($rel === 'sent') echo '<span class="muted small">İstek gönderildi</span> ' . friend_form('remove', $uid, 'İptal', 'btn small ghost');
    elseif ($rel === 'received') echo friend_form('accept', $uid, 'İsteği Kabul Et') . ' ' . friend_form('remove', $uid, 'Reddet', 'btn small ghost');
    elseif ($rel === 'friends') echo '<span class="tag s-bitirdim">Arkadaşsınız</span> ' . friend_form('remove', $uid, 'Arkadaşlıktan Çıkar', 'btn small ghost', 'Arkadaşlıktan çıkarılsın mı?');
    ?>
  </div>
</section>

<section class="panel">
  <div class="bl"><span><b>Seviye <?= $lv['level'] ?></b> — <?= e($lv['title']) ?></span><span class="muted small"><?= $lv['xp'] ?> / <?= $lv['next'] ?> XP</span></div>
  <div class="progress"><div style="width:<?= $lv['pct'] ?>%"></div></div>
</section>

<section class="summary">
  <div><b><?= (int)$gst['games'] ?></b><span>Oyun</span></div>
  <div><b><?= e(fmt_hours($gst['hours'])) ?></b><span>Toplam Süre</span></div>
  <div><b><?= (int)$gst['finished'] ?></b><span>Bitirilen</span></div>
  <div><b><?= (int)$gst['friends'] ?></b><span>Arkadaş</span></div>
  <div><b><?= $earned ?>/<?= count($badges) ?></b><span>Rozet</span></div>
</section>

<?php if ($favs): ?>
<h2>★ Favori Oyunlar</h2>
<div class="grid mini">
  <?php foreach ($favs as $g): ?>
    <a class="card" href="game_view.php?id=<?= (int)$g['id'] ?>"><?= cover_html($g) ?>
      <div class="info"><h3><?= e($g['title']) ?></h3><div class="hours"><?= e(fmt_hours($g['hours'])) ?></div></div></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<h2>Rozetler</h2>
<div class="badges">
  <?php foreach ($badges as $b): ?>
    <div class="badge <?= $b['ok'] ? '' : 'off' ?>" title="<?= e($b['desc']) ?>">
      <span class="ic"><?= $b['ic'] ?></span><b><?= e($b['name']) ?></b>
      <div class="muted small"><?= e($b['desc']) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<h2>Oyunlar</h2>
<?php if (!$games): ?>
  <p class="muted">Henüz oyun yok.</p>
<?php else: ?>
<div class="grid mini">
  <?php foreach ($games as $g): ?>
    <a class="card" href="game_view.php?id=<?= (int)$g['id'] ?>">
      <?= cover_html($g) ?>
      <div class="info">
        <h3><?= e($g['title']) ?></h3>
        <div class="hours"><?= e(fmt_hours($g['hours'])) ?></div>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include 'footer.php'; ?>
