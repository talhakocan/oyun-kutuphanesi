<?php
require 'config.php';
require_login();
$me = (int)$_SESSION['user_id'];

$a = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
$a->execute([$me]);
if (!$a->fetchColumn()) {
    flash('Bu sayfa sadece yöneticiler içindir.', 'err');
    header('Location: library.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $target = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete_user' && $target !== $me) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$target]);
        flash('Kullanıcı ve tüm verileri silindi.');
    } else {
        flash('Kendi hesabını buradan silemezsin.', 'err');
    }
    header('Location: admin.php'); exit;
}

$tot = [
    'users'    => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'games'    => $pdo->query('SELECT COUNT(*) FROM games')->fetchColumn(),
    'hours'    => $pdo->query('SELECT COALESCE(SUM(hours),0) FROM games WHERE status <> "istek"')->fetchColumn(),
    'comments' => $pdo->query('SELECT COUNT(*) FROM game_comments')->fetchColumn(),
    'friends'  => $pdo->query('SELECT COUNT(*) FROM friendships WHERE status = "accepted"')->fetchColumn(),
];
$users = $pdo->query('SELECT u.id, u.username, u.email, u.created_at, u.is_admin,
    (SELECT COUNT(*) FROM games WHERE user_id = u.id) AS gc,
    (SELECT COALESCE(SUM(hours),0) FROM games WHERE user_id = u.id AND status <> "istek") AS h
    FROM users u ORDER BY u.id')->fetchAll();
$pop = $pdo->query('SELECT MIN(title) AS title, COUNT(DISTINCT user_id) AS players, SUM(hours) AS total
    FROM games GROUP BY LOWER(title) ORDER BY players DESC, total DESC LIMIT 5')->fetchAll();

$title = 'Yönetim Paneli';
include 'header.php';
?>
<h1>🛡️ Yönetim Paneli</h1>
<section class="summary">
  <div><b><?= (int)$tot['users'] ?></b><span>Kullanıcı</span></div>
  <div><b><?= (int)$tot['games'] ?></b><span>Oyun kaydı</span></div>
  <div><b><?= e(fmt_hours($tot['hours'])) ?></b><span>Toplam süre</span></div>
  <div><b><?= (int)$tot['comments'] ?></b><span>Yorum</span></div>
  <div><b><?= (int)$tot['friends'] ?></b><span>Arkadaşlık</span></div>
</section>

<div class="two">
  <section class="panel">
    <h2>Kullanıcılar</h2>
    <div class="tablewrap">
    <table class="tbl">
      <tr><th>#</th><th>Kullanıcı</th><th>E-posta</th><th>Oyun</th><th>Süre</th><th>Üyelik</th><th></th></tr>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?= (int)$u['id'] ?></td>
        <td><a href="profile.php?u=<?= urlencode($u['username']) ?>"><?= e($u['username']) ?></a><?= $u['is_admin'] ? ' 🛡️' : '' ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= (int)$u['gc'] ?></td>
        <td><?= e(fmt_hours($u['h'])) ?></td>
        <td><?= e(date('d.m.Y', strtotime($u['created_at']))) ?></td>
        <td><?php if ((int)$u['id'] !== $me): ?>
          <form method="post" onsubmit="return confirm('Bu kullanıcı ve tüm verileri silinsin mi?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="link danger">Sil</button></form>
        <?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
  </section>
  <section class="panel">
    <h2>En popüler 5 oyun</h2>
    <?php foreach ($pop as $p): ?>
      <div class="bl row2"><span><?= e($p['title']) ?></span><span><?= (int)$p['players'] ?> oyuncu</span></div>
    <?php endforeach; ?>
    <p class="muted small" style="margin-top:14px">İlk kayıt olan kullanıcı otomatik yönetici olur.</p>
  </section>
</div>
<?php include 'footer.php'; ?>
