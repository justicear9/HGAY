<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');
require_once dirname(__DIR__) . '/config/database.php';

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';

try {
  $pdo = dbConnection();
  $params = [];
  $where = ['is_active = 1'];

  if ($category !== '' && preg_match('/^[a-z_]+$/', $category)) {
    $where[] = 'category = :cat';
    $params[':cat'] = $category;
  }

  if ($q !== '') {
    $where[] = '(question LIKE :like OR answer LIKE :like OR keywords LIKE :like)';
    $params[':like'] = '%' . $q . '%';
  }

  $sql = 'SELECT id, category, question, answer FROM fact_cards WHERE ' . implode(' AND ', $where) . ' ORDER BY category, sort_order, id LIMIT 50';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  $categories = require dirname(__DIR__) . '/config/fact_categories.php';
  foreach ($rows as &$r) {
    $r['category_label'] = $categories[$r['category']] ?? $r['category'];
  }

  echo json_encode(['results' => $rows, 'count' => count($rows)]);
} catch (Throwable $e) {
  error_log('fact_check_search: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['results' => [], 'count' => 0, 'error' => 'Search unavailable']);
}
