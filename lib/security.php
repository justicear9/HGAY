<?php
/**
 * CSRF, rate limiting, order access tokens, and security headers.
 */
declare(strict_types=1);

require_once __DIR__ . '/session.php';

/** Application secret for HMAC tokens (set app_secret in config/site.php). */
function hgay_app_secret(): string
{
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }

    $path = dirname(__DIR__) . '/config/site.php';
    if (is_file($path)) {
        $cfg = require $path;
        if (is_array($cfg)) {
            $fromConfig = trim((string) ($cfg['app_secret'] ?? ''));
            if (strlen($fromConfig) >= 32) {
                $secret = $fromConfig;
                return $secret;
            }
        }
    }

    // Fallback: derive from Hubtel credentials so existing installs work without site.php change.
    $hubtelPath = dirname(__DIR__) . '/config/hubtel.php';
    if (is_file($hubtelPath)) {
        $hubtel = require $hubtelPath;
        if (is_array($hubtel)) {
            $id = (string) ($hubtel['client_id'] ?? $hubtel['api_id'] ?? '');
            $key = (string) ($hubtel['client_secret'] ?? $hubtel['api_key'] ?? '');
            if ($id !== '' && $key !== '') {
                $secret = hash('sha256', 'hgay|' . $id . '|' . $key);
                return $secret;
            }
        }
    }

    $secret = hash('sha256', 'hgay-insecure-fallback-change-app-secret');
    return $secret;
}

function hgay_security_send_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function hgay_csrf_token(): string
{
    hgay_session_start();
    if (empty($_SESSION['hgay_csrf_token']) || !is_string($_SESSION['hgay_csrf_token'])) {
        $_SESSION['hgay_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['hgay_csrf_token'];
}

function hgay_csrf_verify(?string $token): bool
{
    hgay_session_start();
    $expected = $_SESSION['hgay_csrf_token'] ?? '';
    if (!is_string($expected) || $expected === '' || $token === null || $token === '') {
        return false;
    }

    return hash_equals($expected, $token);
}

function hgay_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(hgay_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Reject cross-site POSTs when Origin/Referer are present and don't match this host.
 */
function hgay_same_origin_post(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return true;
    }

    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        if (is_string($originHost) && strtolower($originHost) !== $host) {
            return false;
        }
        return true;
    }

    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if ($referer !== '') {
        $refererHost = parse_url($referer, PHP_URL_HOST);
        if (is_string($refererHost) && strtolower($refererHost) !== $host) {
            return false;
        }
    }

    return true;
}

/**
 * File-based rate limiter. Returns true if the action is allowed.
 */
function hgay_rate_limit(string $bucket, int $maxAttempts, int $windowSeconds): bool
{
    $ip = hgay_client_ip();
    $key = preg_replace('/[^a-z0-9_-]/i', '_', $bucket) . '_' . md5($ip);
    $dir = sys_get_temp_dir() . '/hgay_rate';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return true;
    }

    $file = $dir . '/' . $key;
    $now = time();
    $attempts = [];

    if (is_file($file)) {
        $raw = @file_get_contents($file);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $attempts = $decoded;
            }
        }
    }

    $cutoff = $now - $windowSeconds;
    $attempts = array_values(array_filter($attempts, static fn ($t) => is_int($t) && $t > $cutoff));

    if (count($attempts) >= $maxAttempts) {
        return false;
    }

    $attempts[] = $now;
    @file_put_contents($file, json_encode($attempts), LOCK_EX);

    return true;
}

function hgay_client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidate = trim($parts[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    }

    return $ip;
}

/** Signed token so only the customer can view order confirmation details. */
function hgay_order_access_token(int $orderId, string $email): string
{
    $payload = $orderId . '|' . strtolower(trim($email));

    return hash_hmac('sha256', $payload, hgay_app_secret());
}

function hgay_order_token_valid(int $orderId, string $email, ?string $token): bool
{
    if ($token === null || $token === '') {
        return false;
    }

    return hash_equals(hgay_order_access_token($orderId, $email), $token);
}
