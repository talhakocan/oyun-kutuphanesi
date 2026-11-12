# 🎮 Oyun Kütüphanem

Steam benzeri, oynanan oyunları ve oynama sürelerini takip eden sosyal bir oyun kütüphanesi sitesi.
**İnternet Programcılığı** dersi proje ödevi — PHP (PDO), MySQL/MariaDB, HTML, CSS, JavaScript.

## Özellikler
- Kayıt / giriş, şifre değiştirme, profil sayfası (biyografi, avatar, haftalık hedef)
- Oyun ekleme, düzenleme, silme, arama, filtreleme, sıralama, favoriler, CSV dışa aktarma
- Süre takibi: hızlı “+ saat” girişi ve oyun sayacı (başlat / bitir)
- Arkadaşlık sistemi, oyunlara yorum, etkinlik akışı, liderlik tablosu, “arkadaşların da oynuyor”
- XP / seviye sistemi, 15 rozet, rozet ve seviye bildirimleri, günlük seri
- İstatistikler: oyun takvimi (ısı haritası), aylık grafik, en çok oynananlar
- **Oyun Özetim** (Spotify Wrapped tarzı), Keşfet sayfası, “Ne oynasam?” çarkı
- Steam’den kapak resmi arama, 4 tema, yönetim paneli

## Kurulum (XAMPP)
1. XAMPP’ten **Apache** ve **MySQL**’i başlat.
2. Bu klasörü `C:\xampp\htdocs\oyun_kutuphanesi` olarak kopyala.
3. `http://localhost/phpmyadmin` → **İçe Aktar** → `database.sql` dosyasını yükle.
   (Eski sürümü kurduysan bunun yerine `guncelleme.sql` dosyasını yükle.)
4. `http://localhost/oyun_kutuphanesi/` adresini aç ve **Kayıt Ol**.
   İlk kayıt olan kullanıcı otomatik yönetici olur.
5. (İsteğe bağlı) Örnek veri için `http://localhost/oyun_kutuphanesi/demo_veri.php` sayfasını aç.
   Demo kullanıcılar: `ali_demo`, `ayse_demo`, `mehmet_demo`, `zeynep_demo` — şifre: `demo1234`.

Veritabanı şifren varsa `config.php` içindeki `$DB_PASS` değerini değiştir.

## Güvenlik
PDO hazırlanmış sorgular (SQL injection), `htmlspecialchars` ile çıktı süzme (XSS), CSRF token,
`password_hash` ile şifre saklama, oturum yenileme ve yetki kontrolleri.

## Veritabanı
![ER diyagramı](er_diyagrami.png)

Daha fazla bilgi: [SUNUM_REHBERI.md](SUNUM_REHBERI.md)
