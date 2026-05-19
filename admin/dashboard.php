<?php
require_once 'auth.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/lib/settings.php';

$pdo = dbConnection();
$priceGhs = getProductPriceGhs($pdo);

$stats = [
  'total' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
  'paid' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn(),
  'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
  'revenue' => (int) $pdo->query("SELECT COALESCE(SUM(amount_pesewas), 0) FROM orders WHERE status = 'paid'")->fetchColumn(),
];
$cardsCount = 0;
try {
  $cardsCount = (int) $pdo->query("SELECT COUNT(*) FROM fact_cards WHERE is_active = 1 AND category != 'card_references'")->fetchColumn();
} catch (PDOException $e) {
}

$recent = $pdo->query("
  SELECT id, name, email, quantity, amount_pesewas, currency, status, created_at
  FROM orders ORDER BY created_at DESC LIMIT 8
")->fetchAll();

$adminTitle = 'Dashboard';
$adminPage = 'dashboard';
require_once 'includes/layout_start.php';
?>
      <header class="admin-header">
        <h1>Dashboard</h1>
        <p>Overview of orders and store settings.</p>
      </header>

      <div class="admin-stats">
        <div class="admin-stat-card">
          <div class="label">Total orders</div>
          <div class="value"><?php echo $stats['total']; ?></div>
        </div>
        <div class="admin-stat-card">
          <div class="label">Paid</div>
          <div class="value"><?php echo $stats['paid']; ?></div>
        </div>
        <div class="admin-stat-card">
          <div class="label">Pending</div>
          <div class="value"><?php echo $stats['pending']; ?></div>
        </div>
        <div class="admin-stat-card">
          <div class="label">Revenue (paid)</div>
          <div class="value"><?php echo number_format($stats['revenue'] / 100, 0); ?> GHS</div>
        </div>
        <div class="admin-stat-card">
          <div class="label">Price per game</div>
          <div class="value"><?php echo number_format($priceGhs, 0); ?> GHS</div>
        </div>
        <div class="admin-stat-card">
          <div class="label">Fact Check Q&amp;A live</div>
          <div class="value"><?php echo $cardsCount; ?></div>
        </div>
      </div>

      <div class="admin-card">
        <h2>Recent orders</h2>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Qty</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $o): ?>
              <tr>
                <td><?php echo htmlspecialchars(date('M j, H:i', strtotime($o['created_at']))); ?></td>
                <td><?php echo htmlspecialchars($o['name']); ?><br><small style="color:var(--text-muted)"><?php echo htmlspecialchars($o['email']); ?></small></td>
                <td><?php echo (int) $o['quantity']; ?></td>
                <td><?php echo number_format($o['amount_pesewas'] / 100, 2); ?> <?php echo htmlspecialchars($o['currency']); ?></td>
                <td><span class="status <?php echo htmlspecialchars($o['status']); ?>"><?php echo htmlspecialchars($o['status']); ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($recent)): ?>
              <tr><td colspan="5">No orders yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <p style="margin-top:1rem"><a href="orders.php" class="btn btn-secondary">View all orders</a></p>
      </div>
<?php require_once 'includes/layout_end.php'; ?>
