<?php
require 'config.php';
require_login();
$me = $_SESSION['user_id'];
$q = trim($_GET['q'] ?? '');

$results = [];
if ($q !== '') {
    $s = $pdo->prepare('SELECT * FROM users WHERE username LIKE ? AND id <> ? ORDER BY username LIMIT 12');
    $s->execute(["%$q%", $me]);
    $results = $s->fetchAll();
}

$base = 'SELECT u.*, COALESCE((SELECT SUM(hours) FROM games g WHERE g.user_id = u.id AND g.status <> "istek"),0) AS total_hours FROM friendships f JOIN users u ON u.id = ';

$recv = $pdo->prepare($base . 'f.requester_id WHERE f.addressee_id = ? AND f.status = "pending"');
$recv->execute([$me]);
$recv = $recv->fetchAll();

$sent = $pdo->prepare($base . 'f.addressee_id WHERE f.requester_id = ? AND f.status = "pending"');
$sent->execute([$me]);
$sent = $sent->fetchAll();

$fr = $pdo->prepare('SELECT u.*, COALESCE((SELECT SUM(hours) FROM games g WHERE g.user_id = u.id AND g.status <> "istek"),0) AS total_hours
    FROM users u WHERE u.id IN (
      SELECT addressee_id FROM friendships WHERE requester_id = ? AND status = "accepted"
      UNION SELECT requester_id FROM friendships WHERE addressee_id = ? AND status = "accepted"
    ) ORDER BY u.username');
$fr->execute([$me, $me]);
$friends = $fr->fetchAll();

function user_row($u, $actions = '') {
    return '<div class="urow">' . avatar_html($u, 42)
        . '<div class="uname"><a href="profile.php?u=' . urlencode($u['username']) . '">' . e($u['username']) . '</a>'
        . (isset($u['total_hours']) ? '<div class="muted small">' . e(fmt_hours($u['total_hours'])) . ' oynadı</div>' : '')
        . '</div><div class="uact">' . $actions . '</div></div>';
}

$title = 'Arkadaşlar';
include 'header.php';
?>
<h1>Arkadaşlar</h1>

<form class="filters" method="get">
  <input name="q" placeholder="Kullanıcı adı ara..." value="<?= e($q) ?>">
  <button class="btn">Ara</button>
</form>

<?php if ($q !== ''): ?>
<section class="panel">
  <h2>Arama sonuçları</h2>
  <?php if (!$results): ?><p class="muted">Kullanıcı bulunamadı.</p><?php endif; ?>
  <?php foreach ($results as $u):
      $rel = relation($pdo, $me, $u['id']);
      if ($rel === 'none') $act = friend_form('add', $u['id'], '+ Ekle');
      elseif ($rel === 'sent') $act = '<span class="muted small">İstek gönderildi</span>';
      elseif ($rel === 'received') $act = friend_form('accept', $u['id'], 'Kabul Et');
      else $act = '<span class="tag s-bitirdim">Arkadaş</span>';
      echo user_row($u, $act);
  endforeach; ?>
</section>
<?php endif; ?>

<?php if ($recv): ?>
<section class="panel">
  <h2>Gelen istekler (<?= count($recv) ?>)</h2>
  <?php foreach ($recv as $u) echo user_row($u, friend_form('accept', $u['id'], 'Kabul Et') . ' ' . friend_form('remove', $u['id'], 'Reddet', 'btn small ghost')); ?>
</section>
<?php endif; ?>

<section class="panel">
  <h2>Arkadaşlarım (<?= count($friends) ?>)</h2>
  <?php if (!$friends): ?><p class="muted">Henüz arkadaşın yok. Yukarıdan kullanıcı adı arayıp ekleyebilirsin.</p><?php endif; ?>
  <?php foreach ($friends as $u) echo user_row($u, friend_form('remove', $u['id'], 'Çıkar', 'btn small ghost', 'Arkadaşlıktan çıkarılsın mı?')); ?>
</section>

<?php if ($sent): ?>
<section class="panel">
  <h2>Gönderilen istekler</h2>
  <?php foreach ($sent as $u) echo user_row($u, friend_form('remove', $u['id'], 'İptal', 'btn small ghost')); ?>
</section>
<?php endif; ?>
<?php include 'footer.php'; ?>
