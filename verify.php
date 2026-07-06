<?php
/**
 * Payment return page — Hubtel redirect and legacy Paystack verification.
 */
declare(strict_types=1);

$reference = isset($_GET['reference']) ? trim((string) $_GET['reference']) : '';
$orderId = isset($_GET['order']) ? (int) $_GET['order'] : 0;
$verified = false;
$error = 'No transaction reference provided.';
$amountFormatted = '0.00 GHS';
$displayReference = $reference;

require_once __DIR__ . '/lib/paths.php';
require_once __DIR__ . '/lib/seo.php';
require_once __DIR__ . '/lib/payment.php';
hgay_seo_send_noindex();

try {
  require_once __DIR__ . '/config/database.php';

  if ($orderId > 0) {
    $pdo = dbConnection();
    $stmt = $pdo->prepare('SELECT id, status, amount_pesewas, currency, paystack_reference FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
      $error = 'Order not found.';
    } elseif (($order['status'] ?? '') === 'paid') {
      $verified = true;
      $displayReference = (string) ($order['paystack_reference'] ?? '');
      $amountFormatted = number_format(((int) $order['amount_pesewas']) / 100, 2) . ' ' . ($order['currency'] ?? 'GHS');
    } else {
      require_once __DIR__ . '/lib/hubtel.php';
      require_once __DIR__ . '/lib/order-payment.php';

      $invoiceId = trim((string) ($order['paystack_reference'] ?? ''));
      if ($invoiceId === '') {
        $invoiceId = hgay_hubtel_client_reference($orderId);
      }

      $statusResult = hgay_hubtel_transaction_status($invoiceId);
      $status = strtolower($statusResult['status']);
      if ($status === 'completed' || $status === 'success') {
        $transactionId = trim((string) (
          $statusResult['body']['checkoutId']
          ?? $statusResult['body']['CheckoutId']
          ?? $statusResult['body']['hubtelTransactionId']
          ?? $invoiceId
        ));
        $amountGhs = isset($statusResult['body']['amount']) ? (float) $statusResult['body']['amount'] : (isset($statusResult['body']['Amount']) ? (float) $statusResult['body']['Amount'] : null);
        $mark = hgay_order_mark_paid($pdo, $orderId, $transactionId, $amountGhs);
        if ($mark['updated'] || ($order['status'] ?? '') === 'paid') {
          $verified = true;
          $displayReference = $transactionId;
          $amountFormatted = number_format(((int) $order['amount_pesewas']) / 100, 2) . ' ' . ($order['currency'] ?? 'GHS');
        } else {
          $error = 'Payment is still processing. If you completed payment, we will confirm shortly by email.';
        }
      } elseif ($status === 'pending') {
        $error = 'Payment is still processing. Please wait a moment and refresh this page.';
      } else {
        $error = 'Payment was not completed. You can try placing your order again.';
      }
    }
  } elseif ($reference !== '' && hgay_payment_mode_is_paystack() && is_file(__DIR__ . '/paystack_config.php')) {
    require_once __DIR__ . '/paystack_config.php';
    require_once __DIR__ . '/lib/order-payment.php';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
      'Cache-Control: no-cache',
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $body = is_string($response) ? json_decode($response, true) : null;
    $verified = ($httpCode === 200 && !empty($body['status']) && !empty($body['data']['status']) && $body['data']['status'] === 'success');
    $data = $verified && isset($body['data']) ? $body['data'] : null;
    $error = $verified ? '' : (isset($body['message']) ? (string) $body['message'] : 'Verification failed.');

    if ($verified && is_array($data)) {
      $paystackOrderId = null;
      $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [];
      $customFields = isset($metadata['custom_fields']) && is_array($metadata['custom_fields']) ? $metadata['custom_fields'] : [];
      foreach ($customFields as $f) {
        if (isset($f['variable_name']) && $f['variable_name'] === 'order_id' && isset($f['value'])) {
          $paystackOrderId = (int) $f['value'];
          break;
        }
      }
      if ($paystackOrderId) {
        $pdo = dbConnection();
        $amountPaid = (int) $data['amount'];
        $mark = hgay_order_mark_paid($pdo, $paystackOrderId, $reference, $amountPaid / 100);
        $verified = $mark['updated'] || $verified;
      }

      $amount = (int) $data['amount'];
      $currency = isset($data['currency']) ? (string) $data['currency'] : 'GHS';
      $amountFormatted = $currency === 'GHS'
        ? number_format($amount / 100, 2) . ' GHS'
        : number_format($amount / 100, 2) . ' ' . $currency;
    }
  } elseif ($reference === '' && $orderId < 1) {
    $error = 'No payment information provided.';
  }
} catch (Throwable $e) {
  error_log('verify.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
  $error = 'Something went wrong. Your payment may have succeeded—please check your email or contact us.';
  $verified = false;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="robots" content="noindex, nofollow">
  <?php require __DIR__ . '/partials/google-analytics.php'; ?>
  <link rel="icon" href="HGAY_ASSETS/Card_Pictures_and_Video/howghrupng.png" type="image/png">
  <link rel="apple-touch-icon" href="HGAY_ASSETS/Card_Pictures_and_Video/howghrupng.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title><?php echo $verified ? 'Payment successful' : 'Payment verification'; ?> — How Ghanaian Are You?</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
  <style>
    .verify-page { min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
    .verify-box { text-align: center; max-width: 420px; }
    .verify-box h1 { margin-bottom: 0.5rem; }
    .verify-box p { color: var(--text-secondary); margin-bottom: 1.5rem; }
    .verify-box .btn { margin-top: 0.5rem; }
  </style>
</head>
<body>
  <main class="verify-page">
    <div class="verify-box">
      <?php if ($verified): ?>
        <h1 class="section-title">Thank you!</h1>
        <p>Your order payment of <strong><?php echo htmlspecialchars($amountFormatted); ?></strong> was successful. We'll be in touch with delivery details.</p>
        <?php if ($displayReference !== ''): ?>
        <p><small>Reference: <?php echo htmlspecialchars($displayReference); ?></small></p>
        <?php endif; ?>
        <a href="<?php echo htmlspecialchars(site_url()); ?>" class="btn btn-primary">Back to home</a>
      <?php else: ?>
        <h1 class="section-title"><?php echo $orderId > 0 ? 'Checking payment' : 'Verification failed'; ?></h1>
        <p><?php echo htmlspecialchars($error); ?></p>
        <a href="<?php echo htmlspecialchars(site_url('#place-order')); ?>" class="btn btn-primary">Try again</a>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
