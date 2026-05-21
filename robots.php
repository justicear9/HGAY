<?php
/**
 * Dynamic robots.txt with absolute sitemap URL.
 */
declare(strict_types=1);

require_once __DIR__ . '/lib/seo.php';

header('Content-Type: text/plain; charset=UTF-8');

$origin = hgay_canonical_origin();

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /api/\n";
echo "Disallow: /config/\n";
echo "Disallow: /uploads/\n";
echo "Disallow: /create_order\n";
echo "Disallow: /verify\n";
echo "Disallow: /partials/\n";
echo "Disallow: /lib/\n";
echo "\n";
echo 'Sitemap: ' . $origin . "/sitemap.xml\n";
