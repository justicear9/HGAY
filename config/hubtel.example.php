<?php
/**
 * Copy to config/hubtel.php on the server (not in Git).
 *
 * Get credentials from Hubtel merchant dashboard → API accounts → HTTP Rest API.
 * https://unity.hubtel.com/merchantaccount/dashboard
 */
return [
    'client_id' => 'your_client_id',
    'client_secret' => 'your_client_secret',
    /** Optional: merchant account number shown in Hubtel dashboard */
    'merchant_account' => '',
];
