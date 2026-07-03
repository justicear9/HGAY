<?php
/**
 * Hubtel Online Checkout (redirect + webhook).
 */
declare(strict_types=1);

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/seo.php';

const HGAY_HUBTEL_API_BASE = 'https://payproxyapi.hubtel.com';

/** @return array<string, mixed> */
function hgay_hubtel_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $path = dirname(__DIR__) . '/config/hubtel.php';
    if (!is_file($path)) {
        $config = [];
        return $config;
    }

    $loaded = require $path;
    $config = is_array($loaded) ? $loaded : [];

    return $config;
}

function hgay_hubtel_is_configured(): bool
{
    $cfg = hgay_hubtel_config();

    return trim((string) ($cfg['client_id'] ?? '')) !== ''
        && trim((string) ($cfg['client_secret'] ?? '')) !== '';
}

function hgay_hubtel_basic_auth_header(): string
{
    $cfg = hgay_hubtel_config();
    $credentials = base64_encode(
        trim((string) $cfg['client_id']) . ':' . trim((string) $cfg['client_secret'])
    );

    return 'Basic ' . $credentials;
}

function hgay_hubtel_invoice_id(int $orderId): string
{
    return 'HGAY-' . $orderId;
}

function hgay_hubtel_order_id_from_invoice(string $invoiceId): ?int
{
    if (preg_match('/^HGAY-(\d+)$/', trim($invoiceId), $m)) {
        return (int) $m[1];
    }

    return null;
}

function hgay_hubtel_format_msisdn(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone) ?? '';
    if ($digits === '') {
        return '';
    }
    if (str_starts_with($digits, '0')) {
        $digits = '233' . substr($digits, 1);
    }
    if (!str_starts_with($digits, '233')) {
        $digits = '233' . ltrim($digits, '0');
    }

    return $digits;
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, http_code: int, body: array<string, mixed>|null, error: string}
 */
function hgay_hubtel_api(string $method, string $path, array $body = []): array
{
    if (!hgay_hubtel_is_configured()) {
        return ['ok' => false, 'http_code' => 0, 'body' => null, 'error' => 'Hubtel is not configured (config/hubtel.php).'];
    }

    $url = HGAY_HUBTEL_API_BASE . $path;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . hgay_hubtel_basic_auth_header(),
        'Content-Type: application/json',
        'Cache-Control: no-cache',
    ]);

    $method = strtoupper($method);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'http_code' => $httpCode, 'body' => null, 'error' => $curlError ?: 'Hubtel request failed.'];
    }

    $decoded = json_decode($response, true);
    $payload = is_array($decoded) ? $decoded : null;
    $responseCode = (string) ($payload['ResponseCode'] ?? '');
    $ok = $httpCode >= 200 && $httpCode < 300 && $responseCode === '00';

    return [
        'ok' => $ok,
        'http_code' => $httpCode,
        'body' => $payload,
        'error' => $ok ? '' : (string) ($payload['ResponseMessage'] ?? 'Hubtel API error.'),
    ];
}

/**
 * @param array<string, mixed> $order
 * @return array{ok: bool, checkout_url: string, invoice_id: string, error: string}
 */
function hgay_hubtel_initiate_checkout(array $order): array
{
    $orderId = (int) ($order['id'] ?? $order['order_id'] ?? 0);
    $amountPesewas = (int) ($order['amount_pesewas'] ?? 0);
    if ($orderId < 1 || $amountPesewas < 1) {
        return ['ok' => false, 'checkout_url' => '', 'invoice_id' => '', 'error' => 'Invalid order for Hubtel checkout.'];
    }

    $invoiceId = hgay_hubtel_invoice_id($orderId);
    $amountGhs = round($amountPesewas / 100, 2);
    $name = trim((string) ($order['name'] ?? 'Customer'));
    $email = trim((string) ($order['email'] ?? ''));
    $phone = hgay_hubtel_format_msisdn((string) ($order['phone_full'] ?? ''));
    $quantity = (int) ($order['quantity'] ?? 1);

    $callbackUrl = hgay_email_absolute_url('api/hubtel_callback');
    $returnUrl = hgay_email_absolute_url('verify?order=' . $orderId);
    $cancelUrl = rtrim(hgay_email_absolute_url(''), '/') . '/#place-order';

    $payload = [
        'InvoiceId' => $invoiceId,
        'TotalAmount' => $amountGhs,
        'Description' => 'How Ghanaian Are You — ' . $quantity . ' game' . ($quantity > 1 ? 's' : ''),
        'CustomerName' => $name,
        'PrimaryCallbackUrl' => $callbackUrl,
        'ReturnUrl' => $returnUrl,
        'CancellationUrl' => $cancelUrl,
    ];
    if ($email !== '') {
        $payload['CustomerEmail'] = $email;
    }
    if ($phone !== '') {
        $payload['CustomerMsisdn'] = $phone;
    }

    $logo = hgay_email_absolute_url('HGAY_ASSETS/logo.png');
    $payload['Logo'] = $logo;

    $result = hgay_hubtel_api('POST', '/items/initiate', $payload);
    if (!$result['ok'] || !is_array($result['body'])) {
        return [
            'ok' => false,
            'checkout_url' => '',
            'invoice_id' => $invoiceId,
            'error' => $result['error'] ?: 'Could not start Hubtel checkout.',
        ];
    }

    $data = $result['body']['Data'] ?? [];
    $checkoutUrl = trim((string) ($data['CheckoutUrl'] ?? ''));
    if ($checkoutUrl === '') {
        return [
            'ok' => false,
            'checkout_url' => '',
            'invoice_id' => $invoiceId,
            'error' => 'Hubtel did not return a checkout URL.',
        ];
    }

    return [
        'ok' => true,
        'checkout_url' => $checkoutUrl,
        'invoice_id' => $invoiceId,
        'error' => '',
    ];
}

/**
 * @return array{ok: bool, status: string, body: array<string, mixed>|null, error: string}
 */
function hgay_hubtel_transaction_status(string $invoiceId): array
{
    $result = hgay_hubtel_api('POST', '/transaction/status', ['InvoiceId' => $invoiceId]);
    if (!is_array($result['body'])) {
        return ['ok' => false, 'status' => '', 'body' => null, 'error' => $result['error']];
    }

    $data = $result['body']['Data'] ?? [];
    $status = trim((string) ($data['Status'] ?? ''));

    return [
        'ok' => $result['ok'],
        'status' => $status,
        'body' => is_array($data) ? $data : null,
        'error' => $result['error'],
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, order_id: int, updated: bool, error: string}
 */
function hgay_hubtel_handle_callback(PDO $pdo, array $payload): array
{
    require_once __DIR__ . '/order-payment.php';

    $invoiceId = trim((string) ($payload['InvoiceId'] ?? $payload['ClientReference'] ?? ''));
    $orderId = hgay_hubtel_order_id_from_invoice($invoiceId);
    if ($orderId === null && isset($payload['ClientReference'])) {
        $orderId = hgay_hubtel_order_id_from_invoice((string) $payload['ClientReference']);
    }
    if ($orderId === null) {
        return ['ok' => false, 'order_id' => 0, 'updated' => false, 'error' => 'Unknown invoice reference.'];
    }

    $status = strtolower(trim((string) ($payload['Status'] ?? '')));
    if ($status !== 'completed' && $status !== 'success') {
        return ['ok' => true, 'order_id' => $orderId, 'updated' => false, 'error' => ''];
    }

    $transactionId = trim((string) ($payload['TransactionId'] ?? $invoiceId));
    $amountGhs = isset($payload['Amount']) ? (float) $payload['Amount'] : null;
    $mark = hgay_order_mark_paid($pdo, $orderId, $transactionId, $amountGhs);

    return [
        'ok' => true,
        'order_id' => $orderId,
        'updated' => $mark['updated'],
        'error' => '',
    ];
}
