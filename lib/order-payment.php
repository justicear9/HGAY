<?php
/**
 * Shared order payment completion (Hubtel, Paystack, etc.).
 */
declare(strict_types=1);

require_once __DIR__ . '/order-mail.php';

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
        $stmt = $pdo->prepare('SELECT id, name, email, quantity, amount_pesewas, currency, paystack_reference FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        return ['updated' => false, 'order' => is_array($order) ? $order : null, 'email_sent' => false];
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
        return ['updated' => false, 'order' => null, 'email_sent' => false];
    }

    $stmt = $pdo->prepare('SELECT id, name, email, quantity, amount_pesewas, currency, paystack_reference FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($order)) {
        return ['updated' => true, 'order' => null, 'email_sent' => false];
    }

    $emailSent = false;
    if (!empty($order['email'])) {
        $order['order_id'] = (int) $order['id'];
        $order['payment_mode'] = 'hubtel';
        $mail = hgay_send_order_confirmation_email($order);
        $emailSent = $mail['ok'];
    }

    return ['updated' => true, 'order' => $order, 'email_sent' => $emailSent];
}
