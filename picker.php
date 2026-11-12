<?php
require 'config.php';
require_login();
$uid = $_SESSION['user_id'];
$from = $_GET['from'] ?? 'unfinished';
$where = 'user_id = ?';
if ($from === 'istek') $where .= ' AND status = "istek"';
elseif ($from === 'oynuyorum') $where .= ' AND status = "oynuyorum"';
elseif ($from === 'all') $where .= '';
else { $from = 'unfinished'; $where .= ' AND status <> "bitirdim"'; }

$s = $pdo->prepare("SELECT id, title, platform, hours, cover_url FROM games WHERE $where");
$s->execute([$uid]);
$list = $s->fetchAll();

$title = 'Ne Oynasam?';
include 'header.php';
$opts = ['unfinished' => 'Bitirmediklerim', 'oynuyorum' => 'Oynuyorum', 'istek' => 'İstek listem', 'all' => 'Hepsi'];
?>
<div class="formbox" style="text-align:center">
  <h1>🎲 Bugün Ne Oynasam?</h1>
  <p class="muted">Karar veremiyorsan çarkı çevir, kader seçsin.</p>
  <form method="get" class="filters" style="justify-content:center">
    <select name="from" onchange="this.form.submit()" style="max-width:240px">
      <?php foreach ($opts as $k => $v): ?><option value="<?= $k ?>" <?= $from === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
    </select>
  </form>

  <?php if (!$list): ?>
    <p class="muted">Bu listede oyun yok.</p>
    <a class="btn" href="game_form.php">Oyun ekle</a>
  <?php else: ?>
    <div class="slot" id="slot">❓</div>
    <button class="btn" id="spin">Çarkı Çevir!</button>
    <div id="result" class="muted" style="margin-top:14px"></div>
  <?php endif; ?>
</div>

<?php if ($list): ?>
<script>
var games = <?= json_encode($list, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
var slot = document.getElementById('slot'), btn = document.getElementById('spin'), res = document.getElementById('result');
var cur = 0, busy = false;
btn.onclick = function () {
  if (busy) return;
  busy = true; slot.classList.remove('done'); res.textContent = '';
  var n = games.length, pick = Math.floor(Math.random() * n);
  var steps = n * 2 + ((pick - cur + n) % n) + 6, i = 0, delay = 45;
  (function tick() {
    cur = (cur + 1) % n; i++;
    slot.textContent = games[cur].title;
    if (i >= steps) {
      slot.classList.add('done'); busy = false;
      res.textContent = '';
      var a = document.createElement('a');
      a.href = 'game_view.php?id=' + games[cur].id;
      a.className = 'btn small';
      a.textContent = 'Oyun sayfasına git';
      var info = document.createElement('div');
      info.textContent = games[cur].platform + ' • şimdiye kadar ' + games[cur].hours + ' saat';
      res.appendChild(info); res.appendChild(a);
      return;
    }
    if (steps - i < 14) delay += 14;
    setTimeout(tick, delay);
  })();
};
</script>
<?php endif; ?>
<?php include 'footer.php'; ?>
