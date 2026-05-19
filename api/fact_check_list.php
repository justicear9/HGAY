<?php
/**
 * Fact Check Q&A + card reference glossary (admin-managed).
 */
header('Content-Type: application/json');
header('Cache-Control: public, max-age=60');
require_once dirname(__DIR__) . '/config/database.php';

const CARD_REFERENCE_CATEGORY = 'card_references';

try {
  $pdo = dbConnection();
  $stmt = $pdo->query("
    SELECT id, category, question, answer, keywords
    FROM fact_cards
    WHERE is_active = 1
    ORDER BY category, sort_order, id
  ");
  $rows = $stmt->fetchAll();
  $categories = require dirname(__DIR__) . '/config/fact_categories.php';

  $facts = [];
  $references = [];

  foreach ($rows as $r) {
    if ($r['category'] === CARD_REFERENCE_CATEGORY) {
      $references[] = [
        'id' => $r['id'],
        'term' => $r['question'],
        'definition' => $r['answer'],
        'keywords' => $r['keywords'] ?? '',
      ];
    } else {
      $r['category_label'] = $categories[$r['category']] ?? $r['category'];
      $facts[] = $r;
    }
  }

  echo json_encode([
    'facts' => $facts,
    'references' => $references,
    'count' => count($facts),
  ]);
} catch (Throwable $e) {
  error_log('fact_check_list: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['facts' => [], 'references' => [], 'count' => 0, 'error' => true]);
}
