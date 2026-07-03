<?php
/**
 * Technical SEO helpers: canonical origin, meta tags, JSON-LD.
 */
declare(strict_types=1);

require_once __DIR__ . '/paths.php';

/** @return array<string, mixed> */
function hgay_site_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $path = dirname(__DIR__) . '/config/site.php';
    if (is_file($path)) {
        $loaded = require $path;
        $config = is_array($loaded) ? $loaded : [];
    } else {
        $config = [];
    }
    return $config;
}

function hgay_canonical_origin(): string
{
    $override = trim((string) (hgay_site_config()['canonical_origin'] ?? ''));
    if ($override !== '') {
        return rtrim($override, '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = hgay_base_path();

    return rtrim($scheme . '://' . $host . ($base === '' ? '' : $base), '/');
}

function hgay_absolute_url(string $path = ''): string
{
    $origin = hgay_canonical_origin();
    $path = ltrim($path, '/');
    if ($path === '') {
        return $origin . '/';
    }

    return $origin . '/' . hgay_asset_path($path);
}

/**
 * Absolute URLs for HTML emails — always HTTPS production when canonical_origin is unset (CLI/cron).
 */
function hgay_email_absolute_url(string $path = ''): string
{
    $override = trim((string) (hgay_site_config()['canonical_origin'] ?? ''));
    $origin = $override !== '' ? rtrim($override, '/') : 'https://howghanaianareyou.com';
    $path = ltrim($path, '/');
    if ($path === '') {
        return $origin . '/';
    }

    return $origin . '/' . hgay_asset_path($path);
}

/** @return array<string, mixed> */
function hgay_seo_page(string $pageKey): array
{
    static $pages = null;
    if ($pages === null) {
        $pages = require dirname(__DIR__) . '/config/seo-pages.php';
    }
    if (!isset($pages[$pageKey]) || !is_array($pages[$pageKey])) {
        throw new InvalidArgumentException('Unknown SEO page: ' . $pageKey);
    }

    return $pages[$pageKey];
}

function hgay_seo_page_url(array $page): string
{
    $path = (string) ($page['path'] ?? '');
    if ($path === '') {
        return hgay_absolute_url('');
    }

    return hgay_absolute_url($path);
}

function hgay_seo_escape_attr(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * @param array<string, mixed> $page
 * @param array<string, mixed> $opts robots, extra_json_ld (array of objects)
 */
function hgay_seo_head_markup(array $page, array $opts = []): string
{
    $url = hgay_seo_page_url($page);
    $title = (string) ($page['title'] ?? 'How Ghanaian Are You?');
    $description = (string) ($page['description'] ?? '');
    $ogType = (string) ($page['og_type'] ?? 'website');
    $imagePath = (string) ($page['og_image'] ?? 'HGAY_ASSETS/Card_Pictures_and_Video/hero.png');
    $imageUrl = hgay_absolute_url($imagePath);
    $robots = (string) ($opts['robots'] ?? 'index, follow');

    $lines = [
        '<link rel="canonical" href="' . hgay_seo_escape_attr($url) . '">',
        '<meta name="robots" content="' . hgay_seo_escape_attr($robots) . '">',
    ];

    if ($description !== '') {
        $lines[] = '<meta name="description" content="' . hgay_seo_escape_attr($description) . '">';
    }

    $lines[] = '<meta property="og:locale" content="en_GH">';
    $lines[] = '<meta property="og:site_name" content="How Ghanaian Are You?">';
    $lines[] = '<meta property="og:type" content="' . hgay_seo_escape_attr($ogType) . '">';
    $lines[] = '<meta property="og:title" content="' . hgay_seo_escape_attr($title) . '">';
    $lines[] = '<meta property="og:url" content="' . hgay_seo_escape_attr($url) . '">';
    if ($description !== '') {
        $lines[] = '<meta property="og:description" content="' . hgay_seo_escape_attr($description) . '">';
    }
    $lines[] = '<meta property="og:image" content="' . hgay_seo_escape_attr($imageUrl) . '">';

    $lines[] = '<meta name="twitter:card" content="summary_large_image">';
    $lines[] = '<meta name="twitter:title" content="' . hgay_seo_escape_attr($title) . '">';
    if ($description !== '') {
        $lines[] = '<meta name="twitter:description" content="' . hgay_seo_escape_attr($description) . '">';
    }
    $lines[] = '<meta name="twitter:image" content="' . hgay_seo_escape_attr($imageUrl) . '">';

    $jsonLd = hgay_seo_json_ld_for_page($page, $opts);
    if ($jsonLd !== '') {
        $lines[] = '<script type="application/ld+json">' . $jsonLd . '</script>';
    }

    return '  ' . implode("\n  ", $lines) . "\n";
}

/**
 * @param array<string, mixed> $page
 * @param array<string, mixed> $opts
 */
function hgay_seo_json_ld_for_page(array $page, array $opts = []): string
{
    $key = $page['json_ld'] ?? null;
    $graphs = [];

    if ($key === 'home') {
        $graphs = array_merge($graphs, hgay_seo_json_ld_home_graph());
    } elseif ($key === 'events') {
        $graphs = array_merge($graphs, hgay_seo_json_ld_events_graph());
    }

    if (!empty($opts['extra_json_ld']) && is_array($opts['extra_json_ld'])) {
        $graphs = array_merge($graphs, $opts['extra_json_ld']);
    }

    if ($graphs === []) {
        return '';
    }

    $payload = count($graphs) === 1
        ? $graphs[0]
        : ['@context' => 'https://schema.org', '@graph' => $graphs];

    if (!isset($payload['@context'])) {
        $payload['@context'] = 'https://schema.org';
    }

    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return is_string($json) ? $json : '';
}

/** @return array<int, array<string, mixed>> */
function hgay_seo_json_ld_home_graph(): array
{
    $origin = hgay_canonical_origin();
    $description = 'A fun, competitive Ghanaian trivia card game with 100 cards for 4–10 players.';
    $price = 100.0;

    try {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/settings.php';
        $price = getProductPriceGhs(dbConnection());
    } catch (Throwable $e) {
    }

    return [
        [
            '@type' => 'WebSite',
            'name' => 'How Ghanaian Are You?',
            'url' => $origin . '/',
        ],
        [
            '@type' => 'Product',
            'name' => 'How Ghanaian Are You? Card Game',
            'description' => $description,
            'image' => [hgay_absolute_url('HGAY_ASSETS/Card_Pictures_and_Video/hero.png')],
            'brand' => [
                '@type' => 'Brand',
                'name' => 'How Ghanaian Are You?',
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $origin . '/#place-order',
                'priceCurrency' => 'GHS',
                'price' => (string) $price,
                'availability' => 'https://schema.org/InStock',
            ],
        ],
    ];
}

/** @return array<int, array<string, mixed>> */
function hgay_seo_json_ld_events_graph(): array
{
    $events = [];
    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = dbConnection();
        $stmt = $pdo->query("
            SELECT title, description, location, event_date, event_time
            FROM events
            WHERE is_active = 1
              AND event_date >= CURDATE()
            ORDER BY event_date ASC, event_time ASC, sort_order ASC, id ASC
            LIMIT 20
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $start = $row['event_date'];
            if (!empty($row['event_time'])) {
                $start .= 'T' . substr((string) $row['event_time'], 0, 8);
            }
            $events[] = [
                '@type' => 'Event',
                'name' => $row['title'],
                'description' => substr((string) $row['description'], 0, 300),
                'startDate' => $start,
                'location' => [
                    '@type' => 'Place',
                    'name' => $row['location'],
                ],
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'eventStatus' => 'https://schema.org/EventScheduled',
            ];
        }
    } catch (Throwable $e) {
    }

    if ($events === []) {
        return [];
    }

    return [
        [
            '@type' => 'ItemList',
            'name' => 'How Ghanaian Are You? Events',
            'itemListElement' => array_map(
                static fn (array $ev, int $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'item' => $ev,
                ],
                $events,
                array_keys($events)
            ),
        ],
    ];
}

/**
 * Inject SEO tags before </head>; skip duplicate canonical.
 *
 * @param array<string, mixed> $page
 * @param array<string, mixed> $opts
 */
function hgay_performance_head_markup(string $pageKey): string
{
    $fontsPath = dirname(__DIR__) . '/partials/head-fonts.php';
    $fonts = is_file($fontsPath) ? (string) file_get_contents($fontsPath) : '';
    $fonts = trim($fonts);

    $lines = [];
    if ($pageKey === 'home') {
        // One preload per breakpoint (matches <picture> sources). Avoid imagesrcset on
        // preload — it can fetch both URLs and trigger "preloaded but not used" warnings.
        $heroMobile = hgay_absolute_url('HGAY_ASSETS/Card_Pictures_and_Video/hero-840.webp');
        $heroDesktop = hgay_absolute_url('HGAY_ASSETS/Card_Pictures_and_Video/hero.webp');
        $lines[] = '<link rel="preload" as="image" type="image/webp" href="'
            . hgay_seo_escape_attr($heroMobile)
            . '" media="(max-width: 768px)" fetchpriority="high">';
        $lines[] = '<link rel="preload" as="image" type="image/webp" href="'
            . hgay_seo_escape_attr($heroDesktop)
            . '" media="(min-width: 769px)" fetchpriority="high">';
    }
    if ($fonts !== '' && stripos($fonts, 'fonts.googleapis.com') !== false) {
        $lines[] = $fonts;
    }

    return $lines === [] ? '' : '  ' . implode("\n  ", $lines) . "\n";
}

function hgay_seo_strip_blocking_fonts(string $html): string
{
    $html = preg_replace('/<link[^>]+fonts\.googleapis\.com[^>]*>\s*/i', '', $html) ?? $html;
    $html = preg_replace('/<link[^>]+rel=["\']preconnect["\'][^>]+fonts\.googleapis\.com[^>]*>\s*/i', '', $html) ?? $html;
    $html = preg_replace('/<link[^>]+rel=["\']preconnect["\'][^>]+fonts\.gstatic\.com[^>]*>\s*/i', '', $html) ?? $html;

    return $html;
}

/**
 * @param array<string, mixed> $page
 * @param array<string, mixed> $opts
 */
function hgay_cache_bust_local_assets(string $html): string
{
    $root = dirname(__DIR__);
    $pattern = '/\b(href|src)=(["\'])(?!https?:|\/\/|data:|#)((?:css|js)\/[^"\'?#]+)\2/i';
    $result = preg_replace_callback(
        $pattern,
        static function (array $m) use ($root): string {
            $path = $m[3];
            $file = $root . '/' . $path;
            if (!is_file($file)) {
                return $m[0];
            }

            return $m[1] . '=' . $m[2] . $path . '?v=' . filemtime($file) . $m[2];
        },
        $html
    );

    return is_string($result) ? $result : $html;
}

function hgay_seo_inject_html(string $html, array $page, array $opts = []): string
{
    $pageKey = (string) ($opts['page_key'] ?? '');
    $html = hgay_seo_strip_blocking_fonts($html);
    $html = hgay_cache_bust_local_assets($html);

    $markup = hgay_performance_head_markup($pageKey);

    if (stripos($html, 'rel="canonical"') === false) {
        $seoMarkup = hgay_seo_head_markup($page, $opts);
        if (stripos($html, 'name="description"') !== false && !empty($page['description'])) {
            $seoMarkup = preg_replace(
                '/<meta name="description" content="[^"]*">\s*/i',
                '',
                $seoMarkup
            ) ?? $seoMarkup;
        }
        $markup .= $seoMarkup;
    }

    if ($markup === '') {
        return $html;
    }

    $replaced = preg_replace('/<\/head>/i', $markup . '</head>', $html, 1);

    return is_string($replaced) ? $replaced : $html;
}

function hgay_seo_send_noindex(): void
{
    if (!headers_sent()) {
        header('X-Robots-Tag: noindex, nofollow', true);
    }
}
