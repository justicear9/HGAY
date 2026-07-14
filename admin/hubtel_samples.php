<?php
/**
 * Hubtel integration samples for go-live (admin only).
 * Run on production — status API is IP-whitelisted to the server.
 */
declare(strict_types=1);

require_once 'auth.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/lib/hubtel.php';

$pdo = dbConnection();
$paidOrders = $pdo->query(
    "SELECT id, paystack_reference, amount_pesewas, currency, status, created_at
     FROM orders
     WHERE status = 'paid'
     ORDER BY id DESC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

$callbackLog = [];
$logFile = dirname(__DIR__) . '/logs/hubtel-callbacks.log';
if (is_file($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $callbackLog = array_slice($lines, -10);
}

$statusSamples = [];
foreach ($paidOrders as $order) {
    $orderId = (int) $order['id'];
    $storedRef = trim((string) ($order['paystack_reference'] ?? ''));
    // Hubtel status API expects merchant clientReference (HGAY-{id}), not checkoutId.
    $ref = hgay_hubtel_client_reference($orderId);
    $statusSamples[] = [
        'order_id' => $orderId,
        'client_reference' => $ref,
        'stored_paystack_reference' => $storedRef,
        'request_url' => 'https://api-txnstatus.hubtel.com/transactions/'
            . rawurlencode(hgay_hubtel_credentials()['merchant_account'])
            . '/status?clientReference=' . rawurlencode($ref),
        'response' => hgay_hubtel_transaction_status($ref),
    ];
}

$export = [
    'generated_at' => gmdate('c'),
    'site' => 'https://howghanaianareyou.com',
    'callback_url' => hgay_email_absolute_url('api/hubtel_callback'),
    'merchant_account' => hgay_hubtel_credentials()['merchant_account'],
    'callback_log_lines' => $callbackLog,
    'transaction_status_checks' => $statusSamples,
];

$adminTitle = 'Hubtel samples';
$adminPage = 'hubtel_samples';
require_once 'includes/layout_start.php';
?>
      <header class="admin-header">
        <h1>Hubtel go-live samples</h1>
        <p>Copy JSON below for Hubtel. Status checks run from this server (whitelisted IP).</p>
      </header>

      <div class="admin-card">
        <h2>Export JSON</h2>
        <p class="hint">Send Hubtel: callback log lines (if any), transaction status responses, and your live site link.</p>
        <pre style="white-space:pre-wrap;word-break:break-word;max-height:70vh;overflow:auto;background:#111;padding:1rem;border-radius:8px;font-size:13px;"><?php
          echo htmlspecialchars(json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        ?></pre>
      </div>

      <?php if ($callbackLog === []): ?>
      <div class="admin-alert" style="margin-top:1rem">
        No rows in <code>logs/hubtel-callbacks.log</code> yet. Deploy callback logging, run one test payment, then refresh this page.
      </div>
      <?php endif; ?>

<?php require_once 'includes/layout_end.php'; ?>
