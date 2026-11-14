# Oyun Kütüphanem — Proje Sunum Rehberi

**Ders:** İnternet Programcılığı • **Teknoloji:** PHP 8 (PDO), MySQL/MariaDB, HTML, CSS, JavaScript • **Sunucu:** XAMPP

## 1. Proje nedir?
Steam benzeri, kullanıcıların oynadığı oyunları ve oynama sürelerini takip ettiği sosyal bir oyun kütüphanesi sitesidir.
Kullanıcılar oyun ekler, süre girer, arkadaş edinir, birbirinin oyunlarına yorum yapar, seviye atlar ve rozet kazanır.

## 2. Özellik listesi
| Grup | Özellikler |
|---|---|
| Hesap | Kayıt, giriş, çıkış, şifre değiştirme, profil düzenleme (biyografi, avatar, haftalık hedef) |
| Kütüphane | Oyun ekleme/düzenleme/silme, arama, filtre, sıralama, favoriler, CSV dışa aktarma |
| Süre takibi | “+ saat” ile hızlı giriş, oyun sayacı (başlat/bitir), oynama geçmişi |
| Sosyal | Arkadaşlık istekleri, profil sayfaları, oyuna yorum, “arkadaşların da oynuyor”, etkinlik akışı, liderlik tablosu |
| Oyunlaştırma | XP ve seviye, 15 rozet, rozet/seviye bildirimi, günlük seri, oyuncu tipi |
| Analiz | İstatistikler, oyun takvimi (ısı haritası), aylık grafik, **Oyun Özetim** (Spotify Wrapped tarzı) |
| Keşif | Keşfet sayfası (popüler oyunlar), “Ne oynasam?” çarkı, Steam’den kapak arama |
| Yönetim | Yönetici paneli (kullanıcı listesi, silme, genel istatistik) |
| Görünüm | Kendi resmini arka plan yapma (yükle/adres/hazır), karartma-bulanıklık, cam efektli arayüz, 4 tema, mobil uyumlu tasarım, açılış sayfası |
| Organizasyon | Özel koleksiyonlar, tamamlama yüzdesi, halka grafikler, rozet ilerlemesi |

## 3. Veritabanı (9 tablo)
`users` ─< `games` ─< `play_sessions` • `games` ─< `game_comments` • `users` ─< `friendships` (kendisiyle çoklu) • `users` ─< `user_badges`
Şema görseli: `er_diyagrami.png`. Tüm yabancı anahtarlar `ON DELETE CASCADE` ile tanımlıdır.

## 4. Güvenlik (hocanın sorabileceği noktalar)
- **SQL Injection:** Tüm sorgular PDO hazırlanmış ifadelerle (prepared statements) çalışır.
- **XSS:** Ekrana basılan her veri `htmlspecialchars` (e() fonksiyonu) ile süzülür.
- **CSRF:** Tüm POST formlarında oturuma bağlı rastgele token kontrol edilir.
- **Şifreler:** `password_hash` / `password_verify` (bcrypt) ile saklanır, düz metin tutulmaz.
- **Yetki:** Her sayfa `require_login()` ile korunur; kullanıcı yalnızca kendi oyununu düzenler/siler; yönetim paneli veritabanındaki `is_admin` alanını kontrol eder.
- **Dosya yükleme:** Gerçek MIME türü (finfo) + boyut + getimagesize kontrolü, rastgele dosya adı, GD ile yeniden kodlama; `uploads/` klasöründe PHP çalıştırma engellenir (.htaccess).
- **CSS enjeksiyonu:** Arka plan adresleri tırnak/parantez içermeyecek şekilde süzülür.
- **Demo modu:** `AUTO_LOGIN` yalnızca sunum içindir; gerçek yayında kapatılmalıdır.
- **Oturum:** Girişte `session_regenerate_id` ile oturum sabitleme saldırısına karşı önlem alınır.

## 5. 5 dakikalık demo akışı
1. Siteyi aç: otomatik kurulur ve demo hesapla girer (giriş gerekmez).
2. Giriş yap → **Kütüphane** (kartlar, filtre, favori, haftalık hedef).
3. Bir oyuna gir → **Oyun Sayacı** ile süre ekle → rozet bildirimi çıkabilir.
4. **Oyun ekle** sayfasında “Steam’de kapak bul” butonunu göster.
5. **Akış** ve **Sıralama** sayfaları (arkadaşların hareketleri, kürsü).
6. **İstatistik** → oyun takvimi ve aylık grafik.
6b. **Ayarlar → Görünüm:** kendi resmini arka plan yap (canlı önizleme).
7. **Daha fazla → Oyun Özetim** (animasyonlu özet kartları) — vurucu final.
8. Tema değiştir (sağ üst), **Yönetim** panelini göster.

## 6. Dosya yapısı
- `config.php` — veritabanı bağlantısı ve yardımcı fonksiyonlar • `gamify.php` — XP/seviye/rozet mantığı
- `header.php`, `footer.php` — ortak şablon • `assets/style.css` — tüm tasarım ve temalar
- Sayfalar: `library`, `game_form`, `game_view`, `profile`, `friends`, `feed`, `leaderboard`, `explore`, `picker`, `stats`, `wrapped`, `settings`, `admin`
- İşlem dosyaları: `game_hours`, `game_delete`, `game_fav`, `friend_action`, `comment_add`, `comment_delete`, `export`, `cover_search`
