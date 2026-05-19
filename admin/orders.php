<?php
require_once 'auth.php';
require_once dirname(__DIR__) . '/config/database.php';

$statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['pending', 'paid', 'failed'], true)
  ? $_GET['status'] : '';

$pdo = dbConnection();
$sql = "SELECT id, name, email, phone_full, quantity, amount_pesewas, currency,
         delivery_country, delivery_region, delivery_address, delivery_postcode,
         paystack_reference, status, created_at FROM orders";
$params = [];
if ($statusFilter !== '') {
  $sql .= ' WHERE status = :status';
  $params[':status'] = $statusFilter;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$countryNames = (require dirname(__DIR__) . '/config/countries.php')['country_names'];

$adminTitle = 'Orders';
$adminPage = 'orders';
require_once 'includes/layout_start.php';
?>
      <header class="admin-header">
        <h1>Orders</h1>
        <p><?php echo count($orders); ?> order(s)</p>
      </header>

      <form method="get" class="admin-filters">
        <label for="filter-status">Status</label>
        <select id="filter-status" name="status" onchange="this.form.submit()">
          <option value="">All</option>
          <option value="pending"<?php echo $statusFilter === 'pending' ? ' selected' : ''; ?>>Pending</option>
          <option value="paid"<?php echo $statusFilter === 'paid' ? ' selected' : ''; ?>>Paid</option>
          <option value="failed"<?php echo $statusFilter === 'failed' ? ' selected' : ''; ?>>Failed</option>
        </select>
      </form>

      <div class="admin-card">
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Qty</th>
                <th>Amount</th>
                <th>Delivery</th>
                <th>Status</th>
                <th>Reference</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
              <tr>
                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($o['created_at']))); ?></td>
                <td>
                  <strong><?php echo htmlspecialchars($o['name']); ?></strong><br>
                  <a href="mailto:<?php echo htmlspecialchars($o['email']); ?>"><?php echo htmlspecialchars($o['email']); ?></a>
                </td>
                <td><?php echo htmlspecialchars($o['phone_full']); ?></td>
                <td><?php echo (int) $o['quantity']; ?></td>
                <td><?php echo number_format($o['amount_pesewas'] / 100, 2); ?> <?php echo htmlspecialchars($o['currency']); ?></td>
                <td style="max-width:220px;font-size:0.85rem">
                  <?php
                  $c = $countryNames[$o['delivery_country']] ?? $o['delivery_country'];
                  echo htmlspecialchars($c . ', ' . $o['delivery_region']);
                  if ($o['delivery_postcode']) echo ', ' . htmlspecialchars($o['delivery_postcode']);
                  echo '<br>' . htmlspecialchars(mb_substr($o['delivery_address'], 0, 80));
                  if (mb_strlen($o['delivery_address']) > 80) echo '…';
                  ?>
                </td>
                <td><span class="status <?php echo htmlspecialchars($o['status']); ?>"><?php echo htmlspecialchars($o['status']); ?></span></td>
                <td style="font-size:0.8rem"><?php echo $o['paystack_reference'] ? htmlspecialchars($o['paystack_reference']) : '—'; ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($orders)): ?>
              <tr><td colspan="8">No orders found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
<?php require_once 'includes/layout_end.php'; ?>
