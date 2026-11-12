<?php
$title = $title ?? 'Oyun Kütüphanem';
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$toast = $_SESSION['toast'] ?? null; unset($_SESSION['toast']);
$page = basename($_SERVER['PHP_SELF']);
function nav_a($file, $label, $page) {
    return '<a href="' . $file . '" class="' . ($page === $file ? 'on' : '') . '">' . $label . '</a>';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | Oyun Kütüphanem</title>
<script>try{document.documentElement.dataset.theme=localStorage.getItem('theme')||'steam';}catch(e){}</script>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <a class="logo" href="library.php">🎮 OYUN KÜTÜPHANEM</a>
  <nav>
  <?php if (!empty($_SESSION['user_id'])): ?>
    <?= nav_a('library.php', 'Kütüphane', $page) ?>
    <?= nav_a('feed.php', 'Akış', $page) ?>
    <?= nav_a('stats.php', 'İstatistik', $page) ?>
    <?= nav_a('friends.php', 'Arkadaşlar', $page) ?>
    <?= nav_a('profile.php', 'Profilim', $page) ?>
    <details class="dd">
      <summary>Daha fazla ▾</summary>
      <div>
        <?= nav_a('explore.php', '🧭 Keşfet', $page) ?>
        <?= nav_a('leaderboard.php', '🏆 Sıralama', $page) ?>
        <?= nav_a('picker.php', '🎲 Ne Oynasam?', $page) ?>
        <?= nav_a('wrapped.php', '🎬 Oyun Özetim', $page) ?>
        <?= nav_a('settings.php', '⚙️ Ayarlar', $page) ?>
        <?php if (!empty($_SESSION['is_admin'])) echo nav_a('admin.php', '🛡️ Yönetim', $page); ?>
      </div>
    </details>
    <a href="game_form.php" class="btn small">+ Oyun Ekle</a>
    <a href="logout.php">Çıkış</a>
  <?php else: ?>
    <a href="login.php">Giriş</a>
    <a href="register.php">Kayıt Ol</a>
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
<main class="wrap">
<?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>"><?= e($flash[0]) ?></div><?php endif; ?>
<?php if ($toast): ?><div class="toast"><?= e($toast) ?></div><?php endif; ?>
