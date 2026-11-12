<?php
require 'config.php';
require_login();
$s = $pdo->prepare('SELECT title, platform, genre, status, hours, rating, favorite, last_played, note FROM games WHERE user_id = ? ORDER BY title');
$s->execute([$_SESSION['user_id']]);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="oyun_kutuphanem_' . date('Y-m-d') . '.csv"');
echo "\xEF\xBB\xBF"; // Excel'de Türkçe karakterler bozulmasın
$out = fopen('php://output', 'w');
fputcsv($out, ['Oyun', 'Platform', 'Tür', 'Durum', 'Saat', 'Puan', 'Favori', 'Son Oynanma', 'Not'], ';');
foreach ($s as $r) {
    fputcsv($out, [$r['title'], $r['platform'], $r['genre'], STATUSES[$r['status']] ?? $r['status'],
        str_replace('.', ',', $r['hours']), $r['rating'], $r['favorite'] ? 'Evet' : '', $r['last_played'], $r['note']], ';');
}
fclose($out);
