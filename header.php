<?php
$title = $title ?? 'Oyun Kütüphanem';
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$toast = $_SESSION['toast'] ?? null; unset($_SESSION['toast']);
$page = basename($_SERVER['PHP_SELF']);
function nav_a($file, $label, $page) {
    return '<a href="' . $file . '" class="' . ($page === $file ? 'on' : '') . '">' . $label . '</a>';
}

// bekleyen arkadaşlık istekleri (menüde rozet)
$pendingReq = 0;
if (!empty($_SESSION['user_id'])) {
    try {
        $pq = $pdo->prepare('SELECT COUNT(*) FROM friendships WHERE addressee_id = ? AND status = "pending"');
        $pq->execute([$_SESSION['user_id']]);
        $pendingReq = (int)$pq->fetchColumn();
    } catch (Throwable $e) {}
}

// kişiselleştirilmiş arka plan (Ayarlar > Görünüm)
$bg = $_SESSION['bg'] ?? [];
$bgImg = (string)($bg['img'] ?? '');
$bgDim = max(0, min(90, (int)($bg['dim'] ?? 55)));
$bgBlur = max(0, min(20, (int)($bg['blur'] ?? 0)));
$bodyClass = ''; $layerStyle = '';
if ($bgImg !== '') {
    if (strpos($bgImg, 'preset:') === 0) {
        $pn = preg_replace('/[^a-z]/', '', substr($bgImg, 7));
        if ($pn !== '') $bodyClass = 'has-bg bgp-' . $pn;
    } else {
        $layerStyle = css_bg($bgImg);
        if ($layerStyle !== '') $bodyClass = 'has-bg';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0f1923">
<title><?= e($title) ?> | Oyun Kütüphanem</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E🎮%3C/text%3E%3C/svg%3E">
<script>try{document.documentElement.dataset.theme=localStorage.getItem('theme')||'steam';}catch(e){}</script>
<style>:root{--dim:<?= number_format($bgDim / 100, 2, '.', '') ?>;--blur:<?= $bgBlur ?>px}</style>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($bodyClass) ?>">
<div id="bglayer" style="<?= $layerStyle ?>"></div><div id="bgdim"></div>
<header class="topbar">
  <a class="logo" href="<?= empty($_SESSION['user_id']) ? 'index.php' : 'library.php' ?>">🎮 OYUN KÜTÜPHANEM</a>
  <nav>
  <?php if (!empty($_SESSION['user_id'])): ?>
    <?= nav_a('library.php', 'Kütüphane', $page) ?>
    <?= nav_a('feed.php', 'Akış', $page) ?>
    <?= nav_a('stats.php', 'İstatistik', $page) ?>
    <?= nav_a('friends.php', 'Arkadaşlar' . ($pendingReq ? ' <span class="nbadge">' . $pendingReq . '</span>' : ''), $page) ?>
    <?= nav_a('profile.php', 'Profilim', $page) ?>
    <details class="dd">
      <summary>Daha fazla ▾</summary>
      <div>
        <?= nav_a('collections.php', '🗂️ Koleksiyonlar', $page) ?>
        <?= nav_a('timeline.php', '🕒 Zaman Çizelgesi', $page) ?>
        <?= nav_a('explore.php', '🧭 Keşfet', $page) ?>
        <?= nav_a('leaderboard.php', '🏆 Sıralama', $page) ?>
        <?= nav_a('picker.php', '🎲 Ne Oynasam?', $page) ?>
        <?= nav_a('wrapped.php', '🎬 Oyun Özetim', $page) ?>
        <?= nav_a('settings.php', '⚙️ Ayarlar', $page) ?>
        <?= nav_a('hakkinda.php', 'ℹ️ Proje Hakkında', $page) ?>
        <?php if (!empty($_SESSION['is_admin'])) echo nav_a('admin.php', '🛡️ Yönetim', $page); ?>
      </div>
    </details>
    <a href="game_form.php" class="btn small">+ Oyun Ekle</a>
    <a href="logout.php">Çıkış</a>
  <?php else: ?>
    <a href="login.php">Giriş</a>
    <a href="register.php" class="btn small">Kayıt Ol</a>
  <?php endif; ?>
    <select id="themeSel" class="themesel" title="Tema">
      <option value="steam">🎮 Steam</option>
      <option value="mor">💜 Mor</option>
      <option value="yesil">💚 Yeşil</option>
      <option value="acik">☀️ Açık</option>
    </select>
  </nav>
</header>
<script>
(function(){var s=document.getElementById('themeSel');try{s.value=localStorage.getItem('theme')||'steam';}catch(e){}
s.onchange=function(){document.documentElement.dataset.theme=s.value;try{localStorage.setItem('theme',s.value);}catch(e){}};})();
</script>
<?php if (!empty($_SESSION['auto'])): ?>
<div class="ribbon" id="ribbon">👀 <b>Demo modundasın</b> — giriş gerekmiyor, örnek verilerle gezebilirsin. Kendi hesabınla denemek için <a href="logout.php">çıkış yapıp</a> kayıt olabilirsin.
  <button type="button" onclick="document.getElementById('ribbon').style.display='none'" title="Kapat">×</button></div>
<?php endif; ?>
<main class="wrap">
<?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>"><?= e($flash[0]) ?></div><?php endif; ?>
<?php if ($toast): ?><div class="toast"><?= e($toast) ?></div><?php endif; ?>
