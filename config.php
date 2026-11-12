<?php
session_start();
date_default_timezone_set('Europe/Istanbul');

$DB_HOST = 'localhost';
$DB_NAME = 'oyun_kutuphanesi';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) {
    die('Veritabanı bağlantı hatası: ' . htmlspecialchars($ex->getMessage()) . '<br>database.sql dosyasını içe aktardın mı?');
}

const STATUSES = [
    'oynuyorum' => 'Oynuyorum',
    'bitirdim'  => 'Bitirdim',
    'biraktim'  => 'Bıraktım',
    'istek'     => 'İstek Listesi',
];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function require_login() {
    if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
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
