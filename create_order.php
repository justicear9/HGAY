<?php
/**
 * Create an order. Hubtel/Paystack: pending + checkout. Pay on delivery: confirmed immediately.
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/settings.php';
require_once __DIR__ . '/lib/payment.php';
require_once __DIR__ . '/lib/paths.php';

$countries = require __DIR__ . '/config/countries.php';
$dialCodes = $countries['dial_codes'];
$phoneLengths = $countries['phone_lengths'] ?? [];
$paymentMode = hgay_payment_mode();

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

$pdo = dbConnection();
$amountGhs = getProductPriceGhs($pdo);
$amountPesewas = (int) round($amountGhs * 100) * $raw['quantity'];
$phoneFull = $dialCodes[$raw['phone_country']] . $raw['phone_number'];

if (!empty($errors)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'errors' => $errors]);
  exit;
}

$initialStatus = $paymentMode === 'pay_on_delivery' ? 'confirmed' : 'pending';

try {
  $stmt = $pdo->prepare("
    INSERT INTO orders (
      name, email, phone_country, phone_full, quantity, amount_pesewas, currency,
      delivery_country, delivery_region, delivery_address, delivery_postcode, status
    ) VALUES (
      :name, :email, :phone_country, :phone_full, :quantity, :amount_pesewas, 'GHS',
      :delivery_country, :delivery_region, :delivery_address, :delivery_postcode, :status
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
    ':status' => $initialStatus,
  ]);
  $orderId = (int) $pdo->lastInsertId();

  if ($paymentMode === 'pay_on_delivery') {
    require_once __DIR__ . '/lib/order-mail.php';
    $mailResult = hgay_send_order_confirmation_email([
      'name' => $raw['name'],
      'email' => $raw['email'],
      'quantity' => $raw['quantity'],
      'amount_pesewas' => $amountPesewas,
      'currency' => 'GHS',
      'paystack_reference' => '',
      'payment_mode' => 'pay_on_delivery',
      'order_id' => $orderId,
    ]);

    $redirect = site_url('order-confirmed?order=' . $orderId);
    if (!$mailResult['ok']) {
      $redirect .= '&email=failed';
    }

    echo json_encode([
      'success' => true,
      'order_id' => $orderId,
      'payment_mode' => 'pay_on_delivery',
      'email_sent' => $mailResult['ok'],
      'redirect' => $redirect,
    ]);
    exit;
  }

  if ($paymentMode === 'hubtel') {
    require_once __DIR__ . '/lib/hubtel.php';
    if (!hgay_hubtel_is_configured()) {
      http_response_code(503);
      echo json_encode([
        'success' => false,
        'error' => 'Online payment is not configured yet. Please try again later or contact us.',
      ]);
      exit;
    }

    $orderRow = [
      'id' => $orderId,
      'name' => $raw['name'],
      'email' => $raw['email'],
      'phone_full' => $phoneFull,
      'quantity' => $raw['quantity'],
      'amount_pesewas' => $amountPesewas,
    ];
    $checkout = hgay_hubtel_initiate_checkout($orderRow);
    if (!$checkout['ok']) {
      error_log('create_order hubtel: ' . $checkout['error']);
      http_response_code(502);
      echo json_encode([
        'success' => false,
        'error' => 'Could not start payment. Please try again.',
      ]);
      exit;
    }

    $stmt = $pdo->prepare('UPDATE orders SET paystack_reference = :ref, updated_at = NOW() WHERE id = :id');
    $stmt->execute([':ref' => $checkout['invoice_id'], ':id' => $orderId]);

    echo json_encode([
      'success' => true,
      'order_id' => $orderId,
      'payment_mode' => 'hubtel',
      'checkout_url' => $checkout['checkout_url'],
    ]);
    exit;
  }

  echo json_encode([
    'success' => true,
    'order_id' => $orderId,
    'amount_pesewas' => $amountPesewas,
    'email' => $raw['email'],
    'payment_mode' => 'paystack',
  ]);
} catch (PDOException $e) {
  error_log('create_order: ' . $e->getMessage());
  $msg = 'Could not create order. Please try again.';
  if (str_contains($e->getMessage(), 'confirmed') || str_contains($e->getMessage(), 'Data truncated')) {
    $msg = 'Orders database needs an update. Run the latest schema-update.sql on the orders table (add confirmed status).';
  }
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => $msg]);
}
