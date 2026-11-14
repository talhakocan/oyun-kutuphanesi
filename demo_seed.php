<?php
// Örnek (demo) veri üretici. Hem otomatik demo modu hem de demo_veri.php tarafından kullanılır.

function demo_catalog() {
    return [
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
}

// [kullanıcı adı, biyografi, haftalık hedef, [[oyun, durum, toplam saat, puan], ...]]
function demo_people() {
    return [
        ['ali_demo', 'Gece oyuncusu. FPS ve RPG bağımlısı.', 14, [[0,'oynuyorum',240,9],[14,'oynuyorum',320,10],[3,'bitirdim',140,10],[4,'oynuyorum',85,9],[2,'bitirdim',64,8],[9,'bitirdim',12,9],[5,'istek',0,null]]],
        ['ayse_demo', 'Hikâye odaklı oyunları severim.', 8, [[3,'bitirdim',160,10],[6,'oynuyorum',95,9],[7,'bitirdim',38,9],[12,'oynuyorum',27,10],[13,'bitirdim',9,9],[10,'bitirdim',120,9],[1,'istek',0,null]]],
        ['mehmet_demo', 'Rekabetçi oyuncu, hedef: Global Elite.', 20, [[0,'oynuyorum',610,8],[1,'oynuyorum',420,7],[11,'oynuyorum',150,8],[14,'oynuyorum',260,10],[2,'biraktim',22,5]]],
        ['zeynep_demo', 'Rahat oyunlar, sakin akşamlar.', 6, [[6,'oynuyorum',210,10],[8,'oynuyorum',74,9],[9,'bitirdim',10,9],[13,'bitirdim',8,10],[7,'bitirdim',31,8]]],
    ];
}

function demo_me_games() {
    return [[14,'oynuyorum',320,10],[0,'oynuyorum',240,9],[3,'bitirdim',140,10],[4,'oynuyorum',85,9],[2,'bitirdim',64,8],[9,'bitirdim',12,9],[12,'oynuyorum',27,10],[5,'istek',0,null],[6,'istek',0,null],[7,'istek',0,null]];
}

function seed_games($pdo, $uid, $list, $catalog, &$gid, $pi, $isMain) {
    $first = true;
    foreach ($list as $gm) {
        [$ci, $status, $target, $rating] = $gm;
        $c = $catalog[$ci];
        $cover = "https://cdn.cloudflare.steamstatic.com/steam/apps/{$c[2]}/header.jpg";
        $prog = $status === 'bitirdim' ? 100 : ($status === 'istek' ? 0 : mt_rand(2, 17) * 5);
        $pdo->prepare('INSERT INTO games (user_id, title, platform, genre, cover_url, status, hours, rating, favorite, progress, created_at)
                       VALUES (?,?,?,?,?,?,?,?,?,?, DATE_SUB(NOW(), INTERVAL ? DAY))')
            ->execute([$uid, $c[0], 'PC', $c[1], $cover, $status, $target, $rating, ($rating === 10 ? 1 : 0), $prog, mt_rand(5, 90)]);
        $gameId = (int)$pdo->lastInsertId();
        $gid[$pi][$ci] = $gameId;

        if ($status === 'istek' || $target <= 0) continue;

        $n = $status === 'oynuyorum' ? mt_rand(6, 14) : ($status === 'bitirdim' ? mt_rand(0, 3) : 1);
        $offsets = [];
        for ($k = 0; $k < $n; $k++) $offsets[] = mt_rand(0, 44);
        if ($first && $status === 'oynuyorum') for ($k = 0; $k < 6; $k++) $offsets[] = $k; // son 6 gün seri
        $marathon = $isMain && $ci === 14;
        if ($marathon) $offsets[] = 3;

        $sum = 0; $latest = null;
        foreach ($offsets as $idx => $off) {
            $h = round(mt_rand(5, 45) / 10, 1);
            if ($marathon && $idx === count($offsets) - 1) $h = 5.5; // maraton oturumu
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

function seed_comment($pdo, $gid, $owner, $catalogIdx, $author, $text) {
    if (!isset($gid[$owner][$catalogIdx])) return;
    $pdo->prepare('INSERT INTO game_comments (game_id, user_id, body, created_at) VALUES (?,?,?, DATE_SUB(NOW(), INTERVAL ? HOUR))')
        ->execute([$gid[$owner][$catalogIdx], $author, $text, mt_rand(2, 120)]);
}

function seed_friend($pdo, $a, $b, $status = 'accepted') {
    if (relation($pdo, $a, $b) !== 'none') return;
    $pdo->prepare('INSERT INTO friendships (requester_id, addressee_id, status) VALUES (?,?,?)')->execute([$a, $b, $status]);
}

// $meId verilirse o kullanıcı demo kullanıcılarla arkadaş olur; $ownLibrary true ise ona örnek bir kütüphane de eklenir.
function seed_demo($pdo, $meId = null, $ownLibrary = false) {
    mt_srand(42);
    $catalog = demo_catalog();
    $people = demo_people();
    $log = [];
    $ids = []; $gid = [];

    $q = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $q->execute(['ali_demo']);
    $already = (bool)$q->fetch();

    if (!$already) {
        $hash = password_hash('demo1234', PASSWORD_DEFAULT);
        foreach ($people as $pi => $p) {
            $pdo->prepare('INSERT INTO users (username, email, password, bio, weekly_goal) VALUES (?,?,?,?,?)')
                ->execute([$p[0], $p[0] . '@example.com', $hash, $p[1], $p[2]]);
            $ids[$pi] = (int)$pdo->lastInsertId();
            seed_games($pdo, $ids[$pi], $p[3], $catalog, $gid, $pi, $pi === 0);
        }
        foreach ([[0,1],[0,2],[1,3],[2,3]] as $fp) seed_friend($pdo, $ids[$fp[0]], $ids[$fp[1]]);
        seed_comment($pdo, $gid, 0, 3, $ids[1], 'Witcher 3’ü bırakma, son görevler efsane!');
        seed_comment($pdo, $gid, 0, 14, $ids[2], '1.6 hâlâ en iyisi, bir maç atalım mı?');
        seed_comment($pdo, $gid, 2, 0, $ids[0], 'Global Elite için antrenman şart 😄');
        seed_comment($pdo, $gid, 1, 6, $ids[3], 'Stardew için tavsiyem: ilk yıl balıkçılığa odaklan.');
        seed_comment($pdo, $gid, 1, 12, $ids[0], 'Hollow Knight müzikleri harika, boss’lar zor ama değer.');
        $log[] = '4 demo kullanıcı, oyunları, oynama kayıtları, arkadaşlıkları ve yorumları eklendi.';
    } else {
        $r = $pdo->query('SELECT username, id FROM users WHERE username IN ("ali_demo","ayse_demo","mehmet_demo","zeynep_demo")')->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($people as $pi => $p) if (isset($r[$p[0]])) $ids[$pi] = (int)$r[$p[0]];
        $log[] = 'Demo kullanıcılar zaten yüklü.';
    }

    if ($meId) {
        $meId = (int)$meId;
        if ($ownLibrary) {
            seed_games($pdo, $meId, demo_me_games(), $catalog, $gid, 4, true);
            $pdo->prepare('INSERT INTO collections (user_id, name, description) VALUES (?,?,?)')->execute([$meId, 'Efsane RPG Oyunları', 'Bitirmeye değer hikâyeler']);
            $col = (int)$pdo->lastInsertId();
            foreach ([3, 4, 12] as $ci) {
                if (isset($gid[4][$ci])) $pdo->prepare('INSERT IGNORE INTO collection_games (collection_id, game_id) VALUES (?,?)')->execute([$col, $gid[4][$ci]]);
            }
            $pdo->prepare('INSERT INTO collections (user_id, name, description) VALUES (?,?,?)')->execute([$meId, 'Rekabetçi FPS', 'Her akşam bir maç']);
            $col2 = (int)$pdo->lastInsertId();
            foreach ([0, 14] as $ci) {
                if (isset($gid[4][$ci])) $pdo->prepare('INSERT IGNORE INTO collection_games (collection_id, game_id) VALUES (?,?)')->execute([$col2, $gid[4][$ci]]);
            }
        }
        foreach ([0, 1, 2] as $pi) if (isset($ids[$pi])) seed_friend($pdo, $meId, $ids[$pi]);
        if (isset($ids[3])) seed_friend($pdo, $ids[3], $meId, 'pending'); // gelen arkadaşlık isteği
        if (!$already && $ownLibrary) {
            seed_comment($pdo, $gid, 4, 3, $ids[1], 'Witcher 3 harika bir seçim!');
            seed_comment($pdo, $gid, 4, 14, $ids[0], 'CS 1.6 hâlâ klasik 🔥');
            seed_comment($pdo, $gid, 2, 0, $meId, 'Bu hafta rekabetçiye devam mı?');
        }
        $log[] = 'Hesap demo kullanıcılarla arkadaş yapıldı.';
    }
    return $log;
}
