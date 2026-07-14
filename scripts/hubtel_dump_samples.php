#!/usr/bin/env php
<?php
/**
 * Run on the production server (SSH / cPanel terminal):
 *   php scripts/hubtel_dump_samples.php
 *
 * Outputs callback log lines + live Transaction Status API responses.
 * Status checks use clientReference HGAY-{orderId} (not Hubtel checkoutId).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/lib/hubtel.php';

$pdo = dbConnection();
$orders = $pdo->query(
    "SELECT id, paystack_reference, amount_pesewas, status
     FROM orders WHERE status = 'paid'
     ORDER BY id DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

$logFile = $root . '/logs/hubtel-callbacks.log';
$callbacks = is_file($logFile) ? array_slice(file($logFile, FILE_IGNORE_NEW_LINES) ?: [], -10) : [];

$out = [
    'generated_at' => gmdate('c'),
    'callback_url' => hgay_email_absolute_url('api/hubtel_callback'),
    'merchant_account' => hgay_hubtel_credentials()['merchant_account'],
    'raw_callback_log' => $callbacks,
    'status_checks' => [],
];

foreach ($orders as $order) {
    $orderId = (int) $order['id'];
    $storedRef = (string) ($order['paystack_reference'] ?? '');
    $clientReference = hgay_hubtel_client_reference($orderId);
    $result = hgay_hubtel_transaction_status($clientReference);
    $out['status_checks'][] = [
        'order_id' => $orderId,
        'client_reference' => $clientReference,
        'stored_paystack_reference' => $storedRef,
        'request_url' => 'https://api-txnstatus.hubtel.com/transactions/'
            . rawurlencode(hgay_hubtel_credentials()['merchant_account'])
            . '/status?clientReference=' . rawurlencode($clientReference),
        'http_ok' => $result['ok'],
        'status' => $result['status'],
        'raw_response' => $result['body'],
        'error' => $result['error'],
    ];
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
