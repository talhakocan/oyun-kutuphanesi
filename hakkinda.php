<?php
require 'config.php';
$tu = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$tg = (int)$pdo->query('SELECT COUNT(*) FROM games')->fetchColumn();
$title = 'Proje Hakkında';
include 'header.php';
?>
<h1>ℹ️ Proje Hakkında</h1>
<p class="muted">İnternet Programcılığı dersi proje ödevi — Steam benzeri sosyal oyun kütüphanesi.</p>

<div class="two" style="grid-template-columns:1fr 1fr">
  <section class="panel">
    <h2>Teknolojiler</h2>
    <div class="chips"><span>PHP 8</span><span>PDO</span><span>MySQL / MariaDB</span><span>HTML5</span><span>CSS3</span><span>JavaScript</span><span>SVG</span><span>XAMPP</span></div>
    <p class="muted small" style="margin-top:12px">Şu an sistemde <b><?= $tu ?></b> kullanıcı ve <b><?= $tg ?></b> oyun kaydı var. Tüm istatistikler canlı veritabanından hesaplanıyor.</p>
  </section>
  <section class="panel">
    <h2>Gezinti önerisi (2 dakika)</h2>
    <ol class="steps">
      <li><a href="library.php">Kütüphane</a>: kartlar, filtre, favori, haftalık hedef</li>
      <li>Bir oyuna girip <b>Oyun Sayacı</b>nı dene, yorumlara bak</li>
      <li><a href="feed.php">Akış</a> ve <a href="leaderboard.php">Sıralama</a></li>
      <li><a href="stats.php">İstatistik</a>: takvim ve grafikler</li>
      <li><a href="wrapped.php">Oyun Özetim</a> (Spotify Wrapped tarzı)</li>
      <li><a href="settings.php">Ayarlar</a>: kendi resmini arka plan yap</li>
    </ol>
  </section>
</div>

<section class="panel">
  <h2>Özellikler</h2>
  <ul class="feat">
    <li>Kayıt/giriş, şifre değiştirme, profil ve avatar yükleme</li>
    <li>Oyun CRUD, arama (canlı), filtre, sıralama, favoriler, CSV dışa aktarma</li>
    <li>Oyun sayacı, hızlı saat girişi, haftalık hedef, günlük seri, tamamlama yüzdesi</li>
    <li>Arkadaşlık sistemi, yorumlar, etkinlik akışı, liderlik tablosu</li>
    <li>XP/seviye, 15 rozet (ilerleme çubuklu), bildirimler</li>
    <li>Koleksiyonlar, zaman çizelgesi, Keşfet sayfası, “Ne oynasam?” çarkı</li>
    <li>Oyun takvimi (ısı haritası), halka ve çubuk grafikler, Oyun Özetim</li>
    <li>Arka plan resmi (yükle/adres/hazır), 4 tema, Steam’den kapak arama</li>
    <li>Yönetim paneli, otomatik kurulum ve otomatik veritabanı güncelleme</li>
  </ul>
</section>

<section class="panel">
  <h2>Veritabanı (9 tablo)</h2>
  <div class="tablewrap"><img src="er_diyagrami.png" alt="ER diyagramı" style="max-width:100%;border-radius:8px"></div>
</section>

<section class="panel">
  <h2>Güvenlik</h2>
  <ul class="feat">
    <li><b>SQL Injection:</b> tüm sorgular PDO hazırlanmış ifadelerle</li>
    <li><b>XSS:</b> çıktılar htmlspecialchars ile süzülür</li>
    <li><b>CSRF:</b> tüm POST formlarında oturum bağlı token</li>
    <li><b>Şifreler:</b> password_hash / password_verify (bcrypt)</li>
    <li><b>Dosya yükleme:</b> gerçek MIME kontrolü, boyut sınırı, rastgele ad, yeniden kodlama, uploads klasöründe kod çalıştırma engeli</li>
    <li><b>Yetki:</b> kullanıcı yalnızca kendi verisini değiştirir, yönetim paneli ayrı yetkiyle korunur</li>
  </ul>
  <p class="muted small">Demo modu (<code>AUTO_LOGIN</code>) yalnızca sunum içindir; gerçek yayında <code>config.php</code> içinde <b>false</b> yapılmalıdır.</p>
</section>
<?php include 'footer.php'; ?>
