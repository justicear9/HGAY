<?php
/**
 * Returns country list for phone (code, name, dial) and delivery (code, name, has_postcode).
 */
header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');
$countries = require dirname(__DIR__) . '/config/countries.php';
$dial = $countries['dial_codes'];
$names = $countries['country_names'];
$postcode = array_flip($countries['postcode_countries']);
$lengths = $countries['phone_lengths'] ?? [];
$phone = [];
$delivery = [];
foreach (array_keys($dial) as $code) {
  $len = $lengths[$code] ?? [6, 15];
  $phone[] = ['code' => $code, 'name' => $names[$code] ?? $code, 'dial' => $dial[$code], 'min_digits' => (int)$len[0], 'max_digits' => (int)$len[1]];
  $delivery[] = ['code' => $code, 'name' => $names[$code] ?? $code, 'has_postcode' => isset($postcode[$code])];
}
usort($phone, fn($a, $b) => strcasecmp($a['name'], $b['name']));
usort($delivery, fn($a, $b) => strcasecmp($a['name'], $b['name']));
echo json_encode(['phone_countries' => $phone, 'delivery_countries' => $delivery]);
