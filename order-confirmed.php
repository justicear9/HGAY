<?php
/**
 * Order confirmation page (pay on delivery).
 */
declare(strict_types=1);

require_once __DIR__ . '/lib/paths.php';
require_once __DIR__ . '/lib/seo.php';
require_once __DIR__ . '/lib/security.php';
require_once __DIR__ . '/config/database.php';

hgay_seo_send_noindex();
hgay_security_send_headers();

$orderId = isset($_GET['order']) ? (int) $_GET['order'] : 0;
$accessToken = isset($_GET['token']) ? trim((string) $_GET['token']) : '';
$emailFailed = isset($_GET['email']) && $_GET['email'] === 'failed';
$order = null;
$error = null;

if ($orderId < 1 || $accessToken === '') {
    $error = 'No order specified.';
} else {
    try {
        $pdo = dbConnection();
        $stmt = $pdo->prepare(
            "SELECT id, name, email, quantity, amount_pesewas, currency, status FROM orders WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row || !hgay_order_token_valid($orderId, (string) ($row['email'] ?? ''), $accessToken)) {
            $error = 'We could not find that order.';
        } elseif (!in_array($row['status'], ['confirmed', 'paid'], true)) {
            $error = 'We could not find that order.';
        } else {
            $order = $row;
        }
    } catch (Throwable $e) {
        error_log('order-confirmed.php: ' . $e->getMessage());
        $error = 'Something went wrong. Please contact us if you need help.';
    }
}

$amountFormatted = $order
    ? number_format((int) $order['amount_pesewas'] / 100, 2) . ' ' . htmlspecialchars($order['currency'])
    : '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="robots" content="noindex, nofollow">
  <?php require __DIR__ . '/partials/google-analytics.php'; ?>
  <link rel="icon" href="HGAY_ASSETS/Card_Pictures_and_Video/howghrupng.png" type="image/png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>Order confirmed — How Ghanaian Are You?</title>
  <link rel="stylesheet" href="css/styles.css">
  <style>
    .verify-page { min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
    .verify-box { text-align: center; max-width: 440px; }
    .verify-box h1 { margin-bottom: 0.5rem; }
    .verify-box p { color: var(--text-secondary); margin-bottom: 1rem; line-height: 1.55; }
    .verify-box .btn { margin-top: 0.5rem; }
    .verify-order-id { font-size: 0.9rem; color: var(--text-muted); }
  </style>
</head>
<body>
  <main class="verify-page">
    <div class="verify-box">
      <?php if ($order): ?>
        <h1 class="section-title">Thank you!</h1>
        <p>Hi <?php echo htmlspecialchars($order['name']); ?>, your order for <strong><?php echo (int) $order['quantity']; ?></strong> game<?php echo (int) $order['quantity'] > 1 ? 's' : ''; ?> (<strong><?php echo $amountFormatted; ?></strong>) is confirmed.</p>
        <p>Pay on delivery when we bring your order. We'll contact you with delivery details soon.</p>
        <?php if ($emailFailed): ?>
        <p style="color:var(--ghana-gold); font-size:0.95rem;">We couldn't send a confirmation email right now, but your order is saved. We'll reach out using your phone number.</p>
        <?php endif; ?>
        <p class="verify-order-id">Order #<?php echo (int) $order['id']; ?></p>
        <a href="<?php echo htmlspecialchars(site_url()); ?>" class="btn btn-primary">Back to home</a>
      <?php else: ?>
        <h1 class="section-title">Order not found</h1>
        <p><?php echo htmlspecialchars($error ?? 'Please try again or contact us.'); ?></p>
        <a href="<?php echo htmlspecialchars(site_url('#place-order')); ?>" class="btn btn-primary">Place an order</a>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
