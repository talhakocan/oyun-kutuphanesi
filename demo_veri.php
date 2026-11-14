<?php
// Gerekirse demo kullanıcıları elle yüklemek / hesabını onlarla arkadaş yapmak için.
require 'config.php';
require_once 'demo_seed.php';
$log = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $log = seed_demo($pdo, $_SESSION['user_id'] ?? null, false);
}
$title = 'Demo Veri';
include 'header.php';
?>
<div class="formbox">
  <h1>🧪 Demo Veri</h1>
  <p class="muted">Örnek kullanıcıları (arkadaşlar, yorumlar, oynama kayıtları) yükler ve hesabını onlarla arkadaş yapar.
     Demo modu açıksa bu zaten otomatik yapılır.</p>
  <?php foreach ($log as $l): ?><div class="flash"><?= e($l) ?></div><?php endforeach; ?>
  <p>Demo kullanıcılar: <b>ali_demo, ayse_demo, mehmet_demo, zeynep_demo</b> — şifre: <b>demo1234</b></p>
  <form method="post"><?= csrf_field() ?><button class="btn">Demo kullanıcıları yükle / arkadaş yap</button></form>
</div>
<?php include 'footer.php'; ?>
