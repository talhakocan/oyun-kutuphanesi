<?php
// Steam mağazasında oyun arar, kapak resmi adreslerini JSON olarak döndürür.
require 'config.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if ($q === '') { echo json_encode(['ok' => true, 'items' => []]); exit; }

$url = 'https://store.steampowered.com/api/storesearch/?term=' . urlencode($q) . '&l=turkish&cc=TR';
$body = null;
if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, // XAMPP'ta sertifika paketi olmayabiliyor (okul projesi için kolaylık)
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
} elseif (ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(['http' => ['timeout' => 8, 'header' => "User-Agent: Mozilla/5.0\r\n"], 'ssl' => ['verify_peer' => false]]);
    $body = @file_get_contents($url, false, $ctx);
}

$data = $body ? json_decode($body, true) : null;
$items = [];
foreach (($data['items'] ?? []) as $it) {
    if (count($items) >= 6) break;
    $id = (int)($it['id'] ?? 0);
    if (!$id) continue;
    $items[] = [
        'name'  => (string)($it['name'] ?? ''),
        'thumb' => (string)($it['tiny_image'] ?? ''),
        'cover' => "https://cdn.cloudflare.steamstatic.com/steam/apps/$id/header.jpg",
    ];
}
echo json_encode(['ok' => $data !== null, 'items' => $items], JSON_UNESCAPED_UNICODE);
