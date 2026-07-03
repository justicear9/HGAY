<?php
/**
 * Copy to config/payment.php on the server.
 *
 * pay_on_delivery — customer places order; pay when delivered.
 * hubtel          — online checkout via Hubtel (MoMo, cards, GHQR). Requires config/hubtel.php.
 * paystack        — legacy Paystack checkout (requires paystack_config.php).
 */
return [
    'mode' => 'hubtel',
];
