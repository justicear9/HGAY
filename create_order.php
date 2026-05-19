<?php
/**
 * Create a pending order and return order_id for Paystack metadata.
 * Validates and sanitizes all inputs; formats phone with country code.
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config/database.php';

$countries = require __DIR__ . '/config/countries.php';
$dialCodes = $countries['dial_codes'];
$phoneLengths = $countries['phone_lengths'] ?? [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

$raw = [
  'name' => isset($_POST['name']) ? trim((string) $_POST['name']) : '',
  'email' => isset($_POST['email']) ? trim((string) $_POST['email']) : '',
  'phone_country' => isset($_POST['phone_country']) ? strtoupper(trim((string) $_POST['phone_country'])) : '',
  'phone_number' => isset($_POST['phone_number']) ? preg_replace('/\D/', '', (string) $_POST['phone_number']) : '',
  'quantity' => isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0,
  'delivery_country' => isset($_POST['delivery_country']) ? strtoupper(trim((string) $_POST['delivery_country'])) : '',
  'delivery_region' => isset($_POST['delivery_region']) ? trim((string) $_POST['delivery_region']) : '',
  'delivery_address' => isset($_POST['delivery_address']) ? trim((string) $_POST['delivery_address']) : '',
  'delivery_postcode' => isset($_POST['delivery_postcode']) ? trim((string) $_POST['delivery_postcode']) : '',
];

$errors = [];

if (strlen($raw['name']) < 2 || strlen($raw['name']) > 255) {
  $errors[] = 'Name must be 2–255 characters.';
}
if (!filter_var($raw['email'], FILTER_VALIDATE_EMAIL)) {
  $errors[] = 'Invalid email.';
}
if (!isset($dialCodes[$raw['phone_country']])) {
  $errors[] = 'Invalid phone country.';
}
$digits = strlen($raw['phone_number']);
if (isset($phoneLengths[$raw['phone_country']])) {
  list($minLen, $maxLen) = $phoneLengths[$raw['phone_country']];
  if ($digits < $minLen || $digits > $maxLen) {
    $errors[] = 'Phone number for this country must be ' . $minLen . '–' . $maxLen . ' digits.';
  }
} elseif ($digits < 6 || $digits > 15) {
  $errors[] = 'Phone number must be 6–15 digits.';
}
if ($raw['quantity'] < 1 || $raw['quantity'] > 99) {
  $errors[] = 'Quantity must be 1–99.';
}
if (strlen($raw['delivery_country']) !== 2 || !isset($dialCodes[$raw['delivery_country']])) {
  $errors[] = 'Invalid delivery country.';
}
if (strlen($raw['delivery_region']) < 1 || strlen($raw['delivery_region']) > 255) {
  $errors[] = 'State/Region is required (max 255 characters).';
}
if (strlen($raw['delivery_address']) < 5 || strlen($raw['delivery_address']) > 2000) {
  $errors[] = 'Address must be 5–2000 characters.';
}

$amountGhs = 100;
$amountPesewas = (int) round($amountGhs * 100) * $raw['quantity'];
$phoneFull = $dialCodes[$raw['phone_country']] . $raw['phone_number'];

if (!empty($errors)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'errors' => $errors]);
  exit;
}

try {
  $pdo = dbConnection();
  $stmt = $pdo->prepare("
    INSERT INTO orders (
      name, email, phone_country, phone_full, quantity, amount_pesewas, currency,
      delivery_country, delivery_region, delivery_address, delivery_postcode, status
    ) VALUES (
      :name, :email, :phone_country, :phone_full, :quantity, :amount_pesewas, 'GHS',
      :delivery_country, :delivery_region, :delivery_address, :delivery_postcode, 'pending'
    )
  ");
  $stmt->execute([
    ':name' => $raw['name'],
    ':email' => $raw['email'],
    ':phone_country' => $raw['phone_country'],
    ':phone_full' => $phoneFull,
    ':quantity' => $raw['quantity'],
    ':amount_pesewas' => $amountPesewas,
    ':delivery_country' => $raw['delivery_country'],
    ':delivery_region' => $raw['delivery_region'],
    ':delivery_address' => $raw['delivery_address'],
    ':delivery_postcode' => $raw['delivery_postcode'] === '' ? null : $raw['delivery_postcode'],
  ]);
  $orderId = (int) $pdo->lastInsertId();
  echo json_encode(['success' => true, 'order_id' => $orderId, 'amount_pesewas' => $amountPesewas, 'email' => $raw['email']]);
} catch (PDOException $e) {
  error_log('create_order: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Could not create order. Please try again.']);
}
