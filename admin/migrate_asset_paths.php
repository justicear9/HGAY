<?php
/**
 * One-time: update gallery_items paths after HGAY_ASSETS / Card_Pictures_and_Video rename.
 * Run while logged in as admin, then delete or restrict this file.
 */
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/lib/gallery.php';

$pdo = dbConnection();
$stmt = $pdo->query('SELECT id, file_path, poster_path FROM gallery_items');
$updated = 0;

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $file = gallery_normalize_asset_path((string) $row['file_path']);
    $poster = (string) ($row['poster_path'] ?? '');
    $posterNorm = $poster !== '' ? gallery_normalize_asset_path($poster) : '';

    if ($file === $row['file_path'] && $posterNorm === $poster) {
        continue;
    }

    $upd = $pdo->prepare('UPDATE gallery_items SET file_path = ?, poster_path = ? WHERE id = ?');
    $upd->execute([
        $file,
        $posterNorm !== '' ? $posterNorm : null,
        (int) $row['id'],
    ]);
    $updated++;
}

header('Content-Type: text/plain; charset=UTF-8');
echo "Updated {$updated} gallery row(s).\n";
echo "Done. Reload the site and remove admin/migrate_asset_paths.php when finished.\n";
