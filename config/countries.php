<?php
/**
 * Country dial codes, names, and list of countries that use postcodes.
 * Backend uses this to format phone_full and to know when to require postcode.
 */
$dial_codes = [
  'GH' => '+233', 'NG' => '+234', 'KE' => '+254', 'ZA' => '+27', 'EG' => '+20',
  'US' => '+1', 'CA' => '+1', 'GB' => '+44', 'IE' => '+353', 'DE' => '+49',
  'FR' => '+33', 'IT' => '+39', 'ES' => '+34', 'NL' => '+31', 'BE' => '+32',
  'CH' => '+41', 'AT' => '+43', 'PL' => '+48', 'PT' => '+351', 'SE' => '+46',
  'NO' => '+47', 'DK' => '+45', 'FI' => '+358', 'AU' => '+61', 'NZ' => '+64',
  'JP' => '+81', 'CN' => '+86', 'IN' => '+91', 'PK' => '+92', 'BD' => '+880',
  'SG' => '+65', 'MY' => '+60', 'PH' => '+63', 'TH' => '+66', 'VN' => '+84',
  'BR' => '+55', 'MX' => '+52', 'AR' => '+54', 'CL' => '+56', 'CO' => '+57',
  'RU' => '+7', 'UA' => '+380', 'TR' => '+90', 'SA' => '+966', 'AE' => '+971',
  'IL' => '+972', 'ET' => '+251', 'TZ' => '+255', 'UG' => '+256', 'SN' => '+221',
  'CI' => '+225', 'CM' => '+237', 'MA' => '+212', 'TN' => '+216', 'LY' => '+218',
];
$country_names = [
  'GH' => 'Ghana', 'NG' => 'Nigeria', 'KE' => 'Kenya', 'ZA' => 'South Africa', 'EG' => 'Egypt',
  'US' => 'United States', 'CA' => 'Canada', 'GB' => 'United Kingdom', 'IE' => 'Ireland', 'DE' => 'Germany',
  'FR' => 'France', 'IT' => 'Italy', 'ES' => 'Spain', 'NL' => 'Netherlands', 'BE' => 'Belgium',
  'CH' => 'Switzerland', 'AT' => 'Austria', 'PL' => 'Poland', 'PT' => 'Portugal', 'SE' => 'Sweden',
  'NO' => 'Norway', 'DK' => 'Denmark', 'FI' => 'Finland', 'AU' => 'Australia', 'NZ' => 'New Zealand',
  'JP' => 'Japan', 'CN' => 'China', 'IN' => 'India', 'PK' => 'Pakistan', 'BD' => 'Bangladesh',
  'SG' => 'Singapore', 'MY' => 'Malaysia', 'PH' => 'Philippines', 'TH' => 'Thailand', 'VN' => 'Vietnam',
  'BR' => 'Brazil', 'MX' => 'Mexico', 'AR' => 'Argentina', 'CL' => 'Chile', 'CO' => 'Colombia',
  'RU' => 'Russia', 'UA' => 'Ukraine', 'TR' => 'Turkey', 'SA' => 'Saudi Arabia', 'AE' => 'UAE',
  'IL' => 'Israel', 'ET' => 'Ethiopia', 'TZ' => 'Tanzania', 'UG' => 'Uganda', 'SN' => 'Senegal',
  'CI' => 'Côte d\'Ivoire', 'CM' => 'Cameroon', 'MA' => 'Morocco', 'TN' => 'Tunisia', 'LY' => 'Libya',
];
$postcode_countries = ['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'NL', 'BE', 'CH', 'AT', 'PL', 'PT', 'SE', 'NO', 'DK', 'FI', 'ES', 'IT', 'IE', 'NZ', 'JP', 'IN', 'BR', 'MX', 'AR', 'RU', 'CN', 'ZA', 'NG', 'KE', 'GH'];

/**
 * National number length (digits only, without country code): [min, max] per country.
 * Used for validation so we accept only correct lengths.
 */
$phone_lengths = [
  'GH' => [9, 9],   'NG' => [10, 11], 'KE' => [9, 9],   'ZA' => [9, 9],   'EG' => [9, 10],
  'US' => [10, 10], 'CA' => [10, 10], 'GB' => [10, 11], 'IE' => [9, 9],   'DE' => [10, 11],
  'FR' => [9, 9],   'IT' => [9, 10],  'ES' => [9, 9],   'NL' => [9, 9],   'BE' => [8, 9],
  'CH' => [9, 9],   'AT' => [10, 13], 'PL' => [9, 9],   'PT' => [9, 9],   'SE' => [7, 9],
  'NO' => [8, 8],   'DK' => [8, 8],   'FI' => [9, 10],  'AU' => [9, 9],   'NZ' => [9, 10],
  'JP' => [10, 10], 'CN' => [11, 11], 'IN' => [10, 10], 'PK' => [10, 10], 'BD' => [10, 10],
  'SG' => [8, 8],   'MY' => [9, 10],  'PH' => [10, 10], 'TH' => [9, 9],   'VN' => [9, 10],
  'BR' => [10, 11], 'MX' => [10, 10], 'AR' => [10, 11], 'CL' => [9, 9],   'CO' => [10, 10],
  'RU' => [10, 10], 'UA' => [9, 9],   'TR' => [10, 10], 'SA' => [9, 9],   'AE' => [9, 9],
  'IL' => [9, 9],   'ET' => [9, 9],   'TZ' => [9, 9],   'UG' => [9, 9],   'SN' => [9, 9],
  'CI' => [10, 10], 'CM' => [9, 9],   'MA' => [9, 9],   'TN' => [8, 8],   'LY' => [9, 10],
];

return [
  'dial_codes' => $dial_codes,
  'country_names' => $country_names,
  'postcode_countries' => $postcode_countries,
  'phone_lengths' => $phone_lengths,
];
