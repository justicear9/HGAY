<?php
/**
 * Public gallery JSON.
 */
header('Content-Type: application/json');
header('Cache-Control: public, max-age=120');
require_once dirname(__DIR__) . '/config/database.php';

$imagesOnly = isset($_GET['images_only']) && $_GET['images_only'] !== '0';
$limit = isset($_GET['limit']) ? max(0, (int) $_GET['limit']) : 0;

try {
  $pdo = dbConnection();
  $sql = "
    SELECT id, media_type, file_path, alt_text, caption, poster_path, layout
    FROM gallery_items
    WHERE is_active = 1
  ";
  if ($imagesOnly) {
    $sql .= " AND media_type = 'image'";
  }
  $sql .= ' ORDER BY sort_order ASC, id ASC';
  if ($limit > 0) {
    $sql .= ' LIMIT ' . $limit;
  }
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  $items = [];
  foreach ($rows as $r) {
    $items[] = [
      'id' => (int) $r['id'],
      'media_type' => $r['media_type'],
      'file_path' => $r['file_path'],
      'alt_text' => $r['alt_text'],
      'caption' => $r['caption'] ?? '',
      'poster_path' => $r['poster_path'] ?? '',
      'layout' => $r['layout'],
    ];
  }
  echo json_encode(['items' => $items, 'count' => count($items)]);
} catch (Throwable $e) {
  error_log('gallery_list: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['items' => [], 'count' => 0, 'error' => true]);
}
