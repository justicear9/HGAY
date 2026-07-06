<?php
/**
 * Copy to config/hubtel.php on the server (not in Git).
 *
 * Programmable API Keys + Collection Account Number:
 * https://developers.hubtel.com/docs/business/getting_started/programmable_keys
 */
return [
    /** Programmable API ID */
    'client_id' => 'your_api_id',
    /** Programmable API Key */
    'client_secret' => 'your_api_key',
    /** Collection Account Number (mandatory for Online Checkout) */
    'merchant_account' => 'your_collection_account_number',
];
