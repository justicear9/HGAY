<?php
/**
 * Optional site URL override for canonical links, Open Graph, and sitemap.
 * Copy to config/site.php and set your production HTTPS origin (no trailing slash).
 *
 * Example: 'canonical_origin' => 'https://howghanaianareyou.com',
 *
 * Used for canonical links, Open Graph, and order confirmation email image URLs.
 */
return [
    'canonical_origin' => '',
    // Random 32+ char secret for order access tokens. Generate: bin2hex(random_bytes(32))
    'app_secret' => '',
];
