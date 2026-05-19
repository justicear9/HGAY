<?php
require_once 'auth.php';
require_once dirname(__DIR__) . '/config/database.php';

$pdo = dbConnection();
$seed = require dirname(__DIR__) . '/config/fact_cards_seed.php';
$inserted = 0;

try {
  $count = (int) $pdo->query('SELECT COUNT(*) FROM fact_cards')->fetchColumn();
  if ($count > 0 && empty($_GET['force'])) {
    header('Location: ' . admin_url('fact-cards') . '?msg=already');
    exit;
  }
  if (!empty($_GET['force'])) {
    $pdo->exec('DELETE FROM fact_cards');
  }
  $stmt = $pdo->prepare('INSERT INTO fact_cards (category, question, answer, keywords, sort_order) VALUES (?, ?, ?, ?, ?)');
  $i = 0;
  foreach ($seed as $card) {
    $stmt->execute([
      $card['category'],
      $card['question'],
      $card['answer'],
      $card['keywords'] ?? '',
      $i++,
    ]);
    $inserted++;
  }
  header('Location: ' . admin_url('fact-cards') . '?msg=seeded&n=' . $inserted);
  exit;
} catch (PDOException $e) {
  header('Location: ' . admin_url('settings') . '?err=' . urlencode('Run schema-update.sql first. ' . $e->getMessage()));
  exit;
}
