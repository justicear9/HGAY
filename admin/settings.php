<?php
require_once 'auth.php';
require_once dirname(__DIR__) . '/lib/security.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/lib/settings.php';
require_once dirname(__DIR__) . '/lib/order-mail.php';

$pdo = dbConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');
  if (!hgay_csrf_verify($csrf)) {
    $error = 'Session expired. Refresh the page and try again.';
  } elseif (isset($_POST['send_test_email'])) {
    $testTo = trim((string) ($_POST['test_email'] ?? ''));
    if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
      $error = 'Enter a valid email address for the test.';
    } else {
      $result = hgay_send_test_email($testTo);
      if ($result['ok']) {
        $message = 'Test email sent to ' . $testTo . '. Check inbox and spam.';
      } else {
        $error = $result['error'];
      }
    }
  } else {
    $price = isset($_POST['product_price_ghs']) ? (float) $_POST['product_price_ghs'] : 0;
    if ($price < 1 || $price > 99999) {
      $error = 'Price must be between 1 and 99,999 GHS.';
    } else {
      settingsSet($pdo, 'product_price_ghs', (string) $price);
      $message = 'Settings saved. Order page will show ' . number_format($price, 2) . ' GHS per game.';
    }
  }
}

$priceGhs = getProductPriceGhs($pdo);
$mailConfigured = hgay_mail_is_configured();

$adminTitle = 'Settings';
$adminPage = 'settings';
require_once 'includes/layout_start.php';
?>
      <header class="admin-header">
        <h1>Settings</h1>
        <p>Manage product price and store options.</p>
      </header>

      <?php if ($message): ?><div class="admin-alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="admin-alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <div class="admin-card">
        <h2>Product price</h2>
        <form method="post" class="admin-form" style="max-width:400px">
          <?php echo hgay_csrf_field(); ?>
          <div class="form-row">
            <label for="product_price_ghs">Price per game (GHS)</label>
            <input type="number" id="product_price_ghs" name="product_price_ghs" min="1" max="99999" step="0.01" value="<?php echo htmlspecialchars((string) $priceGhs); ?>" required>
            <p class="hint">Used for checkout (Hubtel / Paystack) and the Place an Order section. Customers pay price × quantity.</p>
          </div>
          <button type="submit" class="btn btn-primary">Save settings</button>
        </form>
      </div>

      <div class="admin-card">
        <h2>Order emails (SMTP)</h2>
        <?php if ($mailConfigured): ?>
        <p class="hint" style="margin-bottom:1rem">SMTP is configured via <code>config/mail.php</code>.</p>
        <?php else: ?>
        <div class="admin-alert error" style="margin-bottom:1rem">
          <strong>Email not configured.</strong> Copy <code>config/mail.example.php</code> to <code>config/mail.php</code> on the server and set your host SMTP credentials. This file is not in GitHub.
        </div>
        <?php endif; ?>
        <form method="post" class="admin-form" style="max-width:420px">
          <?php echo hgay_csrf_field(); ?>
          <div class="form-row">
            <label for="test_email">Send test confirmation email</label>
            <input type="email" id="test_email" name="test_email" placeholder="you@example.com" required>
            <p class="hint">Uses the kente order template. Check spam if nothing arrives in a minute.</p>
          </div>
          <button type="submit" name="send_test_email" value="1" class="btn btn-secondary">Send test email</button>
        </form>
      </div>

      <div class="admin-card">
        <h2>Database setup</h2>
        <p class="hint" style="margin-bottom:1rem">If you have not run the latest schema, import <code>schema-update.sql</code> in phpMyAdmin, then seed Fact Check cards.</p>
        <a href="seed_fact_cards" class="btn btn-secondary">Seed Fact Check cards</a>
      </div>
<?php require_once 'includes/layout_end.php'; ?>
