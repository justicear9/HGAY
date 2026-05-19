<?php
/**
 * Admin: list all orders with user and delivery info. Filter by status.
 */
require_once 'auth.php';
require_once dirname(__DIR__) . '/config/database.php';

$statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['pending', 'paid', 'failed'], true)
  ? $_GET['status'] : '';

$pdo = dbConnection();
$sql = "
  SELECT id, name, email, phone_country, phone_full, quantity, amount_pesewas, currency,
         delivery_country, delivery_region, delivery_address, delivery_postcode,
         paystack_reference, status, created_at
  FROM orders
";
$params = [];
if ($statusFilter !== '') {
  $sql .= " WHERE status = :status";
  $params[':status'] = $statusFilter;
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$countryNames = (require dirname(__DIR__) . '/config/countries.php')['country_names'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders — Admin</title>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    body { padding: 1rem 2rem; max-width: 1200px; margin: 0 auto; }
    h1 { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.9rem; }
    th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border, #333); }
    th { background: var(--bg-secondary, #1a1a1a); font-weight: 600; }
    tr:hover { background: rgba(255,255,255,0.03); }
    .status { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.85rem; }
    .status.paid { background: rgba(40,167,69,0.3); color: #5cb85c; }
    .status.pending { background: rgba(255,193,7,0.2); color: #ffc107; }
    .status.failed { background: rgba(220,53,69,0.2); color: #dc3545; }
    .amount { white-space: nowrap; }
    .detail { max-width: 200px; overflow: hidden; text-overflow: ellipsis; }
    @media (max-width: 768px) { table, th, td { display: block; } th { display: none; } td { padding: 6px 0; border: none; } td::before { content: attr(data-label); font-weight: 600; margin-right: 8px; } }
  </style>
</head>
<body>
  <h1>
    <span>Preorders</span>
    <span>
      <a href="logout.php" class="btn btn-secondary">Log out</a>
      <a href="../index.html" class="btn btn-secondary">Site</a>
    </span>
  </h1>
  <form method="get" class="admin-filters" style="display:flex; align-items:center; gap:1rem; margin:1rem 0;">
    <label for="filter-status">Status</label>
    <select id="filter-status" name="status" onchange="this.form.submit()">
      <option value="">All</option>
      <option value="pending"<?php echo $statusFilter === 'pending' ? ' selected' : ''; ?>>Pending</option>
      <option value="paid"<?php echo $statusFilter === 'paid' ? ' selected' : ''; ?>>Paid</option>
      <option value="failed"<?php echo $statusFilter === 'failed' ? ' selected' : ''; ?>>Failed</option>
    </select>
  </form>
  <p><?php echo count($orders); ?> order(s)</p>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Name</th>
        <th>Email</th>
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
          <td data-label="Date"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($o['created_at']))); ?></td>
          <td data-label="Name"><?php echo htmlspecialchars($o['name']); ?></td>
          <td data-label="Email"><a href="mailto:<?php echo htmlspecialchars($o['email']); ?>"><?php echo htmlspecialchars($o['email']); ?></a></td>
          <td data-label="Phone"><?php echo htmlspecialchars($o['phone_full']); ?></td>
          <td data-label="Qty"><?php echo (int) $o['quantity']; ?></td>
          <td data-label="Amount" class="amount"><?php echo number_format($o['amount_pesewas'] / 100, 2); ?> <?php echo htmlspecialchars($o['currency']); ?></td>
          <td data-label="Delivery" class="detail">
            <?php
            $c = $countryNames[$o['delivery_country']] ?? $o['delivery_country'];
            echo htmlspecialchars($c . ', ' . $o['delivery_region']);
            if ($o['delivery_postcode']) echo ', ' . htmlspecialchars($o['delivery_postcode']);
            echo ' — ' . htmlspecialchars(mb_substr($o['delivery_address'], 0, 60));
            if (mb_strlen($o['delivery_address']) > 60) echo '…';
            ?>
          </td>
          <td data-label="Status"><span class="status <?php echo htmlspecialchars($o['status']); ?>"><?php echo htmlspecialchars($o['status']); ?></span></td>
          <td data-label="Reference"><?php echo $o['paystack_reference'] ? htmlspecialchars($o['paystack_reference']) : '—'; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($orders)): ?>
    <p>No orders yet.</p>
  <?php endif; ?>
</body>
</html>
