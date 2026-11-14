<?php
require 'config.php';
if (!empty($_SESSION['user_id']) || (AUTO_LOGIN && !isset($_SESSION['no_auto']))) { header('Location: library.php'); exit; }

$tu = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$tg = (int)$pdo->query('SELECT COUNT(*) FROM games WHERE status <> "istek"')->fetchColumn();
$th = (float)$pdo->query('SELECT COALESCE(SUM(hours),0) FROM games WHERE status <> "istek"')->fetchColumn();

$title = 'Hoş geldin';
include 'header.php';
?>
<section class="landing">
  <div class="lhero">
    <div class="lbadge">🎮 Oyun takip &amp; sosyal platform</div>
    <h1>Oyun dünyanı <span class="grad">tek yerde</span> topla</h1>
    <p>Oynadığın oyunları ekle, saatlerini takip et, arkadaşlarınla yarış, rozet kazan ve yıl sonunda <b>Oyun Özetin</b>'i izle.</p>
    <div class="lact">
      <?php if (AUTO_LOGIN): ?><a class="btn big" href="library.php?demo=1">👀 Hemen dene (giriş gerekmez)</a><?php endif; ?>
      <a class="btn big" href="register.php">Ücretsiz Kayıt Ol</a>
      <a class="btn big ghost" href="login.php">Giriş Yap</a>
    </div>
    <div class="lstats">
      <div><b><?= $tu ?></b><span>oyuncu</span></div>
      <div><b><?= $tg ?></b><span>oyun kaydı</span></div>
      <div><b><?= e(fmt_hours($th)) ?></b><span>oynanan süre</span></div>
    </div>
  </div>

  <div class="lgrid">
    <div class="lcard"><span>📚</span><h3>Kütüphane</h3><p>Kapaklı kartlar, arama, filtre, favoriler ve özel koleksiyonlar.</p></div>
    <div class="lcard"><span>⏱️</span><h3>Süre takibi</h3><p>Oyun sayacı, hızlı saat girişi, haftalık hedef ve günlük seri.</p></div>
    <div class="lcard"><span>🤝</span><h3>Sosyal</h3><p>Arkadaş ekle, yorum yaz, akışı izle, liderlik tablosunda yarış.</p></div>
    <div class="lcard"><span>🏅</span><h3>Seviye &amp; rozet</h3><p>XP kazan, 15 rozeti topla, seviye atla.</p></div>
    <div class="lcard"><span>📅</span><h3>İstatistikler</h3><p>Oyun takvimi, halka grafikler ve aylık süre grafiği.</p></div>
    <div class="lcard"><span>🎨</span><h3>Kişiselleştir</h3><p>Kendi resmini arka plan yap, 4 tema arasından seç.</p></div>
  </div>
</section>
<?php include 'footer.php'; ?>
