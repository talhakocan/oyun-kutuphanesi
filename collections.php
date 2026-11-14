<?php
require 'config.php';
require_login();
$uid = (int)$_SESSION['user_id'];
$cid = (int)($_GET['id'] ?? 0);

function own_collection($pdo, $cid, $uid) {
    $s = $pdo->prepare('SELECT * FROM collections WHERE id = ? AND user_id = ?');
    $s->execute([$cid, $uid]);
    return $s->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    $pc = (int)($_POST['cid'] ?? 0);
    if ($act === 'create') {
        $name = trim($_POST['name'] ?? ''); $desc = trim($_POST['description'] ?? '');
        if ($name === '' || mb_strlen($name) > 40 || mb_strlen($desc) > 120) flash('Koleksiyon adı 1-40, açıklama en fazla 120 karakter olmalı.', 'err');
        else {
            $pdo->prepare('INSERT INTO collections (user_id, name, description) VALUES (?,?,?)')->execute([$uid, $name, $desc ?: null]);
            flash('Koleksiyon oluşturuldu.');
            header('Location: collections.php?id=' . $pdo->lastInsertId()); exit;
        }
    } elseif ($act === 'delete' && own_collection($pdo, $pc, $uid)) {
        $pdo->prepare('DELETE FROM collections WHERE id = ?')->execute([$pc]);
        flash('Koleksiyon silindi.');
        header('Location: collections.php'); exit;
    } elseif ($act === 'add' && own_collection($pdo, $pc, $uid)) {
        $gid = (int)($_POST['game_id'] ?? 0);
        $ok = $pdo->prepare('SELECT id FROM games WHERE id = ? AND user_id = ?');
        $ok->execute([$gid, $uid]);
        if ($ok->fetch()) {
            $pdo->prepare('INSERT IGNORE INTO collection_games (collection_id, game_id) VALUES (?,?)')->execute([$pc, $gid]);
            flash('Oyun koleksiyona eklendi.');
        }
        header('Location: collections.php?id=' . $pc); exit;
    } elseif ($act === 'remove' && own_collection($pdo, $pc, $uid)) {
        $pdo->prepare('DELETE FROM collection_games WHERE collection_id = ? AND game_id = ?')->execute([$pc, (int)($_POST['game_id'] ?? 0)]);
        header('Location: collections.php?id=' . $pc); exit;
    }
    header('Location: collections.php' . ($pc ? '?id=' . $pc : '')); exit;
}

$col = $cid ? own_collection($pdo, $cid, $uid) : null;
if ($cid && !$col) { flash('Koleksiyon bulunamadı.', 'err'); header('Location: collections.php'); exit; }

$title = $col ? $col['name'] : 'Koleksiyonlar';
include 'header.php';

if ($col):
    $gs = $pdo->prepare('SELECT g.* FROM collection_games cg JOIN games g ON g.id = cg.game_id WHERE cg.collection_id = ? ORDER BY g.title');
    $gs->execute([$cid]);
    $games = $gs->fetchAll();
    $avail = $pdo->prepare('SELECT id, title FROM games WHERE user_id = ? AND id NOT IN (SELECT game_id FROM collection_games WHERE collection_id = ?) ORDER BY title');
    $avail->execute([$uid, $cid]);
    $avail = $avail->fetchAll();
    $totalH = 0; foreach ($games as $g) $totalH += (float)$g['hours'];
?>
<p><a href="collections.php">← Tüm koleksiyonlar</a></p>
<section class="profilehead">
  <div class="pinfo">
    <h1>🗂️ <?= e($col['name']) ?></h1>
    <p class="muted"><?= e($col['description'] ?: 'Açıklama yok.') ?></p>
    <div class="small muted"><?= count($games) ?> oyun • toplam <?= e(fmt_hours($totalH)) ?></div>
  </div>
  <form method="post" onsubmit="return confirm('Koleksiyon silinsin mi? (Oyunlar silinmez)')">
    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="cid" value="<?= $cid ?>">
    <button class="btn small ghost">Koleksiyonu sil</button>
  </form>
</section>

<?php if ($avail): ?>
<form method="post" class="filters">
  <?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="cid" value="<?= $cid ?>">
  <select name="game_id" required>
    <option value="">Koleksiyona oyun ekle...</option>
    <?php foreach ($avail as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e($a['title']) ?></option><?php endforeach; ?>
  </select>
  <button class="btn">Ekle</button>
</form>
<?php endif; ?>

<?php if (!$games): ?><div class="empty"><p>Bu koleksiyon henüz boş.</p></div><?php endif; ?>
<div class="grid mini">
  <?php foreach ($games as $g): ?>
    <div class="card">
      <a href="game_view.php?id=<?= (int)$g['id'] ?>"><?= cover_html($g) ?></a>
      <div class="info">
        <h3><a href="game_view.php?id=<?= (int)$g['id'] ?>"><?= e($g['title']) ?></a></h3>
        <div class="hours"><?= e(fmt_hours($g['hours'])) ?></div>
        <form method="post" onsubmit="return confirm('Koleksiyondan çıkarılsın mı?')">
          <?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="cid" value="<?= $cid ?>"><input type="hidden" name="game_id" value="<?= (int)$g['id'] ?>">
          <button class="link danger">Çıkar</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php else:
    $ls = $pdo->prepare('SELECT c.*, (SELECT COUNT(*) FROM collection_games WHERE collection_id = c.id) AS cnt FROM collections c WHERE c.user_id = ? ORDER BY c.created_at DESC');
    $ls->execute([$uid]);
    $cols = $ls->fetchAll();
?>
<h1>🗂️ Koleksiyonlar</h1>
<p class="muted">Oyunlarını kendi listelerine ayır: “Efsane RPG'ler”, “Arkadaşlarla oynananlar”, “Yaz tatili”…</p>

<form method="post" class="filters">
  <?= csrf_field() ?><input type="hidden" name="action" value="create">
  <input name="name" placeholder="Yeni koleksiyon adı" maxlength="40" required>
  <input name="description" placeholder="Açıklama (isteğe bağlı)" maxlength="120">
  <button class="btn">Oluştur</button>
</form>

<?php if (!$cols): ?><div class="empty"><p>Henüz koleksiyonun yok. Yukarıdan ilkini oluştur.</p></div><?php endif; ?>
<div class="grid">
  <?php foreach ($cols as $c):
      $m = $pdo->prepare('SELECT g.* FROM collection_games cg JOIN games g ON g.id = cg.game_id WHERE cg.collection_id = ? ORDER BY g.hours DESC LIMIT 4');
      $m->execute([$c['id']]);
      $mos = $m->fetchAll(); ?>
    <a class="card colcard" href="collections.php?id=<?= (int)$c['id'] ?>">
      <div class="mosaic">
        <?php for ($i = 0; $i < 4; $i++): echo isset($mos[$i]) ? cover_html($mos[$i]) : '<div class="cover empty-cell"></div>'; endfor; ?>
      </div>
      <div class="info">
        <h3><?= e($c['name']) ?></h3>
        <div class="muted small"><?= (int)$c['cnt'] ?> oyun<?= $c['description'] ? ' • ' . e($c['description']) : '' ?></div>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include 'footer.php'; ?>
