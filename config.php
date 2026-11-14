<?php
session_start();
date_default_timezone_set('Europe/Istanbul');

// ======================= AYARLAR =======================
// AUTO_LOGIN = true  → giriş/kayıt gerekmeden doğrudan demo hesapla açılır (ödev ve sunum için).
// Siteyi gerçekten internette yayınlarsan bunu MUTLAKA false yap.
const AUTO_LOGIN = true;
const AUTO_USER  = 'oyuncu';

$DB_HOST = 'localhost';
$DB_NAME = 'oyun_kutuphanesi';
$DB_USER = 'root';
$DB_PASS = '';

function db_connect($host, $name, $user, $pass) {
    return new PDO('mysql:host=' . $host . ($name ? ';dbname=' . $name : '') . ';charset=utf8mb4', $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

// .sql dosyasındaki ifadeleri tek tek çalıştırır (var olan tablolar etkilenmez)
function run_sql_file($pdo, $file) {
    $sql = @file_get_contents($file);
    if ($sql === false) return;
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (explode(';', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') continue;
        try { $pdo->exec($stmt); } catch (PDOException $e) { /* zaten var olabilir */ }
    }
}

try {
    $pdo = db_connect($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);
} catch (PDOException $ex) {
    if (strpos($ex->getMessage(), '1049') !== false) {
        // Veritabanı yok → otomatik kurulum (phpMyAdmin'e gerek yok)
        try {
            $root = db_connect($DB_HOST, '', $DB_USER, $DB_PASS);
            run_sql_file($root, __DIR__ . '/database.sql');
            $pdo = db_connect($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);
        } catch (PDOException $ex2) {
            die('Otomatik kurulum yapılamadı: ' . htmlspecialchars($ex2->getMessage()));
        }
    } else {
        die('Veritabanına bağlanılamadı: ' . htmlspecialchars($ex->getMessage()) . '<br>XAMPP Control Panel\'de <b>MySQL</b>\'in çalıştığından (yeşil) emin ol.');
    }
}

// Eski sürümden kalan veritabanını otomatik günceller (eksik tablo/sütunları ekler)
function ensure_schema($pdo) {
    if (!empty($_SESSION['schema_ok'])) return;
    run_sql_file($pdo, __DIR__ . '/database.sql');
    $cols = [
        'users' => [
            'bio' => 'VARCHAR(200) DEFAULT NULL', 'avatar_url' => 'VARCHAR(500) DEFAULT NULL',
            'weekly_goal' => 'DECIMAL(5,1) DEFAULT NULL', 'is_admin' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'bg_image' => 'VARCHAR(255) DEFAULT NULL', 'bg_dim' => 'TINYINT NOT NULL DEFAULT 55', 'bg_blur' => 'TINYINT NOT NULL DEFAULT 0',
        ],
        'games' => ['favorite' => 'TINYINT(1) NOT NULL DEFAULT 0', 'progress' => 'TINYINT NOT NULL DEFAULT 0'],
        'play_sessions' => ['created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],
    ];
    foreach ($cols as $t => $list) {
        foreach ($list as $c => $def) {
            try {
                if (!$pdo->query("SHOW COLUMNS FROM `$t` LIKE '$c'")->fetch()) $pdo->exec("ALTER TABLE `$t` ADD COLUMN `$c` $def");
            } catch (PDOException $e) {}
        }
    }
    $_SESSION['schema_ok'] = 1;
}
ensure_schema($pdo);

const STATUSES = [
    'oynuyorum' => 'Oynuyorum',
    'bitirdim'  => 'Bitirdim',
    'biraktim'  => 'Bıraktım',
    'istek'     => 'İstek Listesi',
];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function require_login() {
    global $pdo;
    if (empty($_SESSION['user_id']) && !auto_login($pdo)) { header('Location: login.php'); exit; }
}

// Demo modu: giriş yapılmamışsa otomatik olarak hazır demo hesapla oturum açar.
function auto_login($pdo) {
    if (!AUTO_LOGIN) return false;
    if (isset($_GET['demo'])) unset($_SESSION['no_auto']);
    if (isset($_SESSION['no_auto'])) return false;

    $s = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $s->execute([AUTO_USER]);
    $u = $s->fetch();
    if (!$u) {
        $pdo->prepare('INSERT INTO users (username, email, password, bio, weekly_goal, is_admin) VALUES (?,?,?,?,?,1)')
            ->execute([AUTO_USER, 'oyuncu@example.com', password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT),
                       'Merhaba! Ben sitenin demo oyuncusuyum. 🎮', 12]);
        $id = (int)$pdo->lastInsertId();
        try { require_once __DIR__ . '/demo_seed.php'; seed_demo($pdo, $id, true); } catch (Throwable $e) { /* demo verisi eklenemese de site açılsın */ }
        $s->execute([AUTO_USER]);
        $u = $s->fetch();
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $u['id'];
    $_SESSION['username'] = $u['username'];
    $_SESSION['is_admin'] = (int)$u['is_admin'];
    $_SESSION['bg'] = ['img' => $u['bg_image'] ?? '', 'dim' => (int)($u['bg_dim'] ?? 55), 'blur' => (int)($u['bg_blur'] ?? 0)];
    $_SESSION['auto'] = 1;
    return true;
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check() {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) die('Geçersiz istek.');
}

function flash($msg, $type = 'ok') { $_SESSION['flash'] = [$msg, $type]; }

function fmt_hours($h) {
    $h = (float)$h;
    return rtrim(rtrim(number_format($h, 1, ',', '.'), '0'), ',') . ' sa';
}

function cover_html($g) {
    $hue = crc32($g['title']) % 360;
    $letter = mb_strtoupper(mb_substr($g['title'], 0, 1));
    $img = $g['cover_url'] ? '<img src="' . e($g['cover_url']) . '" alt="" loading="lazy" onerror="this.style.display=\'none\'">' : '';
    return '<div class="cover" style="--h:' . $hue . '"><span>' . e($letter) . '</span>' . $img . '</div>';
}

function relation($pdo, $me, $other) {
    if ((int)$me === (int)$other) return 'self';
    $s = $pdo->prepare('SELECT requester_id, status FROM friendships WHERE (requester_id=? AND addressee_id=?) OR (requester_id=? AND addressee_id=?)');
    $s->execute([$me, $other, $other, $me]);
    $r = $s->fetch();
    if (!$r) return 'none';
    if ($r['status'] === 'accepted') return 'friends';
    return ((int)$r['requester_id'] === (int)$me) ? 'sent' : 'received';
}

function avatar_html($u, $size = 64) {
    $hue = crc32($u['username']) % 360;
    $l = mb_strtoupper(mb_substr($u['username'], 0, 1));
    $img = !empty($u['avatar_url']) ? '<img src="' . e($u['avatar_url']) . '" alt="" onerror="this.style.display=\'none\'">' : '';
    return '<div class="avatar" style="--h:' . $hue . ';width:' . $size . 'px;height:' . $size . 'px;font-size:' . round($size / 2) . 'px"><span>' . e($l) . '</span>' . $img . '</div>';
}

function friend_form($action, $target, $label, $class = 'btn small', $confirm = '') {
    $on = $confirm ? ' onsubmit="return confirm(\'' . e($confirm) . '\')"' : '';
    return '<form method="post" action="friend_action.php" class="inl"' . $on . '>' . csrf_field()
         . '<input type="hidden" name="action" value="' . e($action) . '">'
         . '<input type="hidden" name="target" value="' . (int)$target . '">'
         . '<button class="' . e($class) . '">' . e($label) . '</button></form>';
}

function friend_ids($pdo, $uid) {
    $s = $pdo->prepare('SELECT addressee_id FROM friendships WHERE requester_id = ? AND status = "accepted" UNION SELECT requester_id FROM friendships WHERE addressee_id = ? AND status = "accepted"');
    $s->execute([$uid, $uid]);
    return array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
}

function time_ago($ts) {
    $d = time() - strtotime($ts);
    if ($d < 60) return 'az önce';
    if ($d < 3600) return floor($d / 60) . ' dk önce';
    if ($d < 86400) return floor($d / 3600) . ' sa önce';
    if ($d < 86400 * 7) return floor($d / 86400) . ' gün önce';
    return date('d.m.Y', strtotime($ts));
}

// ---------- Dosya yükleme (arka plan, avatar, kapak) ----------
const UPLOAD_DIR = __DIR__ . '/uploads';

function ensure_upload_dir() {
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
    $ht = UPLOAD_DIR . '/.htaccess';
    if (!file_exists($ht)) {
        // yüklenen klasörde php vb. kodların çalışmasını engelle
        @file_put_contents($ht, "<FilesMatch \"\\.(php|phtml|phar|php[0-9])$\">\n  Require all denied\n</FilesMatch>\nOptions -Indexes\n");
    }
}

// Güvenli resim yükleme: gerçek MIME (finfo), boyut, getimagesize, rastgele ad, GD ile yeniden kodlama.
// Döner: [yol|null, hata|null]
function save_image_upload($field, $prefix, $maxBytes = 6291456) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) return [null, 'Dosya yüklenemedi (hata kodu ' . (int)$f['error'] . '). Dosya çok büyük olabilir.'];
    if ($f['size'] < 1 || $f['size'] > $maxBytes) return [null, 'Dosya boyutu en fazla ' . round($maxBytes / 1048576) . ' MB olabilir.'];
    if (!is_uploaded_file($f['tmp_name'])) return [null, 'Geçersiz yükleme.'];

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($map[$mime])) return [null, 'Sadece JPG, PNG veya WEBP resimler yüklenebilir.'];
    $info = @getimagesize($f['tmp_name']);
    if (!$info || $info[0] < 16 || $info[1] < 16 || $info[0] * $info[1] > 40000000) return [null, 'Geçerli bir resim değil ya da çözünürlüğü çok yüksek.'];

    ensure_upload_dir();
    $name = preg_replace('/[^a-z0-9]/i', '', $prefix) . '_' . bin2hex(random_bytes(8)) . '.' . $map[$mime];
    $dest = UPLOAD_DIR . '/' . $name;

    $done = false;
    if (function_exists('imagecreatefromjpeg')) {
        $img = null;
        if ($mime === 'image/jpeg') $img = @imagecreatefromjpeg($f['tmp_name']);
        elseif ($mime === 'image/png') $img = @imagecreatefrompng($f['tmp_name']);
        elseif (function_exists('imagecreatefromwebp')) $img = @imagecreatefromwebp($f['tmp_name']);
        if ($img) {
            $w = imagesx($img); $h = imagesy($img); $max = 1920;
            if ($w > $max) {
                $nh = (int)round($h * $max / $w);
                $res = imagecreatetruecolor($max, $nh);
                if ($mime !== 'image/jpeg') { imagealphablending($res, false); imagesavealpha($res, true); }
                imagecopyresampled($res, $img, 0, 0, 0, 0, $max, $nh, $w, $h);
                imagedestroy($img); $img = $res;
            }
            if ($mime === 'image/jpeg') $done = imagejpeg($img, $dest, 88);
            elseif ($mime === 'image/png') { imagesavealpha($img, true); $done = imagepng($img, $dest, 6); }
            elseif (function_exists('imagewebp')) $done = imagewebp($img, $dest, 88);
            imagedestroy($img);
        }
    }
    if (!$done && !move_uploaded_file($f['tmp_name'], $dest)) return [null, 'Dosya kaydedilemedi (uploads klasörü yazılabilir mi?).'];
    return ['uploads/' . $name, null];
}

function delete_upload($path) {
    if (is_string($path) && preg_match('#^uploads/[A-Za-z0-9_.]+$#', $path)) {
        $full = UPLOAD_DIR . '/' . basename($path);
        if (is_file($full)) @unlink($full);
    }
}

// CSS'e güvenle gömülebilen arka plan adresi (tırnak, parantez, boşluk vb. içeren adresleri reddeder)
function css_bg($u) {
    $u = trim((string)$u);
    if ($u === '' || !preg_match('#^(https?://|uploads/)[^\s\'"()\\\\<>]+$#i', $u)) return '';
    return "background-image:url('" . e($u) . "')";
}
