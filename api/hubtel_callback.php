<?php
/**
 * Hubtel payment webhook — called when a checkout completes.
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

try {
    $pdo = dbConnection();
    $result = hgay_hubtel_handle_callback($pdo, $payload);
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook received',
        'order_id' => $result['order_id'],
        'updated' => $result['updated'],
    ]);
} catch (Throwable $e) {
    error_log('hubtel_callback: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
