<?php
/**
 * Hubtel payment webhook — called when a checkout completes.
 * Payment is only confirmed via Hubtel status API (never trust body alone).
 */
declare(strict_types=1);

header('Content-Type: application/json');
header('X-Robots-Tag: noindex, nofollow');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/lib/hubtel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0700, true);
}
if (is_dir($logDir) && is_string($raw) && $raw !== '') {
    @file_put_contents(
        $logDir . '/hubtel-callbacks.log',
        gmdate('c') . ' ' . $raw . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

try {
    $pdo = dbConnection();
    $result = hgay_hubtel_handle_callback($pdo, $payload);
    if (!$result['ok']) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Could not process webhook']);
        exit;
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'OK']);
} catch (Throwable $e) {
    error_log('hubtel_callback: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
