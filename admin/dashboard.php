<?php
require_once 'auth.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/lib/settings.php';

$priceGhs = 100;
$stats = ['total' => 0, 'paid' => 0, 'pending' => 0, 'revenue' => 0];
$cardsCount = 0;
$recent = [];
$dbError = '';

try {
  $pdo = dbConnection();
  $priceGhs = getProductPriceGhs($pdo);
  $stats = [
    'total' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'paid' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'revenue' => (int) $pdo->query("SELECT COALESCE(SUM(amount_pesewas), 0) FROM orders WHERE status = 'paid'")->fetchColumn(),
  ];
  try {
    $cardsCount = (int) $pdo->query("SELECT COUNT(*) FROM fact_cards WHERE is_active = 1 AND category != 'card_references'")->fetchColumn();
  } catch (PDOException $e) {
    // fact_cards table or is_active column may be missing on older DBs
  }
  $recent = $pdo->query("
    SELECT id, name, email, quantity, amount_pesewas, currency, status, created_at
    FROM orders ORDER BY created_at DESC LIMIT 6
  ")->fetchAll();
} catch (Throwable $e) {
  error_log('admin/dashboard.php: ' . $e->getMessage());
  $dbError = 'Database error. Check config/database.php and run schema.sql / schema-update.sql on the server.';
}

$adminTitle = 'Dashboard';
$adminPage = 'dashboard';
require_once 'includes/layout_start.php';
?>
      <?php if ($dbError): ?>
      <div class="admin-auth-alert" role="alert" style="margin-bottom:1.5rem"><?php echo htmlspecialchars($dbError); ?></div>
      <?php endif; ?>
      <header class="dash-header">
        <div>
          <h1 class="dash-title">Dashboard</h1>
          <p class="dash-subtitle">Welcome back — here’s what’s happening with your store.</p>
        </div>
        <a href="orders" class="btn btn-primary dash-header-btn">View all orders</a>
      </header>

      <div class="dash-stats">
        <article class="dash-stat-card">
          <div class="dash-stat-top">
            <span class="dash-stat-icon dash-stat-icon--gold"><?php echo admin_icon('dollar'); ?></span>
            <?php echo admin_icon('trending', 'dash-stat-trend'); ?>
          </div>
          <h3 class="dash-stat-label">Revenue (paid)</h3>
          <p class="dash-stat-value"><?php echo number_format($stats['revenue'] / 100, 0); ?> <span class="dash-stat-unit">GHS</span></p>
        </article>
        <article class="dash-stat-card">
          <div class="dash-stat-top">
            <span class="dash-stat-icon dash-stat-icon--green"><?php echo admin_icon('cart'); ?></span>
            <?php echo admin_icon('trending', 'dash-stat-trend'); ?>
          </div>
          <h3 class="dash-stat-label">Paid orders</h3>
          <p class="dash-stat-value"><?php echo $stats['paid']; ?></p>
        </article>
        <article class="dash-stat-card">
          <div class="dash-stat-top">
            <span class="dash-stat-icon dash-stat-icon--amber"><?php echo admin_icon('clock'); ?></span>
          </div>
          <h3 class="dash-stat-label">Pending</h3>
          <p class="dash-stat-value"><?php echo $stats['pending']; ?></p>
          <?php if ($stats['pending'] > 0): ?>
          <p class="dash-stat-hint">Needs attention</p>
          <?php endif; ?>
        </article>
        <article class="dash-stat-card">
          <div class="dash-stat-top">
            <span class="dash-stat-icon dash-stat-icon--purple"><?php echo admin_icon('book'); ?></span>
          </div>
          <h3 class="dash-stat-label">Fact Check Q&amp;A</h3>
          <p class="dash-stat-value"><?php echo $cardsCount; ?> <span class="dash-stat-unit">live</span></p>
          <p class="dash-stat-hint"><?php echo number_format($priceGhs, 0); ?> GHS per game</p>
        </article>
      </div>

      <div class="dash-grid">
        <section class="dash-panel dash-panel--wide">
          <div class="dash-panel-head">
            <h2 class="dash-panel-title">Recent orders</h2>
            <a href="orders" class="dash-panel-link">View all</a>
          </div>
          <ul class="dash-activity">
            <?php foreach ($recent as $o): ?>
            <?php
              $statusClass = htmlspecialchars($o['status']);
              $timeAgo = date('M j, g:i A', strtotime($o['created_at']));
            ?>
            <li class="dash-activity-item">
              <span class="dash-activity-icon dash-activity-icon--<?php echo $statusClass === 'paid' ? 'green' : ($statusClass === 'pending' ? 'amber' : 'red'); ?>">
                <?php echo admin_icon('cart'); ?>
              </span>
              <div class="dash-activity-body">
                <p class="dash-activity-title"><?php echo htmlspecialchars($o['name']); ?> — <?php echo (int) $o['quantity']; ?> game<?php echo (int) $o['quantity'] > 1 ? 's' : ''; ?></p>
                <p class="dash-activity-desc"><?php echo number_format($o['amount_pesewas'] / 100, 2); ?> <?php echo htmlspecialchars($o['currency']); ?> · <span class="status <?php echo $statusClass; ?>"><?php echo $statusClass; ?></span></p>
              </div>
              <time class="dash-activity-time" datetime="<?php echo htmlspecialchars($o['created_at']); ?>"><?php echo htmlspecialchars($timeAgo); ?></time>
            </li>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?>
            <li class="dash-activity-empty">No orders yet. Share your Place an Order link to get started.</li>
            <?php endif; ?>
          </ul>
        </section>

        <aside class="dash-panel">
          <h2 class="dash-panel-title">Quick links</h2>
          <ul class="dash-quick-links">
            <li><a href="settings"><?php echo admin_icon('settings'); ?> Product price &amp; setup</a></li>
            <li><a href="fact-cards"><?php echo admin_icon('book'); ?> Manage Fact Check</a></li>
            <li><a href="events"><?php echo admin_icon('calendar'); ?> Manage Events</a></li>
            <li><a href="gallery"><?php echo admin_icon('image'); ?> Manage Gallery</a></li>
            <li><a href="card-references"><?php echo admin_icon('tag'); ?> Card references</a></li>
            <li><a href="<?php echo htmlspecialchars(site_url('fact-check')); ?>" target="_blank" rel="noopener"><?php echo admin_icon('external'); ?> Preview Fact Check</a></li>
            <li><a href="<?php echo htmlspecialchars(site_url('events')); ?>" target="_blank" rel="noopener"><?php echo admin_icon('external'); ?> Preview Events</a></li>
            <li><a href="<?php echo htmlspecialchars(site_url('gallery')); ?>" target="_blank" rel="noopener"><?php echo admin_icon('external'); ?> Preview Gallery</a></li>
            <li><a href="<?php echo htmlspecialchars(site_url('#place-order')); ?>" target="_blank" rel="noopener"><?php echo admin_icon('external'); ?> Preview order page</a></li>
          </ul>

          <div class="dash-mini-stats">
            <div class="dash-mini-row">
              <span>Total orders</span>
              <strong><?php echo $stats['total']; ?></strong>
            </div>
            <div class="dash-mini-row">
              <span>Order conversion (paid / total)</span>
              <strong><?php echo $stats['total'] > 0 ? round(100 * $stats['paid'] / $stats['total']) : 0; ?>%</strong>
            </div>
            <div class="dash-progress"><span style="width:<?php echo $stats['total'] > 0 ? round(100 * $stats['paid'] / $stats['total']) : 0; ?>%"></span></div>
          </div>
        </aside>
      </div>
<?php require_once 'includes/layout_end.php'; ?>
