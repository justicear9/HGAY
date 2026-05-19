<?php
header('Content-Type: application/json');
header('Cache-Control: public, max-age=60');
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/lib/settings.php';

try {
  $pdo = dbConnection();
  $priceGhs = getProductPriceGhs($pdo);
  $currency = settingsGet($pdo, 'product_currency', 'GHS') ?: 'GHS';
  echo json_encode([
    'price_ghs' => $priceGhs,
    'price_pesewas' => (int) round($priceGhs * 100),
    'currency' => $currency,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['price_ghs' => 100, 'price_pesewas' => 10000, 'currency' => 'GHS']);
}
