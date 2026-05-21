<?php
/**
 * XML sitemap for public pages (clean URLs).
 * Served as /sitemap.xml via .htaccess.
 */
declare(strict_types=1);

require_once __DIR__ . '/lib/paths.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex'); // sitemap file itself should not rank

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$scheme = $https ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = hgay_base_path();
$origin = rtrim($scheme . '://' . $host . ($base === '' ? '' : $base), '/');

/** @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}> */
function hgay_sitemap_entries(string $origin): array
{
    $root = __DIR__;
    $pages = [
        ['path' => '', 'file' => 'index.html', 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['path' => 'gallery', 'file' => 'gallery.html', 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['path' => 'fact-check', 'file' => 'fact-check.html', 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['path' => 'events', 'file' => 'events.html', 'changefreq' => 'daily', 'priority' => '0.8'],
    ];

    $entries = [];
    foreach ($pages as $page) {
        $file = $root . DIRECTORY_SEPARATOR . $page['file'];
        $lastmod = is_file($file)
            ? gmdate('Y-m-d', (int) filemtime($file))
            : gmdate('Y-m-d');
        $loc = $page['path'] === ''
            ? $origin . '/'
            : $origin . '/' . $page['path'];
        $entries[] = [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $page['changefreq'],
            'priority' => $page['priority'],
        ];
    }

    return $entries;
}

$entries = hgay_sitemap_entries($origin);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($entries as $entry) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($entry['loc'], ENT_XML1) . "</loc>\n";
    echo '    <lastmod>' . $entry['lastmod'] . "</lastmod>\n";
    echo '    <changefreq>' . $entry['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $entry['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
