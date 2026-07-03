<?php
/**
 * Checkout mode: hubtel | pay_on_delivery | paystack (legacy)
 */
declare(strict_types=1);

function hgay_payment_mode(): string
{
    static $mode = null;
    if ($mode !== null) {
        return $mode;
    }

    $path = dirname(__DIR__) . '/config/payment.php';
    $configured = 'pay_on_delivery';
    if (is_file($path)) {
        $cfg = require $path;
        if (is_array($cfg) && isset($cfg['mode'])) {
            $configured = (string) $cfg['mode'];
        }
    }

    $mode = in_array($configured, ['hubtel', 'paystack', 'pay_on_delivery'], true)
        ? $configured
        : 'pay_on_delivery';

    return $mode;
}

function hgay_payment_mode_is_online(): bool
{
    return in_array(hgay_payment_mode(), ['hubtel', 'paystack'], true);
}

function hgay_payment_mode_is_hubtel(): bool
{
    return hgay_payment_mode() === 'hubtel';
}

function hgay_payment_mode_is_paystack(): bool
{
    return hgay_payment_mode() === 'paystack';
}

function hgay_payment_provider_label(): string
{
    switch (hgay_payment_mode()) {
        case 'hubtel':
            return 'Hubtel';
        case 'paystack':
            return 'Paystack';
        case 'pay_on_delivery':
            return 'Pay on delivery';
        default:
            $mode = hgay_payment_mode();
            throw new UnexpectedValueException('Unknown payment mode: ' . $mode);
    }
}
