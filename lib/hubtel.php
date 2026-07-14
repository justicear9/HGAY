<?php
/**
 * Hubtel Online Checkout (redirect + webhook).
 * API: https://developers.hubtel.com/docs/business/api_documentation/payment_apis/online_checkout
 */
declare(strict_types=1);

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/security.php';

const HGAY_HUBTEL_CHECKOUT_API = 'https://payproxyapi.hubtel.com';
const HGAY_HUBTEL_STATUS_API = 'https://api-txnstatus.hubtel.com';

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

/** @return array{client_id: string, client_secret: string, merchant_account: string} */
function hgay_hubtel_credentials(): array
{
    $cfg = hgay_hubtel_config();
    $clientId = trim((string) ($cfg['client_id'] ?? $cfg['api_id'] ?? ''));
    $clientSecret = trim((string) ($cfg['client_secret'] ?? $cfg['api_key'] ?? ''));
    $merchantAccount = trim((string) (
        $cfg['merchant_account']
        ?? $cfg['merchant_account_number']
        ?? $cfg['collection_account_number']
        ?? ''
    ));

    return [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'merchant_account' => $merchantAccount,
    ];
}

function hgay_hubtel_is_configured(): bool
{
    $creds = hgay_hubtel_credentials();

    return $creds['client_id'] !== ''
        && $creds['client_secret'] !== ''
        && $creds['merchant_account'] !== '';
}

function hgay_hubtel_basic_auth_header(): string
{
    $creds = hgay_hubtel_credentials();
    $credentials = base64_encode($creds['client_id'] . ':' . $creds['client_secret']);

    return 'Basic ' . $credentials;
}

function hgay_hubtel_client_reference(int $orderId): string
{
    return 'HGAY-' . $orderId;
}

/**
 * Access token for verify return URL (requires lib/security.php).
 *
 * @param array<string, mixed> $order
 */
function hgay_hubtel_order_return_token(array $order): string
{
    $orderId = (int) ($order['id'] ?? $order['order_id'] ?? 0);
    $email = trim((string) ($order['email'] ?? ''));

    return hgay_order_access_token($orderId, $email);
}

function hgay_hubtel_order_id_from_reference(string $reference): ?int
{
    if (preg_match('/^HGAY-(\d+)$/', trim($reference), $m)) {
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
 * @param array<string, mixed>|null $payload
 */
function hgay_hubtel_response_code(?array $payload): string
{
    if ($payload === null) {
        return '';
    }

    return (string) ($payload['responseCode'] ?? $payload['ResponseCode'] ?? '');
}

function hgay_hubtel_response_ok(?array $payload): bool
{
    $code = hgay_hubtel_response_code($payload);

    return $code === '0000' || $code === '00';
}

/**
 * @param array<string, mixed>|null $payload
 * @return array<string, mixed>
 */
function hgay_hubtel_response_data(?array $payload): array
{
    if ($payload === null) {
        return [];
    }

    $data = $payload['data'] ?? $payload['Data'] ?? [];

    return is_array($data) ? $data : [];
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, http_code: int, body: array<string, mixed>|null, error: string}
 */
function hgay_hubtel_api(string $method, string $baseUrl, string $path, array $body = []): array
{
    if (!hgay_hubtel_is_configured()) {
        return [
            'ok' => false,
            'http_code' => 0,
            'body' => null,
            'error' => 'Hubtel is not configured. Set client_id, client_secret, and merchant_account in config/hubtel.php.',
        ];
    }

    $url = rtrim($baseUrl, '/') . $path;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . hgay_hubtel_basic_auth_header(),
        'Accept: application/json',
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
    $ok = $httpCode >= 200 && $httpCode < 300 && hgay_hubtel_response_ok($payload);

    $error = '';
    if (!$ok) {
        if ($httpCode === 401) {
            $error = 'Hubtel rejected the API credentials (401). Confirm Programmable API ID/Key and Collection Account Number.';
        } elseif ($payload !== null) {
            $message = (string) ($payload['message'] ?? $payload['Message'] ?? $payload['responseMessage'] ?? $payload['ResponseMessage'] ?? '');
            $error = $message !== '' ? $message : 'Hubtel API error.';
        } else {
            $error = 'Hubtel API error (HTTP ' . $httpCode . ').';
        }
    }

    return [
        'ok' => $ok,
        'http_code' => $httpCode,
        'body' => $payload,
        'error' => $error,
    ];
}

/**
 * @param array<string, mixed> $order
 * @return array{ok: bool, checkout_url: string, client_reference: string, error: string}
 */
function hgay_hubtel_initiate_checkout(array $order): array
{
    $orderId = (int) ($order['id'] ?? $order['order_id'] ?? 0);
    $amountPesewas = (int) ($order['amount_pesewas'] ?? 0);
    if ($orderId < 1 || $amountPesewas < 1) {
        return ['ok' => false, 'checkout_url' => '', 'client_reference' => '', 'error' => 'Invalid order for Hubtel checkout.'];
    }

    $creds = hgay_hubtel_credentials();
    $clientReference = hgay_hubtel_client_reference($orderId);
    $amountGhs = round($amountPesewas / 100, 2);
    $name = trim((string) ($order['name'] ?? 'Customer'));
    $email = trim((string) ($order['email'] ?? ''));
    $phone = hgay_hubtel_format_msisdn((string) ($order['phone_full'] ?? ''));
    $quantity = (int) ($order['quantity'] ?? 1);

    $payload = [
        'totalAmount' => $amountGhs,
        'description' => 'How Ghanaian Are You — ' . $quantity . ' game' . ($quantity > 1 ? 's' : ''),
        'callbackUrl' => hgay_email_absolute_url('api/hubtel_callback'),
        'returnUrl' => hgay_email_absolute_url(
            'verify?order=' . $orderId . '&token=' . rawurlencode(hgay_hubtel_order_return_token($order))
        ),
        'cancellationUrl' => hgay_email_absolute_url('/#place-order'),
        'merchantAccountNumber' => $creds['merchant_account'],
        'clientReference' => $clientReference,
        'payeeName' => $name,
    ];
    if ($email !== '') {
        $payload['payeeEmail'] = $email;
    }
    if ($phone !== '') {
        $payload['payeeMobileNumber'] = $phone;
    }

    $result = hgay_hubtel_api('POST', HGAY_HUBTEL_CHECKOUT_API, '/items/initiate', $payload);
    if (!$result['ok'] || !is_array($result['body'])) {
        return [
            'ok' => false,
            'checkout_url' => '',
            'client_reference' => $clientReference,
            'error' => $result['error'] ?: 'Could not start Hubtel checkout.',
        ];
    }

    $data = hgay_hubtel_response_data($result['body']);
    $checkoutUrl = trim((string) ($data['checkoutUrl'] ?? $data['CheckoutUrl'] ?? ''));
    if ($checkoutUrl === '') {
        return [
            'ok' => false,
            'checkout_url' => '',
            'client_reference' => $clientReference,
            'error' => 'Hubtel did not return a checkout URL.',
        ];
    }

    return [
        'ok' => true,
        'checkout_url' => $checkoutUrl,
        'client_reference' => $clientReference,
        'error' => '',
    ];
}

/**
 * @return array{ok: bool, status: string, body: array<string, mixed>|null, error: string}
 */
function hgay_hubtel_transaction_status(string $clientReference): array
{
    $creds = hgay_hubtel_credentials();
    $path = '/transactions/' . rawurlencode($creds['merchant_account'])
        . '/status?clientReference=' . rawurlencode($clientReference);

    $result = hgay_hubtel_api('GET', HGAY_HUBTEL_STATUS_API, $path);
    if (!is_array($result['body'])) {
        return ['ok' => false, 'status' => '', 'body' => null, 'error' => $result['error']];
    }

    $data = hgay_hubtel_response_data($result['body']);
    if ($data === [] && isset($result['body']['status'])) {
        $data = $result['body'];
    }

    $status = trim((string) ($data['status'] ?? $data['Status'] ?? $result['body']['status'] ?? $result['body']['Status'] ?? ''));

    return [
        'ok' => $result['ok'] || $status !== '',
        'status' => $status,
        'body' => $data !== [] ? $data : $result['body'],
        'error' => $result['error'],
    ];
}

function hgay_hubtel_status_is_paid(string $status): bool
{
    $status = strtolower(trim($status));

    // Callback uses Success/Completed; Transaction Status Check uses Paid.
    return $status === 'success' || $status === 'completed' || $status === 'paid';
}

/**
 * Extract paid amount (GHS) from Hubtel status/callback payload.
 */
function hgay_hubtel_amount_ghs_from_body(?array $body): ?float
{
    if ($body === null || $body === []) {
        return null;
    }

    if (isset($body['amount'])) {
        return (float) $body['amount'];
    }
    if (isset($body['Amount'])) {
        return (float) $body['Amount'];
    }
    if (isset($body['totalAmount'])) {
        return (float) $body['totalAmount'];
    }
    if (isset($body['TotalAmount'])) {
        return (float) $body['TotalAmount'];
    }

    return null;
}

/**
 * Confirm payment with Hubtel status API before marking an order paid.
 *
 * @return array{ok: bool, updated: bool, error: string}
 */
function hgay_hubtel_confirm_paid_order(PDO $pdo, int $orderId, string $clientReference): array
{
    require_once __DIR__ . '/order-payment.php';

    $stmt = $pdo->prepare('SELECT id, status, amount_pesewas FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($order)) {
        return ['ok' => false, 'updated' => false, 'error' => 'Order not found.'];
    }

    if (($order['status'] ?? '') === 'paid') {
        return ['ok' => true, 'updated' => false, 'error' => ''];
    }

    if (($order['status'] ?? '') !== 'pending') {
        return ['ok' => false, 'updated' => false, 'error' => 'Order is not awaiting payment.'];
    }

    // Always look up by merchant clientReference (HGAY-{id}), never by Hubtel checkoutId.
    $lookupRef = hgay_hubtel_client_reference($orderId);
    if ($clientReference !== '' && hgay_hubtel_order_id_from_reference($clientReference) === $orderId) {
        $lookupRef = $clientReference;
    }

    $statusResult = hgay_hubtel_transaction_status($lookupRef);
    if (!hgay_hubtel_status_is_paid($statusResult['status'])) {
        return ['ok' => true, 'updated' => false, 'error' => ''];
    }

    $body = is_array($statusResult['body']) ? $statusResult['body'] : [];
    $amountGhs = hgay_hubtel_amount_ghs_from_body($body);
    if ($amountGhs === null) {
        $amountGhs = ((int) $order['amount_pesewas']) / 100;
    }

    // Persist clientReference so future status checks keep working.
    $mark = hgay_order_mark_paid($pdo, $orderId, $lookupRef, $amountGhs);
    if (!$mark['updated'] && ($order['status'] ?? '') !== 'paid') {
        return ['ok' => false, 'updated' => false, 'error' => 'Payment could not be confirmed.'];
    }

    return ['ok' => true, 'updated' => $mark['updated'], 'error' => ''];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, order_id: int, updated: bool, error: string}
 */
function hgay_hubtel_handle_callback(PDO $pdo, array $payload): array
{
    $data = hgay_hubtel_response_data($payload);
    $clientReference = trim((string) (
        $data['clientReference']
        ?? $data['ClientReference']
        ?? $payload['clientReference']
        ?? $payload['ClientReference']
        ?? ''
    ));

    $orderId = hgay_hubtel_order_id_from_reference($clientReference);
    if ($orderId === null) {
        return ['ok' => false, 'order_id' => 0, 'updated' => false, 'error' => 'Unknown client reference.'];
    }

    $confirm = hgay_hubtel_confirm_paid_order($pdo, $orderId, $clientReference);

    return [
        'ok' => $confirm['ok'],
        'order_id' => $orderId,
        'updated' => $confirm['updated'],
        'error' => $confirm['error'],
    ];
}
