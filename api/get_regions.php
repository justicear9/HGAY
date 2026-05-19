<?php
/**
 * Returns states/regions for a country (for searchable dropdown).
 * GET ?country=GH
 */
header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');

$country = isset($_GET['country']) ? strtoupper(trim((string) $_GET['country'])) : '';
if (strlen($country) !== 2) {
  echo json_encode([]);
  exit;
}

$regions = require dirname(__DIR__) . '/config/regions.php';
$list = isset($regions[$country]) ? $regions[$country] : [];
echo json_encode($list);
