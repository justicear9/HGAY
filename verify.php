<?php
/**
 * Paystack payment verification.
 * Called after customer completes payment (callback from Paystack popup).
 * Updates order to paid and stores Paystack reference.
 */
$reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';
$verified = false;
$error = 'No transaction reference provided.';
$data = null;
$orderUpdated = false;
$orderForEmail = null;
$amount = 0;
$currency = 'GHS';
$amountFormatted = '0.00 GHS';

require_once __DIR__ . '/lib/paths.php';

try {
  require_once __DIR__ . '/paystack_config.php';
  require_once __DIR__ . '/config/database.php';

  if ($reference !== '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
      'Cache-Control: no-cache',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $body = is_string($response) ? json_decode($response, true) : null;
    $verified = ($httpCode === 200 && !empty($body['status']) && !empty($body['data']['status']) && $body['data']['status'] === 'success');
    $data = $verified && isset($body['data']) ? $body['data'] : null;
    $error = $verified ? null : (isset($body['message']) ? $body['message'] : 'Verification failed.');

    if ($verified && $data) {
      $orderId = null;
      $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [];
      $customFields = isset($metadata['custom_fields']) && is_array($metadata['custom_fields']) ? $metadata['custom_fields'] : [];
      foreach ($customFields as $f) {
        if (isset($f['variable_name']) && $f['variable_name'] === 'order_id' && isset($f['value'])) {
          $orderId = (int) $f['value'];
          break;
        }
      }
      if ($orderId) {
        $pdo = dbConnection();
        $amountPaid = (int) $data['amount'];
        $stmt = $pdo->prepare("
          UPDATE orders SET status = 'paid', paystack_reference = :ref, updated_at = NOW()
          WHERE id = :id AND status = 'pending' AND amount_pesewas = :amount
        ");
        $stmt->execute([':ref' => $reference, ':id' => $orderId, ':amount' => $amountPaid]);
        $orderUpdated = $stmt->rowCount() > 0;
        if ($orderUpdated) {
          $stmt = $pdo->prepare("SELECT name, email, quantity, amount_pesewas, currency, paystack_reference FROM orders WHERE id = ?");
          $stmt->execute([$orderId]);
          $orderForEmail = $stmt->fetch();
        }
      }
    }

    $amount = $data ? (int) $data['amount'] : 0;
    $currency = $data ? (isset($data['currency']) ? $data['currency'] : 'GHS') : 'GHS';
    $amountFormatted = $currency === 'GHS' ? number_format($amount / 100, 2) . ' GHS' : number_format($amount / 100, 2) . ' ' . $currency;
  }
} catch (Throwable $e) {
  error_log('verify.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
  $error = 'Something went wrong. Your payment may have succeeded—please check your email or contact us.';
  $verified = false;
  $data = null;
}

if ($orderUpdated && $orderForEmail && !empty($orderForEmail['email'])) {
  try {
    require_once __DIR__ . '/config/mail.php';
    require_once __DIR__ . '/lib/mail.php';
    $order = $orderForEmail;
    ob_start();
    include __DIR__ . '/templates/order-confirmation-email.php';
    $html = ob_get_clean();
    if ($html !== false && $html !== '') {
      sendSmtpMail(
        $orderForEmail['email'],
        'Order confirmed — How Ghanaian Are You?',
        $html
      );
    }
  } catch (Throwable $e) {
    error_log('verify.php email send: ' . $e->getMessage());
  }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="HGAY ASSETS/Card Pictures and Video/howghrupng.png" type="image/png">
  <link rel="apple-touch-icon" href="HGAY ASSETS/Card Pictures and Video/howghrupng.png">
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
        <p><small>Reference: <?php echo htmlspecialchars($reference); ?></small></p>
        <a href="<?php echo htmlspecialchars(site_url()); ?>" class="btn btn-primary">Back to home</a>
      <?php else: ?>
        <h1 class="section-title">Verification failed</h1>
        <p><?php echo htmlspecialchars($error); ?></p>
        <a href="<?php echo htmlspecialchars(site_url('#place-order')); ?>" class="btn btn-primary">Try again</a>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
