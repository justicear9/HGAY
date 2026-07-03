<?php
/**
 * Order confirmation emails.
 */
declare(strict_types=1);

require_once __DIR__ . '/mail.php';

/**
 * @param array<string, mixed> $order
 * @return array{ok: bool, error: string}
 */
function hgay_send_order_confirmation_email(array $order): array
{
    if (!hgay_mail_is_configured()) {
        $msg = 'Order email not sent: create config/mail.php from config/mail.example.php with real SMTP credentials.';
        error_log($msg);
        return ['ok' => false, 'error' => $msg];
    }

    $email = trim((string) ($order['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid customer email on order.'];
    }

    ob_start();
    include dirname(__DIR__) . '/templates/order-confirmation-email.php';
    $html = ob_get_clean();
    if ($html === false || $html === '') {
        return ['ok' => false, 'error' => 'Could not build order confirmation email HTML.'];
    }

    $result = sendSmtpMail($email, 'Order confirmed — How Ghanaian Are You?', $html);
    if (!$result['ok']) {
        error_log('Order email to ' . $email . ' failed: ' . $result['error']);
    }
    return $result;
}

/**
 * @return array{ok: bool, error: string}
 */
function hgay_send_test_email(string $to): array
{
    if (!hgay_mail_is_configured()) {
        return ['ok' => false, 'error' => 'config/mail.php is missing or not configured.'];
    }

    $order = [
        'name' => 'Test Customer',
        'email' => $to,
        'quantity' => 1,
        'amount_pesewas' => 10000,
        'currency' => 'GHS',
        'paystack_reference' => '',
        'payment_mode' => 'pay_on_delivery',
        'order_id' => 0,
    ];

    return hgay_send_order_confirmation_email($order);
}
