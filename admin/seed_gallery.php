<?php
require_once 'auth.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/lib/paths.php';

$pdo = dbConnection();
$seed = require dirname(__DIR__) . '/config/gallery_seed.php';
$inserted = 0;

try {
  $count = (int) $pdo->query('SELECT COUNT(*) FROM gallery_items')->fetchColumn();
  if ($count > 0 && empty($_GET['force'])) {
    header('Location: ' . admin_url('gallery') . '?msg=already');
    exit;
  }
  if (!empty($_GET['force'])) {
    $pdo->exec('DELETE FROM gallery_items');
  }
  $stmt = $pdo->prepare('
    INSERT INTO gallery_items (media_type, file_path, alt_text, caption, poster_path, layout, sort_order, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
  ');
  foreach ($seed as $item) {
    $stmt->execute([
      $item['media_type'],
      $item['file_path'],
      $item['alt_text'],
      $item['caption'],
      $item['poster_path'],
      $item['layout'],
      $item['sort_order'],
    ]);
    $inserted++;
  }
  header('Location: ' . admin_url('gallery') . '?msg=seeded&n=' . $inserted);
  exit;
} catch (PDOException $e) {
  header('Location: ' . admin_url('gallery') . '?err=' . urlencode('Run schema-update.sql first. ' . $e->getMessage()));
  exit;
}
