<?php
// Sunum için örnek (demo) kullanıcı, oyun, süre, arkadaşlık ve yorum verisi yükler.
// Giriş yapmış durumdayken çalıştırırsan, demo kullanıcılar seninle de arkadaş olur.
require 'config.php';

$catalog = [
    0  => ['Counter-Strike 2', 'FPS', 730],
    1  => ['Dota 2', 'MOBA', 570],
    2  => ['Grand Theft Auto V', 'Aksiyon', 271590],
    3  => ['The Witcher 3', 'RPG', 292030],
    4  => ['Elden Ring', 'RPG', 1245620],
    5  => ['Cyberpunk 2077', 'RPG', 1091500],
    6  => ['Stardew Valley', 'Simülasyon', 413150],
    7  => ['Hades', 'Roguelike', 1145360],
    8  => ['Terraria', 'Macera', 105600],
    9  => ['Portal 2', 'Bulmaca', 620],
    10 => ['Skyrim', 'RPG', 489830],
    11 => ['Rocket League', 'Spor', 252950],
    12 => ['Hollow Knight', 'Metroidvania', 367520],
    13 => ['Celeste', 'Platform', 504230],
    14 => ['Counter-Strike 1.6', 'FPS', 10],
];
// [kullanıcı adı, biyografi, haftalık hedef, [[oyun, durum, toplam saat, puan], ...]]
$people = [
    ['ali_demo', 'Gece oyuncusu. FPS ve RPG bağımlısı.', 14, [[0,'oynuyorum',240,9],[14,'oynuyorum',320,10],[3,'bitirdim',140,10],[4,'oynuyorum',85,9],[2,'bitirdim',64,8],[9,'bitirdim',12,9],[5,'istek',0,null]]],
    ['ayse_demo', 'Hikâye odaklı oyunları severim.', 8, [[3,'bitirdim',160,10],[6,'oynuyorum',95,9],[7,'bitirdim',38,9],[12,'oynuyorum',27,10],[13,'bitirdim',9,9],[10,'bitirdim',120,9],[1,'istek',0,null]]],
    ['mehmet_demo', 'Rekabetçi oyuncu, hedef: Global Elite.', 20, [[0,'oynuyorum',610,8],[1,'oynuyorum',420,7],[11,'oynuyorum',150,8],[14,'oynuyorum',260,10],[2,'biraktim',22,5]]],
    ['zeynep_demo', 'Rahat oyunlar, sakin akşamlar.', 6, [[6,'oynuyorum',210,10],[8,'oynuyorum',74,9],[9,'bitirdim',10,9],[13,'bitirdim',8,10],[7,'bitirdim',31,8]]],
];
$friendPairs = [[0,1,'accepted'],[0,2,'accepted'],[1,3,'accepted'],[2,3,'accepted'],[3,0,'pending']];
$comments = [
    [1, 0, 3,  'Witcher 3’ü bırakma, son görevler efsane!'],
    [2, 0, 14, '1.6 hâlâ en iyisi, bir maç atalım mı?'],
    [0, 2, 0,  'Global Elite için antrenman şart 😄'],
    [3, 1, 6,  'Stardew için tavsiyem: ilk yıl balıkçılığa odaklan.'],
    [0, 1, 12, 'Hollow Knight müzikleri harika, boss’lar zor ama değer.'],
];

$log = [];
$exists = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$exists->execute(['ali_demo']);
$already = (bool)$exists->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    mt_srand(42);
    $ids = [];
    $gid = [];

    if (!$already) {
        $hash = password_hash('demo1234', PASSWORD_DEFAULT);
        foreach ($people as $pi => $p) {
            $pdo->prepare('INSERT INTO users (username, email, password, bio, weekly_goal) VALUES (?,?,?,?,?)')
                ->execute([$p[0], $p[0] . '@example.com', $hash, $p[1], $p[2]]);
            $uid = (int)$pdo->lastInsertId();
            $ids[$pi] = $uid;

            $first = true;
            foreach ($p[3] as $gm) {
                [$ci, $status, $target, $rating] = $gm;
                $c = $catalog[$ci];
                $cover = "https://cdn.cloudflare.steamstatic.com/steam/apps/{$c[2]}/header.jpg";
                $pdo->prepare('INSERT INTO games (user_id, title, platform, genre, cover_url, status, hours, rating, favorite, created_at)
                               VALUES (?,?,?,?,?,?,?,?,?, DATE_SUB(NOW(), INTERVAL ? DAY))')
                    ->execute([$uid, $c[0], 'PC', $c[1], $cover, $status, $target, $rating, ($rating === 10 ? 1 : 0), mt_rand(5, 90)]);
                $gameId = (int)$pdo->lastInsertId();
                $gid[$pi][$ci] = $gameId;

                if ($status === 'istek' || $target <= 0) continue;

                $n = $status === 'oynuyorum' ? mt_rand(6, 14) : ($status === 'bitirdim' ? mt_rand(0, 3) : 1);
                $offsets = [];
                for ($k = 0; $k < $n; $k++) $offsets[] = mt_rand(0, 44);
                if ($first && $status === 'oynuyorum') for ($k = 0; $k < 6; $k++) $offsets[] = $k; // son 6 gün seri
                if ($pi === 0 && $ci === 14) { $offsets[] = 3; }

                $sum = 0; $latest = null;
                foreach ($offsets as $idx => $off) {
                    $h = round(mt_rand(5, 45) / 10, 1);
                    if ($pi === 0 && $ci === 14 && $idx === count($offsets) - 1) $h = 5.5; // ali için maraton
                    $day = date('Y-m-d', strtotime("-$off day"));
                    $stamp = $day . ' ' . sprintf('%02d:%02d:00', mt_rand(14, 23), mt_rand(0, 59));
                    $pdo->prepare('INSERT INTO play_sessions (game_id, user_id, hours, played_on, created_at) VALUES (?,?,?,?,?)')
                        ->execute([$gameId, $uid, $h, $day, $stamp]);
                    $sum += $h;
                    if ($latest === null || $day > $latest) $latest = $day;
                }
                if ($latest !== null) {
                    $pdo->prepare('UPDATE games SET hours = GREATEST(hours, ?), last_played = ? WHERE id = ?')->execute([$sum, $latest, $gameId]);
                }
                $first = false;
            }
        }
        foreach ($friendPairs as $fp) {
            $pdo->prepare('INSERT INTO friendships (requester_id, addressee_id, status) VALUES (?,?,?)')->execute([$ids[$fp[0]], $ids[$fp[1]], $fp[2]]);
        }
        foreach ($comments as $c) {
            $pdo->prepare('INSERT INTO game_comments (game_id, user_id, body, created_at) VALUES (?,?,?, DATE_SUB(NOW(), INTERVAL ? HOUR))')
                ->execute([$gid[$c[1]][$c[2]], $ids[$c[0]], $c[3], mt_rand(2, 120)]);
        }
        $log[] = '4 demo kullanıcı, oyunları, oynama kayıtları, arkadaşlıkları ve yorumları eklendi.';
    } else {
        $log[] = 'Demo kullanıcılar zaten yüklü.';
    }

    // giriş yapmış kullanıcıyı demo kullanıcılarla arkadaş yap
    if (!empty($_SESSION['user_id'])) {
        $me = (int)$_SESSION['user_id'];
        $names = array_column($people, 0);
        $in = implode(',', array_fill(0, count($names), '?'));
        $q = $pdo->prepare("SELECT id FROM users WHERE username IN ($in)");
        $q->execute($names);
        $linked = 0;
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $did) {
            if ($did != $me && relation($pdo, $me, $did) === 'none') {
                $pdo->prepare('INSERT INTO friendships (requester_id, addressee_id, status) VALUES (?,?, "accepted")')->execute([$me, $did]);
                $linked++;
            }
        }
        if ($linked) $log[] = "Hesabın $linked demo kullanıcıyla arkadaş yapıldı.";
    } else {
        $log[] = 'Giriş yapmadığın için arkadaşlık bağlanmadı. Giriş yapıp bu sayfayı tekrar çalıştırırsan demo kullanıcılar seninle de arkadaş olur.';
    }
    $already = true;
}

$title = 'Demo Veri';
include 'header.php';
?>
<div class="formbox">
  <h1>🧪 Demo Veri Yükle</h1>
  <p class="muted">Sunum öncesi site boş görünmesin diye örnek kullanıcılar, oyunlar, süreler, arkadaşlıklar ve yorumlar ekler.</p>
  <?php foreach ($log as $l): ?><div class="flash"><?= e($l) ?></div><?php endforeach; ?>
  <p>Demo kullanıcılar: <b>ali_demo, ayse_demo, mehmet_demo, zeynep_demo</b><br>
     Hepsinin şifresi: <b>demo1234</b></p>
  <form method="post">
    <?= csrf_field() ?>
    <button class="btn"><?= $already ? 'Hesabımı demo kullanıcılarla arkadaş yap' : 'Demo verilerini yükle' ?></button>
  </form>
  <p class="muted small" style="margin-top:16px">Demo kullanıcıları silmek için yönetim panelini (ilk kayıtlı hesap) kullan.
  Ödevi teslim etmeden önce bu dosyayı (demo_veri.php) silmen önerilir.</p>
</div>
<?php include 'footer.php'; ?>
