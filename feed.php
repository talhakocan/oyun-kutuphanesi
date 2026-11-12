<?php
require 'config.php';
require_login();
$me = (int)$_SESSION['user_id'];
$ids = array_merge([$me], friend_ids($pdo, $me));
$in = implode(',', array_fill(0, count($ids), '?'));

$sql = "(SELECT g.created_at AS ts, 'game' AS type, u.username, u.avatar_url, g.title, g.id AS gid, 0 AS num, '' AS txt
         FROM games g JOIN users u ON u.id = g.user_id WHERE g.user_id IN ($in) AND g.status <> 'istek')
        UNION ALL
        (SELECT ps.created_at, 'play', u.username, u.avatar_url, g.title, g.id, ps.hours, ''
         FROM play_sessions ps JOIN games g ON g.id = ps.game_id JOIN users u ON u.id = ps.user_id WHERE ps.user_id IN ($in))
        UNION ALL
        (SELECT c.created_at, 'comment', u.username, u.avatar_url, g.title, g.id, 0, c.body
         FROM game_comments c JOIN games g ON g.id = c.game_id JOIN users u ON u.id = c.user_id WHERE c.user_id IN ($in))
        ORDER BY ts DESC LIMIT 40";
$st = $pdo->prepare($sql);
$st->execute(array_merge($ids, $ids, $ids));
$items = $st->fetchAll();

$title = 'Etkinlik Akışı';
include 'header.php';
?>
<h1>Etkinlik Akışı</h1>
<p class="muted">Senin ve arkadaşlarının son hareketleri.</p>
<section class="panel">
<?php if (!$items): ?><p class="muted">Henüz etkinlik yok. Oyun ekle, süre gir ya da arkadaş edin!</p><?php endif; ?>
<?php foreach ($items as $it):
    $isMe = $it['username'] === $_SESSION['username'];
    $link = '<a href="game_view.php?id=' . (int)$it['gid'] . '">' . e($it['title']) . '</a>';
    $uLink = '<a href="profile.php?u=' . urlencode($it['username']) . '"><b>' . e($isMe ? 'Sen' : $it['username']) . '</b></a>';
    if ($it['type'] === 'game') $text = "🆕 $uLink kütüphaneye $link oyununu ekledi.";
    elseif ($it['type'] === 'play') $text = "⏱️ $uLink $link oyununda <b>" . e(fmt_hours($it['num'])) . '</b> oynadı.';
    else $text = "💬 $uLink $link oyununa yorum yaptı: <span class=\"muted\">“" . e(mb_strimwidth($it['txt'], 0, 120, '…')) . '”</span>';
?>
  <div class="feeditem">
    <?= avatar_html($it, 38) ?>
    <div class="cbody"><?= $text ?><div class="muted small"><?= e(time_ago($it['ts'])) ?></div></div>
  </div>
<?php endforeach; ?>
</section>
<?php include 'footer.php'; ?>
