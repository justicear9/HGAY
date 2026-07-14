<?php
/**
 * Shared order payment completion (Hubtel, Paystack, etc.).
 */
declare(strict_types=1);

require_once __DIR__ . '/order-mail.php';

function hgay_orders_has_email_sent_column(PDO $pdo): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }

    try {
        $pdo->query('SELECT confirmation_email_sent_at FROM orders LIMIT 0');
        $has = true;
    } catch (Throwable $e) {
        $has = false;
    }

    return $has;
}

/**
 * Send confirmation email once for a paid/confirmed order.
 *
 * @return array{ok: bool, sent: bool, error: string}
 */
function hgay_order_ensure_confirmation_email(PDO $pdo, int $orderId, bool $force = false): array
{
    $cols = 'id, name, email, quantity, amount_pesewas, currency, paystack_reference, status';
    if (hgay_orders_has_email_sent_column($pdo)) {
        $cols .= ', confirmation_email_sent_at';
    }

    $stmt = $pdo->prepare("SELECT {$cols} FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($order)) {
        return ['ok' => false, 'sent' => false, 'error' => 'Order not found.'];
    }

    $status = (string) ($order['status'] ?? '');
    if (!in_array($status, ['paid', 'confirmed'], true)) {
        return ['ok' => false, 'sent' => false, 'error' => 'Order is not confirmed/paid.'];
    }

    if (!$force && hgay_orders_has_email_sent_column($pdo) && !empty($order['confirmation_email_sent_at'])) {
        return ['ok' => true, 'sent' => false, 'error' => ''];
    }

    $order['order_id'] = (int) $order['id'];
    $order['payment_mode'] = $status === 'confirmed' ? 'pay_on_delivery' : 'hubtel';
    $mail = hgay_send_order_confirmation_email($order);

    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0700, true);
    }
    if (is_dir($logDir)) {
        @file_put_contents(
            $logDir . '/order-emails.log',
            gmdate('c') . ' order=' . $orderId
                . ' to=' . (string) ($order['email'] ?? '')
                . ' ok=' . ($mail['ok'] ? '1' : '0')
                . ' error=' . ($mail['error'] ?? '')
                . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    if (!$mail['ok']) {
        return ['ok' => false, 'sent' => false, 'error' => $mail['error']];
    }

    if (hgay_orders_has_email_sent_column($pdo)) {
        $upd = $pdo->prepare('UPDATE orders SET confirmation_email_sent_at = NOW(), updated_at = NOW() WHERE id = ?');
        $upd->execute([$orderId]);
    }

    return ['ok' => true, 'sent' => true, 'error' => ''];
}

/**
 * Mark a pending order as paid (idempotent) and send confirmation email once.
 *
 * @return array{updated: bool, order: array<string, mixed>|null, email_sent: bool}
 */
function hgay_order_mark_paid(PDO $pdo, int $orderId, string $paymentRef, ?float $amountGhs = null): array
{
    $stmt = $pdo->prepare('SELECT id, status, amount_pesewas FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        return ['updated' => false, 'order' => null, 'email_sent' => false];
    }

    if (($existing['status'] ?? '') === 'paid') {
        $email = hgay_order_ensure_confirmation_email($pdo, $orderId);
        $stmt = $pdo->prepare('SELECT id, name, email, quantity, amount_pesewas, currency, paystack_reference FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'updated' => false,
            'order' => is_array($order) ? $order : null,
            'email_sent' => $email['sent'],
        ];
    }

    $expectedPesewas = (int) $existing['amount_pesewas'];
    if ($amountGhs === null) {
        error_log('hgay_order_mark_paid: amount required for order ' . $orderId);
        return ['updated' => false, 'order' => null, 'email_sent' => false];
    }

    $paidPesewas = (int) round($amountGhs * 100);
    if ($paidPesewas < 1 || $paidPesewas !== $expectedPesewas) {
        error_log('hgay_order_mark_paid: amount mismatch for order ' . $orderId);
        return ['updated' => false, 'order' => null, 'email_sent' => false];
    }

    $stmt = $pdo->prepare("
        UPDATE orders
        SET status = 'paid', paystack_reference = :ref, updated_at = NOW()
        WHERE id = :id AND status = 'pending'
    ");
    $stmt->execute([':ref' => $paymentRef, ':id' => $orderId]);
    if ($stmt->rowCount() < 1) {
        // Another request may have just marked it paid — still ensure email.
        $email = hgay_order_ensure_confirmation_email($pdo, $orderId);
        $stmt = $pdo->prepare('SELECT id, name, email, quantity, amount_pesewas, currency, paystack_reference, status FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        $alreadyPaid = is_array($order) && ($order['status'] ?? '') === 'paid';

        return [
            'updated' => false,
            'order' => is_array($order) ? $order : null,
            'email_sent' => $alreadyPaid ? $email['sent'] : false,
        ];
    }

    $email = hgay_order_ensure_confirmation_email($pdo, $orderId);

    $stmt = $pdo->prepare('SELECT id, name, email, quantity, amount_pesewas, currency, paystack_reference FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'updated' => true,
        'order' => is_array($order) ? $order : null,
        'email_sent' => $email['sent'] || $email['ok'],
    ];
}
