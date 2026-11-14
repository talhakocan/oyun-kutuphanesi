<?php
// Seviye (XP) ve rozet hesapları. Hepsi veritabanındaki verilerden anlık hesaplanır.

function collect_stats($pdo, $uid) {
    $s = $pdo->prepare('SELECT COUNT(*) games, COALESCE(SUM(hours),0) hours, COALESCE(SUM(status="bitirdim"),0) finished,
        COUNT(DISTINCT NULLIF(genre,"")) genres, COALESCE(SUM(rating IS NOT NULL),0) rated
        FROM games WHERE user_id = ? AND status <> "istek"');
    $s->execute([$uid]);
    $st = $s->fetch();

    $one = function ($sql, $n = 1) use ($pdo, $uid) {
        $q = $pdo->prepare($sql);
        $q->execute(array_fill(0, $n, $uid));
        return (float)$q->fetchColumn();
    };
    $st['wish']       = $one('SELECT COUNT(*) FROM games WHERE user_id = ? AND status = "istek"');
    $st['friends']    = $one('SELECT COUNT(*) FROM friendships WHERE status = "accepted" AND (requester_id = ? OR addressee_id = ?)', 2);
    $st['comments']   = $one('SELECT COUNT(*) FROM game_comments WHERE user_id = ?');
    $st['maxsession'] = $one('SELECT COALESCE(MAX(hours),0) FROM play_sessions WHERE user_id = ?');
    $st['days']       = $one('SELECT COUNT(DISTINCT played_on) FROM play_sessions WHERE user_id = ?');
    foreach ($st as $k => $v) $st[$k] = (float)$v;
    return $st;
}

function calc_xp($st) {
    return (int)round($st['hours'] * 10 + $st['games'] * 20 + $st['finished'] * 100
        + $st['friends'] * 30 + $st['comments'] * 10 + $st['days'] * 15);
}

function level_info($xp) {
    $lvl = (int)floor(sqrt($xp / 50)) + 1;
    $cur = 50 * ($lvl - 1) * ($lvl - 1);
    $next = 50 * $lvl * $lvl;
    $pct = $next > $cur ? (int)round(($xp - $cur) / ($next - $cur) * 100) : 0;
    $titles = [1 => 'Çaylak', 3 => 'Oyuncu', 5 => 'Veteran', 8 => 'Usta', 12 => 'Efsane', 18 => 'Oyun Tanrısı'];
    $title = 'Çaylak';
    foreach ($titles as $min => $t) if ($lvl >= $min) $title = $t;
    return ['level' => $lvl, 'title' => $title, 'xp' => $xp, 'cur' => $cur, 'next' => $next, 'pct' => $pct];
}

function get_badges($st) {
    $b = function ($ic, $name, $desc, $cur, $max) {
        return ['ic' => $ic, 'name' => $name, 'desc' => $desc, 'cur' => min($cur, $max), 'max' => $max, 'ok' => $cur >= $max];
    };
    return [
        $b('🎮', 'İlk Adım',         'Kütüphanene ilk oyunu ekle',     $st['games'], 1),
        $b('📚', 'Koleksiyoncu',     '10 oyun ekle',                   $st['games'], 10),
        $b('🏛️', 'Müze',             '25 oyun ekle',                   $st['games'], 25),
        $b('⏱️', 'Isınma Turu',      'Toplam 10 saat oyna',            $st['hours'], 10),
        $b('🔥', '100 Saat Kulübü',  'Toplam 100 saat oyna',           $st['hours'], 100),
        $b('👑', '500 Saat Efsanesi','Toplam 500 saat oyna',           $st['hours'], 500),
        $b('🏁', 'Bitirici',         '3 oyun bitir',                   $st['finished'], 3),
        $b('🏆', 'Platin Usta',      '10 oyun bitir',                  $st['finished'], 10),
        $b('🏃', 'Maraton',          'Tek seferde 5+ saat oyna',       $st['maxsession'], 5),
        $b('📅', 'Düzenli',          '7 farklı günde oyun oyna',       $st['days'], 7),
        $b('🌈', 'Tür Gezgini',      '5 farklı türde oyun ekle',       $st['genres'], 5),
        $b('⭐', 'Eleştirmen',       '5 oyuna puan ver',               $st['rated'], 5),
        $b('🤝', 'Sosyal Kelebek',   '3 arkadaş edin',                 $st['friends'], 3),
        $b('💬', 'Sohbetçi',         '5 yorum yaz',                    $st['comments'], 5),
        $b('🎁', 'Hayalperest',      'İstek listesine 3 oyun ekle',    $st['wish'], 3),
    ];
}

// Yeni kazanılan rozetleri kaydeder ve ekranda bildirim (toast) gösterir.
function award_new_badges($pdo, $uid, $st) {
    try {
        $have = $pdo->prepare('SELECT badge_key FROM user_badges WHERE user_id = ?');
        $have->execute([$uid]);
        $have = $have->fetchAll(PDO::FETCH_COLUMN);
        $new = [];
        foreach (get_badges($st) as $b) {
            if (!$b['ok']) continue;
            $k = substr(md5($b['name']), 0, 10);
            if (!in_array($k, $have, true)) {
                $pdo->prepare('INSERT IGNORE INTO user_badges (user_id, badge_key) VALUES (?, ?)')->execute([$uid, $k]);
                $new[] = $b['ic'] . ' ' . $b['name'];
            }
        }
        if ($new) {
            $msg = '🏅 Yeni rozet: ' . implode(', ', array_slice($new, 0, 3));
            if (count($new) > 3) $msg .= ' ve ' . (count($new) - 3) . ' tane daha';
            $_SESSION['toast'] = $msg . '!';
        }
    } catch (PDOException $e) { /* user_badges tablosu yoksa sessizce geç */ }
}
